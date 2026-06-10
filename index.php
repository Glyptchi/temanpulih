<?php
require __DIR__ . '/config/auth.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TemanPulih - A gentle space to heal</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="icon" type="image/png" href="mascott/favicon.png" />
</head>
<body>
  <main class="landing-shell">
    <div class="landing-glow"></div>
    <div class="landing-decor">
      <span class="floating-emoji sparkle">&#10024;</span>
      <span class="floating-emoji flower">&#127800;</span>
      <span class="floating-emoji star">&#11088;</span>
      <span class="floating-emoji tulip">&#127799;</span>
      <span class="floating-emoji mini-sparkle">&#10024;</span>
      <span class="floating-emoji mini-flower">&#127804;</span>
    </div>
    <section class="landing-content">
      <div class="mascot-stack">
        <div class="mascot-card">
          <img src="mascott/2-clean.png" alt="Puli, your healing companion" width="224" height="224" />
        </div>
        <span class="eyebrow">TemanPulih</span>
      </div>
      <h1>A small world to heal in,<br /><strong>together with Puli.</strong></h1>
      <p>A soft, safe space for the days you need someone to listen.</p>
      <div class="landing-actions">
        <a href="onboarding.php" class="primary-button">Begin your journey</a>
        <a href="login.php" class="secondary-button">I already have an account</a>
      </div>
    </section>
  </main>
</body>
</html>
