<?php
$serverName = $_SERVER['SERVER_NAME'] ?? '';
$httpHost = $_SERVER['HTTP_HOST'] ?? $serverName;
$isLocal = in_array($serverName, ['localhost', '127.0.0.1', '::1'], true)
    || str_starts_with($httpHost, 'localhost')
    || str_starts_with($httpHost, '127.0.0.1')
    || PHP_SAPI === 'cli';

if ($isLocal) {
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'temanpuli';
} else {
    $host = 'sql110.infinityfree.com';
    $user = 'if0_42146789';
    $password = 'Temanpulih123';
    $database = 'if0_42146789_temanpuli';
}

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
