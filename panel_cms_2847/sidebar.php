<?php
$current_page = basename($_SERVER['PHP_SELF']);
// Dynamic counts for sidebar badges
$sidebarLeadCount = 0;
$sidebarReviewCount = 0;
$sidebarAlertCount = 0;
if (isset($pdo)) {
    try { $sidebarLeadCount = (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(); } catch(Exception $e) {}
    try { $sidebarReviewCount = (int)$pdo->query("SELECT COUNT(*) FROM reviews WHERE status='pending'")->fetchColumn(); } catch(Exception $e) {}
    try { $sidebarAlertCount = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE status='active'")->fetchColumn(); } catch(Exception $e) {}
}
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 256 256" fill="currentColor" style="flex-shrink:0;">
                <path d="M251.76,88.94l-120-64a8,8,0,0,0-7.52,0l-120,64a8,8,0,0,0,0,14.12L32,117.87V176a16,16,0,0,0,8,13.83l80,44.44a15.92,15.92,0,0,0,16,0l80-44.44A16,16,0,0,0,224,176V117.87l24-12.81A8,8,0,0,0,251.76,88.94ZM208,176l-80,44.44L48,176V127.93l72,38.41a8,8,0,0,0,7.52,0l80-42.67ZM128,154.07,25.37,96,128,37.93,230.63,96Z"/>
            </svg>
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
            <?php if ($sidebarLeadCount > 0): ?>
            <span style="background: #0F172A; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; font-weight: 700;"><?= $sidebarLeadCount ?></span>
            <?php endif; ?>
        </a>
        
        <a href="colleges.php" class="<?php echo ($current_page == 'colleges.php' || $current_page == 'college_form.php') ? 'active' : ''; ?>">
            <i class="ph ph-buildings"></i> Colleges
        </a>
        <a href="universities.php" class="<?php echo ($current_page == 'universities.php' || $current_page == 'university_form.php') ? 'active' : ''; ?>">
            <i class="ph ph-bank"></i> Universities
        </a>
        <a href="schools.php" class="<?php echo ($current_page == 'schools.php' || $current_page == 'school_form.php') ? 'active' : ''; ?>">
            <i class="ph ph-graduation-cap"></i> Schools
        </a>
        <a href="rankings.php" class="<?php echo ($current_page == 'rankings.php') ? 'active' : ''; ?>">
            <i class="ph ph-medal"></i> Rankings
        </a>
        <a href="hero_banner.php" class="<?php echo ($current_page == 'hero_banner.php') ? 'active' : ''; ?>">
            <i class="ph ph-images"></i> Hero Banner
        </a>
        <a href="featured_colleges.php" class="<?php echo ($current_page == 'featured_colleges.php') ? 'active' : ''; ?>">
            <i class="ph ph-trophy"></i> Featured Colleges
        </a>
        <a href="featured_schools.php" class="<?php echo ($current_page == 'featured_schools.php') ? 'active' : ''; ?>">
            <i class="ph ph-star-half"></i> Featured Schools
        </a>
        <a href="seat_matrix.php" class="<?php echo ($current_page == 'seat_matrix.php') ? 'active' : ''; ?>">
            <i class="ph ph-table"></i> Seat Matrix
        </a>
        <a href="exams.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'exams.php' || basename($_SERVER['PHP_SELF']) == 'exam_form.php' ? 'active' : ''; ?>"><i class="ph ph-exam"></i> Exams</a>
        <a href="courses.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['courses.php', 'course_form.php', 'course_specializations.php', 'course_career_paths.php']) ? 'active' : ''; ?>"><i class="ph ph-books"></i> Courses</a>
        <a href="careers.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['careers.php', 'career_form.php']) ? 'active' : ''; ?>"><i class="ph ph-compass"></i> Careers</a>
        <a href="course_categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'course_categories.php' ? 'active' : ''; ?>"><i class="ph ph-folders"></i> Course Categories</a>
        <a href="applications.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['applications.php', 'application_details.php']) ? 'active' : ''; ?>"><i class="ph ph-file-text"></i> Applications</a>
        <a href="scholarships.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['scholarships.php', 'scholarship_form.php']) ? 'active' : ''; ?>"><i class="ph ph-graduation-cap"></i> Scholarships</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Engagement</div>
        
        <a href="reviews.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['reviews.php', 'review_moderation.php']) ? 'active' : ''; ?>" style="display:flex; justify-content:space-between; align-items:center;">
            <span style="display:flex; align-items:center; gap:12px;"><i class="ph ph-star"></i> Reviews</span>
            <?php if ($sidebarReviewCount > 0): ?>
            <span style="background: rgba(255,255,255,0.1); color: #fff; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px;"><?= $sidebarReviewCount ?></span>
            <?php endif; ?>
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

        <a href="analytics_reports.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['analytics_reports.php', 'analytics_report_form.php']) ? 'active' : ''; ?>"><i class="ph ph-file-pdf"></i> Reports</a>
        <a href="nps_feedback.php" class="<?php echo ($current_page == 'nps_feedback.php') ? 'active' : ''; ?>"><i class="ph ph-smiley"></i> NPS Feedback</a>
        <a href="cookie_consents.php" class="<?php echo ($current_page == 'cookie_consents.php') ? 'active' : ''; ?>"><i class="ph ph-cookie"></i> Cookie Consents</a>

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

        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Security & Moderation</div>
        <a href="moderation_queue.php" class="<?php echo ($current_page == 'moderation_queue.php') ? 'active' : ''; ?>"><i class="ph ph-shield-check"></i> Moderation Queue</a>
        <a href="spam_logs.php" class="<?php echo ($current_page == 'spam_logs.php') ? 'active' : ''; ?>"><i class="ph ph-warning-circle"></i> Spam & Bans</a>
        
        <a href="users.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['users.php']) ? 'active' : ''; ?>"><i class="ph ph-users"></i> User Management</a>
        <a href="shortlists.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['shortlists.php']) ? 'active' : ''; ?>"><i class="ph ph-heart"></i> Student Shortlists</a>
        <a href="roles.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['roles.php']) ? 'active' : ''; ?>"><i class="ph ph-shield-star"></i> Roles & Permissions</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">Partner Management</div>
        <a href="partners.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['partners.php']) ? 'active' : ''; ?>"><i class="ph ph-handshake"></i> Partner Accounts</a>
        <a href="partner_requests.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['partner_requests.php']) ? 'active' : ''; ?>"><i class="ph ph-envelope-open"></i> Content Requests</a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">College Portal</div>
        <a href="college_accounts.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['college_accounts.php','college_submissions.php']) ? 'active' : ''; ?>" style="display:flex; justify-content:space-between; align-items:center;">
            <span style="display:flex; align-items:center; gap:12px;"><i class="ph ph-graduation-cap"></i> Accounts</span>
            <?php try { $pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM college_accounts WHERE status='pending'")->fetchColumn(); if($pendingCount > 0): ?>
            <span style="background:#fbbf24;color:#19376D;font-size:0.7rem;padding:2px 6px;border-radius:10px;font-weight:700;"><?= $pendingCount ?></span>
            <?php endif; } catch(Exception $e) {} ?>
        </a>
        <a href="college_submissions.php" class="<?php echo ($current_page == 'college_submissions.php') ? 'active' : ''; ?>" style="display:flex; justify-content:space-between; align-items:center;">
            <span style="display:flex; align-items:center; gap:12px;"><i class="ph ph-tray"></i> Submissions</span>
            <?php try { $subCount = (int)$pdo->query("SELECT COUNT(*) FROM college_submissions WHERE status='pending'")->fetchColumn(); if($subCount > 0): ?>
            <span style="background:#fbbf24;color:#19376D;font-size:0.7rem;padding:2px 6px;border-radius:10px;font-weight:700;"><?= $subCount ?></span>
            <?php endif; } catch(Exception $e) {} ?>
        </a>
        
        <div style="margin: 20px 24px 8px 24px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); font-weight: 700;">System</div>
        <a href="audit_logs.php" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['audit_logs.php']) ? 'active' : ''; ?>"><i class="ph ph-file-search"></i> Audit Logs</a>
        <a href="alerts.php" style="display:flex; justify-content:space-between; align-items:center;" class="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['alerts.php']) ? 'active' : ''; ?>">
            <span style="display:flex; align-items:center; gap:12px;"><i class="ph ph-bell-ringing"></i> Alerts</span>
            <?php if ($sidebarAlertCount > 0): ?>
            <span style="background: #19376D; color: #0F172A; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; font-weight: 700;"><?= $sidebarAlertCount ?></span>
            <?php endif; ?>
        </a>
    </nav>
</aside>
