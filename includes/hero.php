<!-- ═══ UNIQUE HERO — Mesh Gradient + Floating Stats ═══ -->
<section class="nh">
  <!-- Animated mesh blobs -->
  <div class="nh-mesh">
    <div class="nh-blob nh-blob-1"></div>
    <div class="nh-blob nh-blob-2"></div>
    <div class="nh-blob nh-blob-3"></div>
  </div>

  <div class="container nh-layout">
    <!-- LEFT: Text + Search -->
    <div class="nh-left">
      <?php if (!empty($totalStudents)): ?>
      <div class="nh-badge"><i class="ph-fill ph-shield-check"></i> Trusted by <?=number_format($totalStudents)?>+ Students</div>
      <?php endif; ?>
      <h1 class="nh-title">Find Your <span class="nh-gradient">Dream College</span> in India</h1>
      <p class="nh-sub">Explore <?=number_format($totalColleges)?>+ colleges, <?=number_format($totalCourses)?>+ courses & <?=number_format($totalExams)?>+ entrance exams — all in one place.</p>

      <!-- Search Card -->
      <div class="nh-search" id="nhSearchWrap">
        <div class="nh-search-tabs">
          <button class="active" data-type="all"><i class="ph ph-magnifying-glass"></i> All</button>
          <button data-type="colleges"><i class="ph ph-buildings"></i> Colleges</button>
          <button data-type="exams"><i class="ph ph-pencil-line"></i> Exams</button>
          <button data-type="courses"><i class="ph ph-book-open"></i> Courses</button>
        </div>
        <div class="nh-search-row">
          <i class="ph ph-magnifying-glass"></i>
          <input type="text" placeholder="Search colleges, exams, courses, careers..." id="nhSearchInput" autocomplete="off">
          <button class="nh-search-btn" id="nhSearchBtn">Search <i class="ph ph-arrow-right"></i></button>
        </div>
        <div class="nh-search-dropdown" id="nhSearchDropdown"></div>
      </div>

      <!-- Quick Links -->
      <div class="nh-quick">
        <span class="nh-quick-label">Popular:</span>
        <a href="<?=collegesUrl(['q'=>'IIT'])?>" class="nh-quick-link">IIT Colleges</a>
        <a href="<?=collegesUrl(['q'=>'Medical'])?>" class="nh-quick-link">Medical Colleges</a>
        <a href="<?=examsUrl()?>" class="nh-quick-link">Entrance Exams</a>
        <a href="<?=coursesUrl(['q'=>'MBA'])?>" class="nh-quick-link">MBA Courses</a>
      </div>
    </div>

    <!-- RIGHT: Floating Stat Cards -->
    <div class="nh-right">
      <div class="nh-card nh-card-1">
        <div class="nh-card-icon"><i class="ph-fill ph-buildings"></i></div>
        <div class="nh-card-text">
          <strong><?=number_format($totalColleges)?>+</strong>
          <span>Colleges</span>
        </div>
      </div>
      <div class="nh-card nh-card-2">
        <div class="nh-card-icon"><i class="ph-fill ph-book-open"></i></div>
        <div class="nh-card-text">
          <strong><?=number_format($totalCourses)?>+</strong>
          <span>Courses</span>
        </div>
      </div>
      <div class="nh-card nh-card-3">
        <div class="nh-card-icon"><i class="ph-fill ph-star"></i></div>
        <div class="nh-card-text">
          <strong><?=number_format($totalReviews)?>+</strong>
          <span>Reviews</span>
        </div>
      </div>
      <div class="nh-card nh-card-4">
        <div class="nh-card-icon nh-card-icon-accent"><i class="ph-fill ph-users"></i></div>
        <div class="nh-card-text">
          <strong><?=number_format($totalStudents ?? 0)?>+</strong>
          <span>Students</span>
        </div>
      </div>
      <!-- Central visual -->
      <div class="nh-center-visual">
        <div class="nh-ring"></div>
        <div class="nh-ring nh-ring-2"></div>
        <i class="ph-fill ph-graduation-cap"></i>
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
  const input = document.getElementById('nhSearchInput');
  const dd = document.getElementById('nhSearchDropdown');
  const btn = document.getElementById('nhSearchBtn');
  const wrap = document.getElementById('nhSearchWrap');
  if (!input || !dd) return;
  let timer = null, activeIdx = -1, abortCtrl = null, filterType = 'all';

  function highlight(text, q) {
    if (!q) return text;
    const esc = q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    return text.replace(new RegExp('(' + esc + ')', 'gi'), '<mark>$1</mark>');
  }

  function iconFor(type) {
    const m = {college:'ph-buildings',exam:'ph-clipboard-text',course:'ph-books',career:'ph-briefcase',article:'ph-newspaper',question:'ph-chat-circle-question',university:'ph-globe-hemisphere-west'};
    return m[type] || 'ph-arrow-right';
  }

  function typeLabel(type) {
    const m = {college:'Colleges',exam:'Exams',course:'Courses',career:'Careers',article:'News & Articles',question:'Questions',university:'Foreign Universities'};
    return m[type] || type;
  }

  function typeColor(type) {
    const m = {college:'#19376D',exam:'#7C3AED',course:'#059669',career:'#EA580C',article:'#D97706',question:'#2563EB',university:'#0891B2'};
    return m[type] || '#64748B';
  }

  function render(results, q) {
    if (filterType !== 'all') {
      results = results.filter(r => r.type === filterType || (filterType === 'colleges' && r.type === 'college') || (filterType === 'exams' && r.type === 'exam') || (filterType === 'courses' && r.type === 'course'));
    }
    if (!results.length) {
      dd.innerHTML = '<div class="nh-search-empty"><i class="ph ph-magnifying-glass"></i>No results found for "<strong>' + q.replace(/</g,'&lt;') + '</strong>"</div>';
      dd.style.display = 'block';
      return;
    }
    const groups = {};
    results.forEach(r => {
      if (!groups[r.type]) groups[r.type] = [];
      groups[r.type].push(r);
    });
    let html = '';
    const order = ['college','exam','course','career','university','article','question'];
    order.forEach(type => {
      if (!groups[type]) return;
      html += '<div class="nh-search-group">';
      html += '<div class="nh-search-group-title"><i class="ph ' + iconFor(type) + '" style="color:' + typeColor(type) + '"></i> ' + typeLabel(type) + '</div>';
      groups[type].forEach(r => {
        html += '<a href="' + r.url + '" class="nh-search-item">';
        html += '<i class="ph ' + iconFor(type) + '" style="color:' + typeColor(type) + '"></i>';
        html += '<div style="flex:1;min-width:0">';
        html += '<div class="nhsi-title">' + highlight(r.title, q) + '</div>';
        if (r.subtitle) html += '<div class="nhsi-sub">' + r.subtitle + '</div>';
        html += '</div>';
        if (r.badge) html += '<span class="nhsi-badge" style="background:' + typeColor(type) + '11;color:' + typeColor(type) + '">' + r.badge + '</span>';
        html += '</a>';
      });
      html += '</div>';
    });
    html += '<div class="nh-search-footer"><a href="/ADMISSION/search.php?q=' + encodeURIComponent(q) + '" class="nh-view-all">View all results for "' + q.replace(/</g,'&lt;') + '" <i class="ph ph-arrow-right"></i></a></div>';
    dd.innerHTML = html;
    dd.style.display = 'block';
    activeIdx = -1;
  }

  function showLoading(q) {
    dd.innerHTML = '<div class="nh-search-loading"><div class="nh-search-spinner"></div>Searching...</div>';
    dd.style.display = 'block';
  }

  function doSearch() {
    const q = input.value.trim();
    clearTimeout(timer);
    if (abortCtrl) abortCtrl.abort();
    if (q.length < 1) { dd.style.display = 'none'; dd.innerHTML = ''; return; }
    showLoading(q);
    timer = setTimeout(() => {
      abortCtrl = new AbortController();
      fetch('/ADMISSION/api/global_search.php?q=' + encodeURIComponent(q), { signal: abortCtrl.signal })
        .then(r => r.json())
        .then(data => { if (data.ok) render(data.results, q); })
        .catch(e => { if (e.name !== 'AbortError') dd.style.display = 'none'; });
    }, 200);
  }

  input.addEventListener('input', doSearch);

  input.addEventListener('keydown', function(e) {
    const items = dd.querySelectorAll('.nh-search-item');
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      if (!items.length) return;
      activeIdx = Math.min(activeIdx + 1, items.length - 1);
      items.forEach((it, i) => it.classList.toggle('active', i === activeIdx));
      items[activeIdx]?.scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      activeIdx = Math.max(activeIdx - 1, 0);
      items.forEach((it, i) => it.classList.toggle('active', i === activeIdx));
      items[activeIdx]?.scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (activeIdx >= 0 && items[activeIdx]) {
        items[activeIdx].click();
      } else if (items.length) {
        items[0].click();
      } else {
        const val = input.value.trim();
        if (val) window.location.href = '/ADMISSION/search.php?q=' + encodeURIComponent(val);
      }
    } else if (e.key === 'Escape') {
      dd.style.display = 'none';
      input.blur();
    }
  });

  input.addEventListener('focus', function() {
    if (this.value.trim().length >= 1 && dd.innerHTML.trim()) dd.style.display = 'block';
  });

  document.addEventListener('click', function(e) {
    if (!e.target.closest('#nhSearchWrap')) dd.style.display = 'none';
  });

  if (btn) {
    btn.addEventListener('click', function() {
      const q = input.value.trim();
      if (q) window.location.href = '/ADMISSION/search.php?q=' + encodeURIComponent(q);
    });
  }

  document.querySelectorAll('.nh-search-tabs button').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.nh-search-tabs button').forEach(b => b.classList.remove('active'));
      tab.classList.add('active');
      filterType = tab.dataset.type || 'all';
      const placeholders = {
        all: 'Search colleges, exams, courses, careers...',
        colleges: 'Search colleges by name, location...',
        exams: 'Search entrance exams...',
        courses: 'Search courses & programs...'
      };
      input.placeholder = placeholders[filterType] || placeholders.all;
      if (input.value.trim().length >= 1) doSearch();
    });
  });
})();
</script>