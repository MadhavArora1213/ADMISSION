<!-- ═══ FEATURED COLLEGES ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div><h2>Curated Institutions</h2><p>Immersive profiles of top-tier colleges</p></div>
      <a href="colleges.php" class="section-link" style="border-color:var(--border);color:var(--text)">Explore All <i class="ph ph-arrow-right"></i></a>
    </div>
    
    <div class="uni-grid">
    <?php if (!empty($featuredColleges)): $ci=0; ?>
      <?php foreach ($featuredColleges as $cl): ?>
      <a href="<?= !empty($cl['slug']) ? 'college.php?slug=' . urlencode($cl['slug']) : 'colleges.php' ?>" class="uni-card-premium reveal reveal-delay-<?=$ci++?>">
        <img src="<?=cImg($cl['cover_image_url'])?>" class="ucp-bg" alt="<?=htmlspecialchars($cl['name'])?>" loading="lazy">
        <div class="ucp-overlay">
          <div class="ucp-tags">
            <span class="ucp-tag"><?=ucfirst(htmlspecialchars($cl['college_type']??'College'))?></span>
            <?php if(!empty($cl['naac_grade'])):?><span class="ucp-tag">NAAC <?=htmlspecialchars($cl['naac_grade'])?></span><?php endif;?>
          </div>
          <div class="ucp-content">
            <h3 class="ucp-title"><?=htmlspecialchars($cl['name'])?></h3>
            <div class="ucp-loc"><i class="ph ph-map-pin"></i> <?=htmlspecialchars($cl['city_name']??'')?><?=($cl['city_name']&&$cl['state_name'])?', ':''?><?=htmlspecialchars($cl['state_name']??'')?></div>
            <div class="ucp-metrics">
              <div><strong><?=!empty($cl['min_fee'])?'₹'.number_format((int)$cl['min_fee']):'—'?></strong><span>Avg Fee/Yr</span></div>
              <div><strong><?=!empty($cl['avg_package'])?'₹'.number_format((float)$cl['avg_package'],1).'L':'—'?></strong><span>Avg Package</span></div>
            </div>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    <?php else: $ci=0; foreach ($fColleges as $cl): ?>
      <a href="<?= !empty($cl['slug']) ? 'college.php?slug=' . urlencode($cl['slug']) : 'colleges.php' ?>" class="uni-card-premium reveal reveal-delay-<?=$ci++?>">
        <img src="<?=$cl['img']?>" class="ucp-bg" alt="<?=$cl['name']?>" loading="lazy">
        <div class="ucp-overlay">
          <div class="ucp-tags"><span class="ucp-tag"><?=$cl['type']?></span></div>
          <div class="ucp-content">
            <h3 class="ucp-title"><?=$cl['name']?></h3>
            <div class="ucp-loc"><i class="ph ph-map-pin"></i> <?=$cl['loc']?></div>
            <div class="ucp-metrics">
              <div><strong><?=$cl['fee']?></strong><span>Total Fees</span></div>
              <div><strong><?=$cl['pkg']?></strong><span>Avg Package</span></div>
            </div>
          </div>
        </div>
      </a>
    <?php endforeach; endif; ?>
    </div>
  </div>
</section>
