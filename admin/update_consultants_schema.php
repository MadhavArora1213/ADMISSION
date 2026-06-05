<?php
require_once 'db.php';

try {
    $sql = "ALTER TABLE consultants 
            ADD COLUMN profile_picture VARCHAR(255) NULL AFTER consultant_name,
            ADD COLUMN slug VARCHAR(255) NULL AFTER consultant_name,
            ADD COLUMN specializations VARCHAR(255) NULL AFTER experience_years,
            ADD COLUMN office_location VARCHAR(255) NULL,
            ADD COLUMN languages_spoken VARCHAR(255) NULL,
            ADD COLUMN website_url VARCHAR(255) NULL,
            ADD COLUMN bio TEXT NULL";
            
    $pdo->exec($sql);
    echo "Successfully updated consultants table schema.\n";
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
