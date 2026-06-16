<?php
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
}
?>
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
        <a href="#" class="pro-icon-btn" title="Saved"><i class="ph ph-heart"></i></a>
        <a href="#" class="pro-icon-btn" title="Notifications"><i class="ph ph-bell"></i></a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <span class="pro-nav-link" style="font-weight:700; color:var(--primary);"><i class="ph ph-user"></i> <?= htmlspecialchars($_SESSION['user_name']) ?></span>
          <form method="POST" action="news_details.php?slug=top-10-engineering-colleges-2026-v2" style="display:inline;">
            <input type="hidden" name="action" value="mock_logout">
            <button type="submit" class="pro-icon-btn" title="Logout" style="border:none; cursor:pointer; background:none;"><i class="ph ph-sign-out" style="color:#ef4444;"></i></button>
          </form>
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
          <a href="#">Colleges <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu">
            <div class="mega-col">
              <h4>Top Courses</h4>
              <ul>
                <?php foreach($navPopularCourses ?? [] as $course): ?>
                <li><a href="#"><?=htmlspecialchars($course['course_name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Top Locations</h4>
              <ul>
                <?php foreach($navStates ?? [] as $st): ?>
                <li><a href="#"><?=htmlspecialchars($st['name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Top Colleges</h4>
              <ul>
                <?php foreach($navColleges ?? [] as $college): ?>
                <li><a href="#"><?=htmlspecialchars($college['name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        </li>

        <li class="pro-has-mega">
          <a href="#">Exams <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu">
            <div class="mega-col">
              <h4>Top UG Exams</h4>
              <ul>
                <?php foreach($navExamsUg ?? [] as $ex): ?>
                <li><a href="#"><?=htmlspecialchars($ex['exam_name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Top PG Exams</h4>
              <ul>
                <?php foreach($navExamsPg ?? [] as $ex): ?>
                <li><a href="#"><?=htmlspecialchars($ex['exam_name'])?></a></li>
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
          <a href="#">Courses <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu">
            <div class="mega-col">
              <h4>Top UG Courses</h4>
              <ul>
                <?php foreach($navCoursesUg ?? [] as $co): ?>
                <li><a href="#"><?=htmlspecialchars($co['course_name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Top PG Courses</h4>
              <ul>
                <?php foreach($navCoursesPg ?? [] as $co): ?>
                <li><a href="#"><?=htmlspecialchars($co['course_name'])?></a></li>
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
          <a href="#">Study Abroad <i class="ph ph-caret-down"></i></a>
          <div class="pro-mega-menu">
            <div class="mega-col">
              <h4>Top Destinations</h4>
              <ul>
                <?php foreach($navCountries ?? [] as $cn): ?>
                <li><a href="#"><?=htmlspecialchars($cn['name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>International Exams</h4>
              <ul>
                <li><a href="#">IELTS</a></li>
                <li><a href="#">TOEFL</a></li>
                <li><a href="#">GRE</a></li>
                <li><a href="#">GMAT</a></li>
                <li><a href="#">SAT</a></li>
              </ul>
            </div>
          </div>
        </li>

        <li><a href="#">Admissions 2026 <span class="nav-badge-hot">LIVE</span></a></li>
        <li><a href="#">Reviews</a></li>
        <li><a href="news.php">News</a></li>
      </ul>
      <ul class="pro-sub-links-right">
        <li><a href="#" class="counselling-btn"><i class="ph-fill ph-headset"></i> Free Counselling <span class="pulse-dot"></span></a></li>
      </ul>
    </div>
  </div>
</header>
