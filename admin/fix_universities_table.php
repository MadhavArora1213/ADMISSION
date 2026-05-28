<?php
require_once 'db.php';

$columns_to_add = [
    "university_type ENUM('govt', 'private', 'deemed', 'autonomous') NULL",
    "ownership ENUM('central', 'state', 'private_trust', 'minority') NULL",
    "status ENUM('active', 'pending', 'archived', 'rejected') DEFAULT 'pending'",
    "is_featured BOOLEAN DEFAULT FALSE",
    "is_verified BOOLEAN DEFAULT FALSE",
    "featured_order INT DEFAULT 0",
    "ranking_nirf INT NULL",
    "ranking_qs INT NULL",
    "ranking_times INT NULL",
    "city_id INT NULL",
    "state_id INT NULL",
    "established_year YEAR NULL",
    "data_quality_score TINYINT DEFAULT 0",
    "autonomous BOOLEAN DEFAULT FALSE",
    "naac_grade ENUM('A++', 'A+', 'A', 'B++', 'B+', 'B', 'C') NULL",
    "ugc_approved BOOLEAN DEFAULT FALSE",
    "aicte_approved BOOLEAN DEFAULT FALSE",
    "nba_approved BOOLEAN DEFAULT FALSE",
    "total_students INT DEFAULT 0",
    "total_faculty INT DEFAULT 0",
    "campus_area_acres FLOAT NULL",
    "verification_status ENUM('unverified', 'pending', 'verified', 'disputed') DEFAULT 'unverified'",
    "verified_by CHAR(36) NULL",
    "verified_at TIMESTAMP NULL",
    "rejection_reason TEXT NULL",
    "duplicate_of CHAR(36) NULL",
    "import_batch_id CHAR(36) NULL",
    "last_data_audit_at TIMESTAMP NULL",
    "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

foreach ($columns_to_add as $col) {
    // Extract column name
    $col_name = explode(' ', trim($col))[0];
    
    // Check if column exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM universities LIKE ?");
    $stmt->execute([$col_name]);
    if ($stmt->rowCount() == 0) {
        echo "Adding column $col_name...<br>";
        $pdo->exec("ALTER TABLE universities ADD COLUMN $col");
    } else {
        echo "Column $col_name already exists.<br>";
    }
}

// Ensure foreign keys are added if possible (only city_id and state_id are safe here, others might need index)
try {
    $pdo->exec("ALTER TABLE universities ADD FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE SET NULL");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE universities ADD FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE SET NULL");
} catch (Exception $e) {}

echo "Finished altering universities table.";
?>
