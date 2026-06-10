<?php
require __DIR__ . '/config/auth.php';
require_login();
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/articles.php';

ensure_article_source_columns($conn);

$slug = $_GET['slug'] ?? '';
$stmt = $conn->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'published' LIMIT 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$article = $stmt->get_result()->fetch_assoc();

if (!$article) {
    set_flash('error', 'Article not found.');
    header('Location: library.php');
    exit;
}

$update = $conn->prepare('UPDATE articles SET read_count = read_count + 1 WHERE id = ?');
$articleId = (int) $article['id'];
$update->bind_param('i', $articleId);
$update->execute();

$isExternal = ($article['source_type'] ?? 'internal') === 'external';
$sourceName = article_source_name($article);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo e($article['title']); ?> - TemanPulih</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="icon" type="image/png" href="mascott/favicon.png" />
</head>
<body class="app-body">
  <header class="app-header">
    <a class="brand" href="home.php" aria-label="TemanPulih home">
      <span class="brand-badge"><img src="mascott/3-clean.png" alt="" /></span>
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

  <main class="article-detail-shell">
    <a class="back-link" href="library.php">&larr; Back to Library</a>
    <article class="article-detail-card">
      <header class="article-detail-hero">
        <div class="article-detail-image">
          <img src="<?php echo e($article['image_url'] ?: 'mascott/self%20love.png'); ?>" alt="" />
        </div>
        <div class="article-detail-heading">
          <p class="article-meta-line">
            <span><?php echo strtoupper(e($article['category'])); ?></span>
            <span><?php echo (int) $article['read_minutes']; ?> min read</span>
            <?php if ($isExternal): ?><span><?php echo e($sourceName); ?></span><?php endif; ?>
          </p>
          <h1><?php echo e($article['title']); ?></h1>
          <p class="article-lead"><?php echo e($article['excerpt']); ?></p>
        </div>
      </header>

      <?php if ($isExternal): ?>
        <section class="external-preview-card">
          <div>
            <span>Preview artikel eksternal</span>
            <h2><?php echo e($article['title']); ?></h2>
            <p><?php echo e($article['excerpt']); ?></p>
            <small>Sumber: <?php echo e($sourceName); ?></small>
          </div>
          <?php if (!empty($article['external_url'])): ?>
            <a class="external-read-button" href="<?php echo e($article['external_url']); ?>" target="_blank" rel="noopener noreferrer">Buka artikel asli</a>
          <?php endif; ?>
        </section>

        <?php if (!empty($article['embed_url'])): ?>
          <div class="external-embed">
            <iframe src="<?php echo e($article['embed_url']); ?>" title="<?php echo e($article['title']); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <div class="article-reader">
        <div class="article-content"><?php echo nl2br(e($article['content'])); ?></div>
      </div>
    </article>
  </main>
</body>
</html>
