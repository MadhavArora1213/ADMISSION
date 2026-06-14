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
