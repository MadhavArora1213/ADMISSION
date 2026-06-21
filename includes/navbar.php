<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/college_helpers.php';
require_once __DIR__ . '/exam_helpers.php';
require_once __DIR__ . '/course_helpers.php';

// --- NAVBAR DATA ---
if (!isset($navColleges)) {
    $navColleges = cAll($pdo, "SELECT name,slug FROM colleges WHERE status='active' ORDER BY is_featured DESC, overall_rating_avg DESC, ranking_nirf ASC LIMIT 50");
    $navStates = cAll($pdo, "SELECT s.id, s.name, COUNT(c.id) as college_count FROM states s LEFT JOIN colleges c ON c.state_id = s.id AND c.status = 'active' GROUP BY s.id, s.name ORDER BY college_count DESC, s.name ASC LIMIT 50");
    $navPopularCourses = cAll($pdo, "SELECT course_name,course_slug FROM courses WHERE status='active' ORDER BY is_popular DESC, total_colleges_offering DESC LIMIT 50");
    $navExamsUg = cAll($pdo, "SELECT exam_name,exam_slug FROM exams LIMIT 50");
    $navExamsPg = cAll($pdo, "SELECT exam_name,exam_slug FROM exams LIMIT 50");
    $navCoursesUg = cAll($pdo, "SELECT course_name,course_slug FROM courses WHERE course_level='UG' ORDER BY total_colleges_offering DESC LIMIT 50");
    $navCoursesPg = cAll($pdo, "SELECT course_name,course_slug FROM courses WHERE course_level='PG' ORDER BY total_colleges_offering DESC LIMIT 50");
    $navCountries = cAll($pdo, "SELECT name FROM countries LIMIT 50");
    $navUnis = cAll($pdo, "SELECT university_name, university_slug FROM foreign_universities ORDER BY qs_rank ASC LIMIT 8");
    $navVisas = cAll($pdo, "SELECT country, visa_type FROM visa_guides ORDER BY country ASC LIMIT 8");
    $navCons = cAll($pdo, "SELECT consultant_name FROM consultants ORDER BY consultant_rating DESC LIMIT 8");
    $navPopularCareersList = cAll($pdo, "SELECT name, slug FROM careers WHERE is_popular = 1 ORDER BY name ASC LIMIT 8");
}
?>
<!-- ═══ PRO STYLE NAVBAR ═══ -->
<header class="pro-header" id="header">
  <div class="pro-nav-main">
    <div class="container pro-nav-flex">
      <div class="pro-nav-left">
        <a href="index.php" class="pro-logo">
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
        <a href="#" class="pro-icon-btn" title="Saved"><i class="ph ph-heart"></i></a>
        <a href="#" class="pro-icon-btn" title="Notifications"><i class="ph ph-bell"></i></a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="pro-user-dropdown">
            <button class="pro-user-avatar" id="userMenuBtn" onclick="toggleUserMenu()">
              <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </button>
            <div class="pro-user-menu" id="userMenu">
              <div class="pro-user-menu-header"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
              <a href="profile.php" class="pro-user-menu-item"><i class="ph ph-user"></i> My Profile</a>
              <div class="pro-user-menu-divider"></div>
              <a href="logout.php" class="pro-user-menu-item logout"><i class="ph ph-sign-out"></i> Logout</a>
            </div>
          </div>
        <?php else: ?>
          <a href="login.php" class="pro-user-btn" title="Login"><i class="ph-fill ph-user-plus"></i></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <div class="pro-nav-sub">
    <div class="container pro-nav-flex">
      <ul class="pro-sub-links">
        
        <li class="pro-has-mega">
          <a href="colleges.php">Colleges <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu">
            <div class="mega-col">
              <h4>Top Courses</h4>
              <ul>
                <?php foreach($navPopularCourses ?? [] as $navCrs): ?>
                <li><a href="#"><?=htmlspecialchars((string)($navCrs['course_name'] ?? ''))?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Top Locations</h4>
              <ul>
                <?php foreach($navStates ?? [] as $st): ?>
                <li><a href="colleges.php?state=<?= (int)$st['id'] ?>"><?=htmlspecialchars($st['name'])?></a></li>
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
              <h4>Top UG Exams</h4>
              <ul>
                <?php foreach($navExamsUg ?? [] as $ex): ?>
                <li><a href="<?= examUrl($ex['exam_slug']) ?>"><?=htmlspecialchars($ex['exam_name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Top PG Exams</h4>
              <ul>
                <?php foreach($navExamsPg ?? [] as $ex): ?>
                <li><a href="<?= examUrl($ex['exam_slug']) ?>"><?=htmlspecialchars($ex['exam_name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Quick Links</h4>
              <ul>
                <li><a href="#">Exam Calendar 2026</a></li>
                <li><a href="#">Application Deadlines</a></li>
                <li><a href="#">Syllabus & Pattern</a></li>
                <li><a href="#">Result Dates</a></li>
              </ul>
            </div>
          </div>
        </li>

        <li class="pro-has-mega">
          <a href="<?= coursesUrl() ?>">Courses <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu">
            <div class="mega-col">
              <h4>Top UG Courses</h4>
              <ul>
                <?php foreach($navCoursesUg ?? [] as $co): ?>
                <li><a href="<?= courseUrl($co['course_slug']) ?>"><?=htmlspecialchars($co['course_name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Top PG Courses</h4>
              <ul>
                <?php foreach($navCoursesPg ?? [] as $co): ?>
                <li><a href="<?= courseUrl($co['course_slug']) ?>"><?=htmlspecialchars($co['course_name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Explore More</h4>
              <ul>
                <li><a href="#">Diploma Courses</a></li>
                <li><a href="#">PhD Programs</a></li>
                <li><a href="#">Online Certifications</a></li>
                <li><a href="#">Highest Paying Courses</a></li>
              </ul>
            </div>
          </div>
        </li>

        <li class="pro-has-mega">
          <a href="study-abroad">Study Abroad <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu">
            <div class="mega-col">
              <h4>Universities Abroad</h4>
              <ul>
                <?php foreach($navUnis ?? [] as $nu): ?>
                <li><a href="study-abroad?tab=universities"><?=htmlspecialchars($nu['university_name'])?></a></li>
                <?php endforeach; ?>
                <li><a href="study-abroad?tab=universities" style="color: var(--yale-blue); font-weight: 700; margin-top: 10px; display: inline-block;">View All Universities &rarr;</a></li>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Visa Guides</h4>
              <ul>
                <?php foreach($navVisas ?? [] as $nv): ?>
                <li><a href="study-abroad?tab=visas"><?=htmlspecialchars($nv['country'])?> Visa Guide</a></li>
                <?php endforeach; ?>
                <li><a href="study-abroad?tab=visas" style="color: var(--yale-blue); font-weight: 700; margin-top: 10px; display: inline-block;">View All Visa Guides &rarr;</a></li>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Consultants</h4>
              <ul>
                <?php foreach($navCons ?? [] as $nc): ?>
                <li><a href="study-abroad?tab=consultants"><?=htmlspecialchars($nc['consultant_name'])?></a></li>
                <?php endforeach; ?>
                <li><a href="study-abroad?tab=consultants" style="color: var(--yale-blue); font-weight: 700; margin-top: 10px; display: inline-block;">View All Consultants &rarr;</a></li>
              </ul>
            </div>
          </div>
        </li>

        <li class="pro-has-mega">
          <a href="counselling">Counseling <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu cns-dropdown-menu">
            <!-- Left Side vertical tabs -->
            <div class="cns-nav-list">
              <div class="cns-nav-item">
                <a href="#"><i class="ph ph-chat-circle-dots"></i> Get Expert Guidance <i class="ph ph-caret-right" style="margin-left:auto; font-size:0.75rem;"></i></a>
                <div class="cns-sub-panel">
                  <h4>Get Expert Guidance</h4>
                  <ul>
                    <li>
                      <a href="ask-question">
                        <span><i class="ph-bold ph-question"></i> Ask a Question</span>
                        <span class="cns-sub-desc">Get quick responses from expert counsellors</span>
                      </a>
                    </li>
                    <li>
                      <a href="discussions">
                        <span><i class="ph-bold ph-chats"></i> Discussions</span>
                        <span class="cns-sub-desc">Participate in career discussion groups</span>
                      </a>
                    </li>
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
                        <li><a href="careers.php?stream=Science"><span><i class="ph ph-atom"></i> Science</span></a></li>
                        <li><a href="careers.php?stream=Commerce"><span><i class="ph ph-chart-line-up"></i> Commerce</span></a></li>
                        <li><a href="careers.php?stream=Humanities"><span><i class="ph ph-palette"></i> Humanities</span></a></li>
                      </ul>
                    </div>
                    <div class="sub-col">
                      <h5>Popular Careers</h5>
                      <ul style="max-height: 250px; overflow-y: visible; display: grid; grid-template-columns: 1fr; gap: 8px;">
                        <?php foreach($navPopularCareersList ?? [] as $popCar): ?>
                          <li><a href="career_details.php?slug=<?= htmlspecialchars($popCar['slug']) ?>"><span><i class="ph ph-briefcase"></i> <?= htmlspecialchars($popCar['name']) ?></span></a></li>
                        <?php endforeach; ?>
                        <li><a href="careers.php" style="background: none !important; border: none !important; padding: 2px 0 !important; color: var(--yale-blue) !important; font-weight: 700 !important;"><span style="color: var(--yale-blue);">&gt; All other careers</span></a></li>
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
                    <li><a href="courses?level=UG"><span><i class="ph ph-book-open"></i> Undergraduate (UG) Courses</span></a></li>
                    <li><a href="courses?level=PG"><span><i class="ph ph-books"></i> Postgraduate (PG) Courses</span></a></li>
                    <li><a href="courses"><span><i class="ph ph-certificate"></i> Diploma & Certifications</span></a></li>
                  </ul>
                </div>
              </div>
              <div class="cns-nav-item">
                <a href="#"><i class="ph ph-book-open"></i> Free Prep Material <i class="ph ph-caret-right" style="margin-left:auto; font-size:0.75rem;"></i></a>
                <div class="cns-sub-panel">
                  <h4>Free Prep Material</h4>
                  <ul>
                    <li><a href="exams"><span><i class="ph ph-calendar"></i> Entrance Exams Pattern</span></a></li>
                    <li><a href="exams"><span><i class="ph ph-file-pdf"></i> Syllabus & Question Papers</span></a></li>
                  </ul>
                </div>
              </div>
              <div class="cns-nav-item">
                <a href="#"><i class="ph ph-certificate"></i> National Boards <i class="ph ph-caret-right" style="margin-left:auto; font-size:0.75rem;"></i></a>
                <div class="cns-sub-panel">
                  <h4>National Boards</h4>
                  <ul>
                    <li><a href="colleges.php?board=cbse"><span><i class="ph ph-building"></i> CBSE (Central Board)</span></a></li>
                    <li><a href="colleges.php?board=cisce"><span><i class="ph ph-compass"></i> ICSE / ISC Board</span></a></li>
                  </ul>
                </div>
              </div>
              <div class="cns-nav-item">
                <a href="#"><i class="ph ph-map-pin"></i> State Boards <i class="ph ph-caret-right" style="margin-left:auto; font-size:0.75rem;"></i></a>
                <div class="cns-sub-panel">
                  <h4>State Boards</h4>
                  <ul>
                    <li><a href="colleges.php?board=up"><span><i class="ph ph-map-trifold"></i> UP Board</span></a></li>
                    <li><a href="colleges.php?board=bihar"><span><i class="ph ph-map-trifold"></i> Bihar Board</span></a></li>
                  </ul>
                </div>
              </div>
              <div class="cns-nav-item">
                <a href="study-abroad"><i class="ph ph-globe"></i> Abroad Counseling</a>
              </div>
              <div class="cns-nav-item">
                <a href="colleges.php?recommend=1"><i class="ph ph-sparkles"></i> Recommendations <span class="cns-badge-new" style="margin-left:5px;">NEW</span></a>
              </div>
              <div class="cns-nav-item highlight-item" style="border-top:1px solid #e2e8f0; margin-top:8px; padding: 12px 16px;">
                <a href="counselling" style="color: #fff !important;"><i class="ph-fill ph-headset"></i> Get Free Counselling</a>
              </div>
            </div>
            <!-- Right side default panel -->
            <div class="cns-default-panel">
              <div>
                <i class="ph ph-hand-pointing" style="font-size: 2.2rem; color: var(--text-muted-alt); margin-bottom: 12px;"></i>
                <h4 style="font-size: 0.95rem; color: var(--oxford-navy); font-weight: 700;">Explore Counseling Services</h4>
                <p style="font-size: 0.78rem; color: var(--text-muted-alt); margin-top: 4px; max-width: 180px; line-height:1.4;">Hover over any item on the left to see details.</p>
              </div>
            </div>
          </div>
        </li>

        <li><a href="#">Admissions 2026 <span class="nav-badge-hot">LIVE</span></a></li>
        <li><a href="#">Reviews</a></li>
        <li><a href="news.php">News</a></li>
      </ul>
      <ul class="pro-sub-links-right">
        <li><a href="counselling" class="counselling-btn"><i class="ph-fill ph-headset"></i> Free Counselling <span class="pulse-dot"></span></a></li>
      </ul>
    </div>
  </div>
<script>
function toggleUserMenu() {
  const menu = document.getElementById('userMenu');
  if (menu) {
    menu.classList.toggle('open');
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
  }
});
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
</style>
</header>
