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
      <a href="<?= schoolUrl($sch['slug']) ?>" class="school-card reveal reveal-delay-<?= $si ?>">
        <div class="school-card-img">
          <img src="<?= cImg($sch['cover_image_url'] ?? '') ?>" alt="<?= htmlspecialchars($sch['name']) ?>" loading="lazy">
          <div class="school-card-badge">
            <?php if ($schRating > 0): ?>
            <span class="badge-rating"><i class="ph-fill ph-star"></i> <?= number_format($schRating, 1) ?></span>
            <?php endif; ?>
            <span class="badge-type"><?= htmlspecialchars(schoolTypeLabel($sch['school_type'])) ?></span>
          </div>
        </div>
        <div class="school-card-body">
          <div class="school-card-tags">
            <?php if (!empty($sch['board_affiliation'])): ?>
            <span class="school-card-tag school-card-tag-board"><?= htmlspecialchars(schoolBoardLabel($sch['board_affiliation'])) ?></span>
            <?php endif; ?>
          </div>
          <h3 class="school-card-name"><?= htmlspecialchars($sch['name']) ?></h3>
          <?php if ($schLocation): ?>
          <div class="school-card-loc"><i class="ph ph-map-pin"></i> <?= htmlspecialchars($schLocation) ?></div>
          <?php endif; ?>
          <div class="school-card-stats">
            <div class="school-card-stat">
              <div class="school-card-stat-icon"><i class="ph ph-users"></i></div>
              <div class="school-card-stat-content">
                <span class="school-card-stat-val"><?= !empty($sch['total_students']) ? number_format((int)$sch['total_students']) : '—' ?></span>
                <span class="school-card-stat-lbl">Students</span>
              </div>
            </div>
            <div class="school-card-stat">
              <div class="school-card-stat-icon"><i class="ph ph-calendar-blank"></i></div>
              <div class="school-card-stat-content">
                <span class="school-card-stat-val"><?= !empty($sch['established_year']) ? (int)$sch['established_year'] : '—' ?></span>
                <span class="school-card-stat-lbl">Established</span>
              </div>
            </div>
          </div>
          <div class="school-card-foot">
            <span class="school-card-cta">View Details <i class="ph ph-arrow-right"></i></span>
            <button class="school-card-save" title="Save school"><i class="ph ph-bookmark-simple"></i></button>
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
