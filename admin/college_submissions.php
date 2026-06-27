<?php
session_start();
require_once __DIR__ . '/db.php';
if (empty($_SESSION['admin_id'])) { header('Location: /ADMISSION/admin/index.php'); exit; }

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    $id = $_POST['submission_id'] ?? '';

    if ($act === 'approve' && $id) {
        $stmt = $pdo->prepare("SELECT * FROM college_submissions WHERE id=?");
        $stmt->execute([$id]);
        $sub = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sub) {
            $data = json_decode($sub['data_json'], true);
            $collegeId = $sub['college_id'];
            $type = $sub['submission_type'];

            // Apply data to main tables based on type
            switch ($type) {
                case 'profile':
                    if ($data && $collegeId) {
                        $fields = [];
                        $vals = [];
                        foreach (['address','city','state','pincode','phone','email','website','established_year','ownership','description','image','logo','slug'] as $f) {
                            if (isset($data[$f])) { $fields[] = "$f=?"; $vals[] = $data[$f]; }
                        }
                        if ($fields) { $vals[] = $collegeId; $pdo->prepare("UPDATE colleges SET ".implode(',',$fields)." WHERE id=?")->execute($vals); }
                    }
                    break;
                case 'courses':
                    if (isset($data['course_name']) && $collegeId) {
                        $cid = $data['course_id'] ?? 0;
                        if ($cid) {
                            $pdo->prepare("UPDATE college_courses SET course_name=?,duration=?,fee=?,intake=? WHERE id=? AND college_id=?")
                                ->execute([$data['course_name'],$data['duration']??'',$data['fee']??0,$data['intake']??0,$cid,$collegeId]);
                        } else {
                            $pdo->prepare("INSERT INTO college_courses (college_id,course_name,duration,fee,intake) VALUES (?,?,?,?,?)")
                                ->execute([$collegeId,$data['course_name'],$data['duration']??'',$data['fee']??0,$data['intake']??0]);
                        }
                    }
                    break;
                case 'placements':
                    if (isset($data['year']) && $collegeId) {
                        $pdo->prepare("INSERT INTO college_placements (college_id,year,avg_package,max_package,placement_rate,top_recruiters) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE avg_package=VALUES(avg_package),max_package=VALUES(max_package),placement_rate=VALUES(placement_rate),top_recruiters=VALUES(top_recruiters)")
                            ->execute([$collegeId,$data['year'],$data['avg_package']??0,$data['max_package']??0,$data['placement_rate']??0,$data['top_recruiters']??'']);
                    }
                    break;
                case 'cutoffs':
                    if (isset($data['exam_name']) && $collegeId) {
                        $pdo->prepare("INSERT INTO college_cutoffs (college_id,exam_name,year,round,cutoff_score,category) VALUES (?,?,?,?,?,?)")
                            ->execute([$collegeId,$data['exam_name'],$data['year']??date('Y'),$data['round']??'',$data['cutoff_score']??'',$data['category']??'general']);
                    }
                    break;
                case 'seat_matrix':
                    if (isset($data['course_id']) && $collegeId) {
                        $pdo->prepare("INSERT INTO seat_matrix (college_id,course_id,category,total_seats) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE total_seats=VALUES(total_seats)")
                            ->execute([$collegeId,$data['course_id'],$data['category']??'general',$data['total_seats']??0]);
                    }
                    break;
            }

            $pdo->prepare("UPDATE college_submissions SET status='approved',reviewed_by=?,reviewed_at=NOW() WHERE id=?")
                ->execute([$_SESSION['admin_id'], $id]);
            $msg = 'Submission approved and applied to database.';
        }
    }

    if ($act === 'reject' && $id) {
        $reason = trim($_POST['rejection_reason'] ?? '');
        $pdo->prepare("UPDATE college_submissions SET status='rejected',rejection_reason=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?")
            ->execute([$reason ?: 'Rejected by admin', $_SESSION['admin_id'], $id]);
        $msg = 'Submission rejected.';
    }
}

