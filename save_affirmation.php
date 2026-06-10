<?php
require __DIR__ . '/config/auth.php';
require_login();
require __DIR__ . '/config/db.php';

$userId = (int) $_SESSION['user_id'];
$content = trim($_POST['content'] ?? '');
$note = trim($_POST['note'] ?? 'A gentle whisper, just for today.');

if ($content === '') {
    set_flash('error', 'No affirmation to save.');
    header('Location: affirmation.php');
    exit;
}

$stmt = $conn->prepare('INSERT INTO saved_affirmations (user_id, affirmation_content, affirmation_note) VALUES (?, ?, ?)');
$stmt->bind_param('iss', $userId, $content, $note);
$stmt->execute();
set_flash('success', 'Saved to your heart.');
header('Location: affirmation.php');
exit;
