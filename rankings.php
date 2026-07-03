<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';
require_once __DIR__ . '/includes/college_helpers.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

$rkSort   = $_GET['sort'] ?? 'nirf';
$rkType   = $_GET['type'] ?? 'all';
$rkState  = isset($_GET['state']) ? (int)$_GET['state'] : 0;
$rkPage   = max(1, (int)($_GET['page'] ?? 1));
$rkPerPage = 20;
$rkOffset  = ($rkPage - 1) * $rkPerPage;

if (!in_array($rkSort, ['nirf','rating','name'], true)) $rkSort = 'nirf';
if (!in_array($rkType, ['all','govt','private','deemed','autonomous'], true)) $rkType = 'all';

$rkWhere = "c.status = 'active' AND c.ranking_nirf IS NOT NULL AND c.ranking_nirf > 0";
$rkBindings = [];

if ($rkType !== 'all') { $rkWhere .= " AND c.college_type = :rktype"; $rkBindings[':rktype'] = $rkType; }
if ($rkState > 0) { $rkWhere .= " AND c.state_id = :rkstate"; $rkBindings[':rkstate'] = $rkState; }

$rkOrderSql = ['nirf'=>'c.ranking_nirf ASC','rating'=>'c.overall_rating_avg DESC, c.ranking_nirf ASC','name'=>'c.name ASC'][$rkSort];

// Count
$rkCountStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM colleges c WHERE $rkWhere");
$rkCountStmt->execute($rkBindings);
$rkTotal = (int)$rkCountStmt->fetchColumn();
$rkTotalPages = max(1, (int)ceil($rkTotal / $rkPerPage));

// Main query
$rkSql = "SELECT c.id, c.name, c.slug, c.college_type, c.naac_grade, c.ranking_nirf,
               c.overall_rating_avg, c.total_reviews, c.established_year, c.total_students,
               s.name AS state_name, ct.name AS city_name,
               cm.logo_url,
               (SELECT MAX(cp.avg_package_lpa) FROM college_placements cp WHERE cp.college_id=c.id) AS avg_package,
               (SELECT MAX(cp.highest_package_lpa) FROM college_placements cp WHERE cp.college_id=c.id) AS highest_package
        FROM colleges c
        LEFT JOIN states s ON c.state_id=s.id
        LEFT JOIN cities ct ON c.city_id=ct.id
        LEFT JOIN (SELECT college_id, logo_url FROM college_media GROUP BY college_id) cm ON cm.college_id=c.id
        WHERE $rkWhere
        ORDER BY $rkOrderSql
        LIMIT $rkPerPage OFFSET $rkOffset";

$rkStmt = $pdo->prepare($rkSql);
$rkStmt->execute($rkBindings);
$rkColleges = $rkStmt->fetchAll(PDO::FETCH_ASSOC);

// Top 3 for podium - uses same filters & sort as main table
$rkTop3Sql = "SELECT c.id, c.name, c.slug, c.naac_grade, c.ranking_nirf,
               c.overall_rating_avg, c.total_students,
               s.name AS state_name, ct.name AS city_name,
               cm.logo_url, cm.cover_image_url,
               (SELECT MAX(cp.avg_package_lpa) FROM college_placements cp WHERE cp.college_id=c.id) AS avg_package
        FROM colleges c
        LEFT JOIN states s ON c.state_id=s.id
        LEFT JOIN cities ct ON c.city_id=ct.id
        LEFT JOIN (SELECT college_id, logo_url, cover_image_url FROM college_media GROUP BY college_id) cm ON cm.college_id=c.id
        WHERE $rkWhere
        ORDER BY $rkOrderSql
        LIMIT 3";
$rkTop3Stmt = $pdo->prepare($rkTop3Sql);
$rkTop3Stmt->execute($rkBindings);
$rkTop3 = $rkTop3Stmt->fetchAll(PDO::FETCH_ASSOC);

