<?php
/**
 * Database Import Script
 * Imports the SQL schema from if0_42146789_temanpuli.sql
 */

// Get database credentials from environment variables
$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$password = getenv('MYSQLPASSWORD');
$database = getenv('MYSQLDATABASE');

if (!$host || !$user || !$password || !$database) {
    die('Error: Missing database credentials in environment variables');
}

// Connect to MySQL
$mysqli = new mysqli($host, $user, $password, $database);

// Check connection
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// Read SQL file
$sqlFile = __DIR__ . '/if0_42146789_temanpuli.sql';
if (!file_exists($sqlFile)) {
    die('Error: SQL file not found at ' . $sqlFile);
}

$sqlContent = file_get_contents($sqlFile);

// Split queries (simple split on semicolon)
$queries = array_filter(array_map('trim', explode(';', $sqlContent)));

$successCount = 0;
$errorCount = 0;
$errors = [];

foreach ($queries as $query) {
    // Skip empty queries and comments
    if (empty($query) || strpos(trim($query), '--') === 0) {
        continue;
    }

    if ($mysqli->query($query) === TRUE) {
        $successCount++;
    } else {
        $errorCount++;
        $errors[] = $mysqli->error;
    }
}

$mysqli->close();

// Output result
echo "<h2>Database Import Result</h2>";
echo "<p><strong>Successful Queries:</strong> $successCount</p>";
if ($errorCount > 0) {
    echo "<p><strong>Failed Queries:</strong> $errorCount</p>";
    echo "<p><strong>Errors:</strong></p><ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green;'><strong>✓ Database imported successfully!</strong></p>";
}
?>

