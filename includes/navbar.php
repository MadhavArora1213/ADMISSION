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
        <a href="#" class="pro-user-btn" title="Profile"><i class="ph-fill ph-user"></i></a>
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
              <h4>Top Streams</h4>
              <ul>
                <?php foreach(array_slice($navCategories ?? [], 0, 8) as $cat): ?>
                <li><a href="#"><?=htmlspecialchars($cat['category_name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Top Locations</h4>
              <ul>
                <?php foreach(array_slice($states ?? [], 0, 8) as $st): ?>
                <li><a href="#"><?=htmlspecialchars($st['name'])?></a></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <div class="mega-col">
              <h4>Top Rated</h4>
              <ul>
                <li><a href="#">NIRF Ranked Colleges</a></li>
                <li><a href="#">Top Private Colleges</a></li>
                <li><a href="#">Top Government Colleges</a></li>
                <li><a href="#">Highest Placements</a></li>
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

        <li><a href="#">Admissions 2026</a></li>
        <li><a href="#">Reviews</a></li>
        <li><a href="#">News</a></li>
      </ul>
      <ul class="pro-sub-links-right">
        <li><a href="#" class="counselling-btn"><i class="ph-fill ph-headset"></i> Free Counselling</a></li>
      </ul>
    </div>
  </div>
</header>
