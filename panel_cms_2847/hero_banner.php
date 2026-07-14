<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

// Fix image URLs — stored as relative paths like 'uploads/xxx.jpg'
function heroImg($url) {
    if (!$url) return 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80';
    if (str_starts_with($url, 'http') || str_starts_with($url, '//')) return $url;
    return '../' . ltrim($url, '/');
}

$msg = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Assign entity to a hero slot
    if ($action === 'assign') {
        $slot = (int)($_POST['slot'] ?? 0);
        $entityType = $_POST['entity_type'] ?? '';
        $entityId = $_POST['entity_id'] ?? '';

        if ($slot >= 1 && $slot <= 5 && in_array($entityType, ['college','university','school']) && !empty($entityId)) {
            $table = $entityType === 'college' ? 'colleges' : ($entityType === 'university' ? 'universities' : 'schools');

            $checkStmt = $pdo->prepare("SELECT hero_priority FROM {$table} WHERE id = ?");
            $checkStmt->execute([$entityId]);
            $existing = $checkStmt->fetchColumn();

            $slotStmt = $pdo->prepare("SELECT id, name FROM {$table} WHERE hero_priority = ? AND id != ?");
            $slotStmt->execute([$slot, $entityId]);
            $slotOccupant = $slotStmt->fetch(PDO::FETCH_ASSOC);

            if ($slotOccupant) {
                if ($existing) {
                    $swapStmt = $pdo->prepare("UPDATE {$table} SET hero_priority = ? WHERE id = ?");
                    $swapStmt->execute([$existing, $slotOccupant['id']]);
                } else {
                    $swapStmt = $pdo->prepare("UPDATE {$table} SET hero_priority = NULL WHERE id = ?");
                    $swapStmt->execute([$slotOccupant['id']]);
                }
            }

            $assignStmt = $pdo->prepare("UPDATE {$table} SET hero_priority = ? WHERE id = ?");
            $assignStmt->execute([$slot, $entityId]);
            $msg = "Slot {$slot} assigned successfully!";
        } else {
            $error = "Invalid slot, entity type, or entity ID.";
        }
    }

    if ($action === 'remove') {
        $entityType = $_POST['entity_type'] ?? '';
        $entityId = $_POST['entity_id'] ?? '';
        if (in_array($entityType, ['college','university','school']) && !empty($entityId)) {
            $table = $entityType === 'college' ? 'colleges' : ($entityType === 'university' ? 'universities' : 'schools');
            $removeStmt = $pdo->prepare("UPDATE {$table} SET hero_priority = NULL WHERE id = ?");
            $removeStmt->execute([$entityId]);
            $msg = "Entity removed from hero banner.";
        }
    }

    if ($action === 'clear_all') {
        $pdo->exec("UPDATE colleges SET hero_priority = NULL");
        $pdo->exec("UPDATE universities SET hero_priority = NULL");
        $pdo->exec("UPDATE schools SET hero_priority = NULL");
        $msg = "All hero slots cleared.";
    }
}

