<!-- ═══ TOP RANKED TABLE ═══ -->
<section class="section-dark">
  <div class="container">
    <div class="section-hdr-flex">
      <div><h2>Elite Rankings 2026</h2><p>The pinnacle of academic excellence curated for you</p></div>
      <a href="<?=collegesUrl()?>" class="section-link">View Full Leaderboard <i class="ph ph-arrow-right"></i></a>
    </div>

    <div class="rank-list">
      <?php if (!empty($featuredColleges)): $rk=1; ?>
        <?php foreach (array_slice($featuredColleges,0,5) as $cl): ?>
        <a href="<?=collegeUrl($cl['slug'] ?? '')?>" class="rank-item">
          <div class="r-rank">#<?=sprintf("%02d", $rk++)?></div>
          <div class="r-col">
            <strong><?=htmlspecialchars($cl['name'])?></strong>
            <span><i class="ph ph-map-pin"></i> <?=htmlspecialchars($cl['city_name']??'')?><?=($cl['city_name']&&$cl['state_name'])?', ':''?><?=htmlspecialchars($cl['state_name']??'')?></span>
          </div>
          <div class="r-meta">
            <strong><?=!empty($cl['ranking_nirf'])?'NIRF Rank '.$cl['ranking_nirf']:'Unranked'?></strong>
            <span>National Ranking</span>
          </div>
          <div class="r-meta">
            <strong><?=!empty($cl['avg_package'])?'₹'.number_format((float)$cl['avg_package'],1).'L':'N/A'?></strong>
            <span>Avg Package</span>
          </div>
          <div class="r-meta">
            <strong><?php if(!empty($cl['overall_rating_avg'])):?><i class="ph-fill ph-star" style="color:#19376D"></i> <?=number_format((float)$cl['overall_rating_avg'],1)?><?php else:?>N/A<?php endif;?></strong>
            <span>Rating</span>
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
