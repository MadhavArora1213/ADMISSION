<!-- ═══ UNIQUE HERO — Mesh Gradient + Floating Stats ═══ -->
<section class="nh">
  <!-- Animated mesh blobs -->
  <div class="nh-mesh">
    <div class="nh-blob nh-blob-1"></div>
    <div class="nh-blob nh-blob-2"></div>
    <div class="nh-blob nh-blob-3"></div>
  </div>

  <div class="container nh-layout">
    <!-- LEFT: Text + Search -->
    <div class="nh-left">
      <div class="nh-badge"><i class="ph-fill ph-shield-check"></i> Trusted by 5 Lakh+ Students</div>
      <h1 class="nh-title">Find Your <span class="nh-gradient">Dream College</span> in India</h1>
      <p class="nh-sub">Explore <?=number_format($totalColleges)?>+ colleges, <?=number_format($totalCourses)?>+ courses & <?=number_format($totalExams)?>+ entrance exams — all in one place.</p>

      <!-- Search Card -->
      <div class="nh-search">
        <div class="nh-search-tabs">
          <button class="active" data-type="colleges"><i class="ph ph-buildings"></i> Colleges</button>
          <button data-type="exams"><i class="ph ph-pencil-line"></i> Exams</button>
          <button data-type="courses"><i class="ph ph-book-open"></i> Courses</button>
        </div>
        <div class="nh-search-row">
          <i class="ph ph-magnifying-glass"></i>
          <input type="text" placeholder="Search by name, stream, location..." id="nhSearchInput">
          <button class="nh-search-btn" onclick="handleNhSearch()">Search <i class="ph ph-arrow-right"></i></button>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="nh-quick">
        <span class="nh-quick-label">Popular:</span>
        <a href="<?=collegesUrl(['q'=>'IIT'])?>" class="nh-quick-link">IIT Colleges</a>
        <a href="<?=collegesUrl(['q'=>'Medical'])?>" class="nh-quick-link">Medical Colleges</a>
        <a href="<?=examsUrl()?>" class="nh-quick-link">Entrance Exams</a>
        <a href="<?=coursesUrl(['level'=>'MBA'])?>" class="nh-quick-link">MBA Courses</a>
      </div>
    </div>

    <!-- RIGHT: Floating Stat Cards -->
    <div class="nh-right">
      <div class="nh-card nh-card-1">
        <div class="nh-card-icon"><i class="ph-fill ph-buildings"></i></div>
        <div class="nh-card-text">
          <strong><?=number_format($totalColleges)?>+</strong>
          <span>Colleges</span>
        </div>
      </div>
      <div class="nh-card nh-card-2">
        <div class="nh-card-icon"><i class="ph-fill ph-book-open"></i></div>
        <div class="nh-card-text">
          <strong><?=number_format($totalCourses)?>+</strong>
          <span>Courses</span>
        </div>
      </div>
      <div class="nh-card nh-card-3">
        <div class="nh-card-icon"><i class="ph-fill ph-star"></i></div>
        <div class="nh-card-text">
          <strong><?=number_format($totalReviews)?>+</strong>
          <span>Reviews</span>
        </div>
      </div>
      <div class="nh-card nh-card-4">
        <div class="nh-card-icon nh-card-icon-accent"><i class="ph-fill ph-users"></i></div>
        <div class="nh-card-text">
          <strong><?=number_format($totalStudents ?? 0)?>+</strong>
          <span>Students</span>
        </div>
      </div>
      <!-- Central visual -->
      <div class="nh-center-visual">
        <div class="nh-ring"></div>
        <div class="nh-ring nh-ring-2"></div>
        <i class="ph-fill ph-graduation-cap"></i>
      </div>
    </div>
  </div>

  <!-- Trust Strip -->
  <div class="nh-trust">
    <div class="container nh-trust-inner">
      <div class="nh-trust-item"><i class="ph ph-shield-check"></i> Verified Data</div>
      <div class="nh-trust-item"><i class="ph ph-chart-line-up"></i> NIRF Rankings</div>
      <div class="nh-trust-item"><i class="ph ph-headset"></i> Expert Guidance</div>
      <div class="nh-trust-item"><i class="ph ph-check-circle"></i> 100% Free</div>
    </div>
  </div>
</section>

<script>
function handleNhSearch() {
  const q = document.getElementById('nhSearchInput')?.value?.trim();
  if (!q) return;
  const activeTab = document.querySelector('.nh-search-tabs button.active');
  const type = activeTab?.dataset.type || 'colleges';
  const url = type === 'colleges' ? '/ADMISSION/colleges.php?q=' + encodeURIComponent(q)
           : type === 'exams' ? '/ADMISSION/exams.php?q=' + encodeURIComponent(q)
           : '/ADMISSION/courses.php?q=' + encodeURIComponent(q);
  window.location.href = url;
}
document.getElementById('nhSearchInput')?.addEventListener('keydown', e => {
  if (e.key === 'Enter') handleNhSearch();
});
document.querySelectorAll('.nh-search-tabs button').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.nh-search-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const placeholders = { colleges: 'Search by name, stream, location...', exams: 'Search entrance exams...', courses: 'Search courses & programs...' };
    document.getElementById('nhSearchInput').placeholder = placeholders[btn.dataset.type] || placeholders.colleges;
  });
});
</script>