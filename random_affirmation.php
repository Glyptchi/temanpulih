<?php
require __DIR__ . '/config/auth.php';
require_login();
require __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

$result = $conn->query('SELECT content, note FROM affirmations ORDER BY RAND() LIMIT 1');
$affirmation = $result ? $result->fetch_assoc() : null;

echo json_encode([
    'content' => $affirmation['content'] ?? 'Kamu tetap layak dicintai, bahkan saat ada ruang kosong yang belum terisi.',
    'note' => $affirmation['note'] ?? 'Pengingat lembut untuk hati yang sedang belajar pulih.',
]);
