<!-- ═══ TOP RANKED TABLE ═══ -->
<section class="section-dark">
  <div class="container">
    <div class="section-hdr-flex">
      <div><h2>Elite Rankings 2026</h2><p>The pinnacle of academic excellence curated for you</p></div>
      <a href="/ADMISSION/rankings.php" class="section-link">View Full Leaderboard <i class="ph ph-arrow-right"></i></a>
    </div>

    <div class="rank-list">
      <?php
      $rankSource = !empty($rankedColleges) ? $rankedColleges : $featuredColleges;
      if (!empty($rankSource)): $rk=1; ?>
        <?php foreach (array_slice($rankSource,0,5) as $cl):
          // Smart fallbacks for package column
          if (!empty($cl['avg_package'])) {
              $pkgText = '₹'.number_format((float)$cl['avg_package'],1).'L';
              $pkgSub = 'Avg Package';
          } elseif (!empty($cl['highest_package'])) {
              $pkgText = '₹'.number_format((float)$cl['highest_package'],1).'L';
              $pkgSub = 'Highest Package';
          } elseif (!empty($cl['established_year']) && $cl['established_year'] > 1900) {
              $pkgText = 'Est. '.$cl['established_year'];
              $pkgSub = 'Founded';
          } else {
              $pkgText = ucfirst($cl['college_type'] ?? 'Institute');
              $pkgSub = 'Institute Type';
          }
          // Smart fallbacks for rating column
          if (!empty($cl['overall_rating_avg'])) {
              $ratingText = '<i class="ph-fill ph-star" style="color:#19376D"></i> '.number_format((float)$cl['overall_rating_avg'],1);
              $ratingSub = 'Rating';
          } elseif (!empty($cl['naac_grade'])) {
              $ratingText = 'NAAC '.$cl['naac_grade'];
              $ratingSub = 'Accreditation';
          } elseif (!empty($cl['total_students'])) {
              $ratingText = number_format((int)$cl['total_students']).'+';
              $ratingSub = 'Students';
          } else {
              $ratingText = ucfirst($cl['college_type'] ?? '—');
              $ratingSub = 'College Type';
          }
        ?>
        <a href="<?=collegeUrl($cl['slug'] ?? '')?>" class="rank-item">
          <div class="r-rank">#<?=sprintf("%02d", $rk++)?></div>
          <div class="r-col">
            <strong><?=htmlspecialchars($cl['name'])?></strong>
            <span><i class="ph ph-map-pin"></i> <?=htmlspecialchars($cl['city_name']??'')?><?=($cl['city_name']&&$cl['state_name'])?', ':''?><?=htmlspecialchars($cl['state_name']??'')?></span>
          </div>
          <div class="r-meta">
            <strong><?=!empty($cl['ranking_nirf'])?'NIRF Rank '.$cl['ranking_nirf']:'<span style="opacity:.4">Unranked</span>'?></strong>
            <span>National Ranking</span>
          </div>
          <div class="r-meta">
            <strong><?=$pkgText?></strong>
            <span><?=$pkgSub?></span>
          </div>
          <div class="r-meta">
            <strong><?=$ratingText?></strong>
            <span><?=$ratingSub?></span>
          </div>
        </a>
        <?php endforeach; ?>
      <?php else: $rk=1; foreach (array_slice($fColleges,0,5) as $cl): ?>
        <a href="<?=collegesUrl()?>" class="rank-item">
          <div class="r-rank">#<?=sprintf("%02d", $rk++)?></div>
          <div class="r-col">
            <strong><?=$cl['name']?></strong>
            <span><i class="ph ph-map-pin"></i> <?=$cl['loc']?></span>
          </div>
          <div class="r-meta">
            <strong>NIRF <?=$rk-1?></strong>
            <span>National Ranking</span>
          </div>
          <div class="r-meta">
            <strong><?=$cl['pkg']?></strong>
            <span>Avg Package</span>
          </div>
          <div class="r-meta">
            <strong><i class="ph-fill ph-star" style="color:#19376D"></i> <?=$cl['rating']?></strong>
            <span>Rating</span>
          </div>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>
