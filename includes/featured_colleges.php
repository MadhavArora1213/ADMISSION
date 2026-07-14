<!-- ═══ FEATURED COLLEGES — Hero-Style Carousel ═══ -->
<section class="section featured-carousel-section">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div>
        <div class="nh-badge" style="margin:0 0 16px"><i class="ph-fill ph-trophy"></i> Top Picks</div>
        <h2>Curated Institutions</h2>
        <p>Immersive profiles of top-tier colleges</p>
      </div>
      <a href="colleges.php" class="section-link" style="border-color:var(--border);color:var(--text)">Explore All <i class="ph ph-arrow-right"></i></a>
    </div>
  </div>

  <div class="fc-carousel" id="fcCarousel">
    <?php if (!empty($featuredColleges)): ?>
      <?php foreach ($featuredColleges as $fi => $cl):
        $fcImg = $cl['cover_image_url'] ?? '';
        $fcName = htmlspecialchars($cl['name'] ?? '');
        $fcCity = $cl['city_name'] ?? '';
        $fcState = $cl['state_name'] ?? '';
        $fcLoc = trim($fcCity . ($fcCity && $fcState ? ', ' : '') . $fcState);
        $fcRating = (float)($cl['overall_rating_avg'] ?? 0);
        $fcType = ucfirst(htmlspecialchars($cl['college_type'] ?? 'College'));
        $fcNaac = $cl['naac_grade'] ?? '';
        $fcNirf = $cl['ranking_nirf'] ?? '';
        $fcFee = $cl['min_fee'] ?? '';
        $fcPkg = $cl['avg_package'] ?? '';
        $fcUrl = !empty($cl['slug']) ? collegeUrl($cl['slug']) : collegesUrl();
      ?>
      <div class="fc-slide <?= $fi === 0 ? 'active' : '' ?>" data-index="<?= $fi ?>">
        <div class="fc-slide-bg" style="background-image:url('<?= htmlspecialchars($fcImg ?: 'https://images.unsplash.com/photo-1562774053-701939374585?w=1920&q=80') ?>')"></div>
        <div class="fc-slide-overlay"></div>
        <div class="fc-slide-content">
          <div class="fc-slide-tags">
            <span class="fc-slide-tag"><?= $fcType ?></span>
            <?php if (!empty($fcNaac)): ?>
            <span class="fc-slide-tag fc-slide-tag-accent">NAAC <?= htmlspecialchars($fcNaac) ?></span>
            <?php endif; ?>
            <?php if (!empty($fcNirf)): ?>
            <span class="fc-slide-tag fc-slide-tag-rank">#<?= htmlspecialchars($fcNirf) ?> NIRF</span>
            <?php endif; ?>
          </div>
          <a href="<?= $fcUrl ?>" class="fc-slide-link">
            <strong><?= $fcName ?></strong>
            <?php if ($fcLoc): ?>
            <span><i class="ph ph-map-pin"></i> <?= htmlspecialchars($fcLoc) ?></span>
            <?php endif; ?>
            <span class="fc-slide-meta">
              <?php if ($fcRating > 0): ?>
              <span class="fc-slide-rating"><i class="ph-fill ph-star"></i> <?= number_format($fcRating, 1) ?></span>
              <?php endif; ?>
              <?php if (!empty($fcFee)): ?>
              <span class="fc-slide-stat"><i class="ph ph-money"></i> ₹<?= number_format((int)$fcFee) ?>/yr</span>
              <?php endif; ?>
              <?php if (!empty($fcPkg)): ?>
              <span class="fc-slide-stat"><i class="ph ph-briefcase"></i> ₹<?= number_format((float)$fcPkg, 1) ?>L pkg</span>
              <?php endif; ?>
              <span class="fc-slide-view">(view details)</span>
            </span>
          </a>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if (count($featuredColleges) > 1): ?>
      <div class="fc-dots" id="fcDots">
        <?php foreach ($featuredColleges as $di => $_): ?>
        <button class="fc-dot <?= $di === 0 ? 'active' : '' ?>" data-idx="<?= $di ?>"></button>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="fc-slide active" data-index="0">
        <div class="fc-slide-bg" style="background-image:url('https://images.unsplash.com/photo-1562774053-701939374585?w=1920&q=80')"></div>
        <div class="fc-slide-overlay"></div>
        <div class="fc-slide-content">
          <a href="<?= collegesUrl() ?>" class="fc-slide-link">
            <strong>Explore Top Colleges</strong>
            <span>Discover 26+ best institutions across India</span>
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<script>
(function(){
  const slides = document.querySelectorAll('#fcCarousel .fc-slide');
  const dots = document.querySelectorAll('#fcCarousel .fc-dot');
  let current = 0, interval = null;

  function goTo(idx) {
    slides.forEach(s => s.classList.remove('active'));
    dots.forEach(d => d.classList.remove('active'));
    if (slides[idx]) slides[idx].classList.add('active');
    if (dots[idx]) dots[idx].classList.add('active');
    current = idx;
  }
  function next() { goTo((current + 1) % Math.max(slides.length, 1)); }
  if (slides.length > 1) {
    interval = setInterval(next, 5000);
    dots.forEach(d => d.addEventListener('click', () => {
      clearInterval(interval);
      goTo(parseInt(d.dataset.idx));
      interval = setInterval(next, 5000);
    }));
  }
})();
</script>
