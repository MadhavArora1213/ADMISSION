<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/college_helpers.php';
require_once __DIR__ . '/exam_helpers.php';
require_once __DIR__ . '/course_helpers.php';

$navBase = '/ADMISSION';

// --- NAVBAR DATA ---
if (!isset($navColleges)) {
    $navColleges = cAll($pdo, "SELECT name,slug FROM colleges WHERE status='active' ORDER BY is_featured DESC, overall_rating_avg DESC, ranking_nirf ASC LIMIT 50");
    $navStates = cAll($pdo, "SELECT s.id, s.name, COUNT(c.id) as college_count FROM states s LEFT JOIN colleges c ON c.state_id = s.id AND c.status = 'active' GROUP BY s.id, s.name ORDER BY college_count DESC, s.name ASC LIMIT 50");
    $navPopularCourses = cAll($pdo, "SELECT course_name,course_slug FROM courses WHERE status='active' ORDER BY is_popular DESC, total_colleges_offering DESC LIMIT 50");
    $navExamsUg = cAll($pdo, "SELECT exam_name,exam_slug FROM exams WHERE status='active' AND (exam_name LIKE '%JEE%' OR exam_name LIKE '%NEET%' OR exam_name LIKE '%CLAT%' OR exam_name LIKE '%CUET%') ORDER BY applicants_last_year DESC LIMIT 10");
    $navExamsPg = cAll($pdo, "SELECT exam_name,exam_slug FROM exams WHERE status='active' AND (exam_name LIKE '%GATE%' OR exam_name LIKE '%CAT%' OR exam_name LIKE '%GMAT%' OR exam_name LIKE '%XAT%' OR exam_name LIKE '%MAT%') ORDER BY applicants_last_year DESC LIMIT 10");
    $navCoursesUg = cAll($pdo, "SELECT course_name,course_slug FROM courses WHERE course_level='UG' ORDER BY total_colleges_offering DESC LIMIT 50");
    $navCoursesPg = cAll($pdo, "SELECT course_name,course_slug FROM courses WHERE course_level='PG' ORDER BY total_colleges_offering DESC LIMIT 50");
    $navCountries = cAll($pdo, "SELECT name FROM countries LIMIT 50");
    $navUnis = cAll($pdo, "SELECT university_name, university_slug FROM foreign_universities ORDER BY qs_rank ASC LIMIT 8");
    $navVisas = cAll($pdo, "SELECT country, visa_type FROM visa_guides ORDER BY country ASC LIMIT 8");
    $navCons = cAll($pdo, "SELECT id, consultant_name FROM consultants ORDER BY consultant_rating DESC LIMIT 8");
    $navPopularCareersList = cAll($pdo, "SELECT name, slug FROM careers WHERE is_popular = 1 ORDER BY name ASC LIMIT 8");

    // --- MORE MEGA MENU DATA ---
    $navMoreCategories = cAll($pdo, "SELECT DISTINCT course_category FROM courses WHERE status='active' AND course_category IS NOT NULL AND course_category != '' ORDER BY course_category ASC");
    $navMoreCourses = cAll($pdo, "SELECT course_name, course_slug, course_category, course_level FROM courses WHERE status='active' AND course_category IS NOT NULL ORDER BY course_category, total_colleges_offering DESC");
    $navMoreExams = cAll($pdo, "SELECT exam_name, exam_slug FROM exams WHERE status='active' ORDER BY applicants_last_year DESC LIMIT 20");
    $navMoreTopStates = cAll($pdo, "SELECT s.id, s.name FROM states s INNER JOIN colleges c ON c.state_id = s.id AND c.status='active' GROUP BY s.id, s.name ORDER BY COUNT(c.id) DESC LIMIT 10");
    $navMoreTopColleges = cAll($pdo, "SELECT name, slug FROM colleges WHERE status='active' ORDER BY overall_rating_avg DESC, ranking_nirf ASC LIMIT 20");
}
?>
<!-- ═══ PRO STYLE NAVBAR ═══ -->
<header class="pro-header" id="header">
  <div class="pro-nav-main">
    <div class="container pro-nav-flex">
      <div class="pro-nav-left">
        <a href="<?= $navBase ?>/index.php" class="pro-logo">
          <i class="ph-fill ph-student"></i>
          <span>AdmissionSeason</span>
        </a>
      </div>

      <div class="pro-nav-search">
        <i class="ph ph-magnifying-glass"></i>
        <input type="text" placeholder="Search for Colleges, Exams, Courses and More.." id="navSearchInput" autocomplete="off">
        <div class="nav-search-dropdown" id="navSearchDropdown"></div>
      </div>
      <script>
      (function(){
        const input = document.getElementById('navSearchInput');
        const dd = document.getElementById('navSearchDropdown');
        if (!input || !dd) return;
        let timer = null, activeIdx = -1, lastQ = '', abortCtrl = null;

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
          if (!results.length) {
            dd.innerHTML = '<div class="nav-search-empty"><i class="ph ph-magnifying-glass" style="font-size:1.5rem;display:block;margin-bottom:8px;opacity:.3"></i>No results found for "<strong>' + q.replace(/</g,'&lt;') + '</strong>"<div style="margin-top:6px;font-size:0.75rem;opacity:.5">Try searching for colleges, exams, courses, careers or news</div></div>';
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
            html += '<div class="nav-search-group">';
            html += '<div class="nav-search-group-title"><i class="ph ' + iconFor(type) + '" style="color:' + typeColor(type) + '"></i> ' + typeLabel(type) + '</div>';
            groups[type].forEach(r => {
              html += '<a href="' + r.url + '" class="nav-search-item">';
              html += '<i class="ph ' + iconFor(type) + '" style="color:' + typeColor(type) + '"></i>';
              html += '<div style="flex:1;min-width:0">';
              html += '<div class="nsi-title">' + highlight(r.title, q) + '</div>';
              if (r.subtitle) html += '<div class="nsi-sub">' + r.subtitle + '</div>';
              html += '</div>';
              if (r.badge) html += '<span class="nsi-badge" style="background:' + typeColor(type) + '11;color:' + typeColor(type) + '">' + r.badge + '</span>';
              html += '</a>';
            });
            html += '</div>';
          });
          html += '<div class="nav-search-footer"><a href="/ADMISSION/search.php?q=' + encodeURIComponent(q) + '" class="nav-view-all">View all results for "' + q.replace(/</g,'&lt;') + '" <i class="ph ph-arrow-right"></i></a></div>';
          dd.innerHTML = html;
          dd.style.display = 'block';
          activeIdx = -1;
        }

        function showLoading(q) {
          dd.innerHTML = '<div class="nav-search-loading"><div class="nav-search-spinner"></div>Searching for "' + q.replace(/</g,'&lt;') + '"...</div>';
          dd.style.display = 'block';
        }

        input.addEventListener('input', function() {
          const q = this.value.trim();
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
        });

        input.addEventListener('keydown', function(e) {
          const items = dd.querySelectorAll('.nav-search-item');
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
            } else if (this.value.trim()) {
              window.location.href = '/ADMISSION/search.php?q=' + encodeURIComponent(this.value.trim());
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
          if (!e.target.closest('.pro-nav-search')) dd.style.display = 'none';
        });
      })();
      </script>

      <div class="pro-nav-right">
        <a href="<?= $navBase ?>/saved_colleges.php" class="pro-icon-btn" title="Saved"><i class="ph ph-heart"></i></a>
        <a href="#" class="pro-icon-btn" title="Notifications"><i class="ph ph-bell"></i></a>
        <a href="<?= $navBase ?>/college/login.php" class="pro-icon-btn" title="College Login" style="font-size:.75rem;width:auto;padding:0 10px;display:flex;align-items:center;gap:4px;text-decoration:none;color:#64748b"><i class="ph ph-graduation-cap"></i> <span style="display:inline">College Login</span></a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="pro-user-dropdown">
            <button class="pro-user-avatar" id="userMenuBtn" onclick="toggleUserMenu()">
              <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </button>
            <div class="pro-user-menu" id="userMenu">
              <div class="pro-user-menu-header"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
              <a href="<?= $navBase ?>/profile.php" class="pro-user-menu-item"><i class="ph ph-user"></i> My Profile</a>
              <div class="pro-user-menu-divider"></div>
              <a href="/ADMISSION/logout.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/') ?>" class="pro-user-menu-item logout"><i class="ph ph-sign-out"></i> Logout</a>
            </div>
          </div>
        <?php else: ?>
          <a href="/ADMISSION/login.php" class="pro-user-btn" title="Login"><i class="ph-fill ph-user-plus"></i></a>
        <?php endif; ?>

        <button class="pro-hamburger" id="proHamburger" onclick="toggleMobileNav()" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </div>

  <!-- Desktop sub-nav -->
  <div class="pro-nav-sub pro-nav-sub-desktop">
    <div class="container pro-nav-flex">
      <ul class="pro-sub-links">
        <li><a href="<?= $navBase ?>/">Home</a></li>
        <li class="pro-has-mega">
          <a href="<?= $navBase ?>/colleges.php">Colleges <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu">
            <div class="mega-col">
              <h4>Top Courses</h4>
              <ul>
                <?php foreach($navPopularCourses ?? [] as $navCrs): ?>
                <li><a href="<?= courseUrl($navCrs['course_slug'] ?? '') ?>"><?=htmlspecialchars((string)($navCrs['course_name'] ?? ''))?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Top Locations</h4>
              <ul>
                <?php foreach($navStates ?? [] as $st): ?>
                <li><a href="<?= $navBase ?>/colleges.php?state=<?= (int)$st['id'] ?>"><?=htmlspecialchars($st['name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Top Colleges</h4>
              <ul>
                <?php foreach($navColleges ?? [] as $navClg): ?>
                <li><a href="<?= collegeUrl($navClg['slug'] ?? '') ?>"><?=htmlspecialchars((string)($navClg['name'] ?? ''))?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </li>

        <li class="pro-has-mega">
          <a href="<?= examsUrl() ?>">Exams <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu">
            <div class="mega-col">
              <h4>UG Exams</h4>
              <ul>
                <?php foreach($navExamsUg ?? [] as $ex): ?>
                <li><a href="<?= examUrl($ex['exam_slug']) ?>"><?=htmlspecialchars($ex['exam_name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>PG Exams</h4>
              <ul>
                <?php foreach($navExamsPg ?? [] as $ex): ?>
                <li><a href="<?= examUrl($ex['exam_slug']) ?>"><?=htmlspecialchars($ex['exam_name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </li>

        <li class="pro-has-mega">
          <a href="<?= coursesUrl() ?>">Courses <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu">
            <div class="mega-col">
              <h4>UG Courses</h4>
              <ul>
                <?php foreach($navCoursesUg ?? [] as $co): ?>
                <li><a href="<?= courseUrl($co['course_slug']) ?>"><?=htmlspecialchars($co['course_name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>PG Courses</h4>
              <ul>
                <?php foreach($navCoursesPg ?? [] as $co): ?>
                <li><a href="<?= courseUrl($co['course_slug']) ?>"><?=htmlspecialchars($co['course_name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Explore More</h4>
              <ul>
                <li><a href="<?= coursesUrl(['level' => 'Diploma']) ?>">Diploma Courses</a></li>
                <li><a href="<?= coursesUrl(['level' => 'PhD']) ?>">PhD Programs</a></li>
                <li><a href="<?= coursesUrl() ?>">All Courses</a></li>
              </ul>
            </div>
          </div>
        </li>

        <li class="pro-has-mega">
          <a href="<?= $navBase ?>/study-abroad">Study Abroad <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu">
            <div class="mega-col">
              <h4>Universities Abroad</h4>
              <ul>
                <?php foreach($navUnis ?? [] as $nu): ?>
                <li><a href="<?= $navBase ?>/foreign-university/<?= htmlspecialchars($nu['university_slug'] ?? $nu['id']) ?>"><?=htmlspecialchars($nu['university_name'])?></a></li>
                <?php endforeach; ?>
                <li><a href="<?= $navBase ?>/study-abroad?tab=universities" style="color: var(--yale-blue); font-weight: 700; margin-top: 10px; display: inline-block;">View All Universities &rarr;</a></li>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Visa Guides</h4>
              <ul>
                <?php foreach($navVisas ?? [] as $nv): ?>
                <li><a href="<?= $navBase ?>/visa-guide/<?= strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nv['country']), '-')) ?>"><?=htmlspecialchars($nv['country'])?> Visa Guide</a></li>
                <?php endforeach; ?>
                <li><a href="<?= $navBase ?>/study-abroad?tab=visas" style="color: var(--yale-blue); font-weight: 700; margin-top: 10px; display: inline-block;">View All Visa Guides &rarr;</a></li>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Consultants</h4>
              <ul>
                <?php foreach($navCons ?? [] as $nc): ?>
                <li><a href="<?= $navBase ?>/consultant/<?= (int)$nc['id'] ?>"><?=htmlspecialchars($nc['consultant_name'])?></a></li>
                <?php endforeach; ?>
                <li><a href="<?= $navBase ?>/study-abroad?tab=consultants" style="color: var(--yale-blue); font-weight: 700; margin-top: 10px; display: inline-block;">View All Consultants &rarr;</a></li>
              </ul>
            </div>
          </div>
        </li>

        <li class="pro-has-mega">
          <a href="<?= $navBase ?>/counselling">Counseling <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu cns-dropdown-menu">
            <div class="cns-nav-list">
              <div class="cns-nav-item">
                <a href="#"><i class="ph ph-chat-circle-dots"></i> Get Expert Guidance <i class="ph ph-caret-right" style="margin-left:auto; font-size:0.75rem;"></i></a>
                <div class="cns-sub-panel">
                  <h4>Get Expert Guidance</h4>
                  <ul>
                    <li><a href="<?= $navBase ?>/ask-question"><span><i class="ph-bold ph-question"></i> Ask a Question</span><span class="cns-sub-desc">Get quick responses from expert counsellors</span></a></li>
                    <li><a href="<?= $navBase ?>/discussions"><span><i class="ph-bold ph-chats"></i> Discussions</span><span class="cns-sub-desc">Participate in career discussion groups</span></a></li>
                  </ul>
                </div>
              </div>
              <div class="cns-nav-item">
                <a href="#"><i class="ph ph-compass"></i> Careers After 12th <i class="ph ph-caret-right" style="margin-left:auto; font-size:0.75rem;"></i></a>
                <div class="cns-sub-panel" style="width: 480px;">
                  <h4>Careers After 12th</h4>
                  <div class="sub-columns" style="display: grid; grid-template-columns: 180px 1fr; gap: 20px;">
                    <div class="sub-col">
                      <h5>By Stream</h5>
                      <ul style="max-height: 250px; overflow-y: visible;">
                        <li><a href="<?= $navBase ?>/careers.php?stream=Science"><span><i class="ph ph-atom"></i> Science</span></a></li>
                        <li><a href="<?= $navBase ?>/careers.php?stream=Commerce"><span><i class="ph ph-chart-line-up"></i> Commerce</span></a></li>
                        <li><a href="<?= $navBase ?>/careers.php?stream=Humanities"><span><i class="ph ph-palette"></i> Humanities</span></a></li>
                      </ul>
                    </div>
                    <div class="sub-col">
                      <h5>Popular Careers</h5>
                      <ul style="max-height: 250px; overflow-y: visible; display: grid; grid-template-columns: 1fr; gap: 8px;">
                        <?php foreach($navPopularCareersList ?? [] as $popCar): ?>
                          <li><a href="<?= $navBase ?>/career/<?= htmlspecialchars($popCar['slug']) ?>"><span><i class="ph ph-briefcase"></i> <?= htmlspecialchars($popCar['name']) ?></span></a></li>
                        <?php endforeach; ?>
                        <li><a href="<?= $navBase ?>/careers.php" style="background: none !important; border: none !important; padding: 2px 0 !important; color: var(--yale-blue) !important; font-weight: 700 !important;"><span style="color: var(--yale-blue);">&gt; All other careers</span></a></li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
              <div class="cns-nav-item">
                <a href="#"><i class="ph ph-graduation-cap"></i> Courses After 12th <i class="ph ph-caret-right" style="margin-left:auto; font-size:0.75rem;"></i></a>
                <div class="cns-sub-panel">
                  <h4>Courses After 12th</h4>
                  <ul>
                    <li><a href="<?= $navBase ?>/courses?level=UG"><span><i class="ph ph-book-open"></i> Undergraduate (UG) Courses</span></a></li>
                    <li><a href="<?= $navBase ?>/courses?level=PG"><span><i class="ph ph-books"></i> Postgraduate (PG) Courses</span></a></li>
                    <li><a href="<?= $navBase ?>/courses"><span><i class="ph ph-certificate"></i> Diploma & Certifications</span></a></li>
                  </ul>
                </div>
              </div>
              <div class="cns-nav-item">
                <a href="#"><i class="ph ph-book-open"></i> Free Prep Material <i class="ph ph-caret-right" style="margin-left:auto; font-size:0.75rem;"></i></a>
                <div class="cns-sub-panel">
                  <h4>Free Prep Material</h4>
                  <ul>
                    <li><a href="<?= $navBase ?>/exams"><span><i class="ph ph-calendar"></i> Entrance Exams Pattern</span></a></li>
                    <li><a href="<?= $navBase ?>/exams"><span><i class="ph ph-file-pdf"></i> Syllabus & Question Papers</span></a></li>
                  </ul>
                </div>
              </div>
              <div class="cns-nav-item">
                <a href="#"><i class="ph ph-certificate"></i> National Boards <i class="ph ph-caret-right" style="margin-left:auto; font-size:0.75rem;"></i></a>
                <div class="cns-sub-panel">
                  <h4>National Boards</h4>
                  <ul>
                    <li><a href="<?= $navBase ?>/colleges.php?board=cbse"><span><i class="ph ph-building"></i> CBSE (Central Board)</span></a></li>
                    <li><a href="<?= $navBase ?>/colleges.php?board=cisce"><span><i class="ph ph-compass"></i> ICSE / ISC Board</span></a></li>
                  </ul>
                </div>
              </div>
              <div class="cns-nav-item">
                <a href="#"><i class="ph ph-map-pin"></i> State Boards <i class="ph ph-caret-right" style="margin-left:auto; font-size:0.75rem;"></i></a>
                <div class="cns-sub-panel">
                  <h4>State Boards</h4>
                  <ul>
                    <li><a href="<?= $navBase ?>/colleges.php?board=up"><span><i class="ph ph-map-trifold"></i> UP Board</span></a></li>
                    <li><a href="<?= $navBase ?>/colleges.php?board=bihar"><span><i class="ph ph-map-trifold"></i> Bihar Board</span></a></li>
                  </ul>
                </div>
              </div>
              <div class="cns-nav-item">
                <a href="<?= $navBase ?>/study-abroad"><i class="ph ph-globe"></i> Abroad Counseling</a>
              </div>
              <div class="cns-nav-item">
                <a href="<?= $navBase ?>/predictor.php"><i class="ph ph-sparkles"></i> Recommendations <span class="cns-badge-new" style="margin-left:5px;">NEW</span></a>
              </div>
              <div class="cns-nav-item highlight-item" style="border-top:1px solid #e2e8f0; margin-top:8px; padding: 12px 16px;">
                <a href="<?= $navBase ?>/counselling" style="color: #000 !important;"><i class="ph-fill ph-headset"></i> Get Free Counselling</a>
              </div>
            </div>
            <div class="cns-default-panel">
              <div>
                <i class="ph ph-hand-pointing" style="font-size: 2.2rem; color: var(--text-muted-alt); margin-bottom: 12px;"></i>
                <h4 style="font-size: 0.95rem; color: var(--oxford-navy); font-weight: 700;">Explore Counseling Services</h4>
                <p style="font-size: 0.78rem; color: var(--text-muted-alt); margin-top: 4px; max-width: 180px; line-height:1.4;">Hover over any item on the left to see details.</p>
              </div>
            </div>
          </div>
        </li>

        <li class="pro-has-mega more-mega-wrap">
          <a href="#">More <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu more-mega-menu">
            <div class="more-mega-sidebar">
              <?php
              $moreIcons = ['Engineering'=>'ph-wrench','Computer Applications'=>'ph-laptop','Management'=>'ph-briefcase','Medical'=>'ph-heart-pulse','Law'=>'ph-scales','Nursing'=>'ph-hand-heart','Arts'=>'ph-palette'];
              $moreStreams = ['Science','Commerce','Humanities'];
              $streamIcons = ['Science'=>'ph-atom','Commerce'=>'ph-chart-line-up','Humanities'=>'ph-book-open-text'];
              ?>
              <?php foreach($navMoreCategories ?? [] as $cat): ?>
              <div class="more-sidebar-item" data-cat="<?= htmlspecialchars($cat['course_category']) ?>">
                <a href="<?= coursesUrl(['category' => $cat['course_category']]) ?>">
                  <i class="ph <?= $moreIcons[$cat['course_category']] ?? 'ph-folder' ?>"></i>
                  <?= htmlspecialchars($cat['course_category']) ?>
                  <i class="ph ph-caret-right more-arrow"></i>
                </a>
              </div>
              <?php endforeach; ?>
              <?php foreach($moreStreams as $ns): ?>
              <div class="more-sidebar-item" data-cat="<?= $ns ?>">
                <a href="<?= '/ADMISSION/careers.php?stream=' . urlencode($ns) ?>">
                  <i class="ph <?= $streamIcons[$ns] ?? 'ph-compass' ?>"></i>
                  <?= $ns ?> Careers
                  <i class="ph ph-caret-right more-arrow"></i>
                </a>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="more-mega-panels">
              <?php
              $allCoursesByCat = [];
              foreach($navMoreCourses ?? [] as $mc) {
                  $cat = $mc['course_category'];
                  if (!isset($allCoursesByCat[$cat])) $allCoursesByCat[$cat] = [];
                  $allCoursesByCat[$cat][] = $mc;
              }
              ?>
              <?php foreach($navMoreCategories ?? [] as $cat): ?>
              <?php $catName = $cat['course_category']; ?>
              <div class="more-panel" data-panel="<?= htmlspecialchars($catName) ?>">
                <div class="more-panel-cols">
                  <div class="more-panel-col">
                    <h4>Top Colleges</h4>
                    <ul>
                      <?php foreach(array_slice($navMoreTopColleges ?? [], 0, 6) as $clg): ?>
                      <li><a href="<?= collegeUrl($clg['slug'] ?? '') ?>"><?= htmlspecialchars($clg['name']) ?></a></li>
                      <?php endforeach; ?>
                      <li><a href="<?= collegesUrl(['q' => $catName]) ?>" class="more-view-all">> All <?= htmlspecialchars($catName) ?> Colleges</a></li>
                    </ul>
                  </div>
                  <div class="more-panel-col">
                    <h4>Popular Courses</h4>
                    <ul>
                      <?php foreach(array_slice($allCoursesByCat[$catName] ?? [], 0, 6) as $co): ?>
                      <li><a href="<?= courseUrl($co['course_slug'] ?? '') ?>"><?= htmlspecialchars($co['course_name']) ?></a></li>
                      <?php endforeach; ?>
                      <li><a href="<?= coursesUrl(['category' => $catName]) ?>" class="more-view-all">> All <?= htmlspecialchars($catName) ?> Courses</a></li>
                    </ul>
                  </div>
                  <div class="more-panel-col">
                    <h4>Top Exams</h4>
                    <ul>
                      <?php foreach(array_slice($navMoreExams ?? [], 0, 6) as $ex): ?>
                      <li><a href="<?= examUrl($ex['exam_slug'] ?? '') ?>"><?= htmlspecialchars($ex['exam_name']) ?></a></li>
                      <?php endforeach; ?>
                      <li><a href="<?= examsUrl() ?>" class="more-view-all">> All Exams</a></li>
                    </ul>
                  </div>
                  <div class="more-panel-col">
                    <h4>Colleges by Location</h4>
                    <ul>
                      <?php foreach(array_slice($navMoreTopStates ?? [], 0, 6) as $st): ?>
                      <li><a href="<?= collegesUrl(['state' => $st['id'], 'q' => $catName]) ?>"><?= htmlspecialchars($catName) ?> Colleges in <?= htmlspecialchars($st['name']) ?></a></li>
                      <?php endforeach; ?>
                      <li><a href="<?= collegesUrl(['q' => $catName]) ?>" class="more-view-all">> All Locations</a></li>
                    </ul>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
              <?php foreach($moreStreams as $ns): ?>
              <div class="more-panel" data-panel="<?= $ns ?>">
                <div class="more-panel-cols">
                  <div class="more-panel-col">
                    <h4>Top Colleges</h4>
                    <ul>
                      <?php foreach(array_slice($navMoreTopColleges ?? [], 0, 6) as $clg): ?>
                      <li><a href="<?= collegeUrl($clg['slug'] ?? '') ?>"><?= htmlspecialchars($clg['name']) ?></a></li>
                      <?php endforeach; ?>
                      <li><a href="<?= collegesUrl() ?>" class="more-view-all">> All Colleges</a></li>
                    </ul>
                  </div>
                  <div class="more-panel-col">
                    <h4>Careers in <?= $ns ?></h4>
                    <ul>
                      <li><a href="<?= '/ADMISSION/careers.php?stream=' . urlencode($ns) ?>"><?= $ns ?> Careers Overview</a></li>
                      <li><a href="<?= '/ADMISSION/careers.php?stream=' . urlencode($ns) ?>" class="more-view-all">> Explore All <?= $ns ?> Careers</a></li>
                    </ul>
                  </div>
                  <div class="more-panel-col">
                    <h4>Popular Courses</h4>
                    <ul>
                      <?php
                      $streamCourses = array_filter($navMoreCourses ?? [], function($c) use ($ns) {
                          return ($ns === 'Science' && in_array($c['course_category'], ['Engineering','Medical','Computer Applications','Nursing'])) ||
                                 ($ns === 'Commerce' && in_array($c['course_category'], ['Management'])) ||
                                 ($ns === 'Humanities' && in_array($c['course_category'], ['Arts','Law']));
                      });
                      foreach(array_slice($streamCourses, 0, 6) as $co): ?>
                      <li><a href="<?= courseUrl($co['course_slug'] ?? '') ?>"><?= htmlspecialchars($co['course_name']) ?></a></li>
                      <?php endforeach; ?>
                      <li><a href="<?= coursesUrl() ?>" class="more-view-all">> All Courses</a></li>
                    </ul>
                  </div>
                  <div class="more-panel-col">
                    <h4>Colleges by Location</h4>
                    <ul>
                      <?php foreach(array_slice($navMoreTopStates ?? [], 0, 6) as $st): ?>
                      <li><a href="<?= collegesUrl(['state' => $st['id']]) ?>"><?= htmlspecialchars($st['name']) ?> Colleges</a></li>
                      <?php endforeach; ?>
                      <li><a href="<?= collegesUrl() ?>" class="more-view-all">> All Locations</a></li>
                    </ul>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
              <div class="more-panel-default">
                <i class="ph ph-hand-pointing" style="font-size: 2.2rem; color: var(--text-muted-alt); margin-bottom: 12px;"></i>
                <h4 style="font-size: 0.95rem; color: var(--oxford-navy); font-weight: 700;">Explore by Category</h4>
                <p style="font-size: 0.78rem; color: var(--text-muted-alt); margin-top: 4px; max-width: 180px; line-height:1.4;">Hover over any category on the left to see details.</p>
              </div>
            </div>
          </div>
        </li>

        <li><a href="<?= $navBase ?>/news.php">News</a></li>
      </ul>
      <ul class="pro-sub-links-right">
        <li><a href="<?= $navBase ?>/counselling" class="counselling-btn"><i class="ph-fill ph-headset"></i> Free Counselling <span class="pulse-dot"></span></a></li>
      </ul>
    </div>
  </div>
</header>

<!-- ═══ MOBILE NAV DRAWER ═══ -->
<div class="pro-mobile-overlay" id="proMobileOverlay" onclick="toggleMobileNav()"></div>
<nav class="pro-mobile-drawer" id="proMobileDrawer">
  <div class="pro-mobile-drawer-header">
    <a href="<?= $navBase ?>/index.php" class="pro-logo" style="font-size:1.2rem">
      <i class="ph-fill ph-student"></i>
      <span>AdmissionSeason</span>
    </a>
    <button class="pro-hamburger active" onclick="toggleMobileNav()" aria-label="Close">
      <span></span><span></span><span></span>
    </button>
  </div>

  <div class="pro-mobile-search">
    <i class="ph ph-magnifying-glass"></i>
    <input type="text" placeholder="Search colleges, exams, courses..." onfocus="window.location='<?= $navBase ?>/search.php'">
  </div>

  <?php if (isset($_SESSION['user_id'])): ?>
  <div class="pro-mobile-user">
    <div class="pro-user-avatar" style="width:36px;height:36px;font-size:.85rem">
      <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
    </div>
    <div>
      <div style="font-weight:700;font-size:.9rem;color:#0B2447"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
      <a href="<?= $navBase ?>/profile.php" style="font-size:.75rem;color:#64748b;text-decoration:none">View Profile</a>
    </div>
  </div>
  <?php else: ?>
  <div class="pro-mobile-user">
    <a href="/ADMISSION/login.php" class="pro-mobile-login-btn"><i class="ph-fill ph-user-plus"></i> Login / Sign Up</a>
  </div>
  <?php endif; ?>

  <div class="pro-mobile-nav-links">
    <div class="pro-mobile-section-title">Quick Links</div>
    <a href="<?= $navBase ?>/" class="pro-mobile-link"><i class="ph ph-house"></i> Home</a>
    <a href="<?= $navBase ?>/colleges.php" class="pro-mobile-link pro-has-sub"><i class="ph ph-buildings"></i> Colleges <i class="ph ph-caret-right pro-mobile-arrow"></i></a>
    <div class="pro-mobile-sub" id="mobileSubColleges">
      <a href="<?= $navBase ?>/colleges.php">All Colleges</a>
      <?php foreach($navStates ?? [] as $st): ?>
      <a href="<?= $navBase ?>/colleges.php?state=<?= (int)$st['id'] ?>"><?=htmlspecialchars($st['name'])?></a>
      <?php endforeach; ?>
    </div>

    <a href="<?= examsUrl() ?>" class="pro-mobile-link pro-has-sub"><i class="ph ph-file-text"></i> Exams <i class="ph ph-caret-right pro-mobile-arrow"></i></a>
    <div class="pro-mobile-sub" id="mobileSubExams">
      <div class="pro-mobile-sub-title">UG Exams</div>
      <?php foreach($navExamsUg ?? [] as $ex): ?>
      <a href="<?= examUrl($ex['exam_slug']) ?>"><?=htmlspecialchars($ex['exam_name'])?></a>
      <?php endforeach; ?>
      <div class="pro-mobile-sub-title" style="margin-top:10px">PG Exams</div>
      <?php foreach($navExamsPg ?? [] as $ex): ?>
      <a href="<?= examUrl($ex['exam_slug']) ?>"><?=htmlspecialchars($ex['exam_name'])?></a>
      <?php endforeach; ?>
    </div>

    <a href="<?= coursesUrl() ?>" class="pro-mobile-link pro-has-sub"><i class="ph ph-book-open"></i> Courses <i class="ph ph-caret-right pro-mobile-arrow"></i></a>
    <div class="pro-mobile-sub" id="mobileSubCourses">
      <div class="pro-mobile-sub-title">UG Courses</div>
      <?php foreach($navCoursesUg ?? [] as $co): ?>
      <a href="<?= courseUrl($co['course_slug']) ?>"><?=htmlspecialchars($co['course_name'])?></a>
      <?php endforeach; ?>
      <div class="pro-mobile-sub-title" style="margin-top:10px">PG Courses</div>
      <?php foreach($navCoursesPg ?? [] as $co): ?>
      <a href="<?= courseUrl($co['course_slug']) ?>"><?=htmlspecialchars($co['course_name'])?></a>
      <?php endforeach; ?>
    </div>

    <a href="<?= $navBase ?>/study-abroad" class="pro-mobile-link"><i class="ph ph-globe"></i> Study Abroad</a>
    <a href="<?= $navBase ?>/counselling" class="pro-mobile-link"><i class="ph ph-headset"></i> Counseling</a>
    <a href="#" class="pro-mobile-link pro-has-sub"><i class="ph ph-grid-four"></i> More <i class="ph ph-caret-right pro-mobile-arrow"></i></a>
    <div class="pro-mobile-sub" id="mobileSubMore">
      <?php foreach($navMoreCategories ?? [] as $cat): ?>
      <a href="<?= coursesUrl(['category' => $cat['course_category']]) ?>"><?= htmlspecialchars($cat['course_category']) ?></a>
      <?php endforeach; ?>
      <a href="/ADMISSION/careers.php?stream=Science">Science Careers</a>
      <a href="/ADMISSION/careers.php?stream=Commerce">Commerce Careers</a>
      <a href="/ADMISSION/careers.php?stream=Humanities">Humanities Careers</a>
    </div>
    <a href="<?= $navBase ?>/news.php" class="pro-mobile-link"><i class="ph ph-newspaper"></i> News</a>

    <div class="pro-mobile-section-title" style="margin-top:16px">Actions</div>
    <a href="<?= $navBase ?>/counselling" class="pro-mobile-link"><i class="ph-fill ph-headset"></i> Free Counselling</a>
    <a href="<?= $navBase ?>/college/login.php" class="pro-mobile-link"><i class="ph ph-graduation-cap"></i> College Login</a>
    <a href="<?= $navBase ?>/college/signup.php" class="pro-mobile-link"><i class="ph ph-buildings"></i> Register Your Institute</a>
    <a href="<?= $navBase ?>/saved_colleges.php" class="pro-mobile-link"><i class="ph ph-heart"></i> Saved Colleges</a>
    <?php if (isset($_SESSION['user_id'])): ?>
    <a href="/ADMISSION/logout.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/') ?>" class="pro-mobile-link" style="color:#DC2626"><i class="ph ph-sign-out"></i> Logout</a>
    <?php endif; ?>
  </div>
</nav>
<script>
function toggleUserMenu() {
  const menu = document.getElementById('userMenu');
  if (menu) menu.classList.toggle('open');
}

function toggleMobileNav() {
  const drawer = document.getElementById('proMobileDrawer');
  const overlay = document.getElementById('proMobileOverlay');
  const burger = document.getElementById('proHamburger');
  const isOpen = drawer.classList.contains('open');
  if (isOpen) {
    drawer.classList.remove('open');
    overlay.classList.remove('open');
    burger.classList.remove('active');
    document.body.style.overflow = '';
  } else {
    drawer.classList.add('open');
    overlay.classList.add('open');
    burger.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
}

document.addEventListener('click', function(e) {
  const menu = document.getElementById('userMenu');
  const btn = document.getElementById('userMenuBtn');
  if (menu && menu.classList.contains('open') && !menu.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
    menu.classList.remove('open');
  }
});

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.getElementById('userMenu')?.classList.remove('open');
    if (document.getElementById('proMobileDrawer')?.classList.contains('open')) toggleMobileNav();
  }
});

document.querySelectorAll('.pro-mobile-link[data-toggle]').forEach(function(link) {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    this.classList.toggle('open');
    var sub = this.nextElementSibling;
    if (sub && sub.classList.contains('pro-mobile-sub')) {
      sub.classList.toggle('open');
    }
  });
});

document.querySelectorAll('.pro-mobile-link.pro-has-sub').forEach(function(link) {
  link.addEventListener('click', function(e) {
    e.preventDefault();
    this.classList.toggle('open');
    var sub = this.nextElementSibling;
    if (sub && sub.classList.contains('pro-mobile-sub')) {
      sub.classList.toggle('open');
    }
  });
});

// ═══ MORE MEGA MENU HOVER LOGIC ═══
document.querySelectorAll('.more-sidebar-item').forEach(function(item) {
  item.addEventListener('mouseenter', function() {
    var cat = this.getAttribute('data-cat');
    // Remove active from all sidebar items
    document.querySelectorAll('.more-sidebar-item').forEach(function(s) { s.classList.remove('active'); });
    // Hide all panels and default
    document.querySelectorAll('.more-panel').forEach(function(p) { p.classList.remove('active'); });
    var def = document.querySelector('.more-panel-default');
    if (def) def.style.display = 'none';
    // Activate hovered item and show its panel
    this.classList.add('active');
    var panel = document.querySelector('.more-panel[data-panel="' + cat + '"]');
    if (panel) panel.classList.add('active');
  });
});

// Reset to default when leaving the mega menu
var moreWrap = document.querySelector('.more-mega-wrap');
if (moreWrap) {
  moreWrap.addEventListener('mouseleave', function() {
    document.querySelectorAll('.more-sidebar-item').forEach(function(s) { s.classList.remove('active'); });
    document.querySelectorAll('.more-panel').forEach(function(p) { p.classList.remove('active'); });
    var def = document.querySelector('.more-panel-default');
    if (def) def.style.display = '';
  });
}
</script>
<style>
/* Custom Counseling subdropdown mega-menu */
.cns-dropdown-menu {
  width: 700px !important;
  display: grid !important;
  grid-template-columns: 240px 1fr !important;
  padding: 0 !important;
  overflow: hidden;
  min-height: 380px;
}

.cns-nav-list {
  background: #f8fafc;
  border-right: 1.5px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  padding: 15px 0;
}

.cns-nav-item {
  position: static; /* position sub-panels relative to the grid container */
}

.cns-nav-item > a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 20px;
  color: #475569;
  font-size: 0.88rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.25s ease;
}

.cns-nav-item > a i:first-child {
  font-size: 1.15rem;
  opacity: 0.85;
}

.cns-nav-item:hover > a {
  background: #fff;
  color: var(--yale-blue);
  padding-left: 24px;
}

.cns-sub-panel {
  position: absolute;
  top: 0;
  right: 0;
  width: 460px;
  height: 100%;
  background: #fff;
  padding: 24px 30px;
  box-sizing: border-box;
  display: none;
  z-index: 5;
  text-align: left;
}

.cns-nav-item:hover .cns-sub-panel {
  display: block !important;
}

.cns-default-panel {
  padding: 24px 30px;
  color: #64748b;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  font-size: 0.9rem;
  font-weight: 500;
}

.cns-sub-panel h4 {
  font-size: 1.05rem;
  color: var(--oxford-navy);
  margin-bottom: 16px;
  font-weight: 700;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 8px;
}

.cns-sub-panel ul {
  list-style: none;
  padding: 0;
  margin: 0;
  max-height: 300px;
  overflow-y: visible !important;
}

.cns-sub-panel ul li {
  margin-bottom: 12px;
}

.cns-sub-panel ul li a {
  display: flex !important;
  flex-direction: column;
  color: #334155;
  text-decoration: none;
  font-size: 0.92rem;
  font-weight: 700;
  transition: all 0.2s ease;
  padding: 10px 14px !important;
  background: #f8fafc !important;
  border-radius: 8px;
  border: 1px solid #f1f5f9;
}

.cns-sub-panel ul li a:hover {
  color: var(--yale-blue) !important;
  border-color: rgba(25, 55, 109, 0.2);
  background: rgba(25, 55, 109, 0.02) !important;
  transform: translateY(-1px);
}

.cns-sub-desc {
  font-size: 0.76rem;
  color: #64748b;
  font-weight: 400;
  margin-top: 2px;
}

.cns-badge-new {
  background: #2563eb;
  color: #fff;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 4px;
}

.cns-nav-list .highlight-item {
  padding: 12px 16px;
  border-top: 1px solid #e2e8f0;
  margin-top: 8px;
}

.cns-nav-list .highlight-item a {
  background: var(--yale-blue);
  color: #fff !important;
  border-radius: 8px;
  padding: 10px 16px !important;
  justify-content: center;
  box-shadow: 0 4px 10px rgba(25, 55, 109, 0.15);
  gap: 8px;
}

.cns-nav-list .highlight-item a:hover {
  background: var(--oxford-navy);
  padding-left: 16px !important;
  transform: translateY(-1px);
}

.sub-columns {
  display: grid;
  grid-template-columns: 1fr;
  gap: 15px;
}
.sub-col h5 {
  font-size: 0.82rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

/* ═══ MORE MEGA MENU (Shiksha-style) ═══ */
.more-mega-menu {
  display: grid !important;
  grid-template-columns: 220px 1fr !important;
  grid-template-rows: auto !important;
  gap: 0 !important;
  width: 920px !important;
  min-width: 920px !important;
  max-width: 920px !important;
  padding: 0 !important;
  overflow: hidden;
  min-height: 420px;
  border-radius: 12px !important;
  margin-top: 0 !important;
  right: auto !important;
  left: -9999px !important;
  transform: translateX(-50%) !important;
}

.pro-has-mega.more-mega-wrap:hover .more-mega-menu {
  left: 50% !important;
  transform: translateX(-50%) translateY(0) !important;
}

.more-mega-sidebar {
  background: #f8fafc;
  border-right: 1.5px solid #e2e8f0;
  display: flex;
  flex-direction: column;
  padding: 12px 0;
  overflow-y: auto;
  max-height: 420px;
}

.more-sidebar-item > a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 18px;
  color: #475569;
  font-size: 0.85rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.more-sidebar-item > a i:first-child {
  font-size: 1.1rem;
  opacity: 0.7;
  width: 20px;
  text-align: center;
}

.more-arrow {
  margin-left: auto;
  font-size: 0.7rem !important;
  opacity: 0.3 !important;
  transition: all 0.2s;
}

.more-sidebar-item:hover > a,
.more-sidebar-item.active > a {
  background: #fff;
  color: var(--yale-blue);
  padding-left: 22px;
}

.more-sidebar-item:hover .more-arrow,
.more-sidebar-item.active .more-arrow {
  opacity: 1 !important;
  color: var(--yale-blue);
}

.more-mega-panels {
  position: relative;
  padding: 24px 28px;
  min-height: 420px;
}

.more-panel {
  display: none;
}

.more-panel.active {
  display: block;
}

.more-panel-default {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  text-align: center;
}

.more-panel.hidden {
  display: none !important;
}

.more-panel-cols {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.more-panel-col h4 {
  font-size: 0.88rem;
  color: var(--oxford-navy);
  margin-bottom: 12px;
  font-weight: 700;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 6px;
  font-family: var(--font2);
  white-space: nowrap;
}

.more-panel-col ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.more-panel-col ul li {
  margin-bottom: 0;
}

.more-panel-col ul li a {
  display: block;
  padding: 5px 0 !important;
  color: rgba(15,23,42,0.5) !important;
  font-size: 0.82rem !important;
  font-weight: 500 !important;
  text-decoration: none;
  transition: all 0.2s;
  white-space: normal !important;
  line-height: 1.35;
}

.more-panel-col ul li a:hover {
  color: var(--yale-blue) !important;
  transform: translateX(3px);
}

.more-view-all {
  color: var(--yale-blue) !important;
  font-weight: 700 !important;
  font-size: 0.8rem !important;
  margin-top: 6px;
  display: inline-block !important;
}
</style>

</header>
