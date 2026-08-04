<?php
/**
 * Database Import Script
 * Imports the SQL schema from if0_42146789_temanpuli.sql
 * IMPORTANT: Delete this file after visiting it once in the browser!
 */

// Get database credentials from environment variables (using the same pattern as config/db.php)
$host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '';
$database = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'temanpuli';
$port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306';

if (!$host || !$user || !$database) {
    die('Error: Missing database credentials in environment variables');
}

// Disable mysqli strict exceptions to prevent 500 errors on query failures
mysqli_report(MYSQLI_REPORT_OFF);

// Connect to MySQL using try-catch for PHP 8.1+ connection exception compatibility
try {
    $mysqli = @new mysqli($host, $user, $password, $database, (int)$port);
    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error);
    }
} catch (Exception $e) {
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()) . '<br><br>' .
        'Coba cek kembali apakah variabel environment database (MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE) ' .
        'sudah terhubung di menu Variables pada layanan PHP Kakak di Railway.');
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
    if (empty($query) || strpos(trim($query), '--') === 0 || strpos(trim($query), '/*') === 0) {
        continue;
    }

    if ($mysqli->query($query) === TRUE) {
        $successCount++;
    } else {
        $errorCount++;
        $errors[] = $mysqli->error . ' (Query: ' . substr($query, 0, 50) . '...)';
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
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: green;'><strong>✓ Database imported successfully!</strong></p>";
}
echo "<p style='color: red; font-weight: bold;'>WARNING: Please delete import_db.php from your project now to prevent security vulnerabilities!</p>";
?>
