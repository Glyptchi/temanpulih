<?php
/**
 * Database Import Endpoint
 * Access this file via browser or curl to trigger database schema import
 * 
 * Usage: GET /import_db_endpoint.php
 * Usage: GET /import_db_endpoint.php?token=YOUR_SECRET_TOKEN
 */

// Optional: Add security token to prevent unauthorized import
define('IMPORT_TOKEN', getenv('IMPORT_TOKEN') ?: 'import123');

// Check token if provided
if (isset($_GET['token']) && $_GET['token'] !== IMPORT_TOKEN) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

header('Content-Type: application/json');

try {
    // Get connection details
    $host = getenv('MYSQLHOST') ?: 'localhost';
    $user = getenv('MYSQLUSER') ?: 'root';
    $password = getenv('MYSQLPASSWORD') ?: '';
    $database = getenv('MYSQLDATABASE') ?: 'temanpuli';
    $port = (int)(getenv('MYSQLPORT') ?: '3306');

    // Connect
    $conn = new mysqli($host, $user, $password, $database, $port);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Set charset
    $conn->set_charset('utf8mb4');

    // Read SQL file
    $sqlFile = __DIR__ . '/if0_42146789_temanpuli.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }

    $sql = file_get_contents($sqlFile);
    if (!$sql) {
        throw new Exception("Failed to read SQL file");
    }

    // Multi-query execution
    $conn->multi_query($sql);
    
    // Process all results
    $results = [];
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());

    if ($conn->error) {
        throw new Exception("SQL execution error: " . $conn->error);
    }

    // Verify tables
    $tableQuery = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $tableQuery->fetch_row()) {
        $tables[] = $row[0];
    }

    // Get record counts
    $counts = [];
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as cnt FROM `$table`");
        $row = $result->fetch_assoc();
        $counts[$table] = (int)$row['cnt'];
    }

    $conn->close();

    echo json_encode([
        'success' => true,
        'message' => 'Database schema imported successfully',
        'tables' => $tables,
        'records' => $counts
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

