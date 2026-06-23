<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../admin/db.php';
}

$navBase = '/ADMISSION';

try {
    $fColleges = $pdo->query("SELECT name, slug FROM colleges ORDER BY is_featured DESC, name ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $fColleges = []; }

try {
    $fExams = $pdo->query("SELECT exam_name, exam_slug FROM exams ORDER BY exam_name ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $fExams = []; }

try {
    $fCountries = $pdo->query("SELECT DISTINCT country FROM foreign_universities WHERE country IS NOT NULL ORDER BY country ASC LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { $fCountries = []; }

try {
    $fCareers = $pdo->query("SELECT name, slug FROM careers ORDER BY is_popular DESC, name ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $fCareers = []; }

function fSlug($text) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
}
function fName($text, $max = 60) {
    $t = trim($text);
    return mb_strlen($t) > $max ? mb_substr($t, 0, $max) . '...' : $t;
}
?>

<!-- ═══ FOOTER ═══ -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="<?= $navBase ?>/" class="flogo"><i class="ph-fill ph-graduation-cap"></i> Admission<span>Season</span></a>
        <p>India's leading college discovery platform. Find detailed info on colleges, courses, exams, and get personalised admission assistance.</p>
        <div class="fsocial">
          <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook"><i class="ph-fill ph-facebook-logo"></i></a>
          <a href="https://twitter.com" target="_blank" rel="noopener" aria-label="X"><i class="ph-fill ph-twitter-logo"></i></a>
          <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram"><i class="ph-fill ph-instagram-logo"></i></a>
          <a href="https://linkedin.com" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="ph-fill ph-linkedin-logo"></i></a>
          <a href="https://youtube.com" target="_blank" rel="noopener" aria-label="YouTube"><i class="ph-fill ph-youtube-logo"></i></a>
        </div>
      </div>

      <div class="footer-col">
        <h4>Top Colleges</h4>
        <ul>
          <?php if (!empty($fColleges)): ?>
            <?php foreach ($fColleges as $fc): ?>
              <li><a href="<?= $navBase ?>/college/<?= urlencode($fc['slug']) ?>" title="<?= htmlspecialchars($fc['name']) ?>"><?= htmlspecialchars(fName($fc['name'])) ?></a></li>
            <?php endforeach; ?>
          <?php else: ?>
            <li><a href="<?= $navBase ?>/colleges">View All Colleges</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Entrance Exams</h4>
        <ul>
          <?php if (!empty($fExams)): ?>
            <?php foreach ($fExams as $fe): ?>
              <li><a href="<?= $navBase ?>/exam/<?= urlencode($fe['exam_slug']) ?>"><?= htmlspecialchars($fe['exam_name']) ?></a></li>
            <?php endforeach; ?>
          <?php else: ?>
            <li><a href="<?= $navBase ?>/exams">View All Exams</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Study Abroad</h4>
        <ul>
          <?php if (!empty($fCountries)): ?>
            <?php foreach ($fCountries as $fcoun): ?>
              <li><a href="<?= $navBase ?>/study-abroad?country=<?= urlencode($fcoun) ?>">Study in <?= htmlspecialchars($fcoun) ?></a></li>
            <?php endforeach; ?>
          <?php else: ?>
            <li><a href="<?= $navBase ?>/study-abroad">Explore Study Abroad</a></li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Careers</h4>
        <ul>
          <?php if (!empty($fCareers)): ?>
            <?php foreach ($fCareers as $fcr): ?>
              <li><a href="<?= $navBase ?>/career/<?= urlencode($fcr['slug']) ?>"><?= htmlspecialchars($fcr['name']) ?></a></li>
            <?php endforeach; ?>
          <?php else: ?>
            <li><a href="<?= $navBase ?>/careers">Explore Careers</a></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> AdmissionSeason. All rights reserved.</p>
      <div class="footer-badges">
        <span><i class="ph ph-shield-check"></i> Verified Data</span>
        <span><i class="ph ph-lock"></i> Secure</span>
        <span><i class="ph ph-star"></i> 5M+ Students</span>
      </div>
    </div>
  </div>
</footer>
