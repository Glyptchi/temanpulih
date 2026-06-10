<?php
require __DIR__ . '/config/auth.php';
redirect_if_logged_in();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/config/db.php';

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        set_flash('error', 'Please fill in all fields.');
        header('Location: register.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Please use a valid email address.');
        header('Location: register.php');
        exit;
    }

    if (strlen($password) < 6) {
        set_flash('error', 'Password must be at least 6 characters.');
        header('Location: register.php');
        exit;
    }

    $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->bind_param('s', $email);
    $check->execute();
    $existingUser = $check->get_result()->fetch_assoc();

    if ($existingUser) {
        set_flash('error', 'That email is already registered.');
        header('Location: register.php');
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $conn->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
    $insert->bind_param('sss', $name, $email, $passwordHash);

    if (!$insert->execute()) {
        set_flash('error', 'Something went wrong. Please try again.');
        header('Location: register.php');
        exit;
    }

    set_flash('success', 'Account created. Please sign in.');
    header('Location: login.php');
    exit;
}

$flash = consume_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TemanPulih - Create account</title>
  <link rel="preload" as="image" href="mascott/2-clean.png" />
  <link rel="stylesheet" href="styles.css" />
  <link rel="icon" type="image/png" href="mascott/favicon.png" />
</head>
<body>
  <main class="auth-shell auth-register-shell" aria-label="Create a TemanPulih account">
    <section class="auth-card auth-card-register">
      <img
        class="auth-mascot"
        src="mascott/2-clean.png"
        alt=""
        width="92"
        height="92"
      />

      <header class="auth-header">
        <h1>Hi there <span aria-hidden="true">&#127799;</span></h1>
        <p>Let's make a soft little space, just for you.</p>
      </header>

      <form class="auth-form" action="register.php" method="post">
        <?php if ($flash): ?>
          <p class="auth-message <?php echo $flash['type'] === 'success' ? 'auth-message-success' : ''; ?>">
            <?php echo e($flash['message']); ?>
          </p>
        <?php endif; ?>

        <label for="name">What can Puli call you?</label>
        <input id="name" name="name" type="text" placeholder="Aurel" autocomplete="name" required />

        <label for="email">Email</label>
        <input id="email" name="email" type="email" placeholder="you@gmail.com" autocomplete="email" required />

        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="Enter Your Password" autocomplete="new-password" required />

        <button type="submit" class="auth-button">Create my space</button>
      </form>

      <p class="auth-footer">Already have a space? <a href="login.php">Sign in</a></p>
    </section>
  </main>
</body>
</html>
