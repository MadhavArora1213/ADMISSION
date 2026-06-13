<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/admin/db.php';

function cCol(PDO $pdo, string $sql, int $d = 0): int {
    try { $s = $pdo->query($sql); $v = $s->fetchColumn(); return $v !== false && $v !== null ? (int)$v : $d; }
    catch (Exception $e) { return $d; }
}
function cAll(PDO $pdo, string $sql): array {
    try { return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
    catch (Exception $e) { return []; }
}

$totalColleges = cCol($pdo, "SELECT COUNT(*) FROM colleges WHERE status='active'", 25);
$totalReviews  = cCol($pdo, "SELECT COUNT(*) FROM reviews WHERE moderation_status='approved'", 100);
$totalExams    = cCol($pdo, "SELECT COUNT(*) FROM exams", 500);
$totalCourses  = cCol($pdo, "SELECT COUNT(*) FROM courses WHERE status='active'", 1000);

$categories = cAll($pdo, "SELECT id,category_name,category_slug,icon_url FROM course_categories WHERE is_featured=1 ORDER BY sort_order ASC LIMIT 6");
$catFallback = [
    ['name'=>'Engineering','slug'=>'engineering','icon'=>'ph-laptop','count'=>'6,000+'],
    ['name'=>'Management','slug'=>'management','icon'=>'ph-briefcase','count'=>'4,500+'],
    ['name'=>'Medical','slug'=>'medical','icon'=>'ph-stethoscope','count'=>'1,200+'],
    ['name'=>'Commerce','slug'=>'commerce','icon'=>'ph-chart-line','count'=>'3,100+'],
    ['name'=>'Arts & Design','slug'=>'arts','icon'=>'ph-palette','count'=>'2,000+'],
    ['name'=>'Law','slug'=>'law','icon'=>'ph-scales','count'=>'1,100+'],
];

$sqlC = "SELECT c.id,c.name,c.slug,c.college_type,c.naac_grade,c.ranking_nirf,c.overall_rating_avg,c.total_reviews,
                s.name AS state_name,ct.name AS city_name,cm.cover_image_url,cm.logo_url,
                (SELECT MAX(avg_package_lpa) FROM college_placements cp WHERE cp.college_id=c.id) AS avg_package,
                (SELECT MIN(annual_fee) FROM college_courses cc WHERE cc.college_id=c.id) AS min_fee
         FROM colleges c LEFT JOIN states s ON c.state_id=s.id LEFT JOIN cities ct ON c.city_id=ct.id
         LEFT JOIN college_media cm ON cm.college_id=c.id
         WHERE c.status='active' AND c.is_featured=1 ORDER BY c.featured_order ASC,c.ranking_nirf ASC LIMIT 8";
$featuredColleges = cAll($pdo, $sqlC);
if (empty($featuredColleges)) $featuredColleges = cAll($pdo, str_replace("AND c.is_featured=1","AND c.overall_rating_avg>0",$sqlC));

$sqlCourses = "SELECT id,course_name,course_slug,course_level,duration_years,avg_salary_lpa,total_colleges_offering,description FROM courses WHERE status='active' ORDER BY total_colleges_offering DESC LIMIT 8";
$popularCourses = cAll($pdo, str_replace("WHERE status='active'","WHERE status='active' AND is_popular=1",$sqlCourses));
if (empty($popularCourses)) $popularCourses = cAll($pdo, $sqlCourses);

$upcomingExams = cAll($pdo, "SELECT e.id,e.name,e.slug,e.level,ed.exam_date,ed.application_start,ed.application_end,ed.result_date,ed.event_name FROM exams e LEFT JOIN exam_dates ed ON ed.exam_id=e.id AND (ed.exam_date>=CURDATE() OR ed.application_end>=CURDATE()) GROUP BY e.id ORDER BY ed.exam_date ASC LIMIT 6");

$reviews = cAll($pdo, "SELECT r.overall_rating,r.review_title,r.review_body,r.batch_year,r.created_at,c.name AS college_name,c.slug AS college_slug FROM reviews r JOIN colleges c ON c.id=r.college_id WHERE r.moderation_status='approved' ORDER BY r.helpful_votes DESC,r.created_at DESC LIMIT 6");

$states = cAll($pdo, "SELECT id,name FROM states ORDER BY name ASC");

function cImg(string $url=''): string { return $url ?: 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80'; }

$fColleges = [
    ['name'=>'IIT Delhi','loc'=>'New Delhi, Delhi NCR','type'=>'Public','rating'=>'4.8','fee'=>'₹2.5L','pkg'=>'₹21.5L','img'=>'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80'],
    ['name'=>'IIM Ahmedabad','loc'=>'Ahmedabad, Gujarat','type'=>'Public','rating'=>'4.7','fee'=>'₹27.5L','pkg'=>'₹33.8L','img'=>'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=800&q=80'],
    ['name'=>'BITS Pilani','loc'=>'Pilani, Rajasthan','type'=>'Private','rating'=>'4.5','fee'=>'₹5.4L','pkg'=>'₹18.2L','img'=>'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=800&q=80'],
    ['name'=>'AIIMS Delhi','loc'=>'New Delhi, Delhi NCR','type'=>'Government','rating'=>'4.9','fee'=>'₹39K','pkg'=>'₹15L+','img'=>'https://images.unsplash.com/photo-1551076805-e1869033e561?w=800&q=80'],
];
$fCourses = [
    ['icon'=>'ph-laptop','level'=>'UG','name'=>'B.Tech','dur'=>'4 yrs','cols'=>'4,500+'],
    ['icon'=>'ph-briefcase','level'=>'PG','name'=>'MBA','dur'=>'2 yrs','cols'=>'3,200+'],
    ['icon'=>'ph-stethoscope','level'=>'UG','name'=>'MBBS','dur'=>'5.5 yrs','cols'=>'650+'],
    ['icon'=>'ph-scales','level'=>'UG','name'=>'LLB','dur'=>'3 yrs','cols'=>'1,800+'],
    ['icon'=>'ph-chart-line','level'=>'UG','name'=>'B.Com','dur'=>'3 yrs','cols'=>'3,000+'],
    ['icon'=>'ph-flask','level'=>'UG','name'=>'B.Sc','dur'=>'3 yrs','cols'=>'2,800+'],
    ['icon'=>'ph-pen-nib','level'=>'UG','name'=>'BA','dur'=>'3 yrs','cols'=>'4,200+'],
    ['icon'=>'ph-buildings','level'=>'UG','name'=>'BCA','dur'=>'3 yrs','cols'=>'1,900+'],
];
$fExams = [
    ['name'=>'JEE Main','level'=>'National','date'=>'24 Jan 2026','last'=>'15 Dec 2025','cols'=>'2,046'],
    ['name'=>'NEET','level'=>'National','date'=>'04 May 2026','last'=>'15 Mar 2026','cols'=>'1,374'],
    ['name'=>'CAT','level'=>'National','date'=>'30 Nov 2025','last'=>'20 Sep 2025','cols'=>'1,781'],
    ['name'=>'GATE','level'=>'National','date'=>'02 Feb 2026','last'=>'10 Oct 2025','cols'=>'895'],
    ['name'=>'CUET','level'=>'National','date'=>'11 May 2026','last'=>'30 Mar 2026','cols'=>'583'],
    ['name'=>'CLAT','level'=>'National','date'=>'03 Dec 2025','last'=>'15 Nov 2025','cols'=>'412'],
];
$examsFeatured = [
    ['name'=>'JEE Main','level'=>'National','date'=>'02 Apr 2026','type'=>'Online','img'=>'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=400&q=80'],
    ['name'=>'NEET','level'=>'National','date'=>'03 May 2026','type'=>'Offline','img'=>'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=400&q=80'],
    ['name'=>'CUET','level'=>'National','date'=>'11 May 2026','type'=>'Offline','img'=>'https://images.unsplash.com/photo-1452860606245-08a5d1cb3b9e?w=400&q=80'],
    ['name'=>'CAT','level'=>'National','date'=>'30 Nov 2025','type'=>'Online','img'=>'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=400&q=80'],
];
$newsItems = [
    ['title'=>'JoSAA 2026 Round 1 Seat Allotment Result Out Now','cat'=>'Admission','date'=>'13 Jun 2026','img'=>'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=600&q=80'],
    ['title'=>'NEET 2026: Expected Cutoff & College Predictions Released','cat'=>'Exam','date'=>'12 Jun 2026','img'=>'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=600&q=80'],
    ['title'=>'CBSE Class 12 Hindi Core Question Paper 2026 with Solution','cat'=>'Board','date'=>'12 Jun 2026','img'=>'https://images.unsplash.com/photo-1452860606245-08a5d1cb3b9e?w=600&q=80'],
    ['title'=>'Top 10 Engineering Colleges in India 2026 – Fees & Ranking','cat'=>'College','date'=>'11 Jun 2026','img'=>'https://images.unsplash.com/photo-1562774053-701939374585?w=600&q=80'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>AdmissionSeason – Top Colleges, Exams & Courses in India 2026</title>
<meta name="description" content="India's leading college discovery platform. Find top colleges, exams, courses, fees, rankings, and admission updates.">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=5">

</head>
<body>

<!-- ═══ BG CANVAS ═══ -->
<div class="bg-canvas">
  <div class="floating-shape"></div>
  <div class="floating-shape"></div>
  <div class="floating-shape"></div>
  <div class="floating-shape"></div>
  <div class="floating-shape"></div>
</div>

<!-- ═══ PRO STYLE NAVBAR ═══ -->
<header class="pro-header" id="header">
  <div class="pro-nav-main">
    <div class="container pro-nav-flex">
      <div class="pro-nav-left">
        <a href="/" class="pro-logo">
          <i class="ph-fill ph-student"></i>
          <span>AdmissionSeason</span>
        </a>
      </div>
      
      <div class="pro-nav-search">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" placeholder="Search for Colleges, Exams, Courses and More..">
      </div>
      
      <div class="pro-nav-right">
        <a href="#" class="pro-nav-link"><i class="ph ph-pencil-simple"></i> Write a Review</a>
        <a href="#" class="pro-nav-link"><i class="ph ph-squares-four"></i> Explore</a>
        <a href="#" class="pro-icon-btn"><i class="ph ph-bell"></i></a>
        <a href="#" class="pro-user-btn"><i class="ph-fill ph-user"></i></a>
      </div>
    </div>
  </div>
  
  <div class="pro-nav-sub">
    <div class="container pro-nav-flex">
      <ul class="pro-sub-links">
        <li><a href="#" class="active"><i class="ph-fill ph-squares-four"></i> All Courses</a></li>
        <?php foreach(array_slice($categories?:$catFallback, 0, 10) as $c): ?>
          <li><a href="#"><?=htmlspecialchars($c['category_name'] ?? $c['name'])?></a></li>
        <?php endforeach; ?>
      </ul>
      <ul class="pro-sub-links-right">
        <li><a href="#"><i class="ph ph-suitcase"></i> Work Abroad</a></li>
        <li><a href="#"><i class="ph ph-globe-hemisphere-west"></i> Study Abroad</a></li>
      </ul>
    </div>
  </div>
</header>

<!-- ═══ PRO STYLE HERO ═══ -->
<section class="pro-hero">
  <div class="pro-hero-bg" style="background-image: url('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=1600&q=80');"></div>
  <div class="container pro-hero-content">
    <h1>Find Over <?=number_format($totalExams?:250)?>+ Exams in India</h1>
    <p>Explore curated data on top institutions, programs, and premium reviews to secure your future.</p>
    
    <div class="pro-hero-search-card">
      <div class="pro-search-tabs">
        <button class="active">Colleges</button>
        <button>Exams</button>
        <button>Courses</button>
      </div>
      <div class="pro-search-input-row">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" placeholder="Search premier colleges, exams, courses and more..">
        <button class="pro-search-btn">Search</button>
      </div>
    </div>
    
    <div class="pro-hero-bottom">
      <div class="pro-recent-visits">
        <strong>Your Recent Visits</strong>
        <a href="#">IIM Ahmedabad, Ahmedabad</a>
        <a href="#">JEE Main</a>
        <a href="#">CBSE X</a>
      </div>
      <button class="pro-counselling-btn">Need Counselling</button>
    </div>
  </div>
</section>

<!-- ═══ FLOATING CHIPS MARQUEE ═══ -->
<div class="marquee-wrap">
  <div class="marquee-inner">
    <div class="m-chip"><i class="ph-fill ph-check-circle"></i> IIT Delhi</div>
    <div class="m-chip m-chip-highlight"><i class="ph-fill ph-star"></i> IIM Ahmedabad</div>
    <div class="m-chip"><i class="ph-fill ph-check-circle"></i> BITS Pilani</div>
    <div class="m-chip"><i class="ph-fill ph-check-circle"></i> AIIMS Delhi</div>
    <div class="m-chip m-chip-highlight"><i class="ph-fill ph-star"></i> IIT Bombay</div>
    <div class="m-chip"><i class="ph-fill ph-check-circle"></i> NLSIU Bangalore</div>
    <div class="m-chip"><i class="ph-fill ph-check-circle"></i> IIT Madras</div>
    <div class="m-chip"><i class="ph-fill ph-check-circle"></i> IIT Delhi</div>
    <div class="m-chip m-chip-highlight"><i class="ph-fill ph-star"></i> IIM Ahmedabad</div>
    <div class="m-chip"><i class="ph-fill ph-check-circle"></i> BITS Pilani</div>
    <div class="m-chip"><i class="ph-fill ph-check-circle"></i> AIIMS Delhi</div>
    <div class="m-chip m-chip-highlight"><i class="ph-fill ph-star"></i> IIT Bombay</div>
    <div class="m-chip"><i class="ph-fill ph-check-circle"></i> NLSIU Bangalore</div>
    <div class="m-chip"><i class="ph-fill ph-check-circle"></i> IIT Madras</div>
  </div>
</div>

<!-- ═══ STREAMS ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr reveal"><h2>Discover Disciplines</h2><p>Immersive exploration of India's top academic fields</p></div>
    <div class="bento-grid">
    <?php if (!empty($categories)): ?>
      <?php foreach ($categories as $i=>$cat): 
        $bClass = 'bento-item reveal reveal-delay-'.$i;
        if($i===0) $bClass .= ' bento-large';
        elseif($i===1 || $i===4) $bClass .= ' bento-wide';
      ?>
      <a href="#" class="<?=$bClass?>">
        <div class="stream-icon"><i class="ph ph-<?=htmlspecialchars($cat['category_slug'])?>"></i></div>
        <h3><?=htmlspecialchars($cat['category_name'])?></h3>
        <span>Explore Programs →</span>
      </a>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($catFallback as $i=>$c): 
        $bClass = 'bento-item reveal reveal-delay-'.$i;
        if($i===0) $bClass .= ' bento-large';
        elseif($i===1 || $i===4) $bClass .= ' bento-wide';
      ?>
      <a href="#" class="<?=$bClass?>">
        <div class="stream-icon"><i class="ph <?=$c['icon']?>"></i></div>
        <h3><?=$c['name']?></h3>
        <span><?=$c['count']?> Programs →</span>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </div>
</section>

<!-- ═══ TOP RANKED TABLE ═══ -->
<section class="section-dark">
  <div class="container">
    <div class="section-hdr-flex">
      <div><h2>Elite Rankings 2026</h2><p>The pinnacle of academic excellence curated for you</p></div>
      <a href="#" class="section-link">View Full Leaderboard <i class="ph ph-arrow-right"></i></a>
    </div>
    
    <div class="rank-list">
      <?php if (!empty($featuredColleges)): $rk=1; ?>
        <?php foreach (array_slice($featuredColleges,0,5) as $cl): ?>
        <a href="#" class="rank-item">
          <div class="r-rank">#<?=sprintf("%02d", $rk++)?></div>
          <div class="r-col">
            <strong><?=htmlspecialchars($cl['name'])?></strong>
            <span><i class="ph ph-map-pin"></i> <?=htmlspecialchars($cl['city_name']??'')?><?=($cl['city_name']&&$cl['state_name'])?', ':''?><?=htmlspecialchars($cl['state_name']??'')?></span>
          </div>
          <div class="r-meta">
            <strong><?=!empty($cl['ranking_nirf'])?'NIRF Rank '.$cl['ranking_nirf']:'Unranked'?></strong>
            <span>National Ranking</span>
          </div>
          <div class="r-meta">
            <strong><?=!empty($cl['avg_package'])?'₹'.number_format((float)$cl['avg_package'],1).'L':'N/A'?></strong>
            <span>Avg Package</span>
          </div>
          <div class="r-meta" style="align-items:flex-end">
            <strong><?php if(!empty($cl['overall_rating_avg'])):?><i class="ph-fill ph-star" style="color:#f59e0b"></i> <?=number_format((float)$cl['overall_rating_avg'],1)?><?php else:?>N/A<?php endif;?></strong>
            <span>Rating</span>
          </div>
        </a>
        <?php endforeach; ?>
      <?php else: $rk=1; foreach (array_slice($fColleges,0,5) as $cl): ?>
        <a href="#" class="rank-item">
          <div class="r-rank">#<?=sprintf("%02d", $rk++)?></div>
          <div class="r-col">
            <strong><?=$cl['name']?></strong>
            <span><i class="ph ph-map-pin"></i> <?=$cl['loc']?></span>
          </div>
          <div class="r-meta">
            <strong>NIRF <?=$rk-1?></strong>
            <span>National Ranking</span>
          </div>
          <div class="r-meta">
            <strong><?=$cl['pkg']?></strong>
            <span>Avg Package</span>
          </div>
          <div class="r-meta" style="align-items:flex-end">
            <strong><i class="ph-fill ph-star" style="color:#f59e0b"></i> <?=$cl['rating']?></strong>
            <span>Rating</span>
          </div>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>

<!-- ═══ FEATURED COLLEGES ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div><h2>Curated Institutions</h2><p>Immersive profiles of top-tier colleges</p></div>
      <a href="#" class="section-link" style="border-color:var(--border);color:var(--text)">Explore All <i class="ph ph-arrow-right"></i></a>
    </div>
    
    <div class="uni-grid">
    <?php if (!empty($featuredColleges)): $ci=0; ?>
      <?php foreach ($featuredColleges as $cl): ?>
      <a href="#" class="uni-card-premium reveal reveal-delay-<?=$ci++?>">
        <img src="<?=cImg($cl['cover_image_url'])?>" class="ucp-bg" alt="<?=htmlspecialchars($cl['name'])?>" loading="lazy">
        <div class="ucp-overlay">
          <div class="ucp-tags">
            <span class="ucp-tag"><?=ucfirst(htmlspecialchars($cl['college_type']??'College'))?></span>
            <?php if(!empty($cl['naac_grade'])):?><span class="ucp-tag">NAAC <?=htmlspecialchars($cl['naac_grade'])?></span><?php endif;?>
          </div>
          <div class="ucp-content">
            <h3 class="ucp-title"><?=htmlspecialchars($cl['name'])?></h3>
            <div class="ucp-loc"><i class="ph ph-map-pin"></i> <?=htmlspecialchars($cl['city_name']??'')?><?=($cl['city_name']&&$cl['state_name'])?', ':''?><?=htmlspecialchars($cl['state_name']??'')?></div>
            <div class="ucp-metrics">
              <div><strong><?=!empty($cl['min_fee'])?'₹'.number_format((int)$cl['min_fee']):'—'?></strong><span>Avg Fee/Yr</span></div>
              <div><strong><?=!empty($cl['avg_package'])?'₹'.number_format((float)$cl['avg_package'],1).'L':'—'?></strong><span>Avg Package</span></div>
            </div>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    <?php else: $ci=0; foreach ($fColleges as $cl): ?>
      <a href="#" class="uni-card-premium reveal reveal-delay-<?=$ci++?>">
        <img src="<?=$cl['img']?>" class="ucp-bg" alt="<?=$cl['name']?>" loading="lazy">
        <div class="ucp-overlay">
          <div class="ucp-tags"><span class="ucp-tag"><?=$cl['type']?></span></div>
          <div class="ucp-content">
            <h3 class="ucp-title"><?=$cl['name']?></h3>
            <div class="ucp-loc"><i class="ph ph-map-pin"></i> <?=$cl['loc']?></div>
            <div class="ucp-metrics">
              <div><strong><?=$cl['fee']?></strong><span>Total Fees</span></div>
              <div><strong><?=$cl['pkg']?></strong><span>Avg Package</span></div>
            </div>
          </div>
        </div>
      </a>
    <?php endforeach; endif; ?>
    </div>
  </div>
</section>

<!-- ═══ TOOLS ═══ -->
<section class="section" id="tools">
  <div class="container">
    <div class="section-hdr reveal"><h2>Student Tools & Resources</h2><p>Smart tools to help you make the right college decision</p></div>
    <div class="tools-grid">
      <a href="#" class="tool-card reveal reveal-delay-1">
        <div class="tool-icon"><i class="ph ph-arrow-counter-clockwise"></i></div>
        <h3>College Compare</h3>
        <p>Compare fees, placements, rankings side-by-side</p>
        <span class="tool-cta">Compare Now <i class="ph ph-arrow-right"></i></span>
      </a>
      <a href="#" class="tool-card reveal reveal-delay-2">
        <div class="tool-icon"><i class="ph ph-calculator"></i></div>
        <h3>College Predictor</h3>
        <p>Know your admission chances by exam score</p>
        <span class="tool-cta">Predict Now <i class="ph ph-arrow-right"></i></span>
      </a>
      <a href="#" class="tool-card reveal reveal-delay-3">
        <div class="tool-icon"><i class="ph ph-chat-circle"></i></div>
        <h3>Ask a Question</h3>
        <p>Get answers from students & admission experts</p>
        <span class="tool-cta">Ask Now <i class="ph ph-arrow-right"></i></span>
      </a>
      <a href="#" class="tool-card reveal reveal-delay-4">
        <div class="tool-icon"><i class="ph ph-bell"></i></div>
        <h3>Admission Alerts</h3>
        <p>Never miss deadlines or exam notifications</p>
        <span class="tool-cta">Subscribe <i class="ph ph-arrow-right"></i></span>
      </a>
    </div>
  </div>
</section>

<!-- ═══ EXAMS ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div><h2>Top Entrance Exams</h2><p>Dates, application deadlines & participating colleges</p></div>
      <a href="#" class="section-link">View All <i class="ph ph-arrow-right"></i></a>
    </div>
    <div class="exams-grid">
    <?php if (!empty($upcomingExams)): ?>
      <?php foreach ($upcomingExams as $ex): ?>
      <div class="exam-card reveal">
        <div class="exam-card-top">
          <div class="exam-icon"><i class="ph ph-pencil-line"></i></div>
          <div><h3><?=htmlspecialchars($ex['name'])?></h3><span class="etag"><?=htmlspecialchars($ex['level']??'National')?></span></div>
        </div>
        <div class="exam-body">
          <?php if(!empty($ex['exam_date'])):?><div><i class="ph ph-calendar-blank"></i><strong>Exam:</strong> <?=date('d M Y',strtotime($ex['exam_date']))?></div><?php endif;?>
          <?php if(!empty($ex['application_end'])):?><div><i class="ph ph-clock-countdown"></i><strong>Last Date:</strong> <?=date('d M Y',strtotime($ex['application_end']))?></div><?php endif;?>
        </div>
        <a href="#" class="exam-link">Details <i class="ph ph-arrow-right"></i></a>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($fExams as $ex): ?>
      <div class="exam-card reveal">
        <div class="exam-card-top">
          <div class="exam-icon"><i class="ph ph-pencil-line"></i></div>
          <div><h3><?=$ex['name']?></h3><span class="etag"><?=$ex['level']?></span></div>
        </div>
        <div class="exam-body">
          <div><i class="ph ph-calendar-blank"></i><strong>Exam:</strong> <?=$ex['date']?></div>
          <div><i class="ph ph-clock-countdown"></i><strong>Last Date:</strong> <?=$ex['last']?></div>
          <div><i class="ph ph-buildings"></i><strong>Colleges:</strong> <?=$ex['cols']?></div>
        </div>
        <a href="#" class="exam-link">Details <i class="ph ph-arrow-right"></i></a>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </div>
</section>

<!-- ═══ FEATURED EXAMS ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr reveal"><h2>Featured Exams 2026</h2><p>Complete info on application process, syllabus & prep tips</p></div>
    <div class="exam-feat-grid">
      <?php foreach ($examsFeatured as $fe): ?>
      <a href="#" class="exam-feat-card reveal">
        <div class="exam-feat-img"><img src="<?=$fe['img']?>" alt="<?=$fe['name']?>" loading="lazy"></div>
        <div class="exam-feat-body">
          <h3><?=$fe['name']?></h3>
          <div class="exam-feat-meta">
            <span><i class="ph ph-calendar-blank"></i> <?=$fe['date']?></span>
            <span class="etag"><?=$fe['level']?></span>
            <span class="etag"><?=$fe['type']?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ COURSES ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div><h2>Popular Courses</h2><p>Explore in-demand programs with top career prospects</p></div>
      <a href="#" class="section-link">View All <i class="ph ph-arrow-right"></i></a>
    </div>
    <div class="courses-grid">
    <?php if (!empty($popularCourses)): ?>
      <?php foreach ($popularCourses as $co): ?>
      <a href="#" class="course-sm reveal">
        <div class="course-sm-icon"><i class="ph ph-book-open"></i></div>
        <h3><?=htmlspecialchars($co['course_name'])?></h3>
        <span class="ctag"><?=htmlspecialchars($co['course_level'])?></span>
        <div class="course-sm-info">
          <?php if(!empty($co['duration_years'])):?><span><i class="ph ph-clock"></i> <?=(int)$co['duration_years']?> yrs</span><?php endif;?>
          <?php if(!empty($co['total_colleges_offering'])):?><span><i class="ph ph-buildings"></i> <?=(int)$co['total_colleges_offering']?></span><?php endif;?>
        </div>
      </a>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($fCourses as $co): ?>
      <a href="#" class="course-sm reveal">
        <div class="course-sm-icon"><i class="ph <?=$co['icon']?>"></i></div>
        <h3><?=$co['name']?></h3>
        <span class="ctag"><?=$co['level']?></span>
        <div class="course-sm-info">
          <span><i class="ph ph-clock"></i> <?=$co['dur']?></span>
          <span><i class="ph ph-buildings"></i> <?=$co['cols']?></span>
        </div>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </div>
</section>

<!-- ═══ REVIEWS ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr reveal"><h2>What Students Say</h2><p>Real reviews from real students about their college experience</p></div>
    <?php if (!empty($reviews)): ?>
    <div class="rev-grid">
      <?php foreach ($reviews as $rv): ?>
      <div class="rev-card reveal">
        <div class="rev-stars"><?php $rr=round((float)$rv['overall_rating']);for($s=1;$s<=5;$s++):?><i class="ph <?=$s<=$rr?'ph-fill ph-star':'ph-star'?>"></i><?php endfor;?><span><?=number_format((float)$rv['overall_rating'],1)?></span></div>
        <h4><?=htmlspecialchars($rv['review_title']??'Great Experience')?></h4>
        <blockquote>"<?=htmlspecialchars(substr($rv['review_body']??'',0,180))?>"</blockquote>
        <div class="rev-author">
          <div class="rev-avatar"><i class="ph ph-user-circle"></i></div>
          <div><strong><?=htmlspecialchars($rv['college_name'])?></strong><span>Batch of <?=htmlspecialchars($rv['batch_year']??'N/A')?></span></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="rev-grid">
      <?php for($i=0;$i<4;$i++): ?>
      <div class="rev-card reveal">
        <div class="rev-stars"><?php for($s=1;$s<=5;$s++):?><i class="ph-fill ph-star"></i><?php endfor;?><span>5.0</span></div>
        <h4>Amazing Campus & Learning Experience</h4>
        <blockquote>"The college exceeded my expectations. Great faculty, excellent infrastructure, and amazing placement support."</blockquote>
        <div class="rev-author">
          <div class="rev-avatar"><i class="ph ph-user-circle"></i></div>
          <div><strong>IIT Delhi</strong><span>Batch of 2025</span></div>
        </div>
      </div>
      <?php endfor;?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ═══ NEWS ═══ -->
<section class="section" id="news">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div><h2>Latest Education News</h2><p>Exam alerts, results, cutoffs, and admission updates</p></div>
      <a href="#" class="section-link">View All <i class="ph ph-arrow-right"></i></a>
    </div>
    <div class="news-grid">
      <?php foreach ($newsItems as $ni): ?>
      <article class="news-card reveal">
        <div class="news-img"><img src="<?=$ni['img']?>" alt="<?=$ni['title']?>" loading="lazy"><span class="news-cat-badge"><?=$ni['cat']?></span></div>
        <div class="news-body">
          <span class="news-date"><i class="ph ph-clock"></i> <?=$ni['date']?></span>
          <h3><?=$ni['title']?></h3>
          <a href="#" class="news-more">Read More <i class="ph ph-arrow-right"></i></a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══ STATS BANNER ═══ -->
<section class="stats-banner">
  <div class="container">
    <div class="stats-grid">
      <div class="stat-cell reveal"><i class="ph ph-buildings"></i><span class="num"><span class="counter" data-target="<?=$totalColleges?>">0</span>K+</span><span class="lbl">Colleges</span></div>
      <div class="stat-cell reveal"><i class="ph ph-book-open"></i><span class="num"><span class="counter" data-target="<?=$totalCourses?>">0</span>+</span><span class="lbl">Courses</span></div>
      <div class="stat-cell reveal"><i class="ph ph-pencil-line"></i><span class="num"><span class="counter" data-target="<?=$totalExams?>">0</span>+</span><span class="lbl">Exams</span></div>
      <div class="stat-cell reveal"><i class="ph ph-chats-circle"></i><span class="num"><span class="counter" data-target="<?=$totalReviews?>">0</span>K+</span><span class="lbl">Reviews</span></div>
      <div class="stat-cell reveal"><i class="ph ph-users"></i><span class="num"><span class="counter" data-target="50">0</span>L+</span><span class="lbl">Students</span></div>
    </div>
  </div>
</section>

<!-- ═══ NEWSLETTER ═══ -->
<section class="section">
  <div class="container">
    <div class="newsletter-card reveal">
      <div class="newsletter-icon"><i class="ph ph-paper-plane-tilt"></i></div>
      <div class="newsletter-text"><h3>Get Admission Alerts 2026</h3><p>College notifications, exam updates & tips in your inbox</p></div>
      <form class="nform" onsubmit="return handleNewsletter(event)">
        <input type="email" placeholder="Enter your email" required>
        <button type="submit" class="btn btn-primary">Subscribe</button>
      </form>
    </div>
  </div>
</section>

<!-- ═══ FOOTER ═══ -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="/" class="flogo"><i class="ph-fill ph-graduation-cap"></i> Admission<span>Season</span></a>
        <p>India's leading college discovery platform. Find detailed info on colleges, courses, exams, and get personalised admission assistance.</p>
        <div class="fsocial">
          <a href="#" aria-label="Facebook"><i class="ph ph-facebook-logo"></i></a>
          <a href="#" aria-label="X"><i class="ph ph-twitter-logo"></i></a>
          <a href="#" aria-label="Instagram"><i class="ph ph-instagram-logo"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="ph ph-linkedin-logo"></i></a>
          <a href="#" aria-label="YouTube"><i class="ph ph-youtube-logo"></i></a>
        </div>
      </div>
      <div class="footer-col">
        <h4>Colleges</h4>
        <ul><li><a href="#">Engineering</a></li><li><a href="#">MBA</a></li><li><a href="#">Medical</a></li><li><a href="#">Law</a></li><li><a href="#">Design</a></li></ul>
      </div>
      <div class="footer-col">
        <h4>Exams</h4>
        <ul><li><a href="#">JEE Main</a></li><li><a href="#">NEET</a></li><li><a href="#">CAT</a></li><li><a href="#">GATE</a></li><li><a href="#">CUET</a></li></ul>
      </div>
      <div class="footer-col">
        <h4>Abroad</h4>
        <ul><li><a href="#">Study in USA</a></li><li><a href="#">Study in UK</a></li><li><a href="#">Study in Canada</a></li><li><a href="#">Study in Australia</a></li><li><a href="#">Study in Germany</a></li></ul>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul><li><a href="#">About</a></li><li><a href="#">Contact</a></li><li><a href="#">Privacy</a></li><li><a href="#">Terms</a></li><li><a href="#">Careers</a></li></ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; <?=date('Y')?> AdmissionSeason. All rights reserved.</p>
      <div class="footer-badges">
        <span><i class="ph ph-shield-check"></i> Verified Data</span>
        <span><i class="ph ph-lock"></i> Secure</span>
        <span><i class="ph ph-star"></i> 5M+ Students</span>
      </div>
    </div>
  </div>
</footer>

<button class="scroll-top" id="scrollTop"><i class="ph ph-arrow-up"></i></button>

<script src="assets/js/main.js?v=5"></script>
</body>
</html>
