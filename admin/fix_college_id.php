<?php
require_once 'db.php';
try {
    $pdo->exec("ALTER TABLE invoices MODIFY college_id VARCHAR(36) NOT NULL");
    echo "Modified college_id to VARCHAR(36)\n";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
