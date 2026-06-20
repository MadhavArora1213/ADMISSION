<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: index.php'); exit; }
require_once 'db.php';

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'config';

// Ensure config exists
$pdo->query("INSERT INTO calculator_config (id) VALUES (1) ON DUPLICATE KEY UPDATE id=1");

// Handle Config Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_config') {
    $default_interest_rate_pct = (float)$_POST['default_interest_rate_pct'];
    $max_tenure_months = (int)$_POST['max_tenure_months'];
    $min_loan_amount = (float)$_POST['min_loan_amount'];
    $max_loan_amount = (float)$_POST['max_loan_amount'];
    $processing_fee_pct = (float)$_POST['processing_fee_pct'];
    $tax_rate = (float)$_POST['tax_rate'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Process Loan Providers
    $lp_names = $_POST['lp_name'] ?? [];
    $lp_rates = $_POST['lp_rate'] ?? [];
    $lp_tenures = $_POST['lp_tenure'] ?? [];
    
    $loan_providers_arr = [];
    foreach($lp_names as $i => $name) {
        if(trim($name) !== '') {
            $loan_providers_arr[] = [
                'name' => trim($name),
                'interest_rate_range' => trim($lp_rates[$i] ?? ''),
                'max_tenure' => (int)($lp_tenures[$i] ?? 0)
            ];
        }
    }
    $loan_providers = json_encode($loan_providers_arr, JSON_UNESCAPED_UNICODE);

    // Process Affiliate Links
    $aff_providers = $_POST['aff_provider'] ?? [];
    $aff_urls = $_POST['aff_url'] ?? [];
    $aff_ctas = $_POST['aff_cta'] ?? [];

    $affiliate_links_arr = [];
    foreach($aff_providers as $i => $provider) {
        if(trim($provider) !== '') {
            $affiliate_links_arr[] = [
                'provider' => trim($provider),
                'url' => trim($aff_urls[$i] ?? ''),
                'cta_label' => trim($aff_ctas[$i] ?? 'Apply Now')
            ];
        }
    }
    $affiliate_links = json_encode($affiliate_links_arr, JSON_UNESCAPED_UNICODE);

    $stmt = $pdo->prepare("
        UPDATE calculator_config 
        SET default_interest_rate_pct=?, max_tenure_months=?, min_loan_amount=?, max_loan_amount=?,
            processing_fee_pct=?, tax_rate=?, is_active=?, loan_providers=?, affiliate_links=?
        WHERE id=1
    ");
    $stmt->execute([
        $default_interest_rate_pct, $max_tenure_months, $min_loan_amount, $max_loan_amount,
        $processing_fee_pct, $tax_rate, $is_active, $loan_providers, $affiliate_links
    ]);
    
    header("Location: emi_calculator.php?tab=config&msg=Configuration Saved");
    exit;
}

if ($tab === 'config') {
    $config = $pdo->query("SELECT * FROM calculator_config WHERE id=1")->fetch();
    $lp_data = json_decode($config['loan_providers'] ?: '[]', true);
    if(empty($lp_data)) $lp_data = [['name'=>'', 'interest_rate_range'=>'', 'max_tenure'=>'']];
    
    $aff_data = json_decode($config['affiliate_links'] ?: '[]', true);
    if(empty($aff_data)) $aff_data = [['provider'=>'', 'url'=>'', 'cta_label'=>'']];
    
} else {
    // Sessions
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
    $where = "1=1";
    $params = [];
    if ($search) {
        $where .= " AND (c.name LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as college_name, u.full_name, u.email, u.phone 
        FROM calculator_sessions s 
        LEFT JOIN colleges c ON s.college_id = c.id 
        LEFT JOIN users u ON s.user_id = u.id 
        WHERE $where 
        ORDER BY s.created_at DESC 
        LIMIT 100
    ");
    $stmt->execute($params);
    $sessions = $stmt->fetchAll();
    
    // Stats
    $stat_total = $pdo->query("SELECT COUNT(*) FROM calculator_sessions")->fetchColumn();
    $stat_leads = $pdo->query("SELECT COUNT(*) FROM calculator_sessions WHERE lead_captured_at IS NOT NULL")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMI Calculator | Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <style>
        body { background-color: var(--bg-light); }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #0f172a; color: #f8fafc; display: flex; flex-direction: column; position: fixed; height: 100vh; left: 0; top: 0; overflow-y: auto; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header .logo { font-size: 1.3rem; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .sidebar-nav { padding: 24px 0; flex: 1; }
        .sidebar-nav a { display: flex; align-items: center; gap: 12px; padding: 16px 24px; color: #f8fafc; transition: all 0.3s ease; text-decoration: none;}
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.05); border-left: 4px solid var(--primary); }
        .main-content { flex: 1; margin-left: 280px; display: flex; flex-direction: column; }
        .topbar { height: 80px; background: #f8fafc; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: flex-end; padding: 0 32px; position: sticky; top: 0; z-index: 10; }
        .content-area { padding: 32px; }
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-header h2 { font-size: 2rem; font-weight: 800; }
        
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;}
        .tab-link { padding: 10px 20px; font-weight: 600; color: var(--text-muted); font-size: 0.9rem; text-decoration: none; transition: all 0.2s; border-bottom: 3px solid transparent; }
        .tab-link:hover { color: var(--primary); }
        .tab-link.active { color: var(--primary); border-bottom-color: var(--primary); }
        
        .panel { background: #fff; border-radius: 12px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); padding: 24px; margin-bottom: 24px;}
        .panel-nopad { padding: 0; overflow: hidden; }
        
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th, td { padding: 14px 16px; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: top;}
        th { font-weight: 700; color: var(--text-muted); text-transform: uppercase; font-size: 0.75rem; background: #f8fafc; }
        tr:hover { background-color: rgba(0,0,0,0.015); }
        
        .badge { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; display: inline-block; white-space: nowrap;}
        .b-green { background:rgba(11,36,71,0.04); color:#0B2447; }
        .b-gray { background:#F8FAFC; color:rgba(15,23,42,0.65); }
        
        .msg-alert { padding: 14px 20px; border-radius: 8px; background: rgba(11,36,71,0.04); color: #0B2447; margin-bottom: 20px; border: 1px solid rgba(11,36,71,0.04); }
        
        .btn-primary { padding: 12px 24px; font-size: 0.95rem; background: var(--primary); color: #fff; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;}
        .btn-primary:hover { opacity: 0.9; }
        
        .search-box { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; padding: 7px 14px; width: 250px;}
        .search-box input { border: none; outline: none; font-size: 0.9rem; width: 100%; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { font-size: 0.85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;}
        .form-group input { padding: 12px; font-size: 0.95rem; border: 1px solid var(--border-color); border-radius: 8px; background: #fff; font-family: inherit;}
        
        /* Dynamic Lists */
        .dynamic-list-container { border: 1px solid var(--border-color); padding: 16px; border-radius: 12px; background: #f8fafc; margin-top: 8px;}
        .dynamic-row { display: flex; gap: 12px; align-items: flex-end; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px dashed var(--border-color);}
        .dynamic-row:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .dynamic-row .form-group { flex: 1; margin: 0; }
        .dynamic-row .form-group label { font-size: 0.75rem; }
        .dynamic-row .form-group input { padding: 8px 12px; font-size: 0.9rem;}
        
        .btn-danger-sm { background: rgba(15,23,42,0.06); color: #0B2447; border: 1px solid rgba(15,23,42,0.06); padding: 8px 12px; border-radius: 8px; cursor: pointer; font-weight:bold; height: 36px;}
        .btn-danger-sm:hover { background: rgba(15,23,42,0.06); }
        
        .btn-secondary-sm { background: #fff; color: var(--text-dark); border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 0.85rem; margin-top: 10px;}
        .btn-secondary-sm:hover { background: #F8FAFC; }
    </style>
</head>
<body>
<div class="admin-layout">
    <?php include 'sidebar.php'; ?>
    <main class="main-content">
        <header class="topbar">
            <div class="user-profile">
                <span><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></span>
            </div>
        </header>
        <div class="content-area">
            <div class="page-header">
                <div>
                    <h2><i class="ph ph-calculator" style="color:var(--primary);"></i> Fee / EMI Calculator</h2>
                    <p style="color:var(--text-muted);">Manage loan calculation settings, affiliate providers, and view warm leads.</p>
                </div>
            </div>

            <?php if(isset($_GET['msg'])): ?>
            <div class="msg-alert"><i class="ph ph-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>

            <div class="filter-bar">
                <a href="?tab=config" class="tab-link <?php echo $tab=='config'?'active':''; ?>">Global Configuration</a>
                <a href="?tab=leads" class="tab-link <?php echo $tab=='leads'?'active':''; ?>">Calculator Sessions & Leads</a>
            </div>

            <?php if($tab === 'config'): ?>
                <form method="POST" class="panel">
                    <input type="hidden" name="action" value="save_config">
                    
                    <div style="margin-bottom:24px; display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" name="is_active" id="is_active" value="1" <?php echo $config['is_active'] ? 'checked' : ''; ?> style="width:20px; height:20px; cursor:pointer;">
                        <label for="is_active" style="font-weight:700; color:rgba(15,23,42,0.9); font-size:1.1rem; cursor:pointer;">Enable Calculator on Frontend</label>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Default Interest Rate (%)</label>
                            <input type="number" step="0.01" name="default_interest_rate_pct" value="<?php echo htmlspecialchars($config['default_interest_rate_pct']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Max Tenure (Months)</label>
                            <input type="number" name="max_tenure_months" value="<?php echo htmlspecialchars($config['max_tenure_months']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Processing Fee (%)</label>
                            <input type="number" step="0.01" name="processing_fee_pct" value="<?php echo htmlspecialchars($config['processing_fee_pct']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Min Loan Amount (₹)</label>
                            <input type="number" step="1" name="min_loan_amount" value="<?php echo htmlspecialchars($config['min_loan_amount']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Max Loan Amount (₹)</label>
                            <input type="number" step="1" name="max_loan_amount" value="<?php echo htmlspecialchars($config['max_loan_amount']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Applicable Tax Rate (GST)</label>
                            <input type="number" step="0.01" name="tax_rate" value="<?php echo htmlspecialchars($config['tax_rate']); ?>" required>
                        </div>

                        <!-- Loan Providers UI -->
                        <div class="form-group full" style="margin-top:20px;">
                            <label><i class="ph ph-bank"></i> Loan Providers</label>
                            <div class="dynamic-list-container" id="lp-container">
                                <?php foreach($lp_data as $lp): ?>
                                <div class="dynamic-row">
                                    <div class="form-group">
                                        <label>Bank / Provider Name</label>
                                        <input type="text" name="lp_name[]" value="<?php echo htmlspecialchars($lp['name']); ?>" placeholder="e.g. HDFC Bank">
                                    </div>
                                    <div class="form-group">
                                        <label>Interest Rate Range</label>
                                        <input type="text" name="lp_rate[]" value="<?php echo htmlspecialchars($lp['interest_rate_range'] ?? ''); ?>" placeholder="e.g. 9.5% - 11%">
                                    </div>
                                    <div class="form-group" style="flex: 0.5;">
                                        <label>Max Tenure</label>
                                        <input type="number" name="lp_tenure[]" value="<?php echo htmlspecialchars($lp['max_tenure'] ?? ''); ?>" placeholder="Months">
                                    </div>
                                    <button type="button" class="btn-danger-sm remove-lp">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn-secondary-sm" id="add-lp">+ Add Provider</button>
                        </div>

                        <!-- Affiliate Monetization UI -->
                        <div class="form-group full">
                            <label><i class="ph ph-link"></i> Affiliate Monetization Links</label>
                            <div class="dynamic-list-container" id="aff-container">
                                <?php foreach($aff_data as $aff): ?>
                                <div class="dynamic-row">
                                    <div class="form-group">
                                        <label>Platform Name</label>
                                        <input type="text" name="aff_provider[]" value="<?php echo htmlspecialchars($aff['provider']); ?>" placeholder="e.g. Credenc">
                                    </div>
                                    <div class="form-group" style="flex: 2;">
                                        <label>Affiliate URL</label>
                                        <input type="url" name="aff_url[]" value="<?php echo htmlspecialchars($aff['url'] ?? ''); ?>" placeholder="https://...">
                                    </div>
                                    <div class="form-group">
                                        <label>Button Label</label>
                                        <input type="text" name="aff_cta[]" value="<?php echo htmlspecialchars($aff['cta_label'] ?? 'Apply Now'); ?>">
                                    </div>
                                    <button type="button" class="btn-danger-sm remove-aff">&times;</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="btn-secondary-sm" id="add-aff">+ Add Affiliate Link</button>
                        </div>
                    </div>
                    
                    <div style="margin-top:30px; text-align:right; border-top:1px solid var(--border-color); padding-top:20px;">
                        <button type="submit" class="btn-primary"><i class="ph ph-floppy-disk"></i> Save Configuration</button>
                    </div>
                </form>
                
                <script>
                $(document).ready(function() {
                    $('#add-lp').click(function() {
                        let html = `
                        <div class="dynamic-row">
                            <div class="form-group">
                                <label>Bank / Provider Name</label>
                                <input type="text" name="lp_name[]" placeholder="e.g. HDFC Bank">
                            </div>
                            <div class="form-group">
                                <label>Interest Rate Range</label>
                                <input type="text" name="lp_rate[]" placeholder="e.g. 9.5% - 11%">
                            </div>
                            <div class="form-group" style="flex: 0.5;">
                                <label>Max Tenure</label>
                                <input type="number" name="lp_tenure[]" placeholder="Months">
                            </div>
                            <button type="button" class="btn-danger-sm remove-lp">&times;</button>
                        </div>`;
                        $('#lp-container').append(html);
                    });
                    
                    $(document).on('click', '.remove-lp', function() {
                        if($('.remove-lp').length > 1) {
                            $(this).closest('.dynamic-row').remove();
                        } else {
                            $(this).closest('.dynamic-row').find('input').val('');
                        }
                    });

                    $('#add-aff').click(function() {
                        let html = `
                        <div class="dynamic-row">
                            <div class="form-group">
                                <label>Platform Name</label>
                                <input type="text" name="aff_provider[]" placeholder="e.g. Credenc">
                            </div>
                            <div class="form-group" style="flex: 2;">
                                <label>Affiliate URL</label>
                                <input type="url" name="aff_url[]" placeholder="https://...">
                            </div>
                            <div class="form-group">
                                <label>Button Label</label>
                                <input type="text" name="aff_cta[]" value="Apply Now">
                            </div>
                            <button type="button" class="btn-danger-sm remove-aff">&times;</button>
                        </div>`;
                        $('#aff-container').append(html);
                    });

                    $(document).on('click', '.remove-aff', function() {
                        if($('.remove-aff').length > 1) {
                            $(this).closest('.dynamic-row').remove();
                        } else {
                            $(this).closest('.dynamic-row').find('input').val('');
                        }
                    });
                });
                </script>

            <?php else: ?>
                <div style="display:flex; justify-content:space-between; margin-bottom:15px; align-items:center;">
                    <div style="display:flex; gap:15px;">
                        <div style="background:#fff; border:1px solid var(--border-color); padding:10px 20px; border-radius:8px;">
                            <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase;">Total Calculations</div>
                            <div style="font-size:1.5rem; font-weight:800;"><?php echo number_format($stat_total); ?></div>
                        </div>
                        <div style="background:#fff; border:1px solid var(--border-color); padding:10px 20px; border-radius:8px;">
                            <div style="font-size:0.75rem; color:var(--text-muted); font-weight:700; text-transform:uppercase;">Loan Leads (Clicks)</div>
                            <div style="font-size:1.5rem; font-weight:800; color:#0B2447;"><?php echo number_format($stat_leads); ?></div>
                        </div>
                    </div>
                    <form method="GET">
                        <input type="hidden" name="tab" value="leads">
                        <div class="search-box">
                            <i class="ph ph-magnifying-glass" style="color:var(--text-muted);"></i>
                            <input type="text" name="q" placeholder="Search college or user..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </form>
                </div>

                <div class="panel panel-nopad">
                    <?php if(empty($sessions)): ?>
                        <p style="color:var(--text-muted); text-align:center; padding:40px;">No calculator sessions found.</p>
                    <?php else: ?>
                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User Context</th>
                                    <th>College Context</th>
                                    <th>Calculation Details</th>
                                    <th>Loan Conversion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($sessions as $s): ?>
                                <tr>
                                    <td style="font-size:0.85rem; white-space:nowrap;"><?php echo date('M d, Y h:i A', strtotime($s['created_at'])); ?></td>
                                    <td>
                                        <?php if($s['user_id']): ?>
                                            <div style="font-weight:700; color:var(--primary);"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                            <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($s['email']); ?></div>
                                            <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo htmlspecialchars($s['phone']); ?></div>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted); font-size:0.85rem; font-style:italic;">Anonymous Guest</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($s['college_name']): ?>
                                            <div style="font-weight:700;"><?php echo htmlspecialchars($s['college_name']); ?></div>
                                            <div style="font-size:0.8rem; color:var(--text-muted);">Fee Target: ₹<?php echo number_format($s['fee_amount']); ?></div>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted); font-size:0.85rem; font-style:italic;">General Calculation</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-size:0.85rem;"><strong>Loan:</strong> ₹<?php echo number_format($s['loan_amount']); ?></div>
                                        <div style="font-size:0.85rem;"><strong>Rate:</strong> <?php echo $s['interest_rate']; ?>% for <?php echo $s['tenure_months']; ?>m</div>
                                        <?php 
                                            $emi = json_decode($s['emi_results'], true);
                                            if($emi && isset($emi['monthly_emi'])):
                                        ?>
                                            <div style="font-size:0.85rem; margin-top:4px; font-weight:700; color:#0B2447;">EMI: ₹<?php echo number_format($emi['monthly_emi']); ?>/mo</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($s['lead_captured_at']): ?>
                                            <span class="badge b-green">Applied For Loan</span>
                                            <div style="font-size:0.75rem; color:var(--text-muted); margin-top:4px;"><?php echo date('M d, h:i A', strtotime($s['lead_captured_at'])); ?></div>
                                        <?php else: ?>
                                            <span class="badge b-gray">Did Not Apply</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
