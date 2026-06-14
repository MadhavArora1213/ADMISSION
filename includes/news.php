<!-- ═══ NEWS ═══ -->
<section class="section" id="news">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div><h2>Latest Education News</h2><p>Exam alerts, results, cutoffs, and admission updates</p></div>
      <a href="news.php" class="section-link">View All <i class="ph ph-arrow-right"></i></a>
    </div>
    <div class="news-grid">
      <?php foreach ($newsItems as $ni): ?>
      <article class="news-card reveal">
        <div class="news-img"><img src="<?=cImg($ni['img'] ?? '')?>" alt="<?=htmlspecialchars($ni['title'])?>" loading="lazy"><span class="news-cat-badge"><?=htmlspecialchars($ni['cat'] ?? 'News')?></span></div>
        <div class="news-body">
          <span class="news-date"><i class="ph ph-clock"></i> <?=!empty($ni['date']) ? date('d M Y', strtotime($ni['date'])) : 'Recent'?></span>
          <h3><?=htmlspecialchars($ni['title'])?></h3>
          <a href="news.php?article=<?=$ni['article_slug']?>" class="news-more">Read More <i class="ph ph-arrow-right"></i></a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
