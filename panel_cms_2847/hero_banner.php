<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'db.php';

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
            // Check if slot is already occupied
            $table = $entityType === 'college' ? 'colleges' : ($entityType === 'university' ? 'universities' : 'schools');
            $idCol = 'id';

            // Check if this entity already has a different priority
            $checkStmt = $pdo->prepare("SELECT hero_priority FROM {$table} WHERE id = ?");
            $checkStmt->execute([$entityId]);
            $existing = $checkStmt->fetchColumn();

            // Check if slot is taken by another entity
            $slotStmt = $pdo->prepare("SELECT id, name FROM {$table} WHERE hero_priority = ? AND id != ?");
            $slotStmt->execute([$slot, $entityId]);
            $slotOccupant = $slotStmt->fetch(PDO::FETCH_ASSOC);

            if ($slotOccupant) {
                // Swap: move occupant to the old slot of this entity (if any), or clear it
                if ($existing) {
                    $swapStmt = $pdo->prepare("UPDATE {$table} SET hero_priority = ? WHERE id = ?");
                    $swapStmt->execute([$existing, $slotOccupant['id']]);
                } else {
                    $swapStmt = $pdo->prepare("UPDATE {$table} SET hero_priority = NULL WHERE id = ?");
                    $swapStmt->execute([$slotOccupant['id']]);
                }
            }

            // Assign this entity to the slot
            $assignStmt = $pdo->prepare("UPDATE {$table} SET hero_priority = ? WHERE id = ?");
            $assignStmt->execute([$slot, $entityId]);

            $msg = "Slot {$slot} assigned successfully!";
        } else {
            $error = "Invalid slot, entity type, or entity ID.";
        }
    }

    // Remove entity from hero slot
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

    // Clear all hero slots
    if ($action === 'clear_all') {
        $pdo->exec("UPDATE colleges SET hero_priority = NULL");
        $pdo->exec("UPDATE universities SET hero_priority = NULL");
        $pdo->exec("UPDATE schools SET hero_priority = NULL");
        $msg = "All hero slots cleared.";
    }
}

// Fetch current hero slots
function fetchHeroSlots($pdo) {
    $slots = [];

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
        LEFT JOIN school_media sm ON sm.school_id=sc.id AND sm.image_type IS NULL
        WHERE sc.hero_priority IS NOT NULL AND sc.status='active'
        ORDER BY sc.hero_priority ASC")->fetchAll(PDO::FETCH_ASSOC);

    $all = array_merge($cols, $unis, $schs);
    usort($all, fn($a, $b) => $a['hero_priority'] <=> $b['hero_priority']);
    return $all;
}

$heroSlots = fetchHeroSlots($pdo);
$occupiedSlots = array_column($heroSlots, 'hero_priority');

