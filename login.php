<?php
require __DIR__ . '/config/auth.php';
redirect_if_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/config/db.php';

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        set_flash('error', 'Please fill in your email and password.');
        header('Location: login.php');
        exit;
    }

    $stmt = $conn->prepare("SELECT id, name, email, password_hash, status FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        set_flash('error', 'Email or password is incorrect.');
        header('Location: login.php');
        exit;
    }

    if ($user['status'] !== 'active') {
        set_flash('error', 'This account is not active.');
        header('Location: login.php');
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];

    header('Location: home.php');
    exit;
}

$flash = consume_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TemanPulih - Sign in</title>
  <link rel="preload" as="image" href="mascott/3-clean.png" />
  <link rel="stylesheet" href="styles.css" />
  <link rel="icon" type="image/png" href="mascott/favicon.png" />
</head>
<body>
  <main class="auth-shell auth-login-shell" aria-label="Sign in to TemanPulih">
    <section class="auth-card auth-card-login">
      <img
        class="auth-mascot"
        src="mascott/3-clean.png"
        alt=""
        width="86"
        height="86"
      />

      <header class="auth-header">
        <h1>Welcome back</h1>
        <p>Puli missed you.</p>
      </header>

      <form class="auth-form" action="login.php" method="post">
        <?php if ($flash): ?>
          <p class="auth-message <?php echo $flash['type'] === 'success' ? 'auth-message-success' : ''; ?>">
            <?php echo e($flash['message']); ?>
          </p>
        <?php endif; ?>

        <label for="email">Email</label>
        <input id="email" name="email" type="email" placeholder="you@gmail.com" autocomplete="email" required />

        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="........" autocomplete="current-password" required />

        <button type="submit" class="auth-button">Enter my space</button>
      </form>

      <p class="auth-footer">New here? <a href="register.php">Create an account</a></p>
    </section>
  </main>
</body>
</html>
