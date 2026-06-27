<!-- ═══ POPULAR COURSES ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div><h2>Popular Courses</h2><p>Explore in-demand programs with top career prospects</p></div>
      <a href="<?=coursesUrl()?>" class="section-link">View All <i class="ph ph-arrow-right"></i></a>
    </div>
    <?php
    $courseIcons = ['ph-laptop','ph-briefcase','ph-code','ph-chart-line-up','ph-graduation-cap','ph-flask','ph-presentation-chart','ph-currency-circle-dollar'];
    ?>
    <div class="courses-grid">
    <?php if (!empty($popularCourses)): ?>
      <?php foreach ($popularCourses as $i => $co):
        $name = htmlspecialchars($co['course_name']);
        $slug = $co['course_slug'] ?? '';
        $level = strtoupper(htmlspecialchars($co['course_level'] ?? 'UG'));
        $dur = (int)($co['duration_years'] ?? 0);
        $colleges = (int)($co['total_colleges_offering'] ?? 0);
        $salary = (float)($co['avg_salary_lpa'] ?? 0);
      ?>
      <a href="<?=courseUrl($slug)?>" class="cs-card reveal reveal-delay-<?=min($i+1, 6)?>">
        <div class="cs-accent"></div>
        <div class="cs-inner">
          <div class="cs-top">
            <div class="cs-icon"><i class="ph <?=$courseIcons[$i % count($courseIcons)]?>"></i></div>
            <span class="cs-level"><?=$level?></span>
          </div>
          <h3><?=$name?></h3>
          <div class="cs-chips">
            <?php if($dur > 0): ?><span class="cs-chip"><i class="ph ph-clock"></i><?=$dur?> yr<?=$dur>1?'s':''?></span><?php endif; ?>
            <?php if($colleges > 0): ?><span class="cs-chip"><i class="ph ph-buildings"></i><?=number_format($colleges)?>+</span><?php endif; ?>
            <?php if($salary > 0): ?><span class="cs-chip cs-salary-chip"><i class="ph ph-trend-up"></i>₹<?=$salary?> LPA</span><?php endif; ?>
          </div>
          <span class="cs-cta">Explore <i class="ph ph-arrow-right"></i></span>
        </div>
      </a>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($fCourses as $i => $co): ?>
      <a href="<?=coursesUrl()?>" class="cs-card reveal">
        <div class="cs-accent"></div>
        <div class="cs-inner">
          <div class="cs-top">
            <div class="cs-icon"><i class="ph <?=$co['icon']?>"></i></div>
            <span class="cs-level"><?=$co['level']?></span>
          </div>
          <h3><?=$co['name']?></h3>
          <div class="cs-chips">
            <span class="cs-chip"><i class="ph ph-clock"></i><?=$co['dur']?></span>
            <span class="cs-chip"><i class="ph ph-buildings"></i><?=$co['cols']?></span>
          </div>
          <span class="cs-cta">Explore <i class="ph ph-arrow-right"></i></span>
        </div>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </div>
</section>
