<!-- ═══ FEATURED SCHOOLS ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr-flex reveal">
      <div>
        <div class="nh-badge" style="margin:0 0 16px"><i class="ph-fill ph-school"></i> Top Schools</div>
        <h2>Featured Schools</h2>
        <p>Explore top-rated schools across India</p>
      </div>
      <a href="<?= schoolsUrl() ?>" class="section-link" style="border-color:var(--border);color:var(--text)">Explore All <i class="ph ph-arrow-right"></i></a>
    </div>

    <?php if (!empty($featuredSchools)): ?>
    <div class="uni-grid">
      <?php foreach ($featuredSchools as $si => $sch):
        $schLocation = trim(($sch['city_name'] ?? '') . ($sch['city_name'] && $sch['state_name'] ? ', ' : '') . ($sch['state_name'] ?? ''));
        $schRating = (float)($sch['overall_rating_avg'] ?? 0);
      ?>
      <a href="<?= schoolUrl($sch['slug']) ?>" class="uc-card reveal reveal-delay-<?= $si ?>">
        <div class="uc-card-img">
          <img src="<?= cImg($sch['cover_image_url'] ?? '') ?>" alt="<?= htmlspecialchars($sch['name']) ?>" loading="lazy">
          <div class="uc-card-gradient"></div>
          <?php if ($schRating > 0): ?>
          <span class="uc-card-rating"><i class="ph-fill ph-star"></i> <?= number_format($schRating, 1) ?></span>
          <?php endif; ?>
        </div>
        <div class="uc-card-body">
          <div class="uc-card-tags">
            <span class="uc-card-tag"><?= htmlspecialchars(schoolTypeLabel($sch['school_type'])) ?></span>
            <?php if (!empty($sch['board_affiliation'])): ?>
            <span class="uc-card-tag uc-card-tag-accent"><?= htmlspecialchars(schoolBoardLabel($sch['board_affiliation'])) ?></span>
            <?php endif; ?>
          </div>
          <h3 class="uc-card-name"><?= htmlspecialchars($sch['name']) ?></h3>
          <?php if ($schLocation): ?>
          <div class="uc-card-loc"><i class="ph ph-map-pin"></i> <?= htmlspecialchars($schLocation) ?></div>
          <?php endif; ?>
          <div class="uc-card-stats">
            <div class="uc-card-stat">
              <i class="ph ph-users"></i>
              <div>
                <span class="uc-card-stat-val"><?= !empty($sch['total_students']) ? number_format((int)$sch['total_students']) : '—' ?></span>
                <span class="uc-card-stat-lbl">Students</span>
              </div>
            </div>
            <div class="uc-card-stat-divider"></div>
            <div class="uc-card-stat">
              <i class="ph ph-calendar"></i>
              <div>
                <span class="uc-card-stat-val"><?= !empty($sch['established_year']) ? (int)$sch['established_year'] : '—' ?></span>
                <span class="uc-card-stat-lbl">Est.</span>
              </div>
            </div>
          </div>
          <div class="uc-card-foot">
            <span class="uc-card-cta">View Details <i class="ph ph-arrow-right"></i></span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:60px 20px;color:#94a3b8">
      <i class="ph ph-graduation-cap" style="font-size:3rem;display:block;margin-bottom:12px;opacity:.15"></i>
      <p>No featured schools yet. Check back soon!</p>
    </div>
    <?php endif; ?>
  </div>
</section>
