<?php
require_once 'db.php';

try {
    $sql = "ALTER TABLE invoices 
            ADD COLUMN invoice_description TEXT NULL AFTER invoice_number,
            ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0.00 AFTER subtotal_amount,
            ADD COLUMN invoice_file VARCHAR(255) NULL AFTER due_date";
            
    $pdo->exec($sql);
    echo "Successfully updated invoices table schema.\n";
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
