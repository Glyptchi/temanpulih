<?php
require __DIR__ . '/config/auth.php';
require_login();
require __DIR__ . '/config/db.php';

$userId = (int) $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT name, email FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Please enter a valid name and email.');
        header('Location: settings.php');
        exit;
    }

    $check = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $check->bind_param('si', $email, $userId);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) {
        set_flash('error', 'That email is already used by another account.');
        header('Location: settings.php');
        exit;
    }

    if ($password !== '') {
        if (strlen($password) < 6) {
            set_flash('error', 'Password must be at least 6 characters.');
            header('Location: settings.php');
            exit;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->prepare('UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?');
        $update->bind_param('sssi', $name, $email, $hash, $userId);
    } else {
        $update = $conn->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
        $update->bind_param('ssi', $name, $email, $userId);
    }

    $update->execute();
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
    set_flash('success', 'Settings saved.');
    header('Location: profile.php');
    exit;
}

$flash = consume_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><title>TemanPulih - Settings</title><link rel="stylesheet" href="styles.css" /><link rel="icon" type="image/png" href="mascott/favicon.png" />
</head>
<body class="app-body">
<header class="app-header"><a class="brand" href="home.php"><span class="brand-badge"><img src="mascott/1-clean.png" alt="" /></span><span>TEMANPULIH</span></a><nav class="top-nav"><a href="home.php">The Hub</a><a href="diary.php">Diary</a><a href="library.php">Library</a><a href="safe_space.php">Safe Space</a><a class="profile-button is-active" href="profile.php">♡</a></nav></header>
<main class="settings-shell">
  <section class="settings-card dashboard-card">
    <p class="section-kicker">SETTINGS</p>
    <h1>Keep your space personal.</h1>
    <?php if ($flash): ?><p class="auth-message <?php echo $flash['type'] === 'success' ? 'auth-message-success' : ''; ?>"><?php echo e($flash['message']); ?></p><?php endif; ?>
    <form class="settings-form" action="settings.php" method="post">
      <label for="name">Name</label>
      <input id="name" name="name" type="text" value="<?php echo e($user['name'] ?? ''); ?>" required />
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?php echo e($user['email'] ?? ''); ?>" required />
      <label for="password">New password <small>(optional)</small></label>
      <input id="password" name="password" type="password" placeholder="Leave blank if unchanged" />
      <button class="pill-button primary-pill" type="submit">Save settings</button>
    </form>
  </section>
</main>
</body></html>
