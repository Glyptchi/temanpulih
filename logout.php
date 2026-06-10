<?php
require __DIR__ . '/config/auth.php';
$_SESSION = [];
set_flash('success', 'You have been logged out.');
header('Location: login.php');
exit;
