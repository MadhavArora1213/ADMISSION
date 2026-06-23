<!-- ═══ STREAMS — Premium Bento Grid ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr reveal">
      <div class="nh-badge" style="margin:0 auto 16px"><i class="ph-fill ph-compass"></i> Explore By Stream</div>
      <h2>Discover Disciplines</h2>
      <p>Immersive exploration of India's top academic fields</p>
    </div>
    <div class="bento-grid">
    <?php
    $streamIcons = ['engineering'=>'ph-laptop','management'=>'ph-briefcase','medical'=>'ph-stethoscope','commerce'=>'ph-chart-line','law'=>'ph-scales','arts'=>'ph-palette','design'=>'ph-palette','science'=>'ph-flask','humanities'=>'ph-books','ug'=>'ph-graduation-cap','pg'=>'phbooks','diploma'=>'ph-certificate','phd'=>'ph-flask'];
    $streamCounts = ['Engineering'=>6000,'Management'=>4500,'Medical'=>1200,'Commerce'=>3100,'Law'=>1100,'Arts & Design'=>2000,'Science'=>2500,'UG'=>8000,'PG'=>3500,'Diploma'=>1500,'PhD'=>800];
    $streamGradients = ['#0B2447,#19376D','#19376D,#1e40af','#0B2447,#0e7490','#19376D,#7c3aed','#0B2447,#be123c','#19376D,#059669'];
    if (!empty($categories)): ?>
      <?php foreach ($categories as $i=>$cat):
        $name = $cat['category_name'] ?? $cat['name'] ?? '';
        $slug = $cat['category_slug'] ?? $cat['slug'] ?? '';
        $icon = $streamIcons[strtolower($slug)] ?? 'ph-graduation-cap';
        $cnt = $streamCounts[$name] ?? $streamCounts[$slug] ?? rand(500,5000);
        $grad = $streamGradients[$i % count($streamGradients)];
        $bClass = 'bento-item reveal reveal-delay-'.$i;
        if($i===0) $bClass .= ' bento-large';
        elseif($i===1 || $i===4) $bClass .= ' bento-wide';
      ?>
      <a href="<?=coursesUrl(['level'=>$name])?>" class="<?=$bClass?>" style="--bento-grad:linear-gradient(135deg,<?=$grad?>)">
        <div class="stream-icon"><i class="ph <?=$icon?>"></i></div>
        <h3><?=htmlspecialchars($name)?></h3>
        <span><?=number_format($cnt)?>+ Programs <i class="ph ph-arrow-right" style="font-size:0.75rem"></i></span>
      </a>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($catFallback as $i=>$c):
        $grad = $streamGradients[$i % count($streamGradients)];
        $bClass = 'bento-item reveal reveal-delay-'.$i;
        if($i===0) $bClass .= ' bento-large';
        elseif($i===1 || $i===4) $bClass .= ' bento-wide';
      ?>
      <a href="<?=coursesUrl(['level'=>$c['name']])?>" class="<?=$bClass?>" style="--bento-grad:linear-gradient(135deg,<?=$grad?>)">
        <div class="stream-icon"><i class="ph <?=$c['icon']?>"></i></div>
        <h3><?=$c['name']?></h3>
        <span><?=$c['count']?> Programs <i class="ph ph-arrow-right" style="font-size:0.75rem"></i></span>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </div>
</section>