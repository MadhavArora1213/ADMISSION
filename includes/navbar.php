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
