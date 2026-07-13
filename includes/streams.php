<!-- ═══ STREAMS — Premium Icon Grid ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr reveal">
      <div class="nh-badge" style="margin:0 auto 16px"><i class="ph-fill ph-compass"></i> Explore By Stream</div>
      <h2>Discover Disciplines</h2>
      <p>Explore top academic fields with curated programs & colleges</p>
    </div>
    <div class="stream-grid">
    <?php
    // Dynamic stream counts from database
    $streamMap = [
      'engineering' => ['icon'=>'ph-laptop','color'=>'#2563eb','bg'=>'#eff6ff','accent'=>'#dbeafe','categories'=>['Engineering','IT & Software'],'desc'=>'Tech, CS, IT & more','img'=>'https://images.unsplash.com/photo-1518770660439-4636190af475?w=600&q=80'],
      'management'  => ['icon'=>'ph-briefcase','color'=>'#7c3aed','bg'=>'#f5f3ff','accent'=>'#ede9fe','categories'=>['Management','Commerce'],'desc'=>'MBA, BBA & Commerce','img'=>'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600&q=80'],
      'medical'     => ['icon'=>'ph-stethoscope','color'=>'#059669','bg'=>'#ecfdf5','accent'=>'#d1fae5','categories'=>['Medical','Nursing'],'desc'=>'MBBS, BDS, Nursing','img'=>'https://images.unsplash.com/photo-1551190822-a9ce113d0d25?w=600&q=80'],
      'law'         => ['icon'=>'ph-scales','color'=>'#dc2626','bg'=>'#fef2f2','accent'=>'#fecaca','categories'=>['Law'],'desc'=>'LLB, CLAT & Legal','img'=>'https://images.unsplash.com/photo-1479142506502-19b3a3b7ff33?w=600&q=80'],
      'science'     => ['icon'=>'ph-flask','color'=>'#0891b2','bg'=>'#ecfeff','accent'=>'#cffafe','categories'=>['Science'],'desc'=>'BSc, Research & Lab','img'=>'https://images.unsplash.com/photo-1507413245164-6160d8298b31?w=600&q=80'],
      'arts'        => ['icon'=>'ph-palette','color'=>'#e11d48','bg'=>'#fff1f2','accent'=>'#ffe4e6','categories'=>['Arts'],'desc'=>'Design, Fine Arts','img'=>'https://images.unsplash.com/photo-1460661419201-fd4cecdf8a8b?w=600&q=80'],
      'design'      => ['icon'=>'ph-magic-wand','color'=>'#9333ea','bg'=>'#faf5ff','accent'=>'#f3e8ff','categories'=>['Design'],'desc'=>'UI/UX, Graphic & more','img'=>'https://images.unsplash.com/photo-1558655146-9f40138edfeb?w=600&q=80'],
      'commerce'    => ['icon'=>'ph-chart-line','color'=>'#d97706','bg'=>'#fffbeb','accent'=>'#fef3c7','categories'=>['Commerce'],'desc'=>'BCom, CA, Finance','img'=>'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=600&q=80'],
    ];

    // Fetch real counts from DB
    $streamCounts = [];
    try {
        foreach ($streamMap as $slug => $sd) {
            $cats = $sd['categories'];
            $placeholders = implode(',', array_fill(0, count($cats), '?'));
            $cntStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM courses WHERE status='active' AND course_category IN ($placeholders)");
            $cntStmt->execute($cats);
            $streamCounts[$slug] = $cntStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
        }
    } catch (Exception $e) {
        foreach ($streamMap as $slug => $sd) $streamCounts[$slug] = 0;
    }

    $streamData = [];
    foreach ($streamMap as $slug => $sd) {
        $count = $streamCounts[$slug] ?? 0;
        $display = $count >= 1000 ? round($count / 100) * 100 : $count;
        $streamData[$slug] = [
            'icon' => $sd['icon'], 'color' => $sd['color'], 'bg' => $sd['bg'], 'accent' => $sd['accent'],
            'count' => $display > 0 ? number_format($display) . '+' : '0',
            'desc' => $sd['desc'],
            'img' => $sd['img'] ?? '',
        ];
    }

    $streamFallback = [
      ['name'=>'Engineering','slug'=>'engineering'],
      ['name'=>'Management','slug'=>'management'],
      ['name'=>'Medical','slug'=>'medical'],
      ['name'=>'Law','slug'=>'law'],
      ['name'=>'Science','slug'=>'science'],
      ['name'=>'Arts','slug'=>'arts'],
      ['name'=>'Commerce','slug'=>'commerce'],
      ['name'=>'Design','slug'=>'design'],
    ];

    // Collect all category names already covered by streamMap
    $coveredCats = [];
    foreach ($streamMap as $sd) {
      foreach ($sd['categories'] as $cn) $coveredCats[strtolower($cn)] = true;
    }

    // Filter categories: skip invalid, empty-count, or already-covered names; limit to 8
    $validCategories = [];
    if (!empty($categories)):
      foreach ($categories as $cat):
        $name = trim($cat['category_name'] ?? $cat['name'] ?? '');
        $slug = strtolower($cat['category_slug'] ?? $cat['slug'] ?? '');
        if (strlen($name) < 3) continue;
        if (isset($coveredCats[strtolower($name)])) continue; // already part of another stream card
        $sd = $streamData[$slug] ?? null;
        if (!$sd) {
          $cntStmt2 = $pdo->prepare("SELECT COUNT(*) as cnt FROM courses WHERE status='active' AND course_category = ?");
          $cntStmt2->execute([$name]);
          $c = $cntStmt2->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
          if ($c == 0) continue;
        } elseif (($streamCounts[$slug] ?? 0) == 0) {
          continue;
        }
        $validCategories[] = $cat;
        if (count($validCategories) >= 8) break;
      endforeach;
    endif;

    // Pad with fallback entries that have real course data
    if (count($validCategories) < 8):
      foreach ($streamFallback as $fb):
        if (count($validCategories) >= 8) break;
        $fbSlug = $fb['slug'];
        if (($streamCounts[$fbSlug] ?? 0) == 0) continue;
        $already = false;
        foreach ($validCategories as $vc):
          $vcSlug = strtolower($vc['category_slug'] ?? $vc['slug'] ?? '');
          if ($vcSlug === $fbSlug) { $already = true; break; }
        endforeach;
        if (!$already) $validCategories[] = ['category_name'=>$fb['name'],'category_slug'=>$fb['slug'],'name'=>$fb['name'],'slug'=>$fb['slug']];
      endforeach;
    endif;

    if (!empty($validCategories)): ?>
      <?php foreach ($validCategories as $i=>$cat):
        $name = $cat['category_name'] ?? $cat['name'] ?? '';
        $slug = strtolower($cat['category_slug'] ?? $cat['slug'] ?? '');
        $sd = $streamData[$slug] ?? null;
        if (!$sd) {
            $cntStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM courses WHERE status='active' AND course_category = ?");
            $cntStmt->execute([$name]);
            $c = $cntStmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;
            $display = $c >= 1000 ? round($c / 100) * 100 : $c;
            $sd = ['icon'=>'ph-graduation-cap','color'=>'#0B2447','bg'=>'#f0f4ff','accent'=>'#e2e8f0','count'=>$display > 0 ? number_format($display).'+' : '0','desc'=>'Explore programs'];
        }
      ?>
      <a href="<?=coursesUrl(['category'=>$name])?>" class="sc<?=!empty($sd['img'])?' sc-img':''?> reveal reveal-delay-<?=$i?>" style="--sc:<?=$sd['color']?>;--sc-bg:<?=$sd['bg']?>;--sc-accent:<?=$sd['accent']?>;<?php if(!empty($sd['img'])):?>background-image:url('<?=$sd['img']?>');<?php endif?>">
        <?php if(!empty($sd['img'])): ?><div class="sc-overlay"></div><?php endif;?>
        <div class="sc-top">
          <div class="sc-icon"><i class="ph <?=$sd['icon']?>"></i></div>
          <div class="sc-badge"><?=$sd['count']?></div>
        </div>
        <h3 class="sc-name"><?=htmlspecialchars($name)?></h3>
        <p class="sc-desc"><?=$sd['desc']?></p>
        <div class="sc-footer">
          <span>Explore</span>
          <i class="ph ph-arrow-up-right"></i>
        </div>
      </a>
      <?php endforeach; ?>
    <?php else: ?>
      <?php foreach ($streamFallback as $i=>$c):
        $sd = $streamData[$c['slug']] ?? ['icon'=>'ph-graduation-cap','color'=>'#0B2447','bg'=>'#f0f4ff','accent'=>'#e2e8f0','count'=>'0','desc'=>'Explore programs','img'=>''];
      ?>
      <a href="<?=coursesUrl(['category'=>$c['name']])?>" class="sc<?=!empty($sd['img'])?' sc-img':''?> reveal reveal-delay-<?=$i?>" style="--sc:<?=$sd['color']?>;--sc-bg:<?=$sd['bg']?>;--sc-accent:<?=$sd['accent']?>;<?php if(!empty($sd['img'])):?>background-image:url('<?=$sd['img']?>');<?php endif?>">
        <?php if(!empty($sd['img'])): ?><div class="sc-overlay"></div><?php endif;?>
        <div class="sc-top">
          <div class="sc-icon"><i class="ph <?=$sd['icon']?>"></i></div>
          <div class="sc-badge"><?=$sd['count']?></div>
        </div>
        <h3 class="sc-name"><?=$c['name']?></h3>
        <p class="sc-desc"><?=$sd['desc']?></p>
        <div class="sc-footer">
          <span>Explore</span>
          <i class="ph ph-arrow-up-right"></i>
        </div>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </div>
</section>