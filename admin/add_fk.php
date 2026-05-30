<?php
require 'db.php';
$sql = "ALTER TABLE notification_logs ADD CONSTRAINT fk_notification_logs_user FOREIGN KEY (user_id) REFERENCES users(id)";
try {
    $pdo->exec($sql);
    echo "Foreign key added successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
