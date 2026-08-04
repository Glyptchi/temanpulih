<?php
/**
 * Database Schema Import Script
 * Imports SQL from if0_42146789_temanpuli.sql to Railway MySQL
 */

// Get connection details from environment (set via Railway reference variables)
$host = getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$database = getenv('MYSQLDATABASE') ?: 'temanpuli';
$port = (int)(getenv('MYSQLPORT') ?: '3306');

echo "========== Database Schema Import ==========\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "User: $user\n";
echo "Database: $database\n";
echo "==========================================\n\n";

// Connect to database
$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error . "\n");
}

echo "✓ Connected to MySQL\n\n";

// Read SQL file
$sqlFile = __DIR__ . '/if0_42146789_temanpuli.sql';
if (!file_exists($sqlFile)) {
    $conn->close();
    die("❌ SQL file not found: $sqlFile\n");
}

$sqlContent = file_get_contents($sqlFile);
if (!$sqlContent) {
    $conn->close();
    die("❌ Failed to read SQL file\n");
}

echo "✓ Read SQL file (" . strlen($sqlContent) . " bytes)\n\n";

// Parse and execute SQL statements
// Split by semicolon but preserve SQL structure
$statements = [];
$currentStatement = '';
$inString = false;
$stringChar = '';

for ($i = 0; $i < strlen($sqlContent); $i++) {
    $char = $sqlContent[$i];
    $nextChar = $i + 1 < strlen($sqlContent) ? $sqlContent[$i + 1] : '';
    
    // Handle string literals
    if (($char === '"' || $char === "'") && ($i === 0 || $sqlContent[$i - 1] !== '\\')) {
        if (!$inString) {
            $inString = true;
            $stringChar = $char;
        } elseif ($char === $stringChar) {
            $inString = false;
        }
    }
    
    // Check for statement end
    if ($char === ';' && !$inString) {
        $currentStatement .= $char;
        $trimmed = trim($currentStatement);
        if (!empty($trimmed)) {
            $statements[] = $trimmed;
        }
        $currentStatement = '';
    } else {
        $currentStatement .= $char;
    }
}

// Add any remaining statement
if (!empty(trim($currentStatement))) {
    $statements[] = trim($currentStatement);
}

echo "Parsed " . count($statements) . " SQL statements\n\n";
echo "Executing statements...\n";
echo "==========================================\n\n";

$executed = 0;
$failed = 0;
$errors = [];

foreach ($statements as $index => $statement) {
    // Skip empty statements
    if (empty(trim($statement))) {
        continue;
    }
    
    // Skip comments
    if (strpos(trim($statement), '--') === 0 || strpos(trim($statement), '#') === 0) {
        continue;
    }
    
    if ($conn->multi_query($statement)) {
        $executed++;
        echo ".";
        
        // Clear results
        while ($conn->more_results() && $conn->next_result()) {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        }
    } else {
        $failed++;
        $errors[] = [
            'statement' => substr($statement, 0, 80) . (strlen($statement) > 80 ? '...' : ''),
            'error' => $conn->error
        ];
        echo "F";
    }
}

echo "\n\n=========== Import Summary ===========\n";
echo "✓ Successfully executed: $executed statements\n";

if ($failed > 0) {
    echo "❌ Failed: $failed statements\n\n";
    echo "Errors:\n";
    foreach ($errors as $err) {
        echo "  - " . $err['statement'] . "\n";
        echo "    Error: " . $err['error'] . "\n";
    }
} else {
    echo "✓ All statements executed successfully!\n";
}

// Verify tables
$result = $conn->query("SHOW TABLES");
if ($result) {
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    $result->free();
    
    echo "\n✓ Tables created: " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
}

// Verify data
$verifyQueries = [
    'affirmations' => 'SELECT COUNT(*) FROM affirmations',
    'users' => 'SELECT COUNT(*) FROM users',
    'journal_prompts' => 'SELECT COUNT(*) FROM journal_prompts',
    'articles' => 'SELECT COUNT(*) FROM articles',
];

echo "\n✓ Data verification:\n";
foreach ($verifyQueries as $name => $query) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_row();
        echo "  - $name: " . $row[0] . " records\n";
        $result->free();
    }
}

echo "\n========================================\n";
echo "✓ Schema import completed!\n";
echo "========================================\n";

$conn->close();
?>

