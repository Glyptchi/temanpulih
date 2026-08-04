<?php
// Simple SQL import script
// Reads SQL file and executes it on the connected database

$sqlFile = 'if0_42146789_temanpuli.sql';

if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile\n");
}

// Read SQL content
$sql = file_get_contents($sqlFile);

if (!$sql) {
    die("Failed to read SQL file\n");
}

// Parse connection details - similar to db.php config
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$database = getenv('MYSQLDATABASE') ?: 'temanpuli';
$port = getenv('MYSQLPORT') ?: '3306';

echo "Connecting to MySQL at $host:$port as $user...\n";

$conn = new mysqli($host, $user, $password, $database, (int)$port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

echo "✓ Connected successfully\n";

// Split SQL statements (basic split by semicolon)
$statements = array_filter(array_map('trim', preg_split('/;(?=\s*$|[\n\r])/m', $sql)));

$executed = 0;
$failed = 0;

foreach ($statements as $statement) {
    if (empty($statement)) continue;
    
    if ($conn->query($statement) === TRUE) {
        $executed++;
    } else {
        $failed++;
        echo "✗ Error: " . $conn->error . "\n";
        echo "   Statement: " . substr($statement, 0, 100) . "...\n";
    }
}

echo "\n=== Import Results ===\n";
echo "✓ Executed: $executed statements\n";
if ($failed > 0) {
    echo "✗ Failed: $failed statements\n";
} else {
    echo "✓ All statements executed successfully!\n";
}

// Verify tables were created
$result = $conn->query("SHOW TABLES");
if ($result) {
    $tableCount = $result->num_rows;
    echo "\n✓ Tables in database: $tableCount\n";
    echo "  Tables created:\n";
    while ($row = $result->fetch_row()) {
        echo "    - " . $row[0] . "\n";
    }
}

$conn->close();
echo "\n✓ Import complete!\n";
?>

