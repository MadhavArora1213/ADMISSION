<?php
require_once 'db.php';

// First, check if `exam_name` exists. If not, check if `name` exists and rename it.
$stmt = $pdo->prepare("SHOW COLUMNS FROM exams LIKE 'exam_name'");
$stmt->execute();
if ($stmt->rowCount() == 0) {
    // Check if 'name' exists
    $stmt2 = $pdo->prepare("SHOW COLUMNS FROM exams LIKE 'name'");
    $stmt2->execute();
    if ($stmt2->rowCount() > 0) {
        $pdo->exec("ALTER TABLE exams CHANGE COLUMN name exam_name VARCHAR(255) NOT NULL");
    } else {
        // If neither exists (unlikely, but safe), just add exam_name
        $pdo->exec("ALTER TABLE exams ADD COLUMN exam_name VARCHAR(255) NOT NULL");
    }
}

// Check if exam_slug exists. If not add it.
$stmt = $pdo->prepare("SHOW COLUMNS FROM exams LIKE 'exam_slug'");
$stmt->execute();
if ($stmt->rowCount() == 0) {
    // Generate a temporary slug for existing rows to satisfy UNIQUE constraint if needed
    // First add without UNIQUE
    $pdo->exec("ALTER TABLE exams ADD COLUMN exam_slug VARCHAR(255) NULL");
    // Update existing rows
    $pdo->exec("UPDATE exams SET exam_slug = CONCAT('exam-', id) WHERE exam_slug IS NULL");
    // Modify to be UNIQUE NOT NULL
    $pdo->exec("ALTER TABLE exams MODIFY COLUMN exam_slug VARCHAR(255) NOT NULL UNIQUE");
}

$columns = [
    "exam_abbreviation VARCHAR(20) NULL",
    "conducting_body VARCHAR(255) NULL",
    "conducting_body_logo VARCHAR(255) NULL",
    "exam_level ENUM('national','state','university','institute') NULL",
    "exam_mode ENUM('online','offline','both') NULL",
    "exam_frequency ENUM('annual','biannual','quarterly','monthly') NULL",
    "participating_colleges_count INT DEFAULT 0",
    "applicants_last_year INT DEFAULT 0",
    "is_national BOOLEAN DEFAULT FALSE",
    "status ENUM('active','upcoming','completed','cancelled') DEFAULT 'upcoming'",
    "age_min INT NULL",
    "age_max INT NULL",
    "min_percentage_required FLOAT NULL",
    "qualifying_exam VARCHAR(255) NULL",
    "nationality ENUM('indian','nri','both') NULL",
    "total_marks INT NULL",
    "total_questions INT NULL",
    "duration_minutes INT NULL",
    "subjects_json JSON NULL",
    "marking_scheme JSON NULL",
    "sections JSON NULL",
    "language_options JSON NULL",
    "application_fee_general DECIMAL(8,2) NULL",
    "application_fee_obc DECIMAL(8,2) NULL",
    "application_fee_sc_st DECIMAL(8,2) NULL",
    "application_fee_pwd DECIMAL(8,2) NULL",
    "application_fee_female DECIMAL(8,2) NULL",
    "application_url VARCHAR(255) NULL",
    "official_website VARCHAR(255) NULL",
    "syllabus_pdf_url VARCHAR(255) NULL",
    "result_url VARCHAR(255) NULL",
    "scorecard_url VARCHAR(255) NULL",
    "counselling_authority VARCHAR(255) NULL",
    "counselling_rounds TINYINT NULL",
    "merit_list_url VARCHAR(255) NULL",
    "normalisation_method TEXT NULL",
    "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

foreach ($columns as $col) {
    $col_name = explode(' ', trim($col))[0];
    $stmt = $pdo->prepare("SHOW COLUMNS FROM exams LIKE ?");
    $stmt->execute([$col_name]);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE exams ADD COLUMN $col");
    }
}

// Now execute the new tables script
$sql = file_get_contents('exams_schema_new_tables.sql');
$pdo->exec($sql);

echo "Exams schema successfully executed!";
?>
