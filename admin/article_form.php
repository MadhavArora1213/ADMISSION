<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$id = isset($_GET['id']) ? $_GET['id'] : null;
$is_edit = $id !== null;
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'content';
$error = '';

// Dropdowns
$categories = $pdo->query("SELECT id, category_name FROM article_categories ORDER BY category_name ASC")->fetchAll();
$authors = $pdo->query("SELECT id, full_name as name FROM users ORDER BY full_name ASC")->fetchAll();
$all_tags = $pdo->query("SELECT id, tag_name FROM tags ORDER BY tag_name ASC")->fetchAll();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tab = $_POST['_tab'] ?? 'content';
    try {
        if ($tab == 'content') {
            $slug = !empty($_POST['article_slug'])
                ? $_POST['article_slug']
                : strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['article_title'])));

            // Slug uniqueness check
            $slugQ = "SELECT id FROM articles WHERE article_slug = :slug" . ($is_edit ? " AND id != :id" : "");
            $slugStmt = $pdo->prepare($slugQ);
            $slugParams = ['slug' => $slug];
            if ($is_edit) $slugParams['id'] = $id;
            $slugStmt->execute($slugParams);
            if ($slugStmt->rowCount() > 0) { $error = "Slug '$slug' already in use."; }
            else {
                $data = [
                    'article_title'       => $_POST['article_title'],
                    'article_slug'        => $slug,
                    'article_type'        => $_POST['article_type'] ?: 'blog',
                    'excerpt'             => $_POST['excerpt'] ?: null,
                    'content_body'        => $_POST['content_body'] ?: null,
                    // Handle featured image upload
                    'featured_image_url'  => (function() {
                        $upload_dir = '../uploads/';
                        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                        $url = $_POST['existing_featured_image_url'] ?? null;
                        if (isset($_FILES['featured_image_file']) && $_FILES['featured_image_file']['error'] == 0) {
                            $ext = pathinfo($_FILES['featured_image_file']['name'], PATHINFO_EXTENSION);
                            $filename = 'article_featured_' . time() . '_' . uniqid() . '.' . $ext;
                            if (move_uploaded_file($_FILES['featured_image_file']['tmp_name'], $upload_dir . $filename)) {
                                $url = BASE_URL . '/uploads/' . $filename;
                                require_once __DIR__ . '/upload_sync.php';
                                sync_to_github('uploads/' . $filename);
                            }
                        }
                        return $url ?: null;
                    })(),
                    'featured_image_alt'  => $_POST['featured_image_alt'] ?: null,
                    'author_id'           => $_POST['author_id'] ?: null,
                    'custom_author_name'  => $_POST['custom_author_name'] ?: null,
                    'editor_id'           => $_POST['editor_id'] ?: null,
                    'category_id'         => $_POST['category_id'] ?: null,
                    'reading_time_mins'   => $_POST['reading_time_mins'] ?: null,
                    'status'              => $_POST['status'] ?: 'draft',
                    'publish_at'          => $_POST['publish_at'] ?: null,
                    'tags'                => !empty($_POST['tag_ids']) ? json_encode(array_map('intval', $_POST['tag_ids'])) : null,
                ];
                if ($is_edit) {
                    $sets = array_map(fn($k) => "$k = :$k", array_keys($data));
                    $data['id'] = $id;
                    $pdo->prepare("UPDATE articles SET " . implode(', ', $sets) . " WHERE id = :id")->execute($data);
                    
                    // Save to Revision History
                    if (!empty($_POST['content_body'])) {
                        $rev_id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
                        $current_version = $pdo->query("SELECT MAX(version) FROM article_revisions WHERE article_id='$id'")->fetchColumn() ?: 0;
                        $pdo->prepare("INSERT INTO article_revisions (id, article_id, version, user_id, content_snapshot) VALUES (?, ?, ?, ?, ?)")->execute([$rev_id, $id, $current_version + 1, $_SESSION['admin_id'] ?? null, $_POST['content_body']]);
                    }
                } else {
                    $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
                        mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,
                        mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
                    $data['id'] = $id;
                    $keys = array_keys($data);
                    $pdo->prepare("INSERT INTO articles (" . implode(',', $keys) . ") VALUES (:" . implode(', :', $keys) . ")")->execute($data);
                    $is_edit = true;
                }
                // Sync article_tags
                if ($is_edit && isset($_POST['tag_ids'])) {
                    $pdo->prepare("DELETE FROM article_tags WHERE article_id = ?")->execute([$id]);
                    foreach($_POST['tag_ids'] as $tid) {
                        $pdo->prepare("INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (?, ?)")->execute([$id, $tid]);
                    }
                }
                header("Location: article_form.php?id=$id&tab=content&msg=saved"); exit;
            }
        } elseif ($tab == 'seo') {
            // Upsert SEO meta
            $seoId = $pdo->query("SELECT id FROM seo_meta WHERE page_type='article' AND page_id='$id'")->fetchColumn();
            if (!$seoId) $seoId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0x0fff)|0x4000,mt_rand(0,0x3fff)|0x8000,mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
            $seoData = [
                'id'                => $seoId,
                'page_type'         => 'article',
                'page_id'           => $id,
                'meta_title'        => $_POST['meta_title'] ?: null,
                'meta_description'  => $_POST['meta_description'] ?: null,
                'og_title'          => $_POST['og_title'] ?: null,
                'og_description'    => $_POST['og_description'] ?: null,
                // Handle OG image upload
                'og_image'          => (function() {
                    $upload_dir = '../uploads/';
                    $url = $_POST['existing_og_image'] ?? null;
                    if (isset($_FILES['og_image_file']) && $_FILES['og_image_file']['error'] == 0) {
                        $ext = pathinfo($_FILES['og_image_file']['name'], PATHINFO_EXTENSION);
                        $filename = 'article_og_' . time() . '_' . uniqid() . '.' . $ext;
                        if (move_uploaded_file($_FILES['og_image_file']['tmp_name'], $upload_dir . $filename)) {
                            $url = BASE_URL . '/uploads/' . $filename;
                            require_once __DIR__ . '/upload_sync.php';
                            sync_to_github('uploads/' . $filename);
                        }
                    }
                    return $url ?: null;
                })(),
                'canonical_url'     => $_POST['canonical_url'] ?: null,
                'schema_type'       => $_POST['schema_type'] ?: null,
                'primary_keyword'   => $_POST['primary_keyword'] ?: null,
            ];
            $pdo->prepare("INSERT INTO seo_meta (id,page_type,page_id,meta_title,meta_description,og_title,og_description,og_image,canonical_url,schema_type,primary_keyword) VALUES (:id,:page_type,:page_id,:meta_title,:meta_description,:og_title,:og_description,:og_image,:canonical_url,:schema_type,:primary_keyword) ON DUPLICATE KEY UPDATE meta_title=VALUES(meta_title),meta_description=VALUES(meta_description),og_title=VALUES(og_title),og_description=VALUES(og_description),og_image=VALUES(og_image),canonical_url=VALUES(canonical_url),schema_type=VALUES(schema_type),primary_keyword=VALUES(primary_keyword)")->execute($seoData);
            header("Location: article_form.php?id=$id&tab=seo&msg=saved"); exit;
        } elseif ($tab == 'schedule') {
            $pdo->prepare("UPDATE articles SET scheduled_at=:scheduled_at, unpublish_at=:unpublish_at, status=:status WHERE id=:id")->execute([
                'scheduled_at'  => $_POST['scheduled_at'] ?: null,
                'unpublish_at'  => $_POST['unpublish_at'] ?: null,
                'status'        => $_POST['status'] ?: 'draft',
                'id'            => $id
            ]);
            header("Location: article_form.php?id=$id&tab=schedule&msg=saved"); exit;
        }
    } catch(Exception $e) { $error = $e->getMessage(); }
}

