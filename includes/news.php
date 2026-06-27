<!-- ═══ NEWS — Editorial ═══ -->
<section class="section news-editorial" id="news">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div><h2>Latest Education News</h2><p>Exam alerts, results, cutoffs, and admission updates</p></div>
      <a href="news.php" class="section-link">View All <i class="ph ph-arrow-right"></i></a>
    </div>

    <?php if (!empty($newsItems)): ?>
    <?php $featured = $newsItems[0]; $rest = array_slice($newsItems, 1); ?>

    <a href="news.php?article=<?=$featured['article_slug']?>" class="ne-hero reveal">
      <div class="ne-hero-bg">
        <img src="<?=cImg($featured['img'] ?? '')?>" alt="<?=htmlspecialchars($featured['title'])?>" loading="lazy">
        <div class="ne-hero-grad"></div>
      </div>
      <div class="ne-hero-content">
        <div class="ne-hero-tags">
          <span class="ne-pill ne-pill-live"><span class="ne-dot"></span> Latest</span>
          <span class="ne-pill"><?=htmlspecialchars($featured['cat'] ?? 'Education')?></span>
        </div>
        <h3><?=htmlspecialchars($featured['title'])?></h3>
        <div class="ne-hero-meta">
          <span><i class="ph ph-calendar-blank"></i> <?=!empty($featured['date']) ? date('d M Y', strtotime($featured['date'])) : 'Recent'?></span>
          <span><i class="ph ph-clock"></i> 3 min read</span>
        </div>
        <span class="ne-read-btn">Read Full Story <i class="ph ph-arrow-right"></i></span>
      </div>
    </a>

    <div class="ne-grid">
      <?php
      $neIcons = ['ph-graduation-cap', 'ph-book-open', 'ph-lightbulb', 'ph-chart-line-up'];
      foreach ($rest as $i => $ni):
        $neI = $neIcons[$i % count($neIcons)];
      ?>
      <a href="news.php?article=<?=$ni['article_slug']?>" class="ne-card reveal" style="--delay:<?= $i * 0.08 ?>s">
        <div class="ne-card-top">
          <img src="<?=cImg($ni['img'] ?? '')?>" alt="<?=htmlspecialchars($ni['title'])?>" loading="lazy">
          <div class="ne-card-img-grad"></div>
          <div class="ne-card-icon"><i class="ph <?=$neI?>"></i></div>
        </div>
        <div class="ne-card-body">
          <span class="ne-pill-sm"><?=htmlspecialchars($ni['cat'] ?? 'Education')?></span>
          <h4><?=htmlspecialchars($ni['title'])?></h4>
          <span class="ne-card-date"><i class="ph ph-clock"></i> <?=!empty($ni['date']) ? date('d M Y', strtotime($ni['date'])) : 'Recent'?></span>
        </div>
        <div class="ne-card-arrow"><i class="ph ph-arrow-up-right"></i></div>
      </a>
      <?php endforeach; ?>
    </div>

    <?php endif; ?>
  </div>
</section>
