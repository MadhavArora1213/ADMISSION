<!-- ═══ TOOLS — Horizontal Feature Cards ═══ -->
<section class="section" id="tools">
  <div class="container">
    <div class="section-hdr reveal">
      <div class="nh-badge" style="margin:0 auto 16px"><i class="ph-fill ph-wrench"></i> Smart Tools</div>
      <h2>Student Tools & Resources</h2>
      <p>Smart tools to help you make the right college decision</p>
    </div>
    <div class="tools-grid">
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/compare.php" class="tool-card tool-card-accent reveal reveal-delay-1">
        <div class="tool-card-left">
          <div class="tool-icon"><i class="ph ph-scales"></i></div>
          <div>
            <h3>College Compare</h3>
            <p>Compare fees, placements, rankings side-by-side</p>
          </div>
        </div>
        <span class="tool-cta">Compare Now <i class="ph ph-arrow-right"></i></span>
      </a>
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/counselling.php" class="tool-card reveal reveal-delay-2">
        <div class="tool-card-left">
          <div class="tool-icon"><i class="ph ph-chart-line-up"></i></div>
          <div>
            <h3>College Predictor</h3>
            <p>Know your admission chances by exam score</p>
          </div>
        </div>
        <span class="tool-cta">Predict Now <i class="ph ph-arrow-right"></i></span>
      </a>
      <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/ask-question.php" class="tool-card reveal reveal-delay-3">
        <div class="tool-card-left">
          <div class="tool-icon"><i class="ph ph-chat-circle"></i></div>
          <div>
            <h3>Ask a Question</h3>
            <p>Get answers from students & admission experts</p>
          </div>
        </div>
        <span class="tool-cta">Ask Now <i class="ph ph-arrow-right"></i></span>
      </a>
    </div>
  </div>
</section>