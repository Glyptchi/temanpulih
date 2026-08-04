<?php
// config/db.php

// Read from Environment Variables (useful for Railway / Local .env)
$host = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: 'localhost';
$user = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '';
$database = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'temanpuli';
$port = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306';

// Detect environment
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$isLocal = in_array($serverName, ['localhost', '127.0.0.1', '::1'], true)
    || str_starts_with($_SERVER['HTTP_HOST'] ?? $serverName, 'localhost')
    || str_starts_with($_SERVER['HTTP_HOST'] ?? $serverName, '127.0.0.1')
    || PHP_SAPI === 'cli';

$isRailway = getenv('RAILWAY_ENVIRONMENT') !== false || getenv('RAILWAY_STATIC_URL') !== false || getenv('PORT') !== false;

if ($isRailway && getenv('DB_HOST') === false && getenv('MYSQLHOST') === false) {
    die("Koneksi database gagal: Variabel environment database belum terhubung di Railway. Silakan tambahkan MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, dan MYSQLDATABASE di menu Variables pada layanan PHP Kakak.");
}

// Fallback to legacy production values if running on InfinityFree host without env configured
if (!$isLocal && !$isRailway && getenv('DB_HOST') === false && getenv('MYSQLHOST') === false) {
    $host = 'sql110.infinityfree.com';
    $user = 'if0_42146789';
    $password = 'Temanpulih123';
    $database = 'if0_42146789_temanpuli';
    $port = '3306';
}

$conn = new mysqli($host, $user, $password, $database, (int)$port);

if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
