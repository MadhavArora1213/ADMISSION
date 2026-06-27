<!-- ═══ TOP ENTRANCE EXAMS ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div><h2>Top Entrance Exams</h2><p>Dates, application deadlines & participating colleges</p></div>
      <a href="<?=examsUrl()?>" class="section-link">View All <i class="ph ph-arrow-right"></i></a>
    </div>
    <?php
    $examList = !empty($upcomingExams) ? $upcomingExams : $fExams;
    ?>
    <div class="exams-grid">
    <?php foreach ($examList as $i => $ex):
      $name = htmlspecialchars($ex['name'] ?? $ex['exam_name'] ?? '');
      $slug = $ex['slug'] ?? $ex['exam_slug'] ?? '';
      $abbr = $ex['abbr'] ?? $ex['exam_abbreviation'] ?? '';
      $abbrText = $abbr ?: strtoupper(substr(preg_replace('/\s+/','', $name), 0, min(6, strlen($name))));
      $level = ucfirst(htmlspecialchars($ex['level'] ?? $ex['exam_level'] ?? 'National'));
      $colleges = $ex['colleges'] ?? $ex['participating_colleges_count'] ?? 0;
      $applicants = $ex['applicants'] ?? $ex['applicants_last_year'] ?? 0;
      $examDate = $ex['exam_date'] ?? null;
      $appEnd = $ex['app_end'] ?? $ex['application_end'] ?? null;
      $today = date('Y-m-d');
      $appStart = $ex['app_start'] ?? $ex['application_start'] ?? null;
      $appOpen = ($appStart && $appStart <= $today && (!$appEnd || $appEnd >= $today));
      $appClosed = ($appEnd && $appEnd < $today);
      $appStatus = $appOpen ? 'open' : ($appClosed ? 'closed' : 'upcoming');
      $appLabel = $appOpen ? 'Registrations Open' : ($appClosed ? 'Closed' : 'Upcoming');
      $daysLeft = $examDate ? max(0, (int)((strtotime($examDate) - strtotime($today)) / 86400)) : null;
    ?>
      <a href="<?=examUrl($slug)?>" class="xm-card reveal reveal-delay-<?=min($i+1, 6)?>">
        <div class="xm-header">
          <span class="xm-abbr"><?=$abbrText?></span>
          <?php if($daysLeft !== null && $daysLeft > 0): ?>
          <span class="xm-countdown"><i class="ph ph-timer"></i> <?=$daysLeft?>d left</span>
          <?php elseif($daysLeft === 0): ?>
          <span class="xm-countdown xm-today"><i class="ph ph-lightning"></i> Live</span>
          <?php endif; ?>
        </div>
        <div class="xm-body">
          <div class="xm-title-row">
            <h3><?=$name?></h3>
            <span class="xm-level"><?=$level?></span>
          </div>
          <div class="xm-status <?=$appStatus?>"><?=$appLabel?></div>
          <div class="xm-dates">
            <?php if($examDate): ?>
            <div class="xm-date-item">
              <i class="ph ph-exam"></i>
              <div>
                <span class="xm-date-main"><?=date('d M Y', strtotime($examDate))?></span>
                <span class="xm-date-sub">Exam Date</span>
              </div>
            </div>
            <?php endif; ?>
            <?php if($appEnd): ?>
            <div class="xm-date-item">
              <i class="ph ph-calendar-check"></i>
              <div>
                <span class="xm-date-main"><?=date('d M Y', strtotime($appEnd))?></span>
                <span class="xm-date-sub">Apply Before</span>
              </div>
            </div>
            <?php endif; ?>
          </div>
          <div class="xm-footer">
            <?php if($colleges > 0): ?>
            <div class="xm-stat"><i class="ph ph-buildings"></i><span><?=number_format($colleges)?>+</span></div>
            <?php endif; ?>
            <?php if($applicants > 0): ?>
            <div class="xm-stat"><i class="ph ph-users-three"></i><span><?=number_format($applicants / 100000, 1)?>L</span></div>
            <?php endif; ?>
            <span class="xm-cta">Details <i class="ph ph-arrow-right"></i></span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
    </div>
  </div>
</section>
