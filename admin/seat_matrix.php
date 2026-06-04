<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_seat_matrix') {
        $college_id = $_POST['college_id'] ?: null;
        $course_id = $_POST['course_id'] ?: null;
        $category = $_POST['category'];
        $year = $_POST['year'];
        $total_seats = $_POST['total_seats'] ?: 0;
        $filled_seats = $_POST['filled_seats'] ?: 0;
        $source = $_POST['source'];

        $stmt = $pdo->prepare("INSERT INTO seat_matrix (college_id, course_id, category, year, total_seats, filled_seats, source) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$college_id, $course_id, $category, $year, $total_seats, $filled_seats, $source]);
        
        header("Location: seat_matrix.php?msg=added");
        exit;
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM seat_matrix WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: seat_matrix.php?msg=deleted");
    exit;
}

// Fetch Seat Matrix data
$seats = $pdo->query("SELECT s.*, col.name as college_name, crs.course_name as course_name 
                      FROM seat_matrix s 
                      LEFT JOIN colleges col ON s.college_id = col.id 
                      LEFT JOIN college_courses crs ON s.course_id = crs.id 
                      ORDER BY s.year DESC, col.name ASC")->fetchAll();

// Fetch Colleges for dropdown
$colleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name ASC")->fetchAll();

// Courses will be fetched dynamically via AJAX based on selected college

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seat Matrix Management | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body{background:var(--bg-light)}.admin-layout{display:flex;min-height:100vh}.sidebar{width:280px;background:#0f172a;color:#f8fafc;display:flex;flex-direction:column;position:fixed;height:100vh;left:0;top:0;overflow-y:auto}.sidebar-header{padding:24px;border-bottom:1px solid rgba(255,255,255,0.1)}.sidebar-header .logo{font-size:1.3rem;color:#f8fafc;display:flex;align-items:center;gap:8px}.sidebar-nav{padding:24px 0;flex:1}.sidebar-nav a{display:flex;align-items:center;gap:12px;padding:16px 24px;color:#f8fafc;transition:all .3s}.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.05);border-left:4px solid var(--primary)}.main-content{flex:1;margin-left:280px;display:flex;flex-direction:column;padding-bottom:60px}.topbar{height:80px;background:#f8fafc;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:flex-end;padding:0 32px;position:sticky;top:0;z-index:10}.content-area{padding:32px}.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.page-header h2{font-size:2rem;font-weight:800}.panel{background:#fff;border-radius:16px;border:1px solid var(--border-color);padding:24px;box-shadow:var(--shadow-sm);margin-bottom:24px}.panel h3{font-size:1.1rem;font-weight:700;color:var(--primary);margin-bottom:20px;display:flex;align-items:center;gap:8px;border-bottom:1px solid var(--border-color);padding-bottom:12px}table{width:100%;border-collapse:collapse;font-size:.88rem}th,td{padding:12px 16px;text-align:left;border-bottom:1px solid var(--border-color)}th{font-weight:700;color:var(--text-muted);text-transform:uppercase;font-size:.75rem;background:#f8fafc}tr:hover{background:rgba(0,0,0,.015)}
        .form-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;}
        .form-group{margin-bottom:16px;}
        .form-group label{display:block;font-size:.85rem;font-weight:700;color:var(--text-main);margin-bottom:8px;}
        .form-control{width:100%;padding:10px 12px;border:1px solid var(--border-color);border-radius:8px;font-size:.9rem;font-family:inherit;}
        .btn-primary{background:var(--primary);color:#fff;border:none;padding:10px 20px;border-radius:8px;font-weight:600;cursor:pointer;transition:all .2s;}
        .btn-primary:hover{background:#1e3a8a;}
        .btn-danger{background:#dc2626;color:#fff;border:none;padding:6px 12px;border-radius:6px;font-weight:600;cursor:pointer;text-decoration:none;font-size:0.75rem;}
        .alert{padding:12px 16px;border-radius:8px;margin-bottom:20px;font-size:0.9rem;font-weight:600;}
        .alert-success{background:#dcfce7;color:#166534;}
        .progress-bar-container {width:100%;background:#e2e8f0;border-radius:10px;overflow:hidden;height:8px;margin-top:6px;}
        .progress-bar {height:100%;background:var(--primary);}
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo isset($_SESSION['admin_username']) ? htmlspecialchars($_SESSION['admin_username']) : 'Admin'; ?></span>
                <a href="logout.php" style="margin-left:16px;color:#19376d;"><i class="ph ph-sign-out" style="font-size:1.5rem;"></i></a>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-table" style="color:var(--primary);"></i> Seat Matrix Management</h2>
                    <p style="color:var(--text-muted);">Manage category-wise seat allocations across colleges and courses.</p>
                </div>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success">Action completed successfully!</div>
            <?php endif; ?>

            <div class="panel">
                <h3><i class="ph ph-plus-circle"></i> Add Seat Matrix Data</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="save_seat_matrix">
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / span 2;">
                            <label>College</label>
                            <select name="college_id" id="college_select" class="form-control" required>
                                <option value="">-- Select College --</option>
                                <?php foreach($colleges as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group" style="grid-column: 1 / span 2;">
                            <label>Course</label>
                            <select name="course_id" id="course_select" class="form-control" required>
                                <option value="">-- Select College First --</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" class="form-control" required>
                                <option value="General">General</option>
                                <option value="OBC">OBC</option>
                                <option value="SC">SC</option>
                                <option value="ST">ST</option>
                                <option value="EWS">EWS</option>
                                <option value="PwD">PwD</option>
                                <option value="NRI">NRI</option>
                                <option value="Mgmt">Management (Mgmt)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Academic Year</label>
                            <input type="number" name="year" class="form-control" value="<?php echo date('Y'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Total Allocated Seats</label>
                            <input type="number" name="total_seats" class="form-control" placeholder="e.g. 120" required>
                        </div>
                        <div class="form-group">
                            <label>Filled Seats (if known)</label>
                            <input type="number" name="filled_seats" class="form-control" placeholder="e.g. 115">
                        </div>
                        <div class="form-group" style="grid-column: 1 / span 2;">
                            <label>Source (e.g., JoSAA, CSAB, Direct)</label>
                            <input type="text" name="source" class="form-control" placeholder="JoSAA Round 6">
                        </div>
                    </div>
                    <div style="margin-top:24px; text-align:right; border-top:1px solid var(--border-color); padding-top:20px;">
                        <button type="submit" class="btn-primary"><i class="ph ph-floppy-disk"></i> Save Seat Record</button>
                    </div>
                </form>
            </div>
            
            <div class="panel">
                <h3><i class="ph ph-list"></i> Seat Matrix Records</h3>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>College</th>
                                <th>Course</th>
                                <th>Category</th>
                                <th>Year</th>
                                <th>Occupancy</th>
                                <th>Source</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($seats as $row): ?>
                            <tr>
                                <td style="font-weight:600; color:var(--primary);"><?php echo htmlspecialchars($row['college_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($row['course_name'] ?? 'Unknown'); ?></td>
                                <td><span class="badge" style="background:#f1f5f9; color:#475569; padding:4px 8px;"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['year']); ?></td>
                                <td style="min-width:150px;">
                                    <div style="display:flex; justify-content:space-between; font-size:0.75rem; font-weight:700;">
                                        <span><?php echo $row['filled_seats']; ?> / <?php echo $row['total_seats']; ?> filled</span>
                                        <?php 
                                            $pct = $row['total_seats'] > 0 ? round(($row['filled_seats'] / $row['total_seats']) * 100) : 0; 
                                        ?>
                                        <span><?php echo $pct; ?>%</span>
                                    </div>
                                    <div class="progress-bar-container">
                                        <div class="progress-bar" style="width:<?php echo $pct; ?>%; <?php if($pct >= 90) echo 'background:#dc2626;'; elseif($pct >= 70) echo 'background:#eab308;'; ?>"></div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['source'] ?? '-'); ?></td>
                                <td>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn-danger" onclick="return confirm('Delete this record?');"><i class="ph ph-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($seats)): ?>
                            <tr><td colspan="7" style="text-align:center; padding:30px; color:var(--text-muted);">No seat matrix records added yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
    document.getElementById('college_select').addEventListener('change', function() {
        const collegeId = this.value;
        const courseSelect = document.getElementById('course_select');
        
        // Reset course dropdown
        courseSelect.innerHTML = '<option value="">-- Loading Courses... --</option>';
        
        if (!collegeId) {
            courseSelect.innerHTML = '<option value="">-- Select College First --</option>';
            return;
        }

        fetch('api/get_college_courses.php?college_id=' + encodeURIComponent(collegeId))
            .then(response => response.json())
            .then(data => {
                courseSelect.innerHTML = '<option value="">-- Select Course --</option>';
                if (data.length === 0) {
                    courseSelect.innerHTML = '<option value="">-- No courses found for this college --</option>';
                } else {
                    data.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.id;
                        option.textContent = course.name;
                        courseSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching courses:', error);
                courseSelect.innerHTML = '<option value="">-- Error loading courses --</option>';
            });
    });
</script>
</body>
</html>
