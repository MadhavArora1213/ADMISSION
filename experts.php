<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch all experts
$experts = $pdo->query("SELECT * FROM experts ORDER BY answer_count DESC")->fetchAll(PDO::FETCH_ASSOC);

// Expert stats
$totalExperts = count($experts);
$verifiedCount = 0;
foreach ($experts as $e) {
    if ($e['verified_badge']) $verifiedCount++;
}

// Track which experts current user follows
$userId = $_SESSION['user_id'] ?? 'user-1234-uuid';
$followedExperts = [];
if (!empty($experts)) {
    $eIds = array_column($experts, 'id');
    $ph = implode(',', array_fill(0, count($eIds), '?'));
    $fStmt = $pdo->prepare("SELECT followable_id FROM follows WHERE user_id = ? AND followable_type = 'expert' AND followable_id IN ($ph)");
    $fStmt->execute(array_merge([$userId], $eIds));
    $followedExperts = $fStmt->fetchAll(PDO::FETCH_COLUMN);
}

// Category grouping
$specializations = [];
foreach ($experts as $e) {
    $spec = $e['specialization'] ?: 'General';
    $specializations[$spec][] = $e;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Panel of Experts | AdmissionSeason</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root { --oxford-navy: #0B2447; --yale-blue: #19376D; --snow-pearl: #F8FAFC; --ink-black: #0F172A; --border-color-alt: #e2e8f0; --text-muted-alt: #64748b; --expert-badge-color: #0d9488; }

        /* Hero */
        .exp-hero {
            background: linear-gradient(135deg, var(--yale-blue) 0%, var(--oxford-navy) 100%);
            color: #fff;
            padding: 50px 0 40px;
            text-align: center;
        }
        .exp-hero h1 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .exp-hero p {
            font-size: 1rem;
            opacity: 0.85;
            max-width: 600px;
            margin: 0 auto 24px;
            line-height: 1.6;
        }
        .exp-stats-row {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }
        .exp-stat-box {
            text-align: center;
        }
        .exp-stat-box .num {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            display: block;
        }
        .exp-stat-box .lbl {
            font-size: 0.82rem;
            opacity: 0.75;
            margin-top: 2px;
        }

        /* Container */
        .exp-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 30px 20px 60px;
        }

        /* Filter Tabs */
        .exp-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 30px;
            justify-content: center;
        }
        .exp-filter-btn {
            padding: 8px 20px;
            border-radius: 100px;
            border: 1px solid var(--border-color-alt);
            background: #fff;
            color: var(--text-muted-alt);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .exp-filter-btn:hover, .exp-filter-btn.active {
            background: var(--yale-blue);
            color: #fff;
            border-color: var(--yale-blue);
        }

        /* Expert Grid */
        .exp-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 24px;
        }

        .exp-card {
            background: #fff;
            border: 1px solid var(--border-color-alt);
            border-radius: 20px;
            padding: 28px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .exp-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(11, 36, 71, 0.06);
            border-color: rgba(25, 55, 109, 0.2);
        }
        .exp-card-top {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 18px;
        }
        .exp-card-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(25, 55, 109, 0.1);
            flex-shrink: 0;
        }
        .exp-card-info h3 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--oxford-navy);
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }
        .exp-card-info .desig {
            font-size: 0.85rem;
            color: var(--text-muted-alt);
            line-height: 1.4;
        }
        .exp-card-info .college {
            font-size: 0.82rem;
            color: var(--yale-blue);
            font-weight: 600;
            margin-top: 2px;
        }

        .exp-card-spec {
            display: inline-block;
            background: rgba(25, 55, 109, 0.06);
            color: var(--yale-blue);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 5px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .exp-card-stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            padding-top: 16px;
            border-top: 1px solid var(--border-color-alt);
        }
        .exp-card-stat {
            text-align: center;
        }
        .exp-card-stat .val {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--oxford-navy);
            display: block;
        }
        .exp-card-stat .lbl {
            font-size: 0.7rem;
            color: var(--text-muted-alt);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .exp-card-actions {
            display: flex;
            gap: 10px;
            margin-top: 18px;
        }
        .exp-card-btn {
            flex: 1;
            padding: 10px;
            border-radius: 10px;
            border: none;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s;
        }
        .exp-card-btn.primary {
            background: var(--yale-blue);
            color: #fff;
        }
        .exp-card-btn.primary:hover {
            background: var(--oxford-navy);
        }
        .exp-card-btn.secondary {
            background: rgba(25, 55, 109, 0.06);
            color: var(--yale-blue);
            border: 1px solid rgba(25, 55, 109, 0.15);
        }
        .exp-card-btn.secondary:hover {
            background: rgba(25, 55, 109, 0.1);
        }
        .exp-follow-btn.active {
            background: var(--yale-blue) !important;
            color: #fff !important;
            border-color: var(--yale-blue) !important;
        }
        .exp-follow-btn.active:hover {
            background: var(--oxford-navy) !important;
        }
        .fl-count {
            font-size: 0.75rem;
            opacity: 0.8;
        }

        .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(13, 148, 136, 0.08);
            color: var(--expert-badge-color);
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 6px;
            margin-top: 4px;
        }

        /* Section Title */
        .exp-section-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--oxford-navy);
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--border-color-alt);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* CTA Section */
        .exp-cta {
            background: linear-gradient(135deg, var(--yale-blue) 0%, var(--oxford-navy) 100%);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            color: #fff;
            margin-top: 40px;
        }
        .exp-cta h3 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        .exp-cta p {
            opacity: 0.85;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }
        .exp-cta-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 32px;
            background: #fff;
            color: var(--yale-blue);
            border: none;
            border-radius: 100px;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
        }
        .exp-cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        /* Empty State */
        .exp-empty {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-muted-alt);
        }
        .exp-empty i {
            font-size: 4rem;
            margin-bottom: 16px;
            color: #cbd5e1;
        }

        @media (max-width: 768px) {
            .exp-hero h1 { font-size: 1.8rem; }
            .exp-grid { grid-template-columns: 1fr; }
            .exp-card-top { flex-direction: column; align-items: center; text-align: center; }
            .exp-card-info h3 { justify-content: center; }
            .exp-card-spec { text-align: center; }
            .exp-stats-row { gap: 24px; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Hero Section -->
<section class="exp-hero">
    <div style="max-width:1100px; margin:0 auto; padding:0 20px;">
        <h1><i class="ph ph-shield-check"></i> Our Panel of Experts</h1>
        <p>Get guidance from verified education experts who have helped thousands of students navigate admissions, placements, and career choices.</p>
        <div class="exp-stats-row">
            <div class="exp-stat-box">
                <span class="num"><?= $totalExperts ?></span>
                <span class="lbl">Total Experts</span>
            </div>
            <div class="exp-stat-box">
                <span class="num"><?= $verifiedCount ?></span>
                <span class="lbl">Verified Experts</span>
            </div>
            <div class="exp-stat-box">
                <span class="num"><?= count($specializations) ?></span>
                <span class="lbl">Specializations</span>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="exp-container">

    <!-- Filter Buttons -->
    <div class="exp-filters">
        <button class="exp-filter-btn active" onclick="filterExperts('all', this)">All Experts</button>
        <?php foreach (array_keys($specializations) as $spec): ?>
            <button class="exp-filter-btn" onclick="filterExperts('<?= htmlspecialchars(strtolower(str_replace(' ', '-', $spec))) ?>', this)"><?= htmlspecialchars($spec) ?></button>
        <?php endforeach; ?>
    </div>

    <?php if (empty($experts)): ?>
        <div class="exp-empty">
            <i class="ph ph-user-check"></i>
            <h3>No Experts Found</h3>
            <p style="margin-top:8px;">Our expert panel is being assembled. Check back soon!</p>
        </div>
    <?php else: ?>
        <div class="exp-grid">
            <?php foreach ($experts as $exp): ?>
                <div class="exp-card" data-spec="<?= htmlspecialchars(strtolower(str_replace(' ', '-', $exp['specialization'] ?: 'general'))) ?>">
                    <div class="exp-card-top">
                        <img src="<?= htmlspecialchars($exp['profile_url'] ?: 'https://images.unsplash.com/photo-1537368910025-700350fe46c7?w=120&h=120&fit=crop') ?>" alt="<?= htmlspecialchars($exp['expert_name']) ?>" class="exp-card-avatar">
                        <div class="exp-card-info">
                            <h3>
                                <?= htmlspecialchars($exp['expert_name']) ?>
                                <?php if ($exp['verified_badge']): ?>
                                    <i class="ph-fill ph-seal-check" style="color:var(--expert-badge-color); font-size:1.1rem;" title="Verified Expert"></i>
                                <?php endif; ?>
                            </h3>
                            <div class="desig"><?= htmlspecialchars($exp['expert_designation']) ?></div>
                            <div class="college"><i class="ph ph-buildings"></i> <?= htmlspecialchars($exp['expert_college'] ?: 'AdmissionSeason') ?></div>
                            <?php if ($exp['verified_badge']): ?>
                                <span class="verified-badge"><i class="ph-fill ph-seal-check"></i> Verified Expert</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <span class="exp-card-spec"><?= htmlspecialchars($exp['specialization'] ?: 'General Guidance') ?></span>

                    <div class="exp-card-stats">
                        <div class="exp-card-stat">
                            <span class="val"><?= number_format((int)$exp['answer_count']) ?></span>
                            <span class="lbl">Answers</span>
                        </div>
                        <div class="exp-card-stat">
                            <span class="val"><?= number_format((int)($exp['follow_count'] ?? 0)) ?></span>
                            <span class="lbl">Followers</span>
                        </div>
                        <div class="exp-card-stat">
                            <span class="val"><?= (int)$exp['response_rate_pct'] ?>%</span>
                            <span class="lbl">Response</span>
                        </div>
                    </div>

                    <div class="exp-card-actions">
                        <a href="<?= BASE_URL ?>/community?tab=qna" class="exp-card-btn primary">
                            <i class="ph ph-chat-text"></i> Ask Expert
                        </a>
                        <button class="exp-card-btn secondary exp-follow-btn <?= in_array($exp['id'], $followedExperts) ? 'active' : '' ?>" onclick="toggleExpertFollow('<?= $exp['id'] ?>', this)">
                            <i class="<?= in_array($exp['id'], $followedExperts) ? 'ph-fill' : 'ph' ?> ph-bell"></i>
                            <span class="fl-label"><?= in_array($exp['id'], $followedExperts) ? 'Following' : 'Follow' ?></span>
                            <span class="fl-count"><?= (int)($exp['follow_count'] ?? 0) > 0 ? '(' . number_format((int)$exp['follow_count']) . ')' : '' ?></span>
                        </button>
                        <?php if (!empty($exp['linkedin_url'])): ?>
                            <a href="<?= htmlspecialchars($exp['linkedin_url']) ?>" target="_blank" class="exp-card-btn secondary">
                                <i class="ph ph-linkedin-logo"></i> LinkedIn
                            </a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/community?tab=ask" class="exp-card-btn secondary">
                                <i class="ph ph-paper-plane-right"></i> Post Query
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- CTA -->
    <div class="exp-cta">
        <h3><i class="ph ph-question"></i> Have a Question?</h3>
        <p>Post your question and get answers from our verified experts within 24 hours.</p>
        <a href="<?= BASE_URL ?>/community?tab=ask" class="exp-cta-btn">
            <i class="ph ph-paper-plane-right"></i> Ask Your Question
        </a>
    </div>
</div>

<script>
function filterExperts(spec, btn) {
    document.querySelectorAll('.exp-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('.exp-card').forEach(card => {
        if (spec === 'all' || card.dataset.spec === spec) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

async function toggleExpertFollow(id, btn) {
    try {
        const res = await fetch('api/community_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'toggle_follow', type: 'expert', id: id })
        });
        const data = await res.json();
        if (data.status === 'success') {
            const isActive = data.action === 'followed';
            btn.classList.toggle('active', isActive);
            const icon = btn.querySelector('i');
            icon.className = isActive ? 'ph-fill ph-bell' : 'ph ph-bell';
            const label = btn.querySelector('.fl-label');
            if (label) label.textContent = isActive ? 'Following' : 'Follow';
            const count = btn.querySelector('.fl-count');
            if (count && data.count > 0) {
                count.textContent = '(' + data.count + ')';
            } else if (count) {
                count.textContent = '';
            }
        } else if (data.message && data.message.includes('login')) {
            if (confirm('Please login to follow experts. Click OK to go to login page.')) {
                window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.href);
            }
        }
    } catch (err) {
        console.error('Follow error:', err);
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