$rkStates = $pdo->query("SELECT id, name FROM states ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$siteBase = defined('BASE_URL') ? BASE_URL : '/ADMISSION';
$canonicalUrl = $siteBase . '/rankings';
$typeLabels = ['all'=>'','govt'=>'Government','private'=>'Private','deemed'=>'Deemed','autonomous'=>'Autonomous'];
$typeLabel = $rkType !== 'all' ? ($typeLabels[$rkType] ?? '') . ' ' : '';
$pageTitle = $typeLabel . 'College Rankings ' . date('Y') . ' - NIRF, Ratings | AdmissionSeason';
$metaDesc = 'Check the latest ' . strtolower($typeLabel) . 'college rankings for ' . date('Y') . '. View NIRF rankings, ratings, placements and compare top colleges in India. ' . $rkTotal . ' colleges listed.';
$metaKeywords = 'college rankings ' . date('Y') . ', NIRF ranking, top colleges india, ' . strtolower($typeLabel) . 'college ranking, best engineering colleges, best medical colleges, college rating, college comparison';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/includes/favicon.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <link rel="canonical" href="<?= $canonicalUrl ?>">
    <meta name="author" content="AdmissionSeason">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
        .rkp{max-width:1100px;margin:0 auto;padding:30px 20px 60px}
        .rkp-hero{text-align:center;margin-bottom:40px}
        .rkp-hero h1{font-family:var(--font);font-size:2.2rem;font-weight:800;color:var(--primary);margin-bottom:8px}
        .rkp-hero p{color:rgba(15,23,42,.5);font-size:1rem}

        .podium{display:flex;align-items:flex-end;justify-content:center;gap:0;margin:0 auto 48px;max-width:820px;padding:0 16px}
        .pod-item{flex:1;max-width:250px;text-align:center;position:relative;padding:0 6px}
        .pod-medal{width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:800;color:#fff;margin:0 auto 10px;box-shadow:0 4px 16px rgba(0,0,0,.15)}
        .pod-medal.m-gold{background:linear-gradient(135deg,#F59E0B,#D97706)}
        .pod-medal.m-silver{background:linear-gradient(135deg,#94A3B8,#64748B)}
        .pod-medal.m-bronze{background:linear-gradient(135deg,#CD7F32,#A0522D)}
        .pod-img{width:64px;height:64px;border-radius:50%;margin:0 auto 8px;overflow:hidden;background:rgba(25,55,109,.06);border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.08)}
        .pod-img img{width:100%;height:100%;object-fit:cover}
        .pod-img .pod-img-ph{width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--primary);background:rgba(25,55,109,.06)}
        .pod-name{font-weight:700;font-size:.88rem;color:rgba(15,23,42,.9);margin-bottom:3px;line-height:1.3}
        .pod-name a{color:inherit;text-decoration:none}.pod-name a:hover{color:var(--primary)}
        .pod-loc{font-size:.72rem;color:rgba(15,23,42,.4);margin-bottom:8px}
        .pod-loc i{font-size:.7rem}
        .pod-stats{display:flex;justify-content:center;gap:14px;margin-bottom:10px}
        .pod-stat{text-align:center}
        .pod-stat strong{display:block;font-size:.88rem;font-weight:700;color:var(--primary)}
        .pod-stat span{font-size:.6rem;color:rgba(15,23,42,.4);text-transform:uppercase;letter-spacing:.04em;font-family:var(--font2)}
        .pod-naac{display:inline-block;padding:3px 10px;border-radius:6px;font-size:.68rem;font-weight:700;background:rgba(5,150,105,.1);color:#059669;margin-bottom:12px}
        .pod-bar{border-radius:10px 10px 0 0;display:flex;align-items:flex-start;justify-content:center;padding-top:16px}
        .pod-bar.b1{background:linear-gradient(180deg,rgba(245,158,11,.12),rgba(245,158,11,.03));height:130px}
        .pod-bar.b2{background:linear-gradient(180deg,rgba(100,116,139,.1),rgba(100,116,139,.02));height:100px}
        .pod-bar.b3{background:linear-gradient(180deg,rgba(205,127,50,.1),rgba(205,127,50,.02));height:80px}
        .pod-num{font-family:var(--font);font-weight:800;font-size:1.8rem;opacity:.1}

        .rkp-filters{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:24px;padding:16px 20px;background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:14px;box-shadow:0 2px 8px rgba(11,36,71,.04)}
        .rkp-filters label{font-size:.82rem;font-weight:600;color:rgba(15,23,42,.5);font-family:var(--font2)}
        .rkp-filters select,.rkp-btn{padding:8px 14px;border-radius:8px;font-size:.85rem;font-weight:600;font-family:var(--font2);border:1px solid rgba(15,23,42,.1);background:#fff;color:rgba(15,23,42,.7);text-decoration:none;transition:all .2s;cursor:pointer}
        .rkp-btn.active,.rkp-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary)}

        .rkp-tw{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:16px;overflow:hidden;box-shadow:0 4px 16px rgba(11,36,71,.06)}
        .rkp-tbl{width:100%;border-collapse:collapse}
        .rkp-tbl thead th{padding:14px 18px;text-align:left;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:rgba(15,23,42,.45);background:rgba(15,23,42,.02);border-bottom:1px solid rgba(15,23,42,.06);font-family:var(--font2);white-space:nowrap}
        .rkp-tbl thead th a{color:inherit;text-decoration:none}.rkp-tbl thead th a:hover,.rkp-tbl thead th a.on{color:var(--primary)}
        .rkp-tbl tbody tr{transition:background .15s}.rkp-tbl tbody tr:hover{background:rgba(25,55,109,.03)}
        .rkp-tbl tbody td{padding:14px 18px;border-bottom:1px solid rgba(15,23,42,.05);font-size:.88rem;vertical-align:middle}
        .rkp-tbl tbody tr:last-child td{border-bottom:none}
        .rk-td-rank{font-family:var(--font);font-size:1.15rem;font-weight:800;color:rgba(15,23,42,.12);min-width:44px}
        .rk-td-rank.top3{color:var(--primary);font-size:1.3rem}
        .rk-td-name a{font-weight:700;color:rgba(15,23,42,.9);text-decoration:none;display:block;line-height:1.3}.rk-td-name a:hover{color:var(--primary)}
        .rk-td-name-row{display:flex;align-items:center;gap:10px}.rk-td-name-row div{flex:1}
        .rk-td-logo{width:36px;height:36px;border-radius:6px;object-fit:cover;background:#f0f0f0;flex-shrink:0}
        .rk-td-loc{font-size:.75rem;color:rgba(15,23,42,.4);margin-top:2px}
        .rk-td-nirf{font-weight:700;color:var(--primary)}
        .rk-td-pkg{font-weight:700}.rk-td-pkg small{display:block;font-size:.7rem;color:rgba(15,23,42,.4);font-weight:400}
        .rk-td-rating{font-weight:700}.rk-td-rating i{color:#19376D}
        .rk-td-naac{display:inline-block;padding:2px 8px;border-radius:5px;font-size:.72rem;font-weight:700;background:rgba(5,150,105,.1);color:#059669}
        .rk-td-type{font-size:.72rem;font-weight:600;padding:2px 8px;border-radius:5px;background:rgba(15,23,42,.05);color:rgba(15,23,42,.6);text-transform:capitalize}

        .rkp-page{display:flex;justify-content:center;align-items:center;gap:6px;margin-top:24px}
        .rkp-page a{display:inline-flex;align-items:center;justify-content:center;min-width:40px;height:38px;padding:0 12px;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;font-family:var(--font2);transition:all .2s;background:#fff;border:1px solid rgba(15,23,42,.1);color:rgba(15,23,42,.7)}
        .rkp-page a:hover{border-color:var(--primary);color:var(--primary)}
        .rkp-page .on{background:var(--primary);color:#fff;border-color:var(--primary)}
        .rkp-page .off{opacity:.35;pointer-events:none}
        .rkp-info{text-align:center;margin-top:12px;color:rgba(15,23,42,.4);font-size:.82rem;font-family:var(--font2)}.rkp-info strong{color:var(--primary);font-weight:800}

        @media(max-width:768px){.rkp{padding:20px 12px 40px}.rkp-hero h1{font-size:1.5rem}.podium{flex-direction:column;align-items:center;gap:12px}.pod-item{max-width:100%;order:unset!important}.pod-bar{display:none}.rkp-tw{overflow-x:auto}.rkp-tbl{min-width:700px}}
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<main class="rkp">
    <div class="rkp-hero">
        <div class="nh-badge" style="margin:0 auto 14px"><i class="ph-fill ph-trophy"></i> Official Rankings</div>
        <h1>College Rankings 2026</h1>
        <p>Complete leaderboard of top colleges in India ranked by NIRF, ratings & placement data</p>
    </div>

    <?php if (!empty($rkTop3)): ?>
    <div class="podium">
        <?php
        $podMedal = ['m-gold', 'm-silver', 'm-bronze'];
        $podBar = ['b1', 'b2', 'b3'];
        $podPos = ['#1', '#2', '#3'];
        $podOrders = [1, 0, 2];
        foreach ($rkTop3 as $idx => $pc):
            $pos = $podOrders[$idx] ?? $idx;
        ?>
        <div class="pod-item" style="order:<?= $pos ?>">
            <div class="pod-medal <?= $podMedal[$idx] ?>"><?= $podPos[$idx] ?></div>
            <div class="pod-name"><a href="<?= BASE_URL ?>/college/<?= htmlspecialchars($pc['slug']) ?>"><?= htmlspecialchars($pc['name']) ?></a></div>
            <div class="pod-loc"><i class="ph ph-map-pin"></i> <?= htmlspecialchars(($pc['city_name']?:'') . (($pc['city_name']&&$pc['state_name'])?', ':'') . ($pc['state_name']?:'')) ?></div>
            <div class="pod-stats">
                <?php if(!empty($pc['avg_package'])): ?><div class="pod-stat"><strong>₹<?= number_format((float)$pc['avg_package'],1) ?>L</strong><span>Avg Package</span></div><?php endif; ?>
                <?php if(!empty($pc['overall_rating_avg'])): ?><div class="pod-stat"><strong><i class="ph-fill ph-star" style="color:#19376D;font-size:.8rem"></i> <?= number_format((float)$pc['overall_rating_avg'],1) ?></strong><span>Rating</span></div><?php endif; ?>
                <div class="pod-stat"><strong><?= number_format((int)($pc['total_students']??0)) ?>+</strong><span>Students</span></div>
            </div>
            <?php if(!empty($pc['naac_grade'])): ?><div class="pod-naac">NAAC <?= htmlspecialchars($pc['naac_grade']) ?></div><?php endif; ?>
            <div class="pod-bar <?= $podBar[$idx] ?>"><span class="pod-num"><?= $podPos[$idx] ?></span></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="rkp-filters">
        <label>Sort:</label>
        <a class="rkp-btn <?= $rkSort==='nirf'?'active':'' ?>" href="?sort=nirf<?= $rkType!=='all'?'&type='.$rkType:'' ?><?= $rkState?'&state='.$rkState:'' ?>">NIRF Rank</a>
        <a class="rkp-btn <?= $rkSort==='rating'?'active':'' ?>" href="?sort=rating<?= $rkType!=='all'?'&type='.$rkType:'' ?><?= $rkState?'&state='.$rkState:'' ?>">Rating</a>
        <a class="rkp-btn <?= $rkSort==='name'?'active':'' ?>" href="?sort=name<?= $rkType!=='all'?'&type='.$rkType:'' ?><?= $rkState?'&state='.$rkState:'' ?>">Name</a>
        <label style="margin-left:auto">Type:</label>
        <select onchange="location.href='?sort=<?= $rkSort ?>&type='+this.value<?= $rkState?"+'&state=$rkState'":'' ?>">
            <option value="all"<?= $rkType==='all'?' selected':'' ?>>All</option>
            <option value="govt"<?= $rkType==='govt'?' selected':'' ?>>Government</option>
            <option value="private"<?= $rkType==='private'?' selected':'' ?>>Private</option>
            <option value="deemed"<?= $rkType==='deemed'?' selected':'' ?>>Deemed</option>
        </select>
        <label>State:</label>
        <select onchange="location.href='?sort=<?= $rkSort ?>&state='+this.value<?= $rkType!=='all'?"+'&type=$rkType'":'' ?>">
            <option value="0"<?= $rkState===0?' selected':'' ?>>All States</option>
            <?php foreach($rkStates as $st): ?><option value="<?= $st['id'] ?>"<?= $rkState==(int)$st['id']?' selected':'' ?>><?= htmlspecialchars($st['name']) ?></option><?php endforeach; ?>
        </select>
    </div>

    <div class="rkp-tw">
        <table class="rkp-tbl">
            <thead><tr>
                <th style="width:60px">#</th>
                <th>College</th>
                <th><a class="<?= $rkSort==='nirf'?'on':'' ?>" href="?sort=nirf<?= $rkType!=='all'?'&type='.$rkType:'' ?><?= $rkState?'&state='.$rkState:'' ?>">NIRF</a></th>
                <th>Package</th>
                <th><a class="<?= $rkSort==='rating'?'on':'' ?>" href="?sort=rating<?= $rkType!=='all'?'&type='.$rkType:'' ?><?= $rkState?'&state='.$rkState:'' ?>">Rating</a></th>
                <th>NAAC</th>
                <th>Type</th>
            </tr></thead>
            <tbody>
            <?php if(empty($rkColleges)): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:rgba(15,23,42,.35)">No colleges found</td></tr>
            <?php else:
                $rkRank = $rkOffset + 1;
                foreach($rkColleges as $rc):
                    $g = strtoupper(trim($rc['naac_grade']??''));
                ?>
                <tr>
                    <td><span class="rk-td-rank<?= $rkRank<=3?' top3':'' ?>">#<?= str_pad((string)$rkRank,2,'0',STR_PAD_LEFT) ?></span></td>
                    <td class="rk-td-name">
                        <a href="<?= BASE_URL ?>/college/<?= htmlspecialchars($rc['slug']) ?>">
                            <div>
                                <div><?= htmlspecialchars($rc['name']) ?></div>
                                <div class="rk-td-loc"><i class="ph ph-map-pin"></i> <?= htmlspecialchars(($rc['city_name']?:'').(($rc['city_name']&&$rc['state_name'])?', ':'').($rc['state_name']?:'')) ?></div>
                            </div>
                        </a>
                    </td>
                    <td><span class="rk-td-nirf"><?= (int)$rc['ranking_nirf'] ?></span></td>
                    <td>
                        <?php if(!empty($rc['avg_package'])): ?><div class="rk-td-pkg">₹<?= number_format((float)$rc['avg_package'],1) ?>L<small>Avg Package</small></div>
                        <?php elseif(!empty($rc['highest_package'])): ?><div class="rk-td-pkg">₹<?= number_format((float)$rc['highest_package'],1) ?>L<small>Highest</small></div>
                        <?php elseif(!empty($rc['established_year'])&&$rc['established_year']>1900): ?><div class="rk-td-pkg" style="opacity:.5">Est. <?= (int)$rc['established_year'] ?></div>
                        <?php else: ?><span style="opacity:.3">—</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if(!empty($rc['overall_rating_avg'])): ?><span class="rk-td-rating"><i class="ph-fill ph-star"></i> <?= number_format((float)$rc['overall_rating_avg'],1) ?></span>
                        <?php else: ?><span style="opacity:.3">—</span><?php endif; ?>
                    </td>
                    <td><?php if(!empty($rc['naac_grade'])): ?><span class="rk-td-naac"><?= htmlspecialchars($rc['naac_grade']) ?></span><?php else: ?>—<?php endif; ?></td>
                    <td><span class="rk-td-type"><?= htmlspecialchars($rc['college_type']??'') ?></span></td>
                </tr>
            <?php $rkRank++; endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="rkp-info">Showing <strong><?= $rkOffset+1 ?>–<?= min($rkOffset+$rkPerPage,$rkTotal) ?></strong> of <strong><?= number_format($rkTotal) ?></strong> ranked colleges</div>

    <?php if($rkTotalPages>1): ?>
    <div class="rkp-page">
        <a href="?sort=<?= $rkSort ?>&page=<?= max(1,$rkPage-1) ?><?= $rkType!=='all'?'&type='.$rkType:'' ?><?= $rkState?'&state='.$rkState:'' ?>" class="<?= $rkPage<=1?'off':'' ?>">&#8592; Prev</a>
        <?php $s=max(1,$rkPage-2);$e=min($rkTotalPages,$rkPage+2);
        if($s>1): ?><a href="?sort=<?= $rkSort ?>&page=1<?= $rkType!=='all'?'&type='.$rkType:'' ?><?= $rkState?'&state='.$rkState:'' ?>">1</a><?php if($s>2)echo'<span style="opacity:.3">…</span>';endif;
        for($i=$s;$i<=$e;$i++): ?><a href="?sort=<?= $rkSort ?>&page=<?= $i ?><?= $rkType!=='all'?'&type='.$rkType:'' ?><?= $rkState?'&state='.$rkState:'' ?>" class="<?= $i===$rkPage?'on':'' ?>"><?= $i ?></a><?php endfor;
        if($e<$rkTotalPages): ?><?php if($e<$rkTotalPages-1)echo'<span style="opacity:.3">…</span>';?><a href="?sort=<?= $rkSort ?>&page=<?= $rkTotalPages ?><?= $rkType!=='all'?'&type='.$rkType:'' ?><?= $rkState?'&state='.$rkState:'' ?>"><?= $rkTotalPages ?></a><?php endif; ?>
        <a href="?sort=<?= $rkSort ?>&page=<?= min($rkTotalPages,$rkPage+1) ?><?= $rkType!=='all'?'&type='.$rkType:'' ?><?= $rkState?'&state='.$rkState:'' ?>" class="<?= $rkPage>=$rkTotalPages?'off':'' ?>">Next &#8594;</a>
    </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
