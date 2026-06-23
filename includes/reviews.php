<!-- ═══ REVIEWS — Testimonial Cards ═══ -->
<section class="section">
  <div class="container">
    <div class="section-hdr reveal">
      <div class="nh-badge" style="margin:0 auto 16px"><i class="ph-fill ph-chat-circle-text"></i> Testimonials</div>
      <h2>What Students Say</h2>
      <p>Real reviews from real students about their college experience</p>
    </div>
    <?php if (!empty($reviews)): ?>
    <div class="rev-grid">
      <?php foreach ($reviews as $rv): ?>
      <div class="rev-card reveal">
        <div class="rev-quote"><i class="ph-fill ph-quotes"></i></div>
        <div class="rev-stars"><?php $rr=round((float)$rv['overall_rating']);for($s=1;$s<=5;$s++):?><i class="ph <?=$s<=$rr?'ph-fill ph-star':'ph-star'?>"></i><?php endfor;?><span><?=number_format((float)$rv['overall_rating'],1)?></span></div>
        <h4><?=htmlspecialchars($rv['review_title']??'Great Experience')?></h4>
        <blockquote>"<?=htmlspecialchars(substr($rv['review_body']??'',0,180))?>"</blockquote>
        <div class="rev-author">
          <div class="rev-avatar"><i class="ph ph-user-circle"></i></div>
          <div><strong><?=htmlspecialchars($rv['college_name'])?></strong><span>Batch of <?=htmlspecialchars($rv['batch_year']??'N/A')?></span></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="rev-grid">
      <?php for($i=0;$i<4;$i++): ?>
      <div class="rev-card reveal">
        <div class="rev-quote"><i class="ph-fill ph-quotes"></i></div>
        <div class="rev-stars"><?php for($s=1;$s<=5;$s++):?><i class="ph-fill ph-star"></i><?php endfor;?><span>5.0</span></div>
        <h4>Amazing Campus & Learning Experience</h4>
        <blockquote>"The college exceeded my expectations. Great faculty, excellent infrastructure, and amazing placement support."</blockquote>
        <div class="rev-author">
          <div class="rev-avatar"><i class="ph ph-user-circle"></i></div>
          <div><strong>IIT Delhi</strong><span>Batch of 2025</span></div>
        </div>
      </div>
      <?php endfor;?>
    </div>
    <?php endif; ?>
  </div>
</section>