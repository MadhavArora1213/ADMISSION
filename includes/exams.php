<!-- ═══ EXAMS ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div><h2>Top Entrance Exams</h2><p>Dates, application deadlines & participating colleges</p></div>
      <a href="#" class="section-link">View All <i class="ph ph-arrow-right"></i></a>
    </div>
    <div class="exams-grid">
    <?php if (!empty($upcomingExams)): ?>
      <?php foreach ($upcomingExams as $ex): ?>
      <div class="exam-card reveal">
        <div class="exam-card-top">
          <div class="exam-icon"><i class="ph ph-pencil-line"></i></div>
          <div><h3><?=htmlspecialchars($ex['name'])?></h3><span class="etag"><?=htmlspecialchars($ex['level']??'National')?></span></div>
        </div>
        <div class="exam-body">
          <?php if(!empty($ex['exam_date'])):?><div><i class="ph ph-calendar-blank"></i><strong>Exam:</strong> <?=date('d M Y',strtotime($ex['exam_date']))?></div><?php endif;?>
          <?php if(!empty($ex['application_end'])):?><div><i class="ph ph-clock-countdown"></i><strong>Last Date:</strong> <?=date('d M Y',strtotime($ex['application_end']))?></div><?php endif;?>
        </div>
        <a href="#" class="exam-link">Details <i class="ph ph-arrow-right"></i></a>
      </div>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($fExams as $ex): ?>
      <div class="exam-card reveal">
        <div class="exam-card-top">
          <div class="exam-icon"><i class="ph ph-pencil-line"></i></div>
          <div><h3><?=$ex['name']?></h3><span class="etag"><?=$ex['level']?></span></div>
        </div>
        <div class="exam-body">
          <div><i class="ph ph-calendar-blank"></i><strong>Exam:</strong> <?=$ex['date']?></div>
          <div><i class="ph ph-clock-countdown"></i><strong>Last Date:</strong> <?=$ex['last']?></div>
          <div><i class="ph ph-buildings"></i><strong>Colleges:</strong> <?=$ex['cols']?></div>
        </div>
        <a href="#" class="exam-link">Details <i class="ph ph-arrow-right"></i></a>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </div>
</section>
