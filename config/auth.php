<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function consume_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {
        set_flash('error', 'Please sign in first.');
        header('Location: login.php');
        exit;
    }
}

function redirect_if_logged_in(string $location = 'home.php'): void
{
    if (!empty($_SESSION['user_id'])) {
        header("Location: {$location}");
        exit;
    }
}
