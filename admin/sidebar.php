<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <i class="ph-fill ph-graduation-cap"></i>
            AdmissionSeason
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div style="margin: 0px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Main</div>
        
        <a href="dashboard.php" class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="ph ph-squares-four"></i> Dashboard
        </a>
        
        <a href="leads.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['leads.php','lead_form.php']) ? 'active' : ''; ?>" style="display:flex; justify-content:space-between; align-items:center;">
            <span style="display:flex; align-items:center; gap:12px;"><i class="ph ph-funnel"></i> Leads</span>
            <span style="background: #ef4444; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; font-weight: 700;">109</span>
        </a>
        
        <a href="colleges.php" class="<?php echo ($current_page == 'colleges.php' || $current_page == 'college_form.php') ? 'active' : ''; ?>">
            <i class="ph ph-buildings"></i> Colleges
        </a>
        <a href="universities.php" class="<?php echo ($current_page == 'universities.php' || $current_page == 'university_form.php') ? 'active' : ''; ?>">
            <i class="ph ph-bank"></i> Universities
        </a>
        <a href="rankings.php" class="<?php echo ($current_page == 'rankings.php') ? 'active' : ''; ?>">
            <i class="ph ph-medal"></i> Rankings
        </a>
        <a href="seat_matrix.php" class="<?php echo ($current_page == 'seat_matrix.php') ? 'active' : ''; ?>">
            <i class="ph ph-table"></i> Seat Matrix
        </a>
        <a href="exams.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'exams.php' || basename($_SERVER['PHP_SELF']) == 'exam_form.php' ? 'active' : ''; ?>"><i class="ph ph-exam"></i> Exams</a>
        <a href="courses.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['courses.php', 'course_form.php', 'course_specializations.php', 'course_career_paths.php']) ? 'active' : ''; ?>"><i class="ph ph-books"></i> Courses</a>
        <a href="course_categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'course_categories.php' ? 'active' : ''; ?>"><i class="ph ph-folders"></i> Course Categories</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Engagement</div>
        
        <a href="reviews.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['reviews.php', 'review_moderation.php']) ? 'active' : ''; ?>" style="display:flex; justify-content:space-between; align-items:center;">
            <span style="display:flex; align-items:center; gap:12px;"><i class="ph ph-star"></i> Reviews</span>
            <span style="background: rgba(255,255,255,0.1); color: #fff; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px;">23</span>
        </a>
        <a href="users.php"><i class="ph ph-users"></i> Users</a>
        <a href="#"><i class="ph ph-chart-line-up"></i> Reports</a>
        <a href="community_dashboard.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['community_dashboard.php','questions_manager.php','answers_manager.php','experts.php','qa_moderation.php']) ? 'active' : ''; ?>"><i class="ph ph-users-three"></i> Community & Q&A</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">CMS</div>
        
        <a href="articles.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['articles.php','article_form.php','article_categories.php']) ? 'active' : ''; ?>"><i class="ph ph-newspaper"></i> Articles</a>
        <a href="media_library.php" class="<?php echo ($current_page == 'media_library.php') ? 'active' : ''; ?>"><i class="ph ph-images"></i> Media Library</a>
        <a href="tags.php" class="<?php echo ($current_page == 'tags.php') ? 'active' : ''; ?>"><i class="ph ph-tag"></i> Tags</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Search & Discovery</div>
        <a href="search_analytics.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['search_analytics.php','search_config.php']) ? 'active' : ''; ?>"><i class="ph ph-magnifying-glass"></i> Search Analytics</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">SEO Management</div>
        <a href="seo_dashboard.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['seo_dashboard.php','redirects.php']) ? 'active' : ''; ?>"><i class="ph ph-globe"></i> SEO Dashboard</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Communications</div>
        <a href="notifications_dashboard.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['notifications_dashboard.php','notification_templates.php','audience_segments.php','notification_campaigns.php','notification_logs.php']) ? 'active' : ''; ?>"><i class="ph ph-megaphone"></i> Notifications</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">AI Systems</div>
        <a href="ai_dashboard.php" class="<?php echo ($current_page == 'ai_dashboard.php') ? 'active' : ''; ?>"><i class="ph ph-robot"></i> AI Engine</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Dashboard Engine</div>
        <a href="manage_widgets.php" class="<?php echo ($current_page == 'manage_widgets.php') ? 'active' : ''; ?>"><i class="ph ph-squares-four"></i> Widget Engine</a>
        <a href="manage_layouts.php" class="<?php echo ($current_page == 'manage_layouts.php') ? 'active' : ''; ?>"><i class="ph ph-layout"></i> Layouts</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">System</div>
        <a href="#" style="display:flex; justify-content:space-between; align-items:center;">
            <span style="display:flex; align-items:center; gap:12px;"><i class="ph ph-bell-ringing"></i> Alerts</span>
            <span style="background: #eab308; color: #422006; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; font-weight: 700;">5</span>
        </a>
        <a href="#"><i class="ph ph-gear"></i> Settings</a>
    </nav>
</aside>
