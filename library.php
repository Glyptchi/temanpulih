<?php
require __DIR__ . '/config/auth.php';
require_login();
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/articles.php';

ensure_article_source_columns($conn);

$categories = ['All', 'Self Love', 'Healing', 'Father Wounds', 'Anxiety', 'Emotional Growth'];
$category = $_GET['category'] ?? 'All';
if (!in_array($category, $categories, true)) {
    $category = 'All';
}

if ($category === 'All') {
    $stmt = $conn->prepare("SELECT id, title, slug, category, excerpt, image_url, read_minutes, source_type, external_url, source_name FROM articles WHERE status = 'published' ORDER BY created_at DESC");
} else {
    $stmt = $conn->prepare("SELECT id, title, slug, category, excerpt, image_url, read_minutes, source_type, external_url, source_name FROM articles WHERE status = 'published' AND category = ? ORDER BY created_at DESC");
    $stmt->bind_param('s', $category);
}
$stmt->execute();
$articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TemanPulih - Library</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="icon" type="image/png" href="mascott/favicon.png" />
</head>
<body class="app-body">
  <header class="app-header">
    <a class="brand" href="home.php" aria-label="TemanPulih home">
      <span class="brand-badge"><img src="mascott/1-clean.png" alt="" /></span>
      <span>TEMANPULIH</span>
    </a>
    <nav class="top-nav" aria-label="Main navigation">
      <a href="home.php">The Hub</a>
      <a href="diary.php">Diary</a>
      <a class="is-active" href="library.php">Library</a>
      <a href="safe_space.php">Safe Space</a>
      <a class="profile-button" href="profile.php" aria-label="Profile">&#9825;</a>
    </nav>
  </header>

  <main class="library-shell">
    <p class="section-kicker">LIBRARY</p>
    <h1>Small stories for the days <em>you need them.</em></h1>

    <div class="library-filters">
      <?php foreach ($categories as $item): ?>
        <a class="<?php echo $item === $category ? 'is-active' : ''; ?>" href="library.php?category=<?php echo urlencode($item); ?>"><?php echo strtoupper(e($item)); ?></a>
      <?php endforeach; ?>
    </div>

    <section class="article-grid">
      <?php foreach ($articles as $article): ?>
        <a class="article-card" href="article.php?slug=<?php echo urlencode($article['slug']); ?>">
          <img src="<?php echo e($article['image_url'] ?: 'mascott/jounaling.png'); ?>" alt="" />
          <div class="article-card-body">
            <div class="article-meta">
              <strong><?php echo strtoupper(e($article['category'])); ?></strong>
              <span><?php echo (int) $article['read_minutes']; ?> min</span>
            </div>
            <?php if (($article['source_type'] ?? 'internal') === 'external'): ?>
              <span class="article-source-badge">Preview dari <?php echo e(article_source_name($article)); ?></span>
            <?php endif; ?>
            <h2><?php echo e($article['title']); ?></h2>
            <p><?php echo e($article['excerpt']); ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </section>
  </main>

  <nav class="bottom-nav" aria-label="Quick navigation">
    <a href="home.php"><span class="nav-icon nav-icon-home" aria-hidden="true"></span><span>Home</span></a>
    <a href="diary.php"><span class="nav-icon nav-icon-book" aria-hidden="true"></span><span>Journal</span></a>
    <a class="nav-plus" href="diary.php">+</a>
    <a class="is-active" href="library.php">&#10023;<span>Explore</span></a>
    <a href="safe_space.php">&#9825;<span>Safe</span></a>
  </nav>
</body>
</html>
