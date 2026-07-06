<?php
// =============================================
// DIAGNOSTIC FILE - DELETE AFTER USE
// Visit: admissionseason.com/panel_cms_2847/diagnose.php
// =============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<style>body{font-family:monospace;padding:20px;background:#111;color:#0f0;} .ok{color:#0f0;} .err{color:#f55;} .warn{color:#fa0;} h2{color:#fff;border-bottom:1px solid #333;}</style>";
echo "<h2>🔍 AdmissionSeason Diagnostics</h2>";

// 1. PHP Version
echo "<h2>1. PHP Version</h2>";
echo "<span class='ok'>PHP " . phpversion() . "</span><br>";

// 2. Extensions
echo "<h2>2. Required Extensions</h2>";
$required = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session', 'fileinfo'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "<span class='ok'>✅ $ext loaded</span><br>";
    } else {
        echo "<span class='err'>❌ $ext NOT loaded</span><br>";
    }
}

// 3. DB Connection
echo "<h2>3. Database Connection</h2>";

require_once __DIR__ . '/db.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<span class='ok'>✅ Database connected successfully!</span><br>";

    // 4. Tables
    echo "<h2>4. Database Tables</h2>";
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (empty($tables)) {
        echo "<span class='err'>❌ No tables found! You need to import the SQL schema.</span><br>";
    } else {
        foreach ($tables as $t) {
            echo "<span class='ok'>✅ $t</span><br>";
        }
    }

    // 5. Check users table
    echo "<h2>5. Users Table Check</h2>";
    if (in_array('users', $tables)) {
        $count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        echo "<span class='ok'>✅ users table exists — $count row(s)</span><br>";
        if ($count == 0) {
            echo "<span class='warn'>⚠️ No users yet — you need to create an admin user.</span><br>";
        }
    } else {
        echo "<span class='err'>❌ 'users' table not found! Schema not imported.</span><br>";
    }

} catch (PDOException $e) {
    echo "<span class='err'>❌ DB Error: " . htmlspecialchars($e->getMessage()) . "</span><br>";
}

// 6. File permissions
echo "<h2>6. File Permissions</h2>";
$paths = [
    __DIR__ . '/db.php',
    __DIR__ . '/../uploads',
    __DIR__ . '/../assets',
];
foreach ($paths as $p) {
    if (file_exists($p)) {
        echo "<span class='ok'>✅ EXISTS: " . htmlspecialchars($p) . "</span><br>";
    } else {
        echo "<span class='err'>❌ MISSING: " . htmlspecialchars($p) . "</span><br>";
    }
}

// 7. mod_rewrite
echo "<h2>7. Apache mod_rewrite</h2>";
if (function_exists('apache_get_modules')) {
    $mods = apache_get_modules();
    if (in_array('mod_rewrite', $mods)) {
        echo "<span class='ok'>✅ mod_rewrite enabled</span><br>";
    } else {
        echo "<span class='err'>❌ mod_rewrite NOT enabled</span><br>";
    }
} else {
    echo "<span class='warn'>⚠️ Cannot detect Apache modules (PHP-FPM) — mod_rewrite status unknown</span><br>";
}

echo "<br><span style='color:#888'>🗑️ Delete this file after checking!</span>";
?>
