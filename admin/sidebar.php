<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="../index.php" class="logo">
            <i class="ph-fill ph-graduation-cap"></i>
            Admission<span>Season</span>
        </a>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="ph ph-squares-four"></i> Dashboard</a>
        <a href="colleges.php" class="<?php echo ($current_page == 'colleges.php' || $current_page == 'college_form.php') ? 'active' : ''; ?>"><i class="ph ph-buildings"></i> Colleges</a>
        <a href="universities.php" class="<?php echo ($current_page == 'universities.php' || $current_page == 'university_form.php') ? 'active' : ''; ?>"><i class="ph ph-bank"></i> Universities</a>
        <a href="#"><i class="ph ph-books"></i> Courses</a>
        <a href="exams.php" class="<?php echo ($current_page == 'exams.php' || $current_page == 'exam_form.php') ? 'active' : ''; ?>"><i class="ph ph-exam"></i> Exams</a>
        <a href="#"><i class="ph ph-users"></i> Users</a>
        <a href="#"><i class="ph ph-chart-line-up"></i> Reports</a>
        <a href="#"><i class="ph ph-gear"></i> Settings</a>
    </nav>
</aside>
