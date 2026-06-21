<!-- ═══ FEATURED EXAMS ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr reveal"><h2>Featured Exams 2026</h2><p>Complete info on application process, syllabus & prep tips</p></div>
    <div class="exam-feat-grid">
      <?php foreach ($examsFeatured as $fe): ?>
      <a href="<?=examUrl($fe['slug'] ?? '')?>" class="exam-feat-card reveal">
        <div class="exam-feat-img"><img src="<?=cImg($fe['img'] ?? '')?>" alt="<?=htmlspecialchars($fe['name'])?>" loading="lazy"></div>
        <div class="exam-feat-body">
          <h3><?=htmlspecialchars($fe['name'])?></h3>
          <div class="exam-feat-meta">
            <?php if(!empty($fe['exam_date'])):?><span><i class="ph ph-calendar-blank"></i> <?=date('d M Y', strtotime($fe['exam_date']))?></span><?php endif;?>
            <span class="etag"><?=htmlspecialchars($fe['level'] ?? 'National')?></span>
            <span class="etag"><?=htmlspecialchars($fe['exam_mode'] ?? 'Offline')?></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
