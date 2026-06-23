<!-- ═══ STREAMS — Color-Coded Cards ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr reveal">
      <div class="nh-badge" style="margin:0 auto 16px"><i class="ph-fill ph-compass"></i> Explore By Stream</div>
      <h2>Discover Disciplines</h2>
      <p>Explore top academic fields with curated programs & colleges</p>
    </div>
    <div class="stream-grid">
    <?php
    $streamData = [
      'engineering' => ['icon'=>'ph-laptop','color'=>'#2563eb','bg'=>'#eff6ff','count'=>'6,000+'],
      'management'  => ['icon'=>'ph-briefcase','color'=>'#7c3aed','bg'=>'#f5f3ff','count'=>'4,500+'],
      'medical'     => ['icon'=>'ph-stethoscope','color'=>'#059669','bg'=>'#ecfdf5','count'=>'1,200+'],
      'commerce'    => ['icon'=>'ph-chart-line','color'=>'#d97706','bg'=>'#fffbeb','count'=>'3,100+'],
      'law'         => ['icon'=>'ph-scales','color'=>'#dc2626','bg'=>'#fef2f2','count'=>'1,100+'],
      'arts'        => ['icon'=>'ph-palette','color'=>'#e11d48','bg'=>'#fff1f2','count'=>'2,000+'],
      'science'     => ['icon'=>'ph-flask','color'=>'#0891b2','bg'=>'#ecfeff','count'=>'2,500+'],
      'design'      => ['icon'=>'ph-magic-wand','color'=>'#9333ea','bg'=>'#faf5ff','count'=>'1,800+'],
    ];
    $streamFallback = [
      ['name'=>'Engineering','slug'=>'engineering','icon'=>'ph-laptop'],
      ['name'=>'Management','slug'=>'management','icon'=>'ph-briefcase'],
      ['name'=>'Medical','slug'=>'medical','icon'=>'ph-stethoscope'],
      ['name'=>'Commerce','slug'=>'commerce','icon'=>'ph-chart-line'],
      ['name'=>'Law','slug'=>'law','icon'=>'ph-scales'],
      ['name'=>'Arts & Design','slug'=>'arts','icon'=>'ph-palette'],
      ['name'=>'Science','slug'=>'science','icon'=>'ph-flask'],
    ];
    if (!empty($categories)): ?>
      <?php foreach ($categories as $i=>$cat):
        $name = $cat['category_name'] ?? $cat['name'] ?? '';
        $slug = strtolower($cat['category_slug'] ?? $cat['slug'] ?? '');
        $sd = $streamData[$slug] ?? ['icon'=>'ph-graduation-cap','color'=>'#0B2447','bg'=>'#f0f4ff','count'=>rand(500,5000).'+'];
      ?>
      <a href="<?=coursesUrl(['level'=>$name])?>" class="stream-card reveal reveal-delay-<?=$i?>" style="--sc-color:<?=$sd['color']?>;--sc-bg:<?=$sd['bg']?>">
        <div class="sc-icon"><i class="ph <?=$sd['icon']?>"></i></div>
        <div class="sc-info">
          <h3><?=htmlspecialchars($name)?></h3>
          <span><?=$sd['count']?> Programs</span>
        </div>
        <div class="sc-arrow"><i class="ph ph-arrow-right"></i></div>
      </a>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($streamFallback as $i=>$c):
        $sd = $streamData[$c['slug']] ?? ['icon'=>$c['icon'],'color'=>'#0B2447','bg'=>'#f0f4ff','count'=>rand(500,5000).'+'];
      ?>
      <a href="<?=coursesUrl(['level'=>$c['name']])?>" class="stream-card reveal reveal-delay-<?=$i?>" style="--sc-color:<?=$sd['color']?>;--sc-bg:<?=$sd['bg']?>">
        <div class="sc-icon"><i class="ph <?=$sd['icon']?>"></i></div>
        <div class="sc-info">
          <h3><?=$c['name']?></h3>
          <span><?=$sd['count']?> Programs</span>
        </div>
        <div class="sc-arrow"><i class="ph ph-arrow-right"></i></div>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </div>
</section>