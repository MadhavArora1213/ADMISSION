<!-- ═══ FLOATING CHIPS MARQUEE ═══ -->
<div class="marquee-wrap">
  <div class="marquee-inner">
    <?php foreach(array_merge($marqueeColleges ?? [], $marqueeColleges ?? []) as $mc): ?>
    <div class="m-chip<?=(!empty($mc['overall_rating_avg']) && $mc['overall_rating_avg']>=4.5)?' m-chip-highlight':''?>"><i class="ph-fill <?=(!empty($mc['overall_rating_avg']) && $mc['overall_rating_avg']>=4.5)?'ph-star':'ph-check-circle'?>"></i> <?=htmlspecialchars($mc['name'])?></div>
    <?php endforeach; ?>
  </div>
</div>
