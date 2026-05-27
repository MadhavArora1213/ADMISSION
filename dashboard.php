<?php
// Mock session for student
session_start();
$_SESSION['user_id'] = 'user-1234-uuid';
$_SESSION['user_name'] = 'Rahul Sharma';

// Mock Data
$shortlists = [
    ['id' => 1, 'name' => 'IIT Bombay', 'location' => 'Mumbai', 'course' => 'B.Tech Computer Science'],
    ['id' => 2, 'name' => 'BITS Pilani', 'location' => 'Pilani', 'course' => 'B.E. Computer Science'],
    ['id' => 3, 'name' => 'IIM Ahmedabad', 'location' => 'Ahmedabad', 'course' => 'MBA']
];

$applications = [
    ['id' => 101, 'college' => 'IIT Bombay', 'status' => 'Pending Review', 'date' => '2026-05-20', 'color' => 'yellow'],
    ['id' => 102, 'college' => 'NIT Trichy', 'status' => 'Accepted', 'date' => '2026-05-15', 'color' => 'green'],
    ['id' => 103, 'college' => 'VIT Vellore', 'status' => 'Action Required', 'date' => '2026-05-25', 'color' => 'red']
];

$notifications = [
    ['id' => 1, 'text' => 'Your application for NIT Trichy has been accepted!', 'time' => '2 hours ago', 'is_read' => false],
    ['id' => 2, 'text' => 'VIT Vellore requires additional documents for your application.', 'time' => '1 day ago', 'is_read' => false],
    ['id' => 3, 'text' => 'Reminder: BITSAT 2026 registration ends in 3 days.', 'time' => '2 days ago', 'is_read' => true]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | AdmissionSeason</title>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background-color: var(--bg-light); }
        .dashboard-layout {
            display: flex;
            min-height: calc(100vh - 70px);
        }
        .user-sidebar {
            width: 250px;
            background: #f8fafc;
            border-right: 1px solid var(--border-color);
            padding: 24px 0;
            display: flex;
            flex-direction: column;
        }
        .user-profile-summary {
            padding: 0 24px 24px 24px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 24px;
        }
        .avatar-lg {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            margin: 0 auto 16px auto;
        }
        .user-sidebar-nav {
            display: flex;
            flex-direction: column;
        }
        .user-sidebar-nav a {
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--text-dark);
            font-weight: 500;
            transition: all 0.3s;
        }
        .user-sidebar-nav a:hover, .user-sidebar-nav a.active {
            background: var(--primary-light);
            color: var(--primary);
            border-right: 3px solid var(--primary);
        }
        .user-sidebar-nav a i {
            font-size: 1.25rem;
        }
        
        .dashboard-content {
            flex: 1;
            padding: 32px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .welcome-banner {
            background: var(--bg-light), #0b2447);
            border-radius: 16px;
            padding: 32px;
            color: #f8fafc;
            margin-bottom: 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
        }
        .welcome-banner h1 {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        .welcome-banner p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
        }
        
        .panel {
            background: #f8fafc;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            padding: 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 32px;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 16px;
        }
        .panel-header h2 {
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Lists */
        .app-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            margin-bottom: 16px;
            transition: all 0.3s;
        }
        .app-item:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow-sm);
        }
        .app-info h3 {
            font-size: 1.1rem;
            margin-bottom: 4px;
        }
        .app-info p {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .status-yellow { background: #f8fafc; color: #19376d; }
        .status-green { background: #f8fafc; color: #19376d; }
        .status-red { background: #f8fafc; color: #19376d; }
        
        /* Notifications */
        .notif-item {
            padding: 16px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            gap: 16px;
        }
        .notif-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .notif-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .notif-unread .notif-icon {
            background: #f8fafc;
            color: #0b2447;
        }
        .notif-content p {
            font-size: 0.95rem;
            margin-bottom: 4px;
            color: var(--text-dark);
        }
        .notif-content span {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        @media (max-width: 992px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .user-sidebar { display: none; }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <i class="ph-fill ph-graduation-cap"></i>
                Admission<span>Season</span>
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="#">Colleges</a></li>
                <li><a href="#">Exams</a></li>
            </ul>
            <div class="nav-actions">
                <div style="display:flex; align-items:center; gap:12px; font-weight:600;">
                    <div style="width:36px; height:36px; border-radius:50%; background:var(--primary); color:#f8fafc; display:flex; align-items:center; justify-content:center;">
                        R
                    </div>
                    Rahul Sharma
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="user-sidebar">
            <div class="user-profile-summary">
                <div class="avatar-lg">R</div>
                <h3>Rahul Sharma</h3>
                <p style="color:var(--text-muted); font-size:0.875rem;">rahul.sharma@example.com</p>
            </div>
            <nav class="user-sidebar-nav">
                <a href="dashboard.php" class="active"><i class="ph ph-squares-four"></i> Dashboard</a>
                <a href="#"><i class="ph ph-bookmark-simple"></i> Shortlists</a>
                <a href="#"><i class="ph ph-file-text"></i> Applications</a>
                <a href="#"><i class="ph ph-bell"></i> Notifications</a>
                <a href="#"><i class="ph ph-user"></i> Profile Settings</a>
                <a href="#" style="color:#19376d; margin-top:24px;"><i class="ph ph-sign-out"></i> Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="dashboard-content">
            <div class="welcome-banner">
                <div>
                    <h1>Welcome back, Rahul!</h1>
                    <p>Here's what's happening with your college applications today.</p>
                </div>
                <div>
                    <button class="btn btn-#f8fafc">Explore Colleges</button>
                </div>
            </div>

            <div class="dashboard-grid">
                <!-- Left Column -->
                <div>
                    <!-- Applications -->
                    <div class="panel">
                        <div class="panel-header">
                            <h2><i class="ph-fill ph-file-text" style="color:var(--primary);"></i> My Applications</h2>
                            <a href="#" style="color:var(--primary); font-weight:600; font-size:0.875rem;">View All</a>
                        </div>
                        <div class="app-list">
                            <?php foreach($applications as $app): ?>
                            <div class="app-item">
                                <div class="app-info">
                                    <h3><?php echo $app['college']; ?></h3>
                                    <p>Applied on <?php echo date('M d, Y', strtotime($app['date'])); ?></p>
                                </div>
                                <div class="status-badge status-<?php echo $app['color']; ?>">
                                    <?php echo $app['status']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Shortlists -->
                    <div class="panel">
                        <div class="panel-header">
                            <h2><i class="ph-fill ph-bookmark-simple" style="color:var(--accent);"></i> Shortlisted Colleges</h2>
                            <a href="#" style="color:var(--primary); font-weight:600; font-size:0.875rem;">View All</a>
                        </div>
                        <div class="app-list">
                            <?php foreach($shortlists as $sl): ?>
                            <div class="app-item">
                                <div class="app-info">
                                    <h3><?php echo $sl['name']; ?></h3>
                                    <p><i class="ph ph-map-pin"></i> <?php echo $sl['location']; ?> &nbsp;•&nbsp; <?php echo $sl['course']; ?></p>
                                </div>
                                <button class="btn btn-primary" style="padding: 8px 16px; font-size:0.875rem;">Apply Now</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div>
                    <!-- Notifications -->
                    <div class="panel">
                        <div class="panel-header">
                            <h2><i class="ph-fill ph-bell" style="color:#19376d;"></i> Notifications</h2>
                        </div>
                        <div class="notif-list">
                            <?php foreach($notifications as $notif): ?>
                            <div class="notif-item <?php echo $notif['is_read'] ? '' : 'notif-unread'; ?>">
                                <div class="notif-icon">
                                    <i class="ph-fill ph-bell-ringing"></i>
                                </div>
                                <div class="notif-content">
                                    <p style="font-weight: <?php echo $notif['is_read'] ? '400' : '600'; ?>;">
                                        <?php echo $notif['text']; ?>
                                    </p>
                                    <span><?php echo $notif['time']; ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Need Help -->
                    <div class="panel" style="background: var(--bg-light); color: #f8fafc; border: none;">
                        <h3 style="margin-bottom: 12px; display:flex; align-items:center; gap:8px;">
                            <i class="ph-fill ph-question"></i> Need Help?
                        </h3>
                        <p style="color: #f8fafc; margin-bottom: 16px; font-size:0.95rem;">Talk to our expert counselors to find the best college for your profile.</p>
                        <button class="btn btn-primary" style="width:100%;">Book Free Counseling</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

</body>
</html>
