<?php
require_once 'db.php';

echo "<h2>Running Rankings & Seat Matrix Schema Update...</h2>";

$sql = file_get_contents('rankings_schema.sql');

if (!$sql) {
    die("Error reading rankings_schema.sql");
}

try {
    $pdo->exec($sql);
    echo "<p style='color:green;'>Rankings & Seat Matrix schema created/updated successfully.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error executing schema: " . $e->getMessage() . "</p>";
}
?>
