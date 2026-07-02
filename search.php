<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$q = trim($_GET['q'] ?? '');
$navBase = defined('BASE_URL') ? BASE_URL : '/ADMISSION';

function searchRelevance(string $text, string $query): int {
    $lower = mb_strtolower($text);
    $q = mb_strtolower($query);
    if ($lower === $q) return 1;
    if (mb_strpos($lower, $q) === 0) return 2;
    if (str_contains($lower, $q)) return 3;
    return 4;
}

$results = ['colleges' => [], 'exams' => [], 'courses' => [], 'careers' => [], 'articles' => [], 'questions' => [], 'universities' => []];

if (mb_strlen($q) >= 1) {
    $like = '%' . $q . '%';

    try {
        $stmt = $pdo->prepare("
            SELECT c.name, c.slug, c.college_type, c.naac_grade, c.ranking_nirf, c.overall_rating_avg, c.total_students, c.total_reviews,
                   ci.name AS city, s.name AS state
            FROM colleges c
            LEFT JOIN cities ci ON c.city_id = ci.id
            LEFT JOIN states s ON c.state_id = s.id
            WHERE c.status = 'active' AND (c.name LIKE ? OR ci.name LIKE ? OR s.name LIKE ?)
            ORDER BY c.is_featured DESC, c.overall_rating_avg DESC LIMIT 20
        ");
        $stmt->execute([$like, $like, $like]);
        $results['colleges'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->prepare("
            SELECT exam_name, exam_abbreviation, exam_slug, exam_level, exam_mode, applicants_last_year, conducting_body
            FROM exams WHERE status != 'cancelled' AND (exam_name LIKE ? OR exam_abbreviation LIKE ?)
            ORDER BY applicants_last_year DESC LIMIT 15
        ");
        $stmt->execute([$like, $like]);
        $results['exams'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->prepare("
            SELECT course_name, course_slug, course_level, course_category, avg_salary_lpa, total_colleges_offering
            FROM courses WHERE status = 'active' AND (course_name LIKE ? OR course_category LIKE ?)
            ORDER BY total_colleges_offering DESC LIMIT 15
        ");
        $stmt->execute([$like, $like]);
        $results['courses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->prepare("
            SELECT name, slug, stream, sub_stream, salary_range, short_description
            FROM careers WHERE name LIKE ? OR stream LIKE ? OR sub_stream LIKE ? OR skills_required LIKE ?
            ORDER BY is_popular DESC LIMIT 10
        ");
        $stmt->execute([$like, $like, $like, $like]);
        $results['careers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->prepare("
            SELECT article_title, article_slug, article_type, publish_at, featured_image_url, view_count
            FROM articles WHERE status = 'published' AND article_title LIKE ?
            ORDER BY publish_at DESC LIMIT 10
        ");
        $stmt->execute([$like]);
        $results['articles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->prepare("
            SELECT question_text, slug, question_category, views, answer_count, created_at
            FROM questions WHERE status IN ('open','answered') AND (question_text LIKE ? OR question_category LIKE ?)
            ORDER BY views DESC LIMIT 10
        ");
        $stmt->execute([$like, $like]);
        $results['questions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    try {
        $stmt = $pdo->prepare("
            SELECT university_name, university_slug, country, qs_rank, city
            FROM foreign_universities WHERE university_name LIKE ? OR country LIKE ? OR city LIKE ?
            ORDER BY qs_rank ASC LIMIT 10
        ");
        $stmt->execute([$like, $like, $like]);
        $results['universities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

$totalResults = array_sum(array_map('count', $results));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results for "<?= htmlspecialchars($q) ?>" - AdmissionSeason</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="<?= $navBase ?>/assets/css/style.css">
    <style>
        .search-page { max-width: 900px; margin: 0 auto; padding: 30px 20px 60px; }
        .search-header { margin-bottom: 30px; }
        .search-header h1 { font-family: var(--font); font-size: 1.6rem; color: var(--primary); margin-bottom: 6px; }
        .search-header p { color: rgba(15,23,42,0.5); font-size: 0.92rem; }
        .search-header mark { background: rgba(37,99,235,0.15); color: inherit; border-radius: 2px; padding: 0 2px; }
        .search-section { margin-bottom: 32px; }
        .search-section-title {
            display: flex; align-items: center; gap: 8px;
            font-family: var(--font); font-size: 1.05rem; font-weight: 700; color: var(--primary);
            margin-bottom: 14px; padding-bottom: 8px; border-bottom: 2px solid rgba(25,55,109,0.08);
        }
        .search-section-title i { font-size: 1.2rem; }
        .search-section-title .count {
            margin-left: auto; font-size: 0.75rem; font-weight: 600; color: rgba(15,23,42,0.4);
            background: rgba(15,23,42,0.04); padding: 2px 10px; border-radius: 20px;
        }
        .search-card {
            display: flex; align-items: flex-start; gap: 14px;
            padding: 14px 18px; border-radius: 12px; text-decoration: none;
            transition: all 0.2s; border: 1px solid transparent;
        }
        .search-card:hover { background: rgba(25,55,109,0.04); border-color: rgba(25,55,109,0.08); }
        .search-card-icon {
            width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }
        .search-card-body { flex: 1; min-width: 0; }
        .search-card-title { font-weight: 600; font-size: 0.95rem; color: rgba(15,23,42,0.9); margin-bottom: 3px; line-height: 1.3; }
        .search-card-title mark { background: rgba(37,99,235,0.15); color: inherit; border-radius: 2px; padding: 0 1px; }
        .search-card-sub { font-size: 0.82rem; color: rgba(15,23,42,0.45); }
        .search-card-badge {
            font-size: 0.68rem; font-weight: 700; padding: 3px 9px; border-radius: 6px;
            white-space: nowrap; flex-shrink: 0; text-transform: uppercase; letter-spacing: 0.03em; align-self: center;
        }
        .search-no-results {
            text-align: center; padding: 60px 20px; color: rgba(15,23,42,0.35);
        }
        .search-no-results i { font-size: 3rem; display: block; margin-bottom: 16px; opacity: 0.3; }
        .search-no-results h2 { font-size: 1.2rem; margin-bottom: 8px; color: rgba(15,23,42,0.5); }
        .search-no-results p { font-size: 0.9rem; }
        .search-input-wrap {
            display: flex; align-items: center; gap: 10px;
            background: #fff; border: 2px solid rgba(15,23,42,0.1); border-radius: 12px;
            padding: 0 16px; height: 48px; margin-bottom: 24px; transition: border-color 0.2s;
        }
        .search-input-wrap:focus-within { border-color: #19376D; }
        .search-input-wrap i { color: rgba(15,23,42,0.35); font-size: 1.2rem; }
        .search-input-wrap input {
            flex: 1; border: none; outline: none; background: none;
            font-size: 1rem; font-family: var(--font2); color: rgba(15,23,42,0.9);
        }
        .search-input-wrap button {
            background: #19376D; color: #fff; border: none; border-radius: 8px;
            padding: 8px 20px; font-weight: 600; font-size: 0.85rem; cursor: pointer;
            font-family: var(--font2); transition: background 0.2s;
        }
        .search-input-wrap button:hover { background: #0B2447; }
        @media (max-width: 640px) {
            .search-page { padding: 20px 16px 40px; }
            .search-card { padding: 12px 14px; }
            .search-card-icon { width: 36px; height: 36px; font-size: 1rem; }
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<main class="search-page">
    <div class="search-header">
        <h1>Search Results</h1>
        <?php if ($q): ?>
            <p><?= $totalResults ?> result<?= $totalResults !== 1 ? 's' : '' ?> found for "<mark><?= htmlspecialchars($q) ?></mark>"</p>
        <?php else: ?>
            <p>Type something to search across colleges, exams, courses and more</p>
        <?php endif; ?>
    </div>

    <form class="search-input-wrap" method="GET" action="">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search colleges, exams, courses, careers, news..." autofocus>
        <button type="submit">Search</button>
    </form>

    <?php if ($q && $totalResults === 0): ?>
        <div class="search-no-results">
            <i class="ph ph-magnifying-glass"></i>
            <h2>No results found</h2>
            <p>Try different keywords or check your spelling</p>
        </div>
    <?php else: ?>

        <?php if (!empty($results['colleges'])): ?>
        <div class="search-section">
            <div class="search-section-title">
                <i class="ph ph-buildings" style="color:#19376D"></i> Colleges
                <span class="count"><?= count($results['colleges']) ?></span>
            </div>
            <?php foreach ($results['colleges'] as $r): ?>
            <a href="<?= $navBase ?>/college/<?= htmlspecialchars($r['slug']) ?>" class="search-card">
                <div class="search-card-icon" style="background:rgba(25,55,109,0.08);color:#19376D"><i class="ph ph-buildings"></i></div>
                <div class="search-card-body">
                    <div class="search-card-title"><?= htmlspecialchars($r['name']) ?></div>
                    <div class="search-card-sub"><?= htmlspecialchars(($r['city'] ?? '') . ($r['state'] ? ', ' . $r['state'] : '')) ?> <?= $r['overall_rating_avg'] ? '· ' . number_format((float)$r['overall_rating_avg'], 1) . ' rating' : '' ?></div>
                </div>
                <?php if ($r['naac_grade']): ?>
                <span class="search-card-badge" style="background:rgba(25,55,109,0.08);color:#19376D">NAAC <?= htmlspecialchars($r['naac_grade']) ?></span>
                <?php elseif ($r['ranking_nirf']): ?>
                <span class="search-card-badge" style="background:rgba(25,55,109,0.08);color:#19376D">NIRF #<?= $r['ranking_nirf'] ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($results['exams'])): ?>
        <div class="search-section">
            <div class="search-section-title">
                <i class="ph ph-clipboard-text" style="color:#7C3AED"></i> Exams
                <span class="count"><?= count($results['exams']) ?></span>
            </div>
            <?php foreach ($results['exams'] as $r): ?>
            <a href="<?= $navBase ?>/exam/<?= htmlspecialchars($r['exam_slug']) ?>" class="search-card">
                <div class="search-card-icon" style="background:rgba(124,58,237,0.08);color:#7C3AED"><i class="ph ph-clipboard-text"></i></div>
                <div class="search-card-body">
                    <div class="search-card-title"><?= htmlspecialchars($r['exam_name']) ?> <?= $r['exam_abbreviation'] ? '(' . htmlspecialchars($r['exam_abbreviation']) . ')' : '' ?></div>
                    <div class="search-card-sub"><?= ucfirst($r['exam_level'] ?? '') ?> Level · <?= ucfirst($r['exam_mode'] ?? 'Online') ?> <?= $r['applicants_last_year'] ? '· ' . number_format((int)$r['applicants_last_year']) . ' applicants' : '' ?></div>
                </div>
                <span class="search-card-badge" style="background:rgba(124,58,237,0.08);color:#7C3AED"><?= strtoupper($r['exam_abbreviation'] ?? mb_substr($r['exam_name'], 0, 4)) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($results['courses'])): ?>
        <div class="search-section">
            <div class="search-section-title">
                <i class="ph ph-books" style="color:#059669"></i> Courses
                <span class="count"><?= count($results['courses']) ?></span>
            </div>
            <?php foreach ($results['courses'] as $r): ?>
            <a href="<?= $navBase ?>/course/<?= htmlspecialchars($r['course_slug']) ?>" class="search-card">
                <div class="search-card-icon" style="background:rgba(5,150,105,0.08);color:#059669"><i class="ph ph-books"></i></div>
                <div class="search-card-body">
                    <div class="search-card-title"><?= htmlspecialchars($r['course_name']) ?></div>
                    <div class="search-card-sub"><?= htmlspecialchars($r['course_level'] ?? '') ?> <?= $r['course_category'] ? '· ' . htmlspecialchars($r['course_category']) : '' ?> <?= $r['total_colleges_offering'] ? '· ' . $r['total_colleges_offering'] . '+ colleges' : '' ?></div>
                </div>
                <span class="search-card-badge" style="background:rgba(5,150,105,0.08);color:#059669"><?= htmlspecialchars($r['course_level'] ?? 'UG') ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($results['careers'])): ?>
        <div class="search-section">
            <div class="search-section-title">
                <i class="ph ph-briefcase" style="color:#EA580C"></i> Careers
                <span class="count"><?= count($results['careers']) ?></span>
            </div>
            <?php foreach ($results['careers'] as $r): ?>
            <a href="<?= $navBase ?>/career/<?= htmlspecialchars($r['slug']) ?>" class="search-card">
                <div class="search-card-icon" style="background:rgba(234,88,12,0.08);color:#EA580C"><i class="ph ph-briefcase"></i></div>
                <div class="search-card-body">
                    <div class="search-card-title"><?= htmlspecialchars($r['name']) ?></div>
                    <div class="search-card-sub"><?= htmlspecialchars($r['sub_stream'] ?? $r['stream'] ?? '') ?> <?= $r['salary_range'] ? '· ' . htmlspecialchars($r['salary_range']) : '' ?></div>
                </div>
                <?php if ($r['stream']): ?>
                <span class="search-card-badge" style="background:rgba(234,88,12,0.08);color:#EA580C"><?= htmlspecialchars($r['stream']) ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($results['universities'])): ?>
        <div class="search-section">
            <div class="search-section-title">
                <i class="ph ph-globe-hemisphere-west" style="color:#0891B2"></i> Foreign Universities
                <span class="count"><?= count($results['universities']) ?></span>
            </div>
            <?php foreach ($results['universities'] as $r): ?>
            <a href="<?= $navBase ?>/foreign-university.php?slug=<?= htmlspecialchars($r['university_slug']) ?>" class="search-card">
                <div class="search-card-icon" style="background:rgba(8,145,178,0.08);color:#0891B2"><i class="ph ph-globe-hemisphere-west"></i></div>
                <div class="search-card-body">
                    <div class="search-card-title"><?= htmlspecialchars($r['university_name']) ?></div>
                    <div class="search-card-sub"><?= htmlspecialchars($r['city'] ? $r['city'] . ', ' . $r['country'] : $r['country']) ?></div>
                </div>
                <?php if ($r['qs_rank']): ?>
                <span class="search-card-badge" style="background:rgba(8,145,178,0.08);color:#0891B2">QS #<?= $r['qs_rank'] ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($results['articles'])): ?>
        <div class="search-section">
            <div class="search-section-title">
                <i class="ph ph-newspaper" style="color:#D97706"></i> News & Articles
                <span class="count"><?= count($results['articles']) ?></span>
            </div>
            <?php foreach ($results['articles'] as $r): ?>
            <a href="<?= $navBase ?>/news_details.php?slug=<?= htmlspecialchars($r['article_slug']) ?>" class="search-card">
                <div class="search-card-icon" style="background:rgba(217,119,6,0.08);color:#D97706"><i class="ph ph-newspaper"></i></div>
                <div class="search-card-body">
                    <div class="search-card-title"><?= htmlspecialchars($r['article_title']) ?></div>
                    <div class="search-card-sub"><?= ucwords(str_replace('_', ' ', $r['article_type'])) ?> <?= $r['publish_at'] ? '· ' . date('d M Y', strtotime($r['publish_at'])) : '' ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($results['questions'])): ?>
        <div class="search-section">
            <div class="search-section-title">
                <i class="ph ph-chat-circle-question" style="color:#2563EB"></i> Questions & Answers
                <span class="count"><?= count($results['questions']) ?></span>
            </div>
            <?php foreach ($results['questions'] as $r): ?>
            <a href="<?= $navBase ?>/question/<?= htmlspecialchars($r['slug']) ?>" class="search-card">
                <div class="search-card-icon" style="background:rgba(37,99,235,0.08);color:#2563EB"><i class="ph ph-chat-circle-question"></i></div>
                <div class="search-card-body">
                    <div class="search-card-title"><?= htmlspecialchars(mb_strimwidth($r['question_text'], 0, 100, '...')) ?></div>
                    <div class="search-card-sub"><?= $r['answer_count'] ?> answers · <?= number_format((int)$r['views']) ?> views</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>
</body>
</html>
