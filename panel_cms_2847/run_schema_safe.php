<?php
require_once 'db.php';

// Add columns safely for university_details_schema.sql
$details_columns = [
    "founded_year YEAR NULL",
    "type_label VARCHAR(100) NULL",
    "campus_type ENUM('urban', 'semi-urban', 'rural') NULL",
    "overall_rating_avg FLOAT DEFAULT 0",
    "total_reviews INT DEFAULT 0",
    "rating_distribution JSON NULL",
    "verified_reviews_count INT DEFAULT 0",
    "publish_status ENUM('draft', 'published', 'archived') DEFAULT 'draft'"
];

foreach ($details_columns as $col) {
    $col_name = explode(' ', trim($col))[0];
    $stmt = $pdo->prepare("SHOW COLUMNS FROM universities LIKE ?");
    $stmt->execute([$col_name]);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE universities ADD COLUMN $col");
    }
}

$media_columns = [
    "image_type ENUM('campus', 'lab', 'hostel', 'event', 'classroom') NULL",
    "video_url VARCHAR(255) NULL",
    "video_type ENUM('tour', 'placement', 'event', 'alumni_talk') NULL",
    "caption VARCHAR(300) NULL",
    "sort_order TINYINT DEFAULT 0",
    "document_type ENUM('brochure', 'prospectus', 'annual_report', 'ranking_cert') NULL",
    "document_url VARCHAR(255) NULL",
    "`360_tour_url` VARCHAR(255) NULL",
    "virtual_tour_enabled BOOLEAN DEFAULT FALSE"
];

foreach ($media_columns as $col) {
    $col_name = explode(' ', trim($col))[0];
    // Remove backticks for checking
    $col_name_clean = str_replace('`', '', $col_name);
    $stmt = $pdo->prepare("SHOW COLUMNS FROM university_media LIKE ?");
    $stmt->execute([$col_name_clean]);
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE university_media ADD COLUMN $col");
    }
}

// Now execute the rest of the script (all the CREATE TABLE statements)
$sql = file_get_contents('university_details_schema.sql');
// Remove the two ALTER TABLE blocks
$parts = explode("-- 3. university_content", $sql);
if(count($parts) > 1) {
    $create_tables_sql = "-- 3. university_content" . $parts[1];
    $pdo->exec($create_tables_sql);
}

echo "All schema fixes applied successfully!";
?>