$article = [];
$seo = [];
$revisions = [];
$article_tag_ids = [];

if ($is_edit) {
    $article = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $article->execute([$id]);
    $article = $article->fetch(PDO::FETCH_ASSOC);
    if (!$article) { header('Location: articles.php'); exit; }

    $seo = $pdo->prepare("SELECT * FROM seo_meta WHERE page_type='article' AND page_id=?");
    $seo->execute([$id]);
    $seo = $seo->fetch(PDO::FETCH_ASSOC) ?: [];

    $revisions = $pdo->prepare("SELECT ar.*, u.full_name as user_name FROM article_revisions ar LEFT JOIN users u ON ar.user_id = u.id WHERE ar.article_id = ? ORDER BY ar.version DESC LIMIT 10");
    $revisions->execute([$id]);
    $revisions = $revisions->fetchAll();

    if (!empty($article['tags'])) {
        $article_tag_ids = json_decode($article['tags'], true) ?: [];
    }
}

function v($arr, $key, $def = '') { return isset($arr[$key]) ? htmlspecialchars($arr[$key]) : $def; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Edit Article' : 'New Article'; ?> | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; padding-bottom: 60px; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; max-width: 1100px; margin: 0 auto; width: 100%; }
        .page-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
        .page-header h2 { font-size: 1.8rem; font-weight: 800; }
        .tabs-nav { display: flex; gap: 6px; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; overflow-x: auto; }
        .tab-link { padding: 8px 16px; font-weight: 600; color: var(--text-muted); border-radius: 8px; text-decoration: none; font-size: 0.9rem; transition: all 0.2s; white-space: nowrap; }
        .tab-link:hover { background: rgba(0,0,0,0.05); color: var(--primary); }
        .tab-link.active { background: var(--primary); color: white; }
        .tab-link.disabled { opacity: 0.45; pointer-events: none; }
        .form-section { background: #f8fafc; border-radius: 14px; border: 1px solid var(--border-color); padding: 28px; box-shadow: var(--shadow-sm); margin-bottom: 20px; }
        .form-section h3 { font-size: 1.05rem; font-weight: 700; color: var(--primary); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        .form-group { margin-bottom: 18px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 7px; font-size: 0.88rem; color: var(--text-muted); }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; font-size: 0.95rem; box-sizing: border-box; background: #fff; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(25,55,109,0.1); }
        textarea.form-control { resize: vertical; min-height: 120px; }
        .char-count { font-size: 0.75rem; color: var(--text-muted); text-align: right; margin-top: 4px; }
        .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; }
        .error-alert { padding: 14px 18px; border-radius: 8px; background: rgba(15,23,42,0.06); color: #0B2447; border: 1px solid rgba(15,23,42,0.06); margin-bottom: 20px; }
        .msg-alert { padding: 14px 18px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; border: 1px solid rgba(11,36,71,0.04); margin-bottom: 20px; }
        .tag-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
        .tag-check { display: flex; align-items: center; gap: 5px; background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 6px; padding: 5px 10px; cursor: pointer; transition: all 0.2s; }
        .tag-check:has(input:checked) { background: var(--primary); color: white; border-color: var(--primary); }
        .tag-check input { display: none; }
        .seo-meter { height: 6px; border-radius: 3px; background: rgba(15,23,42,0.08); margin-top: 5px; overflow: hidden; }
        .seo-meter-fill { height: 100%; border-radius: 3px; transition: width 0.3s; }
        .revision-item { background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .badge { padding: 3px 10px; border-radius: 5px; font-size: 0.72rem; font-weight: 700; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" style="margin-left:16px; color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <a href="articles.php" style="color:var(--text-muted);"><i class="ph ph-arrow-left" style="font-size:1.5rem;"></i></a>
                <h2><?php echo $is_edit ? 'Edit: ' . htmlspecialchars(substr($article['article_title'],0,40)).(strlen($article['article_title'])>40?'…':'') : 'New Article'; ?></h2>
            </div>

            <?php if ($is_edit): ?>
            <div class="tabs-nav">
                <a href="?id=<?php echo $id; ?>&tab=content" class="tab-link <?php echo $current_tab=='content'?'active':''; ?>"><i class="ph ph-article"></i> Content</a>
                <a href="?id=<?php echo $id; ?>&tab=seo" class="tab-link <?php echo $current_tab=='seo'?'active':''; ?>"><i class="ph ph-magnifying-glass"></i> SEO</a>
                <a href="?id=<?php echo $id; ?>&tab=schedule" class="tab-link <?php echo $current_tab=='schedule'?'active':''; ?>"><i class="ph ph-calendar-clock"></i> Schedule</a>
                <a href="?id=<?php echo $id; ?>&tab=revisions" class="tab-link <?php echo $current_tab=='revisions'?'active':''; ?>"><i class="ph ph-clock-countdown"></i> Revisions</a>
            </div>
            <?php else: ?>
            <div class="tabs-nav">
                <span class="tab-link active"><i class="ph ph-article"></i> Content</span>
                <span class="tab-link disabled">SEO</span>
                <span class="tab-link disabled">Schedule</span>
                <span class="tab-link disabled">Revisions</span>
            </div>
            <?php endif; ?>

            <?php if($error): ?><div class="error-alert"><i class="ph ph-warning"></i> <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if(isset($_GET['msg']) && $_GET['msg']=='saved'): ?><div class="msg-alert"><i class="ph ph-check-circle"></i> Saved successfully!</div><?php endif; ?>

            <?php if ($current_tab == 'content'): ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_tab" value="content">
                <div class="form-section">
                    <h3><i class="ph ph-text-aa"></i> Article Details</h3>
                    <div class="form-group full"><label>Article Title *</label><input type="text" name="article_title" class="form-control" required value="<?php echo v($article,'article_title'); ?>" placeholder="Enter full article title..."></div>
                    <div class="form-grid">
                        <div class="form-group"><label>URL Slug (auto-generated if blank)</label><input type="text" name="article_slug" class="form-control" value="<?php echo v($article,'article_slug'); ?>"></div>
                        <div class="form-group"><label>Article Type</label>
                            <select name="article_type" class="form-control">
                                <?php foreach(['blog','news','guide','exam_update','opinion','ranking'] as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo v($article,'article_type')==$t?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$t)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Category</label>
                            <select name="category_id" class="form-control">
                                <option value="">-- Select Category --</option>
                                <?php foreach($categories as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo v($article,'category_id')==$c['id']?'selected':''; ?>><?php echo htmlspecialchars($c['category_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Status</label>
                            <select name="status" class="form-control">
                                <?php foreach(['draft','pending_review','published','archived'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo v($article,'status')==$s?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Author (System User)</label>
                            <select name="author_id" class="form-control">
                                <option value="">-- Select --</option>
                                <?php foreach($authors as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo v($article,'author_id')==$u['id']?'selected':''; ?>><?php echo htmlspecialchars($u['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>OR Custom Author Name</label>
                            <input type="text" name="custom_author_name" class="form-control" placeholder="E.g. Guest Contributor" value="<?php echo v($article,'custom_author_name'); ?>">
                        </div>
                        <div class="form-group"><label>Editor</label>
                            <select name="editor_id" class="form-control">
                                <option value="">-- Select --</option>
                                <?php foreach($authors as $u): ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo v($article,'editor_id')==$u['id']?'selected':''; ?>><?php echo htmlspecialchars($u['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Publish At</label><input type="datetime-local" name="publish_at" class="form-control" value="<?php echo !empty($article['publish_at']) ? date('Y-m-d\TH:i', strtotime($article['publish_at'])) : ''; ?>"></div>
                        <div class="form-group"><label>Reading Time (mins)</label><input type="number" name="reading_time_mins" class="form-control" value="<?php echo v($article,'reading_time_mins'); ?>"></div>
                    </div>
                    <div class="form-group full">
                        <label>Excerpt (max 300 chars)</label>
                        <textarea name="excerpt" class="form-control" rows="3" maxlength="300" id="excerpt"><?php echo v($article,'excerpt'); ?></textarea>
                        <div class="char-count"><span id="ec">0</span>/300</div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="ph ph-text-align-left"></i> Content Body</h3>
                    <div class="form-group">
                        <label>Content <small style="color:var(--text-muted); font-weight:400;">— use the toolbar to insert <strong>tables</strong>, <strong>images</strong> and <strong>file attachments</strong></small></label>
                        <textarea name="content_body" id="content_body" class="form-control" rows="16" style="font-family: 'Courier New', monospace; font-size:0.88rem;"><?php echo v($article,'content_body'); ?></textarea>
                    </div>
                    <!-- Hidden file picker used by the editor's "Upload" toolbar button -->
                    <input type="file" id="editorFilePicker" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rar,.7z,.mp4,.webm,.mp3,.wav" style="display:none;">
                </div>

                <div class="form-section">
                    <h3><i class="ph ph-image"></i> Featured Image</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Image</label><?php if(!empty($article['featured_image_url'])): ?>
                            <div style="margin-bottom: 8px;"><img src="<?php echo htmlspecialchars($article['featured_image_url']); ?>" style="height: 50px; border-radius: 4px; border: 1px solid #ccc;"></div>
                        <?php endif; ?>
                        <input type="hidden" name="existing_featured_image_url" value="<?php echo v($article,'featured_image_url'); ?>">
                        <input type="file" name="featured_image_file" class="form-control" accept="image/*"></div>
                        <div class="form-group"><label>Alt Text</label><input type="text" name="featured_image_alt" class="form-control" value="<?php echo v($article,'featured_image_alt'); ?>"></div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><i class="ph ph-tag"></i> Tags</h3>
                    <div class="tag-grid">
                        <?php foreach($all_tags as $tag): ?>
                        <label class="tag-check">
                            <input type="checkbox" name="tag_ids[]" value="<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $article_tag_ids)?'checked':''; ?>>
                            <?php echo htmlspecialchars($tag['tag_name']); ?>
                        </label>
                        <?php endforeach; ?>
                        <?php if(empty($all_tags)): ?>
                        <p style="color:var(--text-muted); font-size:0.88rem;">No tags yet. <a href="tags.php">Add some tags</a>.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="articles.php" class="btn" style="background:#F8FAFC; color:rgba(15,23,42,0.65);">Cancel</a>
                    <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Article</button>
                </div>
            </form>

            <?php elseif($current_tab == 'seo'): ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_tab" value="seo">
                <div class="form-section">
                    <h3><i class="ph ph-magnifying-glass"></i> Search Engine Optimization</h3>
                    <div class="form-group">
                        <label>Meta Title <small style="color:var(--text-muted);">(max 70 chars)</small></label>
                        <input type="text" name="meta_title" class="form-control" maxlength="70" id="meta_title" value="<?php echo v($seo,'meta_title'); ?>">
                        <div class="seo-meter"><div class="seo-meter-fill" id="mt_fill" style="background:#0B2447; width:0%;"></div></div>
                        <div class="char-count"><span id="mtc">0</span>/70</div>
                    </div>
                    <div class="form-group">
                        <label>Meta Description <small style="color:var(--text-muted);">(max 160 chars)</small></label>
                        <textarea name="meta_description" class="form-control" maxlength="160" rows="3" id="meta_desc"><?php echo v($seo,'meta_description'); ?></textarea>
                        <div class="seo-meter"><div class="seo-meter-fill" id="md_fill" style="background:#0B2447; width:0%;"></div></div>
                        <div class="char-count"><span id="mdc">0</span>/160</div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group"><label>Primary Keyword</label><input type="text" name="primary_keyword" class="form-control" value="<?php echo v($seo,'primary_keyword'); ?>"></div>
                        <div class="form-group"><label>Schema Type</label>
                            <select name="schema_type" class="form-control">
                                <option value="">-- None --</option>
                                <?php foreach(['Article','NewsArticle','HowTo','FAQ'] as $st): ?>
                                <option value="<?php echo $st; ?>" <?php echo v($seo,'schema_type')==$st?'selected':''; ?>><?php echo $st; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Canonical URL</label><input type="url" name="canonical_url" class="form-control" value="<?php echo v($seo,'canonical_url'); ?>"></div>
                        <div class="form-group"><label>OG Image</label><?php if(!empty($seo['og_image'])): ?>
                            <div style="margin-bottom: 8px;"><img src="<?php echo htmlspecialchars($seo['og_image']); ?>" style="height: 50px; border-radius: 4px; border: 1px solid #ccc;"></div>
                        <?php endif; ?>
                        <input type="hidden" name="existing_og_image" value="<?php echo v($seo,'og_image'); ?>">
                        <input type="file" name="og_image_file" class="form-control" accept="image/*"></div>
                    </div>
                    <div class="form-group"><label>OG Title</label><input type="text" name="og_title" class="form-control" value="<?php echo v($seo,'og_title'); ?>"></div>
                    <div class="form-group"><label>OG Description</label><textarea name="og_description" class="form-control" rows="2"><?php echo v($seo,'og_description'); ?></textarea></div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save SEO</button>
                </div>
            </form>

            <?php elseif($current_tab == 'schedule'): ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="_tab" value="schedule">
                <div class="form-section">
                    <h3><i class="ph ph-calendar-clock"></i> Publishing Schedule</h3>
                    <div class="form-grid">
                        <div class="form-group"><label>Status</label>
                            <select name="status" class="form-control">
                                <?php foreach(['draft','pending_review','published','archived'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo v($article,'status')==$s?'selected':''; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Scheduled Publish At</label><input type="datetime-local" name="scheduled_at" class="form-control" value="<?php echo !empty($article['scheduled_at']) ? date('Y-m-d\TH:i', strtotime($article['scheduled_at'])) : ''; ?>"></div>
                        <div class="form-group"><label>Auto Unpublish At</label><input type="datetime-local" name="unpublish_at" class="form-control" value="<?php echo !empty($article['unpublish_at']) ? date('Y-m-d\TH:i', strtotime($article['unpublish_at'])) : ''; ?>"></div>
                    </div>
                    <div style="background:#F8FAFC; border-radius:10px; padding:16px; margin-top:8px;">
                        <div style="display:flex; gap:24px; font-size:0.88rem;">
                            <div><span style="color:var(--text-muted);">Auto-save version:</span> <strong><?php echo v($article,'auto_save_version','1'); ?></strong></div>
                            <div><span style="color:var(--text-muted);">Last draft saved:</span> <strong><?php echo !empty($article['draft_saved_at']) ? date('d M Y H:i', strtotime($article['draft_saved_at'])) : 'Never'; ?></strong></div>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="ph ph-floppy-disk"></i> Save Schedule</button>
                </div>
            </form>

            <?php elseif($current_tab == 'revisions'): ?>
            <div class="form-section">
                <h3><i class="ph ph-clock-countdown"></i> Revision History</h3>
                <?php if(empty($revisions)): ?>
                    <p style="color:var(--text-muted);">No revisions saved yet.</p>
                <?php else: foreach($revisions as $rev): ?>
                <div class="revision-item">
                    <div>
                        <div style="font-weight:700;">Version <?php echo $rev['version']; ?></div>
                        <div style="font-size:0.82rem; color:var(--text-muted);">By <?php echo htmlspecialchars($rev['user_name'] ?: 'Admin'); ?> on <?php echo date('d M Y H:i', strtotime($rev['saved_at'])); ?></div>
                    </div>
                    <span class="badge" style="background:rgba(11,36,71,0.06); color:#19376D;">v<?php echo $rev['version']; ?></span>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>
<!-- jQuery and Trumbowyg -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/plugins/table/ui/trumbowyg.table.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/plugins/colors/ui/trumbowyg.colors.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/plugins/table/trumbowyg.table.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/plugins/colors/trumbowyg.colors.min.js"></script>
<style>
    /* Make the Trumbowyg editor fit the admin panel visually */
    .trumbowyg-editor { min-height: 300px; }
</style>
<script>
// Char counters
function setupCounter(inputId, countId, max, fillId) {
    const el = document.getElementById(inputId);
    const cnt = document.getElementById(countId);
    const fill = document.getElementById(fillId);
    if (!el) return;
    function update() {
        const len = el.value.length;
        if(cnt) cnt.textContent = len;
        if(fill) {
            const pct = Math.min((len/max)*100, 100);
            fill.style.width = pct + '%';
            fill.style.background = pct < 50 ? '#19376D' : pct <= 90 ? '#0B2447' : '#0F172A';
        }
    }
    el.addEventListener('input', update);
    update();
}
setupCounter('excerpt','ec',300,null);
setupCounter('meta_title','mtc',70,'mt_fill');
setupCounter('meta_desc','mdc',160,'md_fill');

$(document).ready(function() {
    if ($('#content_body').length) {
            try {
            if (!$.fn.trumbowyg || !$.trumbowyg) {
                throw new Error('Trumbowyg not loaded');
            }
            if (!$.trumbowyg.plugins || !$.trumbowyg.plugins.table) {
                console.warn('[Trumbowyg] table plugin not registered');
            }
            $('#content_body').trumbowyg({
                semantic: true,
                removeformatPasted: true,
                resetCss: true,
                autogrow: true,
                btnsDef: {
                    // Custom "Upload" button — handles images AND files (PDF/doc/zip/etc).
                    // We can't use the upload plugin's built-in button because its modal
                    // hardcodes accept="image/*". Our button opens a native file picker
                    // (see #editorFilePicker) that accepts any allowed type.
                    uploadFile: {
                        title: 'Upload image or file',
                        ico: 'insert-image',
                        fn: function () {
                            var picker = document.getElementById('editorFilePicker');
                            if (picker) { picker.value = ''; picker.click(); }
                        }
                    }
                },
                btns: [
                    ['viewHTML'],
                    ['undo', 'redo'],
                    ['formatting'],
                    ['strong', 'em', 'del', 'underline'],
                    ['foreColor', 'backColor'],
                    ['link'],
                    ['insertImage'],
                    ['justifyLeft', 'justifyCenter', 'justifyRight'],
                    ['unorderedList', 'orderedList'],
                    ['horizontalRule'],
                    ['table'],                       // insert/edit tables
                    ['uploadFile'],                  // upload image OR file
                    ['removeformat'],
                    ['fullscreen']
                ],
                plugins: {
                    // Table plugin (v3.0) — insert + add/remove rows & cols + merge + resize
                    table: { rows: 5, columns: 5 }
                }
            });
            console.log('[Trumbowyg] editor initialized OK');
            console.log('[Trumbowyg] editor html before upload:', $('#content_body').trumbowyg('html').slice(0,200));
        } catch (err) {
            console.error('[Trumbowyg] init FAILED:', err);
            alert('Editor failed to load: ' + err.message + '\n\nCheck the browser console (F12) for details.');
        }

        // Handle the hidden file picker selection
        $(document).off('change', '#editorFilePicker').on('change', '#editorFilePicker', function () {
            console.log('[Trumbowyg] file picked:', this.files && this.files.length ? this.files[0].name : null);
            var f = this.files && this.files[0];
            if (!f) { return; }
            var editor = $('#content_body');

            // Remember cursor position so the inserted media lands at the caret
            editor.trumbowyg('saveRange');

            var formData = new FormData();
            formData.append('fileToUpload', f);
            var placeholderId = 'trb-uploading-' + Date.now();
            var placeholderHtml = '<p id="' + placeholderId + '"><em>Uploading ' + $('<div>').text(f.name).html() + '…</em></p>';
            editor.trumbowyg('execCmd', 'insertHtml', placeholderHtml);


            $.ajax({
                url: 'editor_upload.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (resp) {
                console.log('[Trumbowyg] upload resp:', resp);
                // Remove placeholder by id selector (robust against Trumbowyg html normalization)
                $('#' + placeholderId).remove();

                if (resp && resp.success && resp.url) {
                    // Important: restore cursor BEFORE setting html / execCmd
                    editor.trumbowyg('restoreRange');
                    var insert;
                    if (resp.isImage) {
                        insert = '<img src="' + resp.url + '" alt="' + (resp.name || '') + '" style="max-width:100%;height:auto;display:block;">';
                    } else {
                        insert = '<a href="' + resp.url + '" download="' + (resp.name || '') + '">📄 ' + (resp.name || 'Download file') + '</a>';
                    }
                    editor.trumbowyg('execCmd', 'insertHtml', insert);
                    console.log('[Trumbowyg] editor html after insert:', $('#content_body').trumbowyg('html').slice(0,250));
                } else {
                    console.log('[Trumbowyg] Upload response:', resp);

                    alert('Upload failed: ' + (resp && resp.message ? resp.message : 'unknown error'));
                }
            }).fail(function (xhr) {
                alert('Upload failed: server error.\n' + (xhr.responseText || '').slice(0, 200));
            });
        });
    }

    // Auto-generate slug from title
    let titleInput = $('input[name="article_title"]');
    let slugInput = $('input[name="article_slug"]');
    let isEdit = <?php echo $is_edit ? 'true' : 'false'; ?>;
    let isManualSlug = isEdit && slugInput.val().length > 0;

    slugInput.on('input', function() {
        isManualSlug = $(this).val().length > 0;
    });

    titleInput.on('input', function() {
        if (!isManualSlug) {
            let title = $(this).val();
            let slug = title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
            slugInput.val(slug);
        }
    });
});
</script>
</body>
</html>