$subs = $pdo->query("
    SELECT s.*, a.institute_name, a.email
    FROM college_submissions s
    LEFT JOIN college_accounts a ON s.account_id = a.id
    ORDER BY FIELD(s.status,'pending','approved','rejected'), s.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$typeLabels = ['profile'=>'College Profile','courses'=>'Course','placements'=>'Placement','cutoffs'=>'Cutoff','seat_matrix'=>'Seat Matrix','facilities'=>'Facilities','faqs'=>'FAQs'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>College Submissions – Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://unpkg.com/@phosphor-icons/web"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans',sans-serif;background:#f1f5f9;padding:24px}
.page-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
.page-header h1{font-size:1.4rem;font-weight:800;color:#0B2447}
.msg{padding:12px 16px;border-radius:10px;background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;font-size:.82rem;margin-bottom:20px}
.card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;margin-bottom:16px}
table{width:100%;border-collapse:collapse;font-size:.8rem}
th{text-align:left;padding:10px 12px;background:#f8fafc;color:#64748b;font-weight:600;border-bottom:1px solid #e2e8f0;font-size:.72rem;text-transform:uppercase}
td{padding:10px 12px;border-bottom:1px solid #f1f5f9;color:#334155}
.badge{display:inline-flex;padding:3px 8px;border-radius:5px;font-size:.65rem;font-weight:600}
.badge-green{background:#dcfce7;color:#166534}
.badge-yellow{background:#fef3c7;color:#92400e}
.badge-red{background:#fef2f2;color:#991b1b}
.badge-blue{background:#eff6ff;color:#1d4ed8}
.btn{padding:6px 12px;border:none;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;font-family:inherit}
.btn-green{background:#16a34a;color:#fff}.btn-green:hover{background:#15803d}
.btn-red{background:#dc2626;color:#fff}.btn-red:hover{background:#b91c1c}
.btn-sm{padding:4px 8px;font-size:.7rem}
.btn-ghost{background:#f1f5f9;color:#334155}
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;align-items:center;justify-content:center}
.modal-bg.show{display:flex}
.modal{background:#fff;border-radius:14px;padding:24px;width:100%;max-width:500px}
.modal h3{font-size:1rem;font-weight:700;margin-bottom:12px;color:#0B2447}
.modal textarea{width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:.82rem;font-family:inherit;margin-bottom:12px}
.modal .btns{display:flex;gap:8px;justify-content:flex-end}
.data-preview{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;font-size:.78rem;color:#475569;max-height:120px;overflow:auto;margin-top:4px;white-space:pre-wrap;word-break:break-all}
.filters{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
.filters select,.filters input{padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:.8rem;font-family:inherit}
</style>
</head>
<body>
<div class="page-header">
  <h1><i class="ph ph-inbox"></i> College Submissions</h1>
  <a href="/ADMISSION/admin/dashboard.php" style="font-size:.82rem;color:#19376D;text-decoration:none"><i class="ph ph-arrow-left"></i> Back</a>
</div>

<?php if($msg): ?><div class="msg"><?=$msg?></div><?php endif;?>

<div class="filters">
  <select id="filterStatus" onchange="filterTable()">
    <option value="">All Status</option>
    <option value="pending">Pending</option>
    <option value="approved">Approved</option>
    <option value="rejected">Rejected</option>
  </select>
  <select id="filterType" onchange="filterTable()">
    <option value="">All Types</option>
    <option value="profile">Profile</option>
    <option value="courses">Courses</option>
    <option value="placements">Placements</option>
    <option value="cutoffs">Cutoffs</option>
    <option value="seat_matrix">Seat Matrix</option>
  </select>
</div>

<div class="card">
<table>
<thead>
<tr><th>Institute</th><th>Type</th><th>Data</th><th>Status</th><th>Date</th><th>Actions</th></tr>
</thead>
<tbody>
<?php if (empty($subs)): ?>
<tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:32px">No submissions yet.</td></tr>
<?php endif; ?>
<?php foreach($subs as $s): ?>
<tr class="sub-row" data-status="<?=$s['status']?>" data-type="<?=$s['submission_type']?>">
  <td style="font-weight:600"><?=htmlspecialchars($s['institute_name'] ?? 'Unknown')?></td>
  <td><span class="badge badge-blue"><?=($typeLabels[$s['submission_type']] ?? $s['submission_type'])?></span></td>
  <td>
    <button class="btn btn-ghost btn-sm" onclick="this.nextElementSibling.classList.toggle('show');this.nextElementSibling.style.display=this.nextElementSibling.style.display==='block'?'none':'block'">View Data</button>
    <div class="data-preview" style="display:none"><?=htmlspecialchars($s['data_json'])?></div>
  </td>
  <td><span class="badge <?=($s['status']==='approved'?'badge-green':($s['status']==='rejected'?'badge-red':'badge-yellow'))?>"><?=ucfirst($s['status'])?></span></td>
  <td><?=date('d M Y', strtotime($s['created_at']))?></td>
  <td>
    <?php if($s['status']==='pending'): ?>
    <form method="POST" style="display:inline"><input type="hidden" name="action" value="approve"><input type="hidden" name="submission_id" value="<?=$s['id']?>"><button class="btn btn-green btn-sm"><i class="ph ph-check"></i> Approve</button></form>
    <button class="btn btn-red btn-sm" onclick="showReject('<?=$s['id']?>')"><i class="ph ph-x"></i> Reject</button>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach;?>
</tbody>
</table>
</div>

<div class="modal-bg" id="rejectModal">
<div class="modal">
  <h3>Reject Submission</h3>
  <form method="POST">
    <input type="hidden" name="action" value="reject">
    <input type="hidden" name="submission_id" id="rejectId">
    <textarea name="rejection_reason" rows="3" placeholder="Reason for rejection (optional)"></textarea>
    <div class="btns">
      <button type="button" class="btn btn-ghost" onclick="document.getElementById('rejectModal').classList.remove('show')">Cancel</button>
      <button type="submit" class="btn btn-red">Reject</button>
    </div>
  </form>
</div>
</div>

<script>
function showReject(id){
  document.getElementById('rejectId').value=id;
  document.getElementById('rejectModal').classList.add('show');
}
function filterTable(){
  var st=document.getElementById('filterStatus').value;
  var ty=document.getElementById('filterType').value;
  document.querySelectorAll('.sub-row').forEach(function(r){
    var show=true;
    if(st && r.dataset.status!==st) show=false;
    if(ty && r.dataset.type!==ty) show=false;
    r.style.display=show?'':'none';
  });
}
</script>
</body>
</html>
