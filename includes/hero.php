<!-- ═══ HERO BANNER — Rotating Background Carousel ═══ -->
<section class="hero-banner" id="heroBanner">
  <!-- Background Slides -->
  <div class="hero-slides" id="heroSlides">
    <?php
    $heroItems = [];
    try {
      $hCols = cAll($pdo, "SELECT c.id, c.name, c.slug, c.overall_rating_avg, c.total_reviews,
        s.name AS state_name, ci.name AS city_name, cm.cover_image_url,
        'college' AS entity_type, c.hero_priority
        FROM colleges c
        LEFT JOIN states s ON c.state_id=s.id LEFT JOIN cities ci ON c.city_id=ci.id
        LEFT JOIN college_media cm ON cm.college_id=c.id AND (cm.image_type='cover' OR cm.image_type IS NULL)
        WHERE c.status='active' AND c.hero_priority IS NOT NULL
        ORDER BY c.hero_priority ASC LIMIT 5");
      foreach ($hCols as $hc) $heroItems[] = $hc;

      $hUnis = cAll($pdo, "SELECT u.id, u.name, u.slug, u.overall_rating_avg, u.total_reviews,
        s.name AS state_name, ci.name AS city_name, u.cover_image_url,
        'university' AS entity_type, u.hero_priority
        FROM universities u
        LEFT JOIN states s ON u.state_id=s.id LEFT JOIN cities ci ON u.city_id=ci.id
        WHERE u.status='active' AND u.hero_priority IS NOT NULL
        ORDER BY u.hero_priority ASC LIMIT 5");
      foreach ($hUnis as $hu) $heroItems[] = $hu;

      $hSchs = cAll($pdo, "SELECT sc.id, sc.name, sc.slug, sc.overall_rating_avg, sc.total_reviews,
        s.name AS state_name, ci.name AS city_name, sm.cover_image_url,
        'school' AS entity_type, sc.hero_priority
        FROM schools sc
        LEFT JOIN states s ON sc.state_id=s.id LEFT JOIN cities ci ON sc.city_id=ci.id
        LEFT JOIN school_media sm ON sm.school_id=sc.id AND (sm.image_type='cover' OR sm.image_type IS NULL)
        WHERE sc.status='active' AND sc.hero_priority IS NOT NULL
        ORDER BY sc.hero_priority ASC LIMIT 5");
      foreach ($hSchs as $hs) $heroItems[] = $hs;

      usort($heroItems, function($a, $b) {
        return (int)($a['hero_priority'] ?? 99) <=> (int)($b['hero_priority'] ?? 99);
      });
    } catch (Throwable $e) {}

    if (empty($heroItems)) {
      $heroItems = array_map(fn($c) => $c + ['entity_type' => 'college'], $featuredColleges ?? []);
    }
    $heroItems = array_slice($heroItems, 0, 5);
    ?>

    <?php if (!empty($heroItems)): ?>
    <?php foreach ($heroItems as $hi => $hero):
      $hImg = $hero['cover_image_url'] ?? '';
      $hName = htmlspecialchars($hero['name'] ?? '');
      $hCity = $hero['city_name'] ?? '';
      $hState = $hero['state_name'] ?? '';
      $hLoc = trim($hCity . ($hCity && $hState ? ', ' : '') . $hState);
      $hRating = (float)($hero['overall_rating_avg'] ?? 0);
      $hType = $hero['entity_type'] ?? 'college';
      $hSlug = $hero['slug'] ?? '';
      $hUrl = ($hType === 'college') ? collegeUrl($hSlug) : (($hType === 'university') ? universityUrl($hSlug) : schoolUrl($hSlug));
      $hTypeLabel = ($hType === 'college') ? 'College' : (($hType === 'university') ? 'University' : 'School');
    ?>
    <div class="hero-slide <?= $hi === 0 ? 'active' : '' ?>" data-index="<?= $hi ?>">
      <div class="hero-slide-bg" style="background-image:url('<?= htmlspecialchars($hImg ?: 'https://images.unsplash.com/photo-1562774053-701939374585?w=1920&q=80') ?>')"></div>
      <div class="hero-slide-overlay"></div>
      <div class="hero-slide-info">
        <span class="hero-slide-type"><?= $hTypeLabel ?></span>
        <a href="<?= $hUrl ?>" class="hero-slide-link">
          <strong><?= $hName ?></strong>
          <?php if ($hLoc): ?><span><i class="ph ph-map-pin"></i> <?= htmlspecialchars($hLoc) ?></span><?php endif; ?>
          <span class="hero-slide-meta">
            <?php if ($hRating > 0): ?><span class="hero-slide-rating"><i class="ph-fill ph-star"></i> <?= number_format($hRating, 1) ?></span><?php endif; ?>
            <span class="hero-slide-view">(view details)</span>
          </span>
        </a>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (count($heroItems) > 1): ?>
    <div class="hero-dots" id="heroDots">
      <?php foreach ($heroItems as $di => $_): ?>
      <button class="hero-dot <?= $di === 0 ? 'active' : '' ?>" data-idx="<?= $di ?>"></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Content Overlay — everything stacked center -->
  <div class="hero-overlay">
    <div class="hero-overlay-inner">
      <h1 class="hero-title">Find <span class="hero-gradient">Best Colleges, Schools & Universities</span> in India</h1>
      <p class="hero-sub">Explore <?= number_format($totalColleges ?? 0) ?>+ colleges, <?= number_format($totalStudents ?? 0) ?>+ students & <?= number_format($totalExams ?? 0) ?>+ entrance exams — all in one place.</p>

      <!-- Search Card -->
      <div class="hero-search-box" id="nhSearchWrap">
        <div class="hero-search-tabs">
          <button class="active" data-type="all"><i class="ph ph-magnifying-glass"></i><span> All</span></button>
          <button data-type="colleges"><i class="ph ph-buildings"></i><span> Colleges</span></button>
          <button data-type="schools"><i class="ph ph-graduation-cap"></i><span> Schools</span></button>
          <button data-type="universities"><i class="ph ph-globe-hemisphere-west"></i><span> Universities</span></button>
          <button data-type="exams"><i class="ph ph-pencil-line"></i><span> Exams</span></button>
          <button data-type="courses"><i class="ph ph-book-open"></i><span> Courses</span></button>
        </div>
        <div class="hero-search-row">
          <i class="ph ph-magnifying-glass"></i>
          <input type="text" placeholder="Search colleges, schools, exams, courses..." id="nhSearchInput" autocomplete="off">
          <button class="hero-search-btn" id="nhSearchBtn">Search <i class="ph ph-arrow-right"></i></button>
        </div>
        <div class="nh-search-dropdown" id="nhSearchDropdown"></div>
      </div>

      <!-- Quick Links -->
      <div class="hero-quick-links">
        <a href="<?= collegesUrl(['q'=>'IIT']) ?>" class="hero-pill">IIT Colleges</a>
        <a href="<?= schoolsUrl() ?>" class="hero-pill">Top Schools</a>
        <a href="<?= universitiesUrl() ?>" class="hero-pill">Universities</a>
        <a href="<?= collegesUrl(['q'=>'Medical']) ?>" class="hero-pill">Medical Colleges</a>
        <a href="<?= examsUrl() ?>" class="hero-pill">Entrance Exams</a>
        <a href="<?= coursesUrl(['q'=>'MBA']) ?>" class="hero-pill">MBA Courses</a>
      </div>
    </div>
  </div>

  <!-- Trust Strip -->
  <div class="nh-trust">
    <div class="container nh-trust-inner">
      <div class="nh-trust-item"><i class="ph ph-shield-check"></i> Verified Data</div>
      <div class="nh-trust-item"><i class="ph ph-chart-line-up"></i> NIRF Rankings</div>
      <div class="nh-trust-item"><i class="ph ph-headset"></i> Expert Guidance</div>
      <div class="nh-trust-item"><i class="ph ph-check-circle"></i> 100% Free</div>
    </div>
  </div>
</section>

<script>
(function(){
  /* ── Hero Carousel ── */
  const slides = document.querySelectorAll('.hero-slide');
  const dots = document.querySelectorAll('.hero-dot');
  let current = 0, interval = null;

  function goTo(idx) {
    slides.forEach(s => s.classList.remove('active'));
    dots.forEach(d => d.classList.remove('active'));
    if (slides[idx]) slides[idx].classList.add('active');
    if (dots[idx]) dots[idx].classList.add('active');
    current = idx;
  }
  function next() { goTo((current + 1) % Math.max(slides.length, 1)); }
  if (slides.length > 1) {
    interval = setInterval(next, 5000);
    dots.forEach(d => d.addEventListener('click', () => {
      clearInterval(interval);
      goTo(parseInt(d.dataset.idx));
      interval = setInterval(next, 5000);
    }));
  }

  /* ── Search ── */
  const input = document.getElementById('nhSearchInput');
  const dd = document.getElementById('nhSearchDropdown');
  const btn = document.getElementById('nhSearchBtn');
  const wrap = document.getElementById('nhSearchWrap');
  if (!input || !dd) return;
  let timer = null, activeIdx = -1, abortCtrl = null, filterType = 'all';

  function highlight(text, q) {
    if (!q) return text;
    return text.replace(new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi'), '<mark>$1</mark>');
  }
  function iconFor(t) { return {college:'ph-buildings',school:'ph-graduation-cap',exam:'ph-clipboard-text',course:'ph-books',career:'ph-briefcase',article:'ph-newspaper',question:'ph-chat-circle-question',university:'ph-globe-hemisphere-west'}[t]||'ph-arrow-right'; }
  function typeLabel(t) { return {college:'Colleges',school:'Schools',exam:'Exams',course:'Courses',career:'Careers',article:'News & Articles',question:'Questions',university:'Universities'}[t]||t; }
  function typeColor(t) { return {college:'#19376D',school:'#0B2447',exam:'#7C3AED',course:'#059669',career:'#EA580C',article:'#D97706',question:'#2563EB',university:'#0891B2'}[t]||'#64748B'; }

  function render(results, q) {
    if (filterType !== 'all') results = results.filter(r => r.type === filterType || (filterType === 'colleges' && r.type === 'college') || (filterType === 'schools' && r.type === 'school') || (filterType === 'universities' && r.type === 'university') || (filterType === 'exams' && r.type === 'exam') || (filterType === 'courses' && r.type === 'course'));
    if (!results.length) { dd.innerHTML = '<div class="nh-search-empty"><i class="ph ph-magnifying-glass"></i>No results found for "<strong>'+q.replace(/</g,'&lt;')+'</strong>"</div>'; dd.style.display='block'; return; }
    const groups = {}; results.forEach(r => { if (!groups[r.type]) groups[r.type] = []; groups[r.type].push(r); });
    let html = '';
    ['college','school','university','exam','course','career','article','question'].forEach(type => {
      if (!groups[type]) return;
      html += '<div class="nh-search-group"><div class="nh-search-group-title"><i class="ph '+iconFor(type)+'" style="color:'+typeColor(type)+'"></i> '+typeLabel(type)+'</div>';
      groups[type].forEach(r => { html += '<a href="'+r.url+'" class="nh-search-item"><i class="ph '+iconFor(type)+'" style="color:'+typeColor(type)+'"></i><div style="flex:1;min-width:0"><div class="nhsi-title">'+highlight(r.title,q)+'</div>'+(r.subtitle?'<div class="nhsi-sub">'+r.subtitle+'</div>':'')+'</div>'+(r.badge?'<span class="nhsi-badge" style="background:'+typeColor(type)+'11;color:'+typeColor(type)+'">'+r.badge+'</span>':'')+'</a>'; });
      html += '</div>';
    });
    html += '<div class="nh-search-footer"><a href="<?= BASE_URL ?>/search.php?q='+encodeURIComponent(q)+'" class="nh-view-all">View all results for "'+q.replace(/</g,'&lt;')+'" <i class="ph ph-arrow-right"></i></a></div>';
    dd.innerHTML = html; dd.style.display = 'block'; activeIdx = -1;
  }
  function doSearch() {
    const q = input.value.trim(); clearTimeout(timer); if (abortCtrl) abortCtrl.abort();
    if (q.length < 1) { dd.style.display='none'; dd.innerHTML=''; return; }
    dd.innerHTML = '<div class="nh-search-loading"><div class="nh-search-spinner"></div>Searching...</div>'; dd.style.display='block';
    timer = setTimeout(() => { abortCtrl = new AbortController(); fetch(BASE_URL+'/api/global_search.php?q='+encodeURIComponent(q),{signal:abortCtrl.signal}).then(r=>r.json()).then(data=>{if(data.ok)render(data.results,q);}).catch(e=>{if(e.name!=='AbortError')dd.style.display='none';}); }, 200);
  }
  input.addEventListener('input', doSearch);
  input.addEventListener('keydown', function(e) {
    const items = dd.querySelectorAll('.nh-search-item');
    if (e.key==='ArrowDown'){e.preventDefault();if(!items.length)return;activeIdx=Math.min(activeIdx+1,items.length-1);items.forEach((it,i)=>it.classList.toggle('active',i===activeIdx));items[activeIdx]?.scrollIntoView({block:'nearest'});}
    else if(e.key==='ArrowUp'){e.preventDefault();activeIdx=Math.max(activeIdx-1,0);items.forEach((it,i)=>it.classList.toggle('active',i===activeIdx));items[activeIdx]?.scrollIntoView({block:'nearest'});}
    else if(e.key==='Enter'){e.preventDefault();if(activeIdx>=0&&items[activeIdx])items[activeIdx].click();else if(items.length)items[0].click();else{const v=input.value.trim();if(v)window.location.href=BASE_URL+'/search.php?q='+encodeURIComponent(v);}}
    else if(e.key==='Escape'){dd.style.display='none';input.blur();}
  });
  input.addEventListener('focus', function(){if(this.value.trim().length>=1&&dd.innerHTML.trim())dd.style.display='block';});
  document.addEventListener('click', function(e){if(!e.target.closest('#nhSearchWrap'))dd.style.display='none';});
  if(btn)btn.addEventListener('click',function(){const q=input.value.trim();if(q)window.location.href=BASE_URL+'/search.php?q='+encodeURIComponent(q);});

  document.querySelectorAll('.hero-search-tabs button').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.hero-search-tabs button').forEach(b => b.classList.remove('active'));
      tab.classList.add('active');
      filterType = tab.dataset.type || 'all';
      const ph = {all:'Search colleges, schools, exams, courses...',colleges:'Search colleges by name, location...',schools:'Search schools by name, board, city...',universities:'Search universities by name, type...',exams:'Search entrance exams...',courses:'Search courses & programs...'};
      input.placeholder = ph[filterType] || ph.all;
      if (input.value.trim().length >= 1) doSearch();
    });
  });
})();
</script>