// Fetch current hero slots
function fetchHeroSlots($pdo) {
    $cols = $pdo->query("SELECT c.id, c.name, c.slug, c.hero_priority, c.overall_rating_avg,
        s.name AS state_name, ci.name AS city_name, cm.cover_image_url,
        'college' AS entity_type
        FROM colleges c
        LEFT JOIN states s ON c.state_id=s.id
        LEFT JOIN cities ci ON c.city_id=ci.id
        LEFT JOIN college_media cm ON cm.college_id=c.id AND (cm.image_type='cover' OR cm.image_type IS NULL)
        WHERE c.hero_priority IS NOT NULL AND c.status='active'
        ORDER BY c.hero_priority ASC")->fetchAll(PDO::FETCH_ASSOC);

    $unis = $pdo->query("SELECT u.id, u.name, u.slug, u.hero_priority, u.overall_rating_avg,
        s.name AS state_name, ci.name AS city_name, u.cover_image_url,
        'university' AS entity_type
        FROM universities u
        LEFT JOIN states s ON u.state_id=s.id
        LEFT JOIN cities ci ON u.city_id=ci.id
        WHERE u.hero_priority IS NOT NULL AND u.status='active'
        ORDER BY u.hero_priority ASC")->fetchAll(PDO::FETCH_ASSOC);

    $schs = $pdo->query("SELECT sc.id, sc.name, sc.slug, sc.hero_priority, sc.overall_rating_avg,
        s.name AS state_name, ci.name AS city_name, sm.cover_image_url,
        'school' AS entity_type
        FROM schools sc
        LEFT JOIN states s ON sc.state_id=s.id
        LEFT JOIN cities ci ON sc.city_id=ci.id
        LEFT JOIN school_media sm ON sm.school_id=sc.id AND (sm.image_type='cover' OR sm.image_type IS NULL)
        WHERE sc.hero_priority IS NOT NULL AND sc.status='active'
        ORDER BY sc.hero_priority ASC")->fetchAll(PDO::FETCH_ASSOC);

    $all = array_merge($cols, $unis, $schs);
    usort($all, fn($a, $b) => (int)$a['hero_priority'] <=> (int)$b['hero_priority']);
    return $all;
}

$heroSlots = fetchHeroSlots($pdo);
$occupiedSlots = array_column($heroSlots, 'hero_priority');

$availableColleges = $pdo->query("SELECT c.id, c.name, ci.name AS city_name FROM colleges c LEFT JOIN cities ci ON c.city_id=ci.id WHERE c.status='active' AND (c.hero_priority IS NULL OR c.hero_priority = 0) ORDER BY c.is_featured DESC, c.overall_rating_avg DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
$availableUnis = $pdo->query("SELECT u.id, u.name, ci.name AS city_name FROM universities u LEFT JOIN cities ci ON u.city_id=ci.id WHERE u.status='active' AND (u.hero_priority IS NULL OR u.hero_priority = 0) ORDER BY u.is_featured DESC, u.overall_rating_avg DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
$availableSchools = $pdo->query("SELECT sc.id, sc.name, ci.name AS city_name FROM schools sc LEFT JOIN cities ci ON sc.city_id=ci.id WHERE sc.status='active' AND (sc.hero_priority IS NULL OR sc.hero_priority = 0) ORDER BY sc.is_featured DESC, sc.overall_rating_avg DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hero Banner Management | AdmissionSeason Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }

        /* Sidebar — exact match with colleges.php */
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 50; transition: transform 0.3s ease; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .sidebar-nav a i { font-size: 1.25rem; }

        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 64px; background: #fff; border-bottom: 1px solid rgba(15,23,42,0.08); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 40; }
        .header-left { display: flex; align-items: center; gap: 12px; }
        .header-right { display: flex; align-items: center; gap: 16px; }
        #topbarToggle { display:none; background:none; border:none; font-size:1.4rem; cursor:pointer; color:#0f172a; padding:4px; }
        .sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:49; }

        .content-area { padding: 32px; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        .panel { background: #f8fafc; border-radius: 16px; border: 1px solid var(--border-color); padding: 24px; box-shadow: var(--shadow-sm); }

        .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }

        /* Slot Grid */
        .hero-slots { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px; }
        .hero-slot { border: 2px dashed var(--border-color); border-radius: 12px; padding: 16px; text-align: center; min-height: 220px; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: all 0.2s; position: relative; background: #fff; }
        .hero-slot.occupied { border: 2px solid #22c55e; border-style: solid; background: #f0fdf4; }
        .hero-slot-number { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); margin-bottom: 8px; }
        .hero-slot-empty { color: var(--text-muted); font-size: 0.85rem; }
        .hero-slot-empty i { display: block; font-size: 2rem; margin-bottom: 8px; opacity: 0.3; }
        .hero-slot-img { width: 100%; height: 80px; object-fit: cover; border-radius: 8px; margin-bottom: 8px; }
        .hero-slot-name { font-size: 0.82rem; font-weight: 700; color: var(--text-dark); margin-bottom: 4px; line-height: 1.3; }
        .hero-slot-meta { font-size: 0.72rem; color: var(--text-muted); margin-bottom: 8px; }
        .hero-slot-type { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 2px 8px; border-radius: 4px; margin-bottom: 8px; }
        .type-college { background: #dbeafe; color: #1e40af; }
        .type-university { background: #e0e7ff; color: #4338ca; }
        .type-school { background: #fef3c7; color: #92400e; }
        .hero-slot-remove { position: absolute; top: 8px; right: 8px; width: 28px; height: 28px; border-radius: 50%; background: #ef4444; color: #fff; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; transition: background 0.2s; }
        .hero-slot-remove:hover { background: #dc2626; }

        /* Assign Form */
        .assign-grid { display: grid; grid-template-columns: 1fr 1fr 2fr auto; gap: 12px; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-group label { font-size: 0.85rem; font-weight: 600; color: var(--text-muted); }
        .form-group select, .form-group input { padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; background: #fff; width: 100%; }
        .form-group select:focus, .form-group input:focus { outline: none; border-color: var(--primary); }
        .assign-btn { padding: 10px 20px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .btn { padding: 10px 20px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; text-decoration: none; border: none; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }

        .msg-alert { padding: 16px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 24px; border: 1px solid rgba(11,36,71,0.04); display: flex; align-items: center; gap: 8px; }
        .msg-error { padding: 16px; border-radius: 8px; background: rgba(239,68,68,0.04); color: #991b1b; margin-bottom: 24px; border: 1px solid rgba(239,68,68,0.1); display: flex; align-items: center; gap: 8px; }

        .hint-text { font-size: 0.88rem; color: var(--text-muted); margin-bottom: 20px; line-height: 1.6; }

        /* Custom Searchable Select */
        .custom-select { position: relative; width: 100%; }
        .custom-select-trigger { padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.9rem; background: #fff; cursor: pointer; display: flex; align-items: center; justify-content: space-between; min-height: 42px; word-break: break-word; }
        .custom-select-trigger .placeholder { color: #999; }
        .custom-select-trigger .arrow { flex-shrink: 0; margin-left: 8px; transition: transform 0.2s; font-size: 0.7rem; color: #999; }
        .custom-select.open .custom-select-trigger { border-color: var(--primary); outline: none; }
        .custom-select.open .custom-select-trigger .arrow { transform: rotate(180deg); }
        .custom-select-dropdown { display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; margin-top: 4px; z-index: 100; box-shadow: 0 8px 24px rgba(0,0,0,0.15); max-height: 50vh; overflow: hidden; flex-direction: column; }
        .custom-select.open .custom-select-dropdown { display: flex; }
        .custom-select-search { padding: 8px; border-bottom: 1px solid #eee; }
        .custom-select-search input { width: 100%; padding: 8px 10px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 0.85rem; outline: none; }
        .custom-select-search input:focus { border-color: var(--primary); }
        .custom-select-options { overflow-y: auto; max-height: 38vh; padding: 4px 0; }
        .custom-select-option { padding: 10px 14px; cursor: pointer; font-size: 0.88rem; color: var(--text-dark); border-bottom: 1px solid #f5f5f5; }
        .custom-select-option:last-child { border-bottom: none; }
        .custom-select-option:hover { background: #f0f4ff; }
        .custom-select-option.selected { background: var(--primary); color: #fff; }
        .custom-select-option.no-results { color: #999; cursor: default; text-align: center; padding: 16px; }
        .custom-select-option.no-results:hover { background: transparent; }

        /* Responsive — exact match with colleges.php */
        @media(max-width:1024px){
            .sidebar { transform:translateX(-100%) !important; }
            .sidebar.open { transform:translateX(0) !important; }
            .sidebar-overlay.show { display:block; }
            #topbarToggle { display:inline-flex !important; }
            .main-content { margin-left:0 !important; }
            .content-area { padding:16px !important; }
            .page-header { flex-wrap:wrap !important; gap:10px !important; }
            .page-header h2 { font-size:1.4rem !important; }
            .hero-slots { grid-template-columns: repeat(2, 1fr); }
            .assign-grid { grid-template-columns: 1fr 1fr; }
            .assign-grid .assign-btn { grid-column: 1 / -1; justify-content: center; }
        }
        @media(max-width:768px){
            .topbar { height:56px !important; padding:0 12px !important; }
            .content-area { padding:12px !important; }
            .page-header h2 { font-size:1.2rem !important; }
            .panel { padding:16px !important; border-radius:12px !important; }
            .hero-slots { grid-template-columns: 1fr 1fr; gap:10px; }
            .hero-slot { min-height:180px; padding:12px; }
            .hero-slot-img { height:60px; }
            .assign-grid { grid-template-columns: 1fr; gap:10px; }
            .assign-grid .assign-btn { width: 100%; justify-content: center; }
            .btn { padding:8px 14px !important; font-size:0.85rem !important; }
        }
        @media(max-width:480px){
            .page-header h2 { font-size:1.1rem !important; }
            .hero-slots { grid-template-columns: 1fr; }
            .hero-slot { min-height:160px; }
            .assign-grid { grid-template-columns: 1fr; }
            .assign-grid .assign-btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'sidebar.php'; ?>
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

        <main class="main-content">
            <header class="topbar">
                <div class="header-left">
                    <button onclick="toggleSidebar()" id="topbarToggle"><i class="ph ph-list"></i></button>
                    <div style="font-weight:700; color:#0f172a;"><i class="ph ph-images"></i> Hero Banner</div>
                </div>
                <div class="header-right">
                    <span style="font-size:0.88rem; color:rgba(15,23,42,0.65);"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
                    <a href="logout.php" style="color:#0f172a; font-size:1.2rem;"><i class="ph ph-sign-out"></i></a>
                </div>
            </header>

            <div class="content-area">
                <div class="page-header">
                    <div>
                        <h2>Hero Banner Management</h2>
                        <p style="color: var(--text-muted);">Assign colleges, universities & schools to the homepage hero carousel.</p>
                    </div>
                </div>

                <?php if ($msg): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="msg-error"><i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Hero Slots (1-5) -->
                <div class="panel">
                    <div class="section-title"><i class="ph ph-images"></i> Hero Banner Slots (1–5)</div>
                    <p class="hint-text">
                        Assign up to 5 institutions to the hero banner carousel. Priority 1 = first shown, Priority 5 = last shown.
                        Supports both organic featured and paid/premium placements. When all slots are full, remove one to add the next.
                    </p>

                    <div class="hero-slots">
                        <?php for ($i = 1; $i <= 5; $i++):
                            $slotItem = null;
                            foreach ($heroSlots as $hs) {
                                if ((int)$hs['hero_priority'] === $i) { $slotItem = $hs; break; }
                            }
                        ?>
                        <div class="hero-slot <?= $slotItem ? 'occupied' : '' ?>">
                            <div class="hero-slot-number">Slot <?= $i ?></div>
                            <?php if ($slotItem): ?>
                                <img src="<?= heroImg($slotItem['cover_image_url'] ?? '') ?>" alt="" class="hero-slot-img" loading="lazy">
                                <span class="hero-slot-type type-<?= $slotItem['entity_type'] ?>"><?= ucfirst($slotItem['entity_type']) ?></span>
                                <div class="hero-slot-name"><?= htmlspecialchars($slotItem['name']) ?></div>
                                <div class="hero-slot-meta"><?= htmlspecialchars(($slotItem['city_name'] ?? '') . (($slotItem['city_name'] ?? '') && ($slotItem['state_name'] ?? '') ? ', ' : '') . ($slotItem['state_name'] ?? '')) ?></div>
                                <?php if ((float)($slotItem['overall_rating_avg'] ?? 0) > 0): ?>
                                <div style="font-size:0.75rem; color:#f59e0b; margin-bottom:6px;"><i class="ph-fill ph-star"></i> <?= number_format((float)$slotItem['overall_rating_avg'], 1) ?></div>
                                <?php endif; ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="entity_type" value="<?= $slotItem['entity_type'] ?>">
                                    <input type="hidden" name="entity_id" value="<?= $slotItem['id'] ?>">
                                    <button type="submit" class="hero-slot-remove" title="Remove from slot"><i class="ph ph-x"></i></button>
                                </form>
                            <?php else: ?>
                                <i class="ph ph-image" style="font-size:2rem; opacity:0.2; margin-bottom:8px;"></i>
                                <div class="hero-slot-empty">Empty Slot</div>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <div style="display:flex; gap:10px; flex-wrap:wrap;">
                        <form method="POST" onsubmit="return confirm('Clear all hero slots?')">
                            <input type="hidden" name="action" value="clear_all">
                            <button type="submit" class="btn btn-danger"><i class="ph ph-trash"></i> Clear All Slots</button>
                        </form>
                    </div>
                </div>

                <!-- Assign New Institution -->
                <div class="panel">
                    <div class="section-title"><i class="ph ph-plus-circle"></i> Assign Institution to Slot</div>
                    <form method="POST" class="assign-grid">
                        <input type="hidden" name="action" value="assign">

                        <div class="form-group">
                            <label>Slot Priority (1–5)</label>
                            <select name="slot" required>
                                <option value="">Select Slot</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?><?= in_array($i, $occupiedSlots) ? ' — Occupied' : '' ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Entity Type</label>
                            <select name="entity_type" id="entityType" required onchange="toggleEntitySelect()">
                                <option value="">Select Type</option>
                                <option value="college">College</option>
                                <option value="university">University</option>
                                <option value="school">School</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Institution</label>
                            <div class="custom-select" id="customEntitySelect">
                                <div class="custom-select-trigger" onclick="toggleCustomSelect()">
                                    <span class="placeholder">Select type first…</span>
                                    <span class="arrow">▼</span>
                                </div>
                                <input type="hidden" name="entity_id" id="entityId" required>
                                <div class="custom-select-dropdown">
                                    <div class="custom-select-search">
                                        <input type="text" placeholder="Search institution…" id="entitySearchInput" oninput="filterCustomOptions()" onkeydown="handleSearchKey(event)">
                                    </div>
                                    <div class="custom-select-options" id="entityOptions"></div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary assign-btn"><i class="ph ph-check"></i> Assign</button>
                    </form>
                </div>

            </div>
        </main>
    </div>

<script>
const entities = {
    college: <?= json_encode($availableColleges) ?>,
    university: <?= json_encode($availableUnis) ?>,
    school: <?= json_encode($availableSchools) ?>
};

let currentEntityType = '';
let selectedValue = '';

function toggleEntitySelect() {
    currentEntityType = document.getElementById('entityType').value;
    const wrap = document.getElementById('customEntitySelect');
    const hidden = document.getElementById('entityId');
    const trigger = wrap.querySelector('.custom-select-trigger');

    selectedValue = '';
    hidden.value = '';

    if (!currentEntityType || !entities[currentEntityType]) {
        trigger.innerHTML = '<span class="placeholder">Select type first…</span><span class="arrow">▼</span>';
        renderEntityOptions();
        return;
    }

    trigger.innerHTML = '<span class="placeholder">Select institution…</span><span class="arrow">▼</span>';
    renderEntityOptions();
}

function renderEntityOptions(filter) {
    const optionsEl = document.getElementById('entityOptions');
    const items = entities[currentEntityType] || [];
    const q = (filter || '').toLowerCase();

    let filtered = items;
    if (q) {
        filtered = items.filter(e => e.name.toLowerCase().includes(q) || (e.city_name || '').toLowerCase().includes(q));
    }

    if (filtered.length === 0) {
        optionsEl.innerHTML = '<div class="custom-select-option no-results">No results found</div>';
        return;
    }

    optionsEl.innerHTML = filtered.map(e => {
        const loc = e.city_name || '';
        const label = e.name + (loc ? ' — ' + loc : '');
        const sel = String(e.id) === selectedValue ? ' selected' : '';
        return '<div class="custom-select-option' + sel + '" data-value="' + e.id + '" onclick="selectEntity(this)" title="' + label.replace(/"/g, '&quot;') + '">' + label + '</div>';
    }).join('');
}

function selectEntity(el) {
    selectedValue = el.dataset.value;
    document.getElementById('entityId').value = selectedValue;

    const wrap = document.getElementById('customEntitySelect');
    const trigger = wrap.querySelector('.custom-select-trigger');
    trigger.innerHTML = '<span>' + el.textContent + '</span><span class="arrow">▼</span>';

    wrap.classList.remove('open');
    document.getElementById('entitySearchInput').value = '';
}

function toggleCustomSelect() {
    const wrap = document.getElementById('customEntitySelect');
    const isOpen = wrap.classList.contains('open');

    // Close all other custom selects
    document.querySelectorAll('.custom-select.open').forEach(s => s.classList.remove('open'));

    if (!isOpen) {
        wrap.classList.add('open');
        renderEntityOptions();
        setTimeout(() => document.getElementById('entitySearchInput').focus(), 50);
    }
}

function filterCustomOptions() {
    const q = document.getElementById('entitySearchInput').value;
    renderEntityOptions(q);
}

function handleSearchKey(e) {
    if (e.key === 'Escape') {
        document.getElementById('customEntitySelect').classList.remove('open');
    }
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        const opts = document.querySelectorAll('.custom-select-option:not(.no-results)');
        if (opts.length) opts[0].focus();
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.custom-select')) {
        document.querySelectorAll('.custom-select.open').forEach(s => s.classList.remove('open'));
    }
});

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>
</body>
</html>
