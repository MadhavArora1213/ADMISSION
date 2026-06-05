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
        <a href="applications.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['applications.php', 'application_details.php']) ? 'active' : ''; ?>"><i class="ph ph-file-text"></i> Applications</a>
        <a href="scholarships.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['scholarships.php', 'scholarship_form.php']) ? 'active' : ''; ?>"><i class="ph ph-graduation-cap"></i> Scholarships</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Engagement</div>
        
        <a href="reviews.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['reviews.php', 'review_moderation.php']) ? 'active' : ''; ?>" style="display:flex; justify-content:space-between; align-items:center;">
            <span style="display:flex; align-items:center; gap:12px;"><i class="ph ph-star"></i> Reviews</span>
            <span style="background: rgba(255,255,255,0.1); color: #fff; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px;">23</span>
        </a>
        <a href="users.php"><i class="ph ph-users"></i> Users</a>
        <a href="user_reports.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['user_reports.php']) ? 'active' : ''; ?>"><i class="ph ph-flag"></i> User Reports</a>
        <a href="community_dashboard.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['community_dashboard.php','questions_manager.php','answers_manager.php','experts.php','qa_moderation.php']) ? 'active' : ''; ?>"><i class="ph ph-users-three"></i> Community & Q&A</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Study Abroad</div>
        <a href="foreign_universities.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['foreign_universities.php', 'foreign_university_form.php']) ? 'active' : ''; ?>"><i class="ph ph-globe-hemisphere-east"></i> Universities Abroad</a>
        <a href="visa_guides.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['visa_guides.php', 'visa_guide_form.php']) ? 'active' : ''; ?>"><i class="ph ph-airplane-tilt"></i> Visa Guides</a>
        <a href="consultants.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['consultants.php', 'consultant_form.php']) ? 'active' : ''; ?>"><i class="ph ph-users-four"></i> Consultants</a>

        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Billing & Revenue</div>
        <a href="invoices.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['invoices.php', 'invoice_form.php']) ? 'active' : ''; ?>"><i class="ph ph-receipt"></i> Invoices</a>
        <a href="subscriptions.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['subscriptions.php', 'subscription_form.php']) ? 'active' : ''; ?>"><i class="ph ph-arrows-clockwise"></i> Subscriptions</a>
        <a href="subscription_plans.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['subscription_plans.php', 'subscription_plan_form.php']) ? 'active' : ''; ?>"><i class="ph ph-list-dashes"></i> Sub. Plans</a>
        <a href="lead_credits.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['lead_credits.php', 'lead_credit_form.php']) ? 'active' : ''; ?>"><i class="ph ph-coins"></i> Lead Credits</a>
        <a href="ad_products.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['ad_products.php', 'ad_product_form.php']) ? 'active' : ''; ?>"><i class="ph ph-megaphone-simple"></i> Ad Products</a>
        <a href="commissions.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['commissions.php', 'commission_form.php']) ? 'active' : ''; ?>"><i class="ph ph-money"></i> Commissions</a>

        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Analytics & Reports</div>
        <a href="page_analytics.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['page_analytics.php', 'page_analytic_form.php']) ? 'active' : ''; ?>"><i class="ph ph-chart-line-up"></i> Traffic</a>
        <a href="funnel_analytics.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['funnel_analytics.php', 'funnel_analytic_form.php']) ? 'active' : ''; ?>"><i class="ph ph-funnel"></i> Funnels</a>


        <a href="analytics_reports.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['analytics_reports.php', 'analytics_report_form.php']) ? 'active' : ''; ?>"><i class="ph ph-file-pdf"></i> Reports</a>

        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">CMS</div>
        
        <a href="articles.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['articles.php','article_form.php','article_categories.php']) ? 'active' : ''; ?>"><i class="ph ph-newspaper"></i> Articles</a>
        <a href="media_library.php" class="<?php echo ($current_page == 'media_library.php') ? 'active' : ''; ?>"><i class="ph ph-images"></i> Media Library</a>
        <a href="tags.php" class="<?php echo ($current_page == 'tags.php') ? 'active' : ''; ?>"><i class="ph ph-tag"></i> Tags</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Search & Discovery</div>
        <a href="search_analytics.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['search_analytics.php','search_config.php']) ? 'active' : ''; ?>"><i class="ph ph-magnifying-glass"></i> Search Analytics</a>
        <a href="compare_engine.php" class="<?php echo ($current_page == 'compare_engine.php') ? 'active' : ''; ?>"><i class="ph ph-scales"></i> Compare Engine</a>
        <a href="emi_calculator.php" class="<?php echo ($current_page == 'emi_calculator.php') ? 'active' : ''; ?>"><i class="ph ph-calculator"></i> Fee / EMI Calculator</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">SEO Management</div>
        <a href="seo_dashboard.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['seo_dashboard.php','redirects.php']) ? 'active' : ''; ?>"><i class="ph ph-globe"></i> SEO Dashboard</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Communications</div>
        <a href="notifications_dashboard.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['notifications_dashboard.php','notification_templates.php','audience_segments.php','notification_campaigns.php','notification_logs.php']) ? 'active' : ''; ?>"><i class="ph ph-megaphone"></i> Notifications</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">AI Systems</div>
        <a href="ai_dashboard.php" class="<?php echo ($current_page == 'ai_dashboard.php') ? 'active' : ''; ?>"><i class="ph ph-robot"></i> AI Engine</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Dashboard Engine</div>
        <a href="manage_widgets.php" class="<?php echo ($current_page == 'manage_widgets.php') ? 'active' : ''; ?>"><i class="ph ph-squares-four"></i> Widget Engine</a>
        <a href="manage_layouts.php" class="<?php echo ($current_page == 'manage_layouts.php') ? 'active' : ''; ?>"><i class="ph ph-layout"></i> Layouts</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Security & Moderation</div>
        <a href="moderation_queue.php" class="<?php echo ($current_page == 'moderation_queue.php') ? 'active' : ''; ?>"><i class="ph ph-shield-check"></i> Moderation Queue</a>
        <a href="spam_logs.php" class="<?php echo ($current_page == 'spam_logs.php') ? 'active' : ''; ?>"><i class="ph ph-warning-circle"></i> Spam & Bans</a>
        
        <a href="users.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['users.php']) ? 'active' : ''; ?>"><i class="ph ph-users"></i> User Management</a>
        <a href="shortlists.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['shortlists.php']) ? 'active' : ''; ?>"><i class="ph ph-heart"></i> Student Shortlists</a>
        <a href="roles.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['roles.php']) ? 'active' : ''; ?>"><i class="ph ph-shield-star"></i> Roles & Permissions</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Partner Management</div>
        <a href="partners.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['partners.php']) ? 'active' : ''; ?>"><i class="ph ph-handshake"></i> Partner Accounts</a>
        <a href="partner_requests.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['partner_requests.php']) ? 'active' : ''; ?>"><i class="ph ph-envelope-open"></i> Content Requests</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">System</div>
        <a href="audit_logs.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['audit_logs.php']) ? 'active' : ''; ?>"><i class="ph ph-file-search"></i> Audit Logs</a>
        <a href="alerts.php" style="display:flex; justify-content:space-between; align-items:center;" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['alerts.php']) ? 'active' : ''; ?>">
            <span style="display:flex; align-items:center; gap:12px;"><i class="ph ph-bell-ringing"></i> Alerts</span>
            <span style="background: #eab308; color: #422006; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; font-weight: 700;">5</span>
        </a>
        <a href="settings.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['settings.php']) ? 'active' : ''; ?>"><i class="ph ph-gear"></i> Settings</a>
    </nav>
</aside>
