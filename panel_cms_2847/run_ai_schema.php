<?php
require_once 'db.php';

echo "<h2>Running AI Systems Schema Update...</h2>";

$sql = file_get_contents('ai_systems_schema.sql');

if (!$sql) {
    die("Error reading ai_systems_schema.sql");
}

try {
    $pdo->exec($sql);
    echo "<p style='color:green;'>AI Systems schema created/updated successfully.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error executing schema: " . $e->getMessage() . "</p>";
}
?>
<br>
<a href="dashboard.php">Back to Dashboard</a>
