<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '0');
require_once __DIR__ . '/panel_cms_2847/db.php';
require_once __DIR__ . '/includes/college_helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$navBase = defined('BASE_URL') ? BASE_URL : '/ADMISSION';

$exams = $pdo->query("SELECT e.id, e.exam_name FROM exams e ORDER BY e.exam_name")->fetchAll(PDO::FETCH_ASSOC);
$states = $pdo->query("SELECT id, name FROM states ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$courseLevels = $pdo->query("SELECT DISTINCT course_level FROM college_courses WHERE course_level IS NOT NULL ORDER BY course_level")->fetchAll(PDO::FETCH_COLUMN);

$siteBase = defined('BASE_URL') ? BASE_URL : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$canonicalUrl = $siteBase . '/predictor';
$pageTitle = 'Free College Predictor ' . date('Y') . ' - Find Best Colleges by Score | AdmissionSeason';
$metaDesc = 'Use our free AI-powered college predictor to find the best colleges for your exam score. Get personalized college recommendations based on JEE, NEET, CUET, CAT scores and more.';
$metaKeywords = 'college predictor ' . date('Y') . ', free college predictor, college admission predictor, JEE college predictor, NEET college predictor, CUET college predictor, college finder by score, best college for my rank, admission predictor india';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
<meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<link rel="canonical" href="<?= $canonicalUrl ?>">
<meta name="author" content="AdmissionSeason">

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:url" content="<?= $canonicalUrl ?>">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>">
<meta property="og:image" content="<?= $siteBase ?>/assets/img/logo.png">
<meta property="og:site_name" content="AdmissionSeason">
<meta property="og:locale" content="en_IN">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?= $canonicalUrl ?>">
<meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($metaDesc) ?>">
<meta name="twitter:image" content="<?= $siteBase ?>/assets/img/logo.png">

<!-- Structured Data: SoftwareApplication -->
<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'SoftwareApplication',
  'name' => 'College Predictor',
  'description' => $metaDesc,
  'url' => $canonicalUrl,
  'applicationCategory' => 'EducationalApplication',
  'operatingSystem' => 'Web',
  'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'INR'],
  'provider' => ['@type' => 'Organization', 'name' => 'AdmissionSeason', 'url' => "$siteBase"],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>

<!-- Structured Data: BreadcrumbList -->
<script type="application/ld+json">
<?= json_encode([
  '@context' => 'https://schema.org',
  '@type' => 'BreadcrumbList',
  'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => "$siteBase/"],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'College Predictor', 'item' => "$siteBase/predictor"],
  ]
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
</script>
<script src="https://unpkg.com/@phosphor-icons/web" defer></script>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?=$navBase?>/assets/css/style.css?v<?=time()?>">
<style>
.pr{padding:40px 0 60px;min-height:100vh}
.pr-hero{text-align:center;margin-bottom:28px}
.pr-hero .nh-badge{margin:0 auto 10px}
.pr-hero h1{font-size:1.6rem;font-weight:800;color:var(--text);margin-bottom:4px;letter-spacing:-.02em}
.pr-hero p{color:var(--text2);font-size:.85rem}

.pr-form{background:var(--card);border:1px solid var(--border);border-radius:20px;padding:32px;max-width:800px;margin:0 auto 36px}
.pr-form h2{font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:20px;display:flex;align-items:center;gap:8px}
.pr-form h2 i{color:var(--secondary);font-size:1.2rem}
.pr-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.pr-field label{display:block;font-size:.78rem;font-weight:600;color:var(--text2);margin-bottom:6px;text-transform:uppercase;letter-spacing:.03em}
.pr-field select,.pr-field input{width:100%;padding:11px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:.88rem;font-family:var(--font2);background:var(--background);color:var(--text);transition:border-color .2s;box-sizing:border-box}
.pr-field select:focus,.pr-field input:focus{outline:none;border-color:var(--secondary)}
.pr-field select{cursor:pointer;appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;padding-right:32px}
.pr-submit{margin-top:20px;text-align:center}
.pr-submit button{padding:14px 48px;background:linear-gradient(135deg,var(--secondary),#4f46e5);color:#fff;border:none;border-radius:12px;font-size:1rem;font-weight:700;cursor:pointer;transition:all .3s;font-family:var(--font);display:inline-flex;align-items:center;gap:8px}
.pr-submit button:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(25,55,109,.3)}
.pr-submit button:disabled{opacity:.4;cursor:not-allowed;transform:none}
.pr-submit button i{font-size:1.1rem}

.pr-results{max-width:900px;margin:0 auto}
.pr-results-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px}
.pr-results-header h2{font-size:1.1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px}
.pr-results-header h2 i{color:var(--secondary)}
.pr-results-count{font-size:.82rem;color:var(--text3);font-weight:500}
.pr-empty{text-align:center;padding:60px 20px;background:var(--card);border:1px solid var(--border);border-radius:20px}
.pr-empty i{font-size:3rem;color:var(--border);margin-bottom:14px;display:block}
.pr-empty h3{font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:6px}
.pr-empty p{color:var(--text2);font-size:.88rem}
.pr-loading{text-align:center;padding:50px}
.pr-loading i{font-size:2rem;color:var(--secondary);animation:spin 1s linear infinite;display:block;margin-bottom:12px}

.pr-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px;margin-bottom:14px;display:flex;gap:20px;align-items:flex-start;transition:all .25s;position:relative;overflow:hidden}
.pr-card:hover{border-color:var(--secondary);box-shadow:0 4px 20px rgba(25,55,109,.08)}
.pr-card.top-pick{border-color:var(--secondary);border-width:2px}
.pr-card.top-pick::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--secondary),#4f46e5)}
.pr-rank{flex-shrink:0;width:44px;height:44px;border-radius:12px;background:linear-gradient(135deg,var(--secondary),#4f46e5);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.9rem}
.pr-card.top-pick .pr-rank{background:linear-gradient(135deg,#059669,#10b981)}
.pr-info{flex:1;min-width:0}
.pr-info h3{font-size:1rem;font-weight:700;color:var(--text);margin-bottom:4px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.pr-info h3 a{color:var(--text);text-decoration:none}
.pr-info h3 a:hover{color:var(--secondary)}
.pr-meta{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:8px}
.pr-tag{font-size:.65rem;font-weight:600;padding:3px 8px;border-radius:4px;background:var(--primary-light);color:var(--text2);text-transform:uppercase}
.pr-tag.gold{background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e}
.pr-tag.green{background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#065f46}
.pr-loc{font-size:.78rem;color:var(--text3);display:flex;align-items:center;gap:4px;margin-bottom:10px}
.pr-stats{display:flex;gap:16px;flex-wrap:wrap}
.pr-stat{display:flex;flex-direction:column}
.pr-stat-val{font-size:.92rem;font-weight:700;color:var(--text)}
.pr-stat-label{font-size:.68rem;color:var(--text3)}
.pr-score{flex-shrink:0;text-align:center;padding:8px 14px;background:var(--primary-light);border-radius:12px;min-width:80px}
.pr-score-val{font-size:1.4rem;font-weight:800;color:var(--secondary);line-height:1}
.pr-score-label{font-size:.62rem;color:var(--text3);margin-top:2px;text-transform:uppercase;font-weight:600}
.pr-match{font-size:.7rem;color:var(--text2);font-weight:600;margin-top:6px}
.pr-match .bar{height:4px;background:var(--primary-light);border-radius:2px;margin-top:4px;overflow:hidden}
.pr-match .bar-fill{height:100%;border-radius:2px;transition:width .6s ease}
.pr-match .bar-fill.high{background:linear-gradient(90deg,#10b981,#059669)}
.pr-match .bar-fill.med{background:linear-gradient(90deg,#f59e0b,#d97706)}
.pr-match .bar-fill.low{background:linear-gradient(90deg,#ef4444,#dc2626)}
.pr-why{margin-top:8px;font-size:.72rem;color:var(--text3);line-height:1.4}
.pr-why i{color:var(--secondary);margin-right:4px}

.pr-loading-bar{display:flex;align-items:center;gap:12px;justify-content:center;margin-bottom:24px}
.pr-loading-bar span{font-size:.82rem;color:var(--text2);font-weight:500}

@keyframes spin{to{transform:rotate(360deg)}}
@media(max-width:768px){
  .pr{padding:30px 0 40px}
  .pr-form{padding:20px;border-radius:16px}
  .pr-grid{grid-template-columns:1fr}
  .pr-hero h1{font-size:1.3rem}
  .pr-card{flex-direction:column;padding:18px}
  .pr-score{align-self:flex-start}
  .pr-submit button{width:100%;justify-content:center}
}
@media(max-width:480px){
  .pr{padding:20px 0 30px}
  .pr-hero{margin-bottom:16px}
  .pr-hero h1{font-size:1.1rem}
  .pr-form{padding:16px}
  .pr-field label{font-size:.72rem}
  .pr-field select,.pr-field input{padding:9px 12px;font-size:.82rem}
  .pr-card{padding:14px;gap:14px}
  .pr-info h3{font-size:.9rem}
  .pr-stats{gap:10px}
  .pr-stat-val{font-size:.82rem}
}
</style>
</head>
<body>
<?php include 'includes/navbar.php';?>

<div class="pr">
  <div class="container">
    <div class="pr-hero">
      <div class="nh-badge"><i class="ph-fill ph-magic-wand"></i> Predictor</div>
      <h1>Find Your Best College Match</h1>
      <p>Enter your exam details and preferences — get personalized college recommendations instantly</p>
    </div>

    <form class="pr-form" id="predictorForm" onsubmit="return runPrediction(event)">
      <h2><i class="ph ph-clipboard-text"></i> Your Details</h2>
      <div class="pr-grid">
        <div class="pr-field">
          <label>Entrance Exam</label>
          <select id="prExam" required>
            <option value="">Select Exam</option>
            <?php foreach($exams as $e):?>
            <option value="<?=$e['id']?>"><?=htmlspecialchars($e['exam_name'])?></option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="pr-field">
          <label>Your Rank / Score</label>
          <input type="number" id="prRank" placeholder="e.g. 15000" min="1" required>
        </div>
        <div class="pr-field">
          <label>Course Level</label>
          <select id="prLevel">
            <option value="">All Levels</option>
            <?php foreach($courseLevels as $cl):?>
            <option value="<?=htmlspecialchars($cl)?>"><?=htmlspecialchars($cl)?></option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="pr-field">
          <label>Category</label>
          <select id="prCategory">
            <option value="General">General</option>
            <option value="OBC">OBC</option>
            <option value="SC">SC</option>
            <option value="ST">ST</option>
            <option value="EWS">EWS</option>
          </select>
        </div>
        <div class="pr-field">
          <label>Max Annual Fee (₹)</label>
          <input type="number" id="prFee" placeholder="e.g. 200000" min="0">
        </div>
        <div class="pr-field">
          <label>Preferred State</label>
          <select id="prState">
            <option value="">Any State</option>
            <?php foreach($states as $s):?>
            <option value="<?=$s['id']?>"><?=htmlspecialchars($s['name'])?></option>
            <?php endforeach;?>
          </select>
        </div>
        <div class="pr-field">
          <label>College Type</label>
          <select id="prType">
            <option value="">Any Type</option>
            <option value="govt">Government</option>
            <option value="private">Private</option>
            <option value="deemed">Deemed University</option>
            <option value="autonomous">Autonomous</option>
          </select>
        </div>
        <div class="pr-field">
          <label>Min NAAC Grade</label>
          <select id="prNaac">
            <option value="">Any Grade</option>
            <option value="A++">A++</option>
            <option value="A+">A+</option>
            <option value="A">A</option>
            <option value="B++">B++</option>
            <option value="B+">B+</option>
          </select>
        </div>
      </div>
      <div class="pr-submit">
        <button type="submit" id="prBtn"><i class="ph ph-magic-wand"></i> Find My Colleges</button>
      </div>
    </form>

    <div class="pr-results" id="prResults"></div>
  </div>
</div>

<script>
var B='<?=$navBase?>';
var naacOrder={'A++':7,'A+':6,'A':5,'B++':4,'B+':3,'B':2,'C':1};

function runPrediction(e){
  e.preventDefault();
  var btn=document.getElementById('prBtn');
  var res=document.getElementById('prResults');
  btn.disabled=true;btn.innerHTML='<i class="ph ph-spinner"></i> Finding…';
  res.innerHTML='<div class="pr-loading"><i class="ph ph-spinner"></i><span>Analyzing colleges…</span></div>';

  var params={
    exam_id:document.getElementById('prExam').value,
    rank:document.getElementById('prRank').value,
    level:document.getElementById('prLevel').value,
    category:document.getElementById('prCategory').value,
    max_fee:document.getElementById('prFee').value,
    state_id:document.getElementById('prState').value,
    college_type:document.getElementById('prType').value,
    naac_min:document.getElementById('prNaac').value
  };

  var qs=Object.keys(params).filter(function(k){return params[k];}).map(function(k){return k+'='+encodeURIComponent(params[k]);}).join('&');

  fetch(B+'/api/predictor.php?'+qs)
  .then(function(r){if(!r.ok)throw new Error('HTTP '+r.status);return r.json();})
  .then(function(d){
    btn.disabled=false;btn.innerHTML='<i class="ph ph-magic-wand"></i> Find My Colleges';
    if(d.error){res.innerHTML='<div class="pr-empty"><i class="ph ph-warning-circle"></i><h3>'+d.error+'</h3></div>';return;}
    renderResults(d);
  }).catch(function(err){
    btn.disabled=false;btn.innerHTML='<i class="ph ph-magic-wand"></i> Find My Colleges';
    res.innerHTML='<div class="pr-empty"><i class="ph ph-warning-circle"></i><h3>Something went wrong</h3><p>'+err.message+'</p></div>';
  });
}

function renderResults(d){
  var res=document.getElementById('prResults');
  if(!d.colleges||!d.colleges.length){
    res.innerHTML='<div class="pr-empty"><i class="ph ph-magnifying-glass"></i><h3>No colleges found</h3><p>Try adjusting your rank, fee range, or preferences</p></div>';
    return;
  }
  var h='<div class="pr-results-header"><h2><i class="ph ph-trophy"></i> Recommended Colleges</h2><span class="pr-results-count">'+d.colleges.length+' matches found</span></div>';
  d.colleges.forEach(function(c,i){
    var pct=c.score;
    var cls=pct>=75?'high':pct>=50?'med':'low';
    var topPick=i===0;
    var why=[];
    if(c.match_cutoff) why.push('Within cutoff range');
    if(c.match_fee) why.push('Within budget');
    if(c.match_location) why.push('Preferred location');
    if(c.match_naac) why.push(c.naac_grade+' NAAC accredited');
    if(c.match_nirf) why.push('NIRF ranked #'+c.ranking_nirf);

    h+='<div class="pr-card'+(topPick?' top-pick':'')+'">'+
      '<div class="pr-rank">#'+(i+1)+'</div>'+
      '<div class="pr-info">'+
        '<h3><a href="'+B+'/college/'+c.slug+'">'+esc(c.name)+'</a></h3>'+
        '<div class="pr-meta">'+
          (c.college_type?'<span class="pr-tag">'+esc(c.college_type)+'</span>':'')+
          (c.naac_grade?'<span class="pr-tag gold">NAAC '+esc(c.naac_grade)+'</span>':'')+
          (c.ranking_nirf?'<span class="pr-tag green">NIRF #'+c.ranking_nirf+'</span>':'')+
          (topPick?'<span class="pr-tag" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#065f46">Top Pick</span>':'')+
        '</div>'+
        '<div class="pr-loc"><i class="ph ph-map-pin"></i> '+esc(c.city_name||'')+(c.state_name?', '+esc(c.state_name):'')+'</div>'+
        '<div class="pr-stats">'+
          (c.avg_package?'<div class="pr-stat"><span class="pr-stat-val">'+c.avg_package+' LPA</span><span class="pr-stat-label">Avg Package</span></div>':'')+
          (c.placement_pct?'<div class="pr-stat"><span class="pr-stat-val">'+Math.round(c.placement_pct)+'%</span><span class="pr-stat-label">Placed</span></div>':'')+
          (c.min_fee?'<div class="pr-stat"><span class="pr-stat-val">₹'+fmtNum(c.min_fee)+'</span><span class="pr-stat-label">Annual Fee</span></div>':'')+
          (c.total_students?'<div class="pr-stat"><span class="pr-stat-val">'+fmtNum(c.total_students)+'</span><span class="pr-stat-label">Students</span></div>':'')+
        '</div>'+
        (why.length?'<div class="pr-why"><i class="ph ph-check-circle"></i> '+why.join(' · ')+'</div>':'')+
      '</div>'+
      '<div class="pr-score">'+
        '<div class="pr-score-val">'+pct+'%</div>'+
        '<div class="pr-score-label">Match</div>'+
        '<div class="pr-match"><div class="bar"><div class="bar-fill '+cls+'" style="width:'+pct+'%"></div></div></div>'+
      '</div>'+
    '</div>';
  });
  res.innerHTML=h;
}

function esc(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
function fmtNum(n){return n?Number(n).toLocaleString('en-IN'):'—';}
</script>
<?php include 'includes/footer.php';?>
</body>
</html>
