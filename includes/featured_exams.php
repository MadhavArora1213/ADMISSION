<!-- ═══ FEATURED EXAMS ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr reveal"><h2>Featured Exams 2026</h2><p>Complete info on application process, syllabus & prep tips</p></div>
    <div class="exam-feat-grid">
      <?php foreach ($examsFeatured as $fe): ?>
      <a href="#" class="exam-feat-card reveal">
        <div class="exam-feat-img"><img src="<?=$fe['img']?>" alt="<?=$fe['name']?>" loading="lazy"></div>
        <div class="exam-feat-body">
          <h3><?=$fe['name']?></h3>
          <div class="exam-feat-meta">
            <span><i class="ph ph-calendar-blank"></i> <?=$fe['date']?></span>
            <span class="etag"><?=$fe['level']?></span>
            <span class="etag"><?=$fe['type']?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
