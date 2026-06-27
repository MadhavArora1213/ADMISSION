<!-- ═══ FEATURED COLLEGES — Premium Cards ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div>
        <div class="nh-badge" style="margin:0 0 16px"><i class="ph-fill ph-trophy"></i> Top Picks</div>
        <h2>Curated Institutions</h2>
        <p>Immersive profiles of top-tier colleges</p>
      </div>
      <a href="colleges.php" class="section-link" style="border-color:var(--border);color:var(--text)">Explore All <i class="ph ph-arrow-right"></i></a>
    </div>
    
    <div class="uni-grid">
    <?php if (!empty($featuredColleges)): $ci=0; ?>
      <?php foreach ($featuredColleges as $cl): ?>
      <a href="<?=!empty($cl['slug']) ? collegeUrl($cl['slug']) : collegesUrl()?>" class="uc-card reveal reveal-delay-<?=$ci++?>">
        <div class="uc-card-img">
          <img src="<?=cImg($cl['cover_image_url'])?>" alt="<?=htmlspecialchars($cl['name'])?>" loading="lazy">
          <div class="uc-card-gradient"></div>
          <?php if(!empty($cl['ranking_nirf'])):?>
          <span class="uc-card-rank"><span>#</span><?=$cl['ranking_nirf']?></span>
          <?php endif;?>
          <?php if(!empty($cl['overall_rating_avg']) && (float)$cl['overall_rating_avg'] > 0):?>
          <span class="uc-card-rating"><i class="ph-fill ph-star"></i> <?=number_format((float)$cl['overall_rating_avg'],1)?></span>
          <?php endif;?>
        </div>
        <div class="uc-card-body">
          <div class="uc-card-tags">
            <span class="uc-card-tag"><?=ucfirst(htmlspecialchars($cl['college_type']??'College'))?></span>
            <?php if(!empty($cl['naac_grade'])):?>
            <span class="uc-card-tag uc-card-tag-accent">NAAC <?=htmlspecialchars($cl['naac_grade'])?></span>
            <?php endif;?>
          </div>
          <h3 class="uc-card-name"><?=htmlspecialchars($cl['name'])?></h3>
          <div class="uc-card-loc"><i class="ph ph-map-pin"></i> <?=htmlspecialchars($cl['city_name']??'')?><?=($cl['city_name']&&$cl['state_name'])?', ':''?><?=htmlspecialchars($cl['state_name']??'')?></div>
          <div class="uc-card-stats">
            <div class="uc-card-stat">
              <i class="ph ph-money"></i>
              <div>
                <span class="uc-card-stat-val"><?=!empty($cl['min_fee'])?'₹'.number_format((int)$cl['min_fee']):'—'?></span>
                <span class="uc-card-stat-lbl">Avg Fee/Yr</span>
              </div>
            </div>
            <div class="uc-card-stat-divider"></div>
            <div class="uc-card-stat">
              <i class="ph ph-briefcase"></i>
              <div>
                <span class="uc-card-stat-val"><?=!empty($cl['avg_package'])?'₹'.number_format((float)$cl['avg_package'],1).'L':'—'?></span>
                <span class="uc-card-stat-lbl">Avg Package</span>
              </div>
            </div>
          </div>
          <div class="uc-card-foot">
            <span class="uc-card-cta">View Details <i class="ph ph-arrow-right"></i></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    <?php else: $ci=0; foreach ($fColleges as $cl): ?>
      <a href="<?=collegesUrl()?>" class="uc-card reveal reveal-delay-<?=$ci++?>">
        <div class="uc-card-img">
          <img src="<?=$cl['img']?>" alt="<?=$cl['name']?>" loading="lazy">
          <div class="uc-card-gradient"></div>
        </div>
        <div class="uc-card-body">
          <span class="uc-card-tag"><?=$cl['type']?></span>
          <h3 class="uc-card-name"><?=$cl['name']?></h3>
          <div class="uc-card-loc"><i class="ph ph-map-pin"></i> <?=$cl['loc']?></div>
          <div class="uc-card-stats">
            <div class="uc-card-stat">
              <i class="ph ph-money"></i>
              <div>
                <span class="uc-card-stat-val"><?=$cl['fee']?></span>
                <span class="uc-card-stat-lbl">Total Fees</span>
              </div>
            </div>
            <div class="uc-card-stat-divider"></div>
            <div class="uc-card-stat">
              <i class="ph ph-briefcase"></i>
              <div>
                <span class="uc-card-stat-val"><?=$cl['pkg']?></span>
                <span class="uc-card-stat-lbl">Avg Package</span>
              </div>
            </div>
          </div>
          <div class="uc-card-foot">
            <span class="uc-card-cta">View Details <i class="ph ph-arrow-right"></i></span>
          </div>
        </div>
      </a>
    <?php endforeach; endif; ?>
    </div>
  </div>
</section>
