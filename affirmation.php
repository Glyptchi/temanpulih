<?php
require __DIR__ . '/config/auth.php';
require_login();
require __DIR__ . '/config/db.php';

$result = $conn->query('SELECT content, note FROM affirmations ORDER BY RAND() LIMIT 1');
$affirmation = $result ? $result->fetch_assoc() : null;
$content = $affirmation['content'] ?? 'Kamu tetap layak dicintai, bahkan saat ada ruang kosong yang belum terisi.';
$note = $affirmation['note'] ?? 'Pengingat lembut untuk hati yang sedang belajar pulih.';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TemanPulih - Affirmation</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="icon" type="image/png" href="mascott/favicon.png" />
</head>
<body class="app-body">
  <header class="app-header">
    <a class="brand" href="home.php" aria-label="TemanPulih home">
      <span class="brand-badge">
        <img src="mascott/2-clean.png" alt="" />
      </span>
      <span>TEMANPULIH</span>
    </a>

    <nav class="top-nav" aria-label="Main navigation">
      <a href="home.php">The Hub</a>
      <a href="diary.php">Diary</a>
      <a href="library.php">Library</a>
      <a href="safe_space.php">Safe Space</a>
      <a class="profile-button" href="profile.php" aria-label="Profile">♡</a>
    </nav>
  </header>

  <main class="affirmation-page">
    <section class="affirmation-hero" aria-label="Daily affirmation">
      <div class="affirmation-mascot-wrap">
        <span class="affirmation-sparkle" aria-hidden="true">✨</span>
        <img src="mascott/self%20love.png" alt="" />
        <span class="affirmation-flower" aria-hidden="true">🌺</span>
      </div>

      <p class="section-kicker">A WHISPER FOR TODAY</p>
      <h1 id="affirmation-text">"<?php echo e($content); ?>"</h1>

      <div class="affirmation-actions">
        <button class="pill-button primary-pill" id="another-whisper" type="button">Another whisper</button>
        <button class="pill-button soft-pill" type="button">Save to my heart</button>
      </div>
    </section>
  </main>

  <nav class="bottom-nav" aria-label="Quick navigation">
    <a class="is-active" href="home.php"><span class="nav-icon nav-icon-home" aria-hidden="true"></span><span>Home</span></a>
    <a href="diary.php"><span class="nav-icon nav-icon-book" aria-hidden="true"></span><span>Journal</span></a>
    <a class="nav-plus" href="diary.php">+</a>
    <a href="library.php">✧<span>Explore</span></a>
    <a href="safe_space.php">♡<span>Safe</span></a>
  </nav>
  <script>
    const anotherButton = document.getElementById("another-whisper");
    const affirmationText = document.getElementById("affirmation-text");

    anotherButton.addEventListener("click", async () => {
      anotherButton.disabled = true;
      anotherButton.textContent = "Finding whisper...";

      try {
        const response = await fetch("random_affirmation.php", {
          headers: { "Accept": "application/json" }
        });
        const data = await response.json();
        affirmationText.textContent = `"${data.content}"`;
      } catch (error) {
        affirmationText.textContent = '"A soft breath is enough for this moment."';
      } finally {
        anotherButton.disabled = false;
        anotherButton.textContent = "Another whisper";
      }
    });
  </script>
</body>
</html>
