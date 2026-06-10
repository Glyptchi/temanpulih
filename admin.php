<?php
require __DIR__ . '/config/auth.php';
require_login();
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/articles.php';

ensure_article_source_columns($conn);

$userId = (int) $_SESSION['user_id'];
$roleStmt = $conn->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
$roleStmt->bind_param('i', $userId);
$roleStmt->execute();
$role = $roleStmt->get_result()->fetch_assoc()['role'] ?? 'user';
if ($role !== 'admin') {
    set_flash('error', 'Admin only.');
    header('Location: profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'save';
    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM articles WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        set_flash('success', 'Article deleted.');
        header('Location: admin.php');
        exit;
    }
}

$stats = [];
$stats['users'] = (int) ($conn->query('SELECT COUNT(*) total FROM users')->fetch_assoc()['total'] ?? 0);
$stats['entries'] = (int) ($conn->query("SELECT COUNT(*) total FROM journal_entries WHERE content IS NOT NULL AND TRIM(content) <> ''")->fetch_assoc()['total'] ?? 0);
$stats['moods'] = (int) ($conn->query('SELECT COUNT(*) total FROM moods')->fetch_assoc()['total'] ?? 0);
$articles = $conn->query('SELECT id, title, category, read_count, status, source_type FROM articles ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
$flash = consume_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TemanPulih - Admin</title>
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
      <a href="library.php">Library</a>
      <a href="safe_space.php">Safe Space</a>
      <a class="profile-button" href="profile.php" aria-label="Profile">&#9825;</a>
    </nav>
  </header>

  <main class="admin-shell">
    <p class="section-kicker">ADMIN</p>
    <h1>A quiet room for caretakers</h1>
    <p class="admin-subtitle">Private journal contents are never shown here, only what helps us care better.</p>

    <section class="profile-stats">
      <div class="dashboard-card stat-card"><span>ACTIVE SOULS</span><strong><?php echo $stats['users']; ?></strong></div>
      <div class="dashboard-card stat-card"><span>ENTRIES</span><strong><?php echo $stats['entries']; ?></strong></div>
      <div class="dashboard-card stat-card"><span>MOODS</span><strong><?php echo $stats['moods']; ?></strong></div>
    </section>

    <?php if ($flash): ?>
      <p class="auth-message <?php echo $flash['type'] === 'success' ? 'auth-message-success' : ''; ?>"><?php echo e($flash['message']); ?></p>
    <?php endif; ?>

    <section class="admin-grid">
      <div class="dashboard-card admin-list-card">
        <div class="admin-list-header">
          <h2>Articles</h2>
          <a class="pill-button primary-pill" href="admin_article.php">New Article</a>
        </div>
        <?php foreach ($articles as $article): ?>
          <div class="admin-article-row">
            <div>
              <strong><?php echo e($article['title']); ?></strong>
              <p><?php echo e($article['category']); ?> &middot; <?php echo e($article['source_type']); ?> &middot; <?php echo (int) $article['read_count']; ?> reads &middot; <?php echo e($article['status']); ?></p>
            </div>
            <div>
              <a href="admin_article.php?id=<?php echo (int) $article['id']; ?>">Edit</a>
              <form method="post" onsubmit="return confirm('Delete this article?')">
                <input type="hidden" name="action" value="delete" />
                <input type="hidden" name="id" value="<?php echo (int) $article['id']; ?>" />
                <button type="submit">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

  </main>
</body>
</html>
