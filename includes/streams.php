<!-- ═══ STREAMS ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr reveal"><h2>Discover Disciplines</h2><p>Immersive exploration of India's top academic fields</p></div>
    <div class="bento-grid">
    <?php if (!empty($categories)): ?>
      <?php foreach ($categories as $i=>$cat): 
        $bClass = 'bento-item reveal reveal-delay-'.$i;
        if($i===0) $bClass .= ' bento-large';
        elseif($i===1 || $i===4) $bClass .= ' bento-wide';
      ?>
      <a href="#" class="<?=$bClass?>">
        <div class="stream-icon"><i class="ph ph-<?=htmlspecialchars($cat['category_slug'])?>"></i></div>
        <h3><?=htmlspecialchars($cat['category_name'])?></h3>
        <span>Explore Programs →</span>
      </a>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($catFallback as $i=>$c): 
        $bClass = 'bento-item reveal reveal-delay-'.$i;
        if($i===0) $bClass .= ' bento-large';
        elseif($i===1 || $i===4) $bClass .= ' bento-wide';
      ?>
      <a href="#" class="<?=$bClass?>">
        <div class="stream-icon"><i class="ph <?=$c['icon']?>"></i></div>
        <h3><?=$c['name']?></h3>
        <span><?=$c['count']?> Programs →</span>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </div>
</section>
