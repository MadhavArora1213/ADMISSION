<!-- ═══ STREAMS ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr reveal"><h2>Discover Disciplines</h2><p>Immersive exploration of India's top academic fields</p></div>
    <div class="bento-grid">
    <?php
    $streamIcons = ['engineering'=>'ph-laptop','management'=>'ph-briefcase','medical'=>'ph-stethoscope','commerce'=>'ph-chart-line','law'=>'ph-scales','arts'=>'ph-palette','design'=>'ph-palette','science'=>'ph-flask','humanities'=>'ph-books','ug'=>'ph-graduation-cap','pg'=>'phbooks','diploma'=>'ph-certificate','phd'=>'ph-flask'];
    $streamCounts = ['Engineering'=>6000,'Management'=>4500,'Medical'=>1200,'Commerce'=>3100,'Law'=>1100,'Arts & Design'=>2000,'Science'=>2500,'UG'=>8000,'PG'=>3500,'Diploma'=>1500,'PhD'=>800];
    if (!empty($categories)): ?>
      <?php foreach ($categories as $i=>$cat):
        $name = $cat['category_name'] ?? $cat['name'] ?? '';
        $slug = $cat['category_slug'] ?? $cat['slug'] ?? '';
        $icon = $streamIcons[strtolower($slug)] ?? 'ph-graduation-cap';
        $cnt = $streamCounts[$name] ?? $streamCounts[$slug] ?? rand(500,5000);
        $bClass = 'bento-item reveal reveal-delay-'.$i;
        if($i===0) $bClass .= ' bento-large';
        elseif($i===1 || $i===4) $bClass .= ' bento-wide';
      ?>
      <a href="<?=coursesUrl(['level'=>$name])?>" class="<?=$bClass?>">
        <div class="stream-icon"><i class="ph <?=$icon?>"></i></div>
        <h3><?=htmlspecialchars($name)?></h3>
        <span><?=number_format($cnt)?>+ Programs →</span>
      </a>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($catFallback as $i=>$c):
        $bClass = 'bento-item reveal reveal-delay-'.$i;
        if($i===0) $bClass .= ' bento-large';
        elseif($i===1 || $i===4) $bClass .= ' bento-wide';
      ?>
      <a href="<?=coursesUrl(['level'=>$c['name']])?>" class="<?=$bClass?>">
        <div class="stream-icon"><i class="ph <?=$c['icon']?>"></i></div>
        <h3><?=$c['name']?></h3>
        <span><?=$c['count']?> Programs →</span>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </div>
</section>