// Fetch available entities for assignment (not already assigned)
$availableColleges = $pdo->query("SELECT c.id, c.name, ci.name AS city_name FROM colleges c LEFT JOIN cities ci ON c.city_id=ci.id WHERE c.status='active' AND (c.hero_priority IS NULL OR c.hero_priority = 0) ORDER BY c.is_featured DESC, c.overall_rating_avg DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
$availableUnis = $pdo->query("SELECT u.id, u.name, ci.name AS city_name FROM universities u LEFT JOIN cities ci ON u.city_id=ci.id WHERE u.status='active' AND (u.hero_priority IS NULL OR u.hero_priority = 0) ORDER BY u.is_featured DESC, u.overall_rating_avg DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
$availableSchools = $pdo->query("SELECT sc.id, sc.name, ci.name AS city_name FROM schools sc LEFT JOIN cities ci ON sc.city_id=ci.id WHERE sc.status='active' AND (sc.hero_priority IS NULL OR sc.hero_priority = 0) ORDER BY sc.is_featured DESC, sc.overall_rating_avg DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hero Banner Management - AdmissionSeason</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.0.3/src/regular/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f1f5f9; color: #0f172a; }
        :root { --primary: #2563eb; --primary-dark: #1d4ed8; --border-color: #e2e8f0; --text-dark: #0f172a; --text-muted: #64748b; }
        .admin-layout { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 260px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; z-index: 50; transition: transform 0.3s ease; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.2rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; font-weight: 700; }
        .sidebar-nav { padding: 12px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 10px 20px; color: #f8fafc; text-decoration: none; font-size: 0.88rem; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left-color: var(--primary); }
        .sidebar-nav a i { font-size: 1.15rem; }

        /* Main */
        .main-content { flex: 1; margin-left: 260px; }
        .topbar { height: 60px; background: #fff; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; padding: 0 24px; position: sticky; top: 0; z-index: 10; }
        .header-left { display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 1rem; }
        .header-right { display: flex; align-items: center; gap: 16px; }
        #topbarToggle { display: none; background: none; border: none; font-size: 1.4rem; cursor: pointer; }
        .content-area { padding: 24px; }

        /* Cards */
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); padding: 24px; margin-bottom: 20px; }

        /* Slot Grid */
        .hero-slots { display: grid; grid-template-columns: repeat(5, 1fr); gap: 16px; margin-bottom: 24px; }
        .hero-slot { border: 2px dashed var(--border-color); border-radius: 12px; padding: 16px; text-align: center; min-height: 200px; display: flex; flex-direction: column; align-items: center; justify-content: center; transition: all 0.2s; position: relative; }
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
        .hero-slot-remove { position: absolute; top: 8px; right: 8px; width: 24px; height: 24px; border-radius: 50%; background: #ef4444; color: #fff; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; }

        /* Assign Form */
        .assign-grid { display: grid; grid-template-columns: 1fr 1fr 2fr auto; gap: 12px; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 4px; }
        .form-group label { font-size: 0.78rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group select, .form-group input { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 0.88rem; background: #fff; }
        .form-group select:focus, .form-group input:focus { outline: none; border-color: var(--primary); }
        .btn { padding: 10px 20px; border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; text-decoration: none; border: none; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-danger { background: #ef4444; color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        .btn-outline { background: transparent; color: var(--primary); border: 1px solid var(--primary); }
        .btn-outline:hover { background: var(--primary); color: #fff; }

        .msg-alert { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.88rem; display: flex; align-items: center; gap: 8px; }
        .msg-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.88rem; display: flex; align-items: center; gap: 8px; }

        .section-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }

        @media(max-width:1024px){
            .sidebar { transform:translateX(-100%) !important; }
            .sidebar.open { transform:translateX(0) !important; }
            .sidebar-overlay.show { display:block; }
            #topbarToggle { display:inline-flex !important; }
            .main-content { margin-left:0 !important; }
            .content-area { padding:16px !important; }
            .hero-slots { grid-template-columns: repeat(2, 1fr); }
            .assign-grid { grid-template-columns: 1fr; }
        }
        @media(max-width:768px){
            .hero-slots { grid-template-columns: 1fr 1fr; }
        }
        @media(max-width:480px){
            .hero-slots { grid-template-columns: 1fr; }
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
                    <div><i class="ph ph-images"></i> Hero Banner Management</div>
                </div>
                <div class="header-right">
                    <span style="font-size:0.88rem; color:rgba(15,23,42,0.65);"><?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?></span>
                    <a href="logout.php" style="color:#0f172a; font-size:1.2rem;"><i class="ph ph-sign-out"></i></a>
                </div>
            </header>

            <div class="content-area">
                <?php if ($msg): ?>
                <div class="msg-alert"><i class="ph ph-check-circle"></i> <?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                <div class="msg-error"><i class="ph ph-warning-circle"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <!-- Hero Slots (1-5) -->
                <div class="panel">
                    <div class="section-title"><i class="ph ph-images"></i> Hero Banner Slots (1-5)</div>
                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:20px;">
                        Assign up to 5 institutions to the hero banner carousel. Priority 1 = first shown, Priority 5 = last shown.
                        These slots support both organic featured and paid/premium placements.
                    </p>

                    <div class="hero-slots">
                        <?php for ($i = 1; $i <= 5; $i++):
                            $slotItem = null;
                            foreach ($heroSlots as $hs) {
                                if ((int)$hs['hero_priority'] === $i) {
                                    $slotItem = $hs;
                                    break;
                                }
                            }
                        ?>
                        <div class="hero-slot <?= $slotItem ? 'occupied' : '' ?>">
                            <div class="hero-slot-number">Slot <?= $i ?></div>
                            <?php if ($slotItem): ?>
                                <img src="<?= htmlspecialchars($slotItem['cover_image_url'] ?: 'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80') ?>" alt="" class="hero-slot-img">
                                <span class="hero-slot-type type-<?= $slotItem['entity_type'] ?>"><?= ucfirst($slotItem['entity_type']) ?></span>
                                <div class="hero-slot-name"><?= htmlspecialchars($slotItem['name']) ?></div>
                                <div class="hero-slot-meta"><?= htmlspecialchars($slotItem['city_name'] ?? '') ?><?= $slotItem['city_name'] && $slotItem['state_name'] ? ', ' : '' ?><?= htmlspecialchars($slotItem['state_name'] ?? '') ?></div>
                                <?php if ($slotItem['overall_rating_avg'] > 0): ?>
                                <div style="font-size:0.75rem; color:#f59e0b; margin-bottom:6px;"><i class="ph-fill ph-star"></i> <?= number_format((float)$slotItem['overall_rating_avg'], 1) ?></div>
                                <?php endif; ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="entity_type" value="<?= $slotItem['entity_type'] ?>">
                                    <input type="hidden" name="entity_id" value="<?= $slotItem['id'] ?>">
                                    <button type="submit" class="hero-slot-remove" title="Remove from slot"><i class="ph ph-x"></i></button>
                                </form>
                            <?php else: ?>
                                <i class="ph ph-image"></i>
                                <div class="hero-slot-empty">Empty Slot</div>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <div style="display:flex; gap:10px; margin-top:12px;">
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
                            <label>Slot Priority (1-5)</label>
                            <select name="slot" required>
                                <option value="">Select Slot</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>" <?= in_array($i, $occupiedSlots) ? 'style="color:#94a3b8"' : '' ?>>
                                    Slot <?= $i ?> <?= in_array($i, $occupiedSlots) ? '(occupied)' : '' ?>
                                </option>
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
                            <select name="entity_id" id="entityId" required>
                                <option value="">Select type first...</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="ph ph-check"></i> Assign</button>
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

function toggleEntitySelect() {
    const type = document.getElementById('entityType').value;
    const select = document.getElementById('entityId');
    select.innerHTML = '<option value="">Loading...</option>';

    if (!type || !entities[type]) {
        select.innerHTML = '<option value="">Select type first...</option>';
        return;
    }

    let html = '<option value="">Select institution...</option>';
    entities[type].forEach(e => {
        const loc = [e.city_name].filter(Boolean).join(', ');
        html += '<option value="' + e.id + '">' + e.name + (loc ? ' — ' + loc : '') + '</option>';
    });
    select.innerHTML = html;
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('show');
}
</script>
</body>
</html>
