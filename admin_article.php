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

function slugify_article(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'article-' . time();
}

function handle_article_upload(?array $file): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gambar gagal. Coba pilih file lain.');
    }

    if (($file['size'] ?? 0) > 1024 * 1024) {
        throw new RuntimeException('Ukuran gambar maksimal 1MB.');
    }

    $info = getimagesize($file['tmp_name']);
    if (!$info) {
        throw new RuntimeException('File harus berupa gambar JPG, PNG, atau WebP.');
    }

    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];
    $type = $info[2] ?? 0;
    if (!isset($allowed[$type])) {
        throw new RuntimeException('Format gambar harus JPG, PNG, atau WebP.');
    }

    $uploadDir = __DIR__ . '/uploads/articles';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'article-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$type];
    $destination = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Gambar belum bisa disimpan. Coba lagi sebentar.');
    }

    return 'uploads/articles/' . $filename;
}

$categoryOptions = ['Self Love', 'Healing', 'Father Wounds', 'Anxiety', 'Emotional Growth'];
$article = null;
$articleId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if ($articleId > 0) {
    $stmt = $conn->prepare('SELECT * FROM articles WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $articleId);
    $stmt->execute();
    $article = $stmt->get_result()->fetch_assoc();
    if (!$article) {
        set_flash('error', 'Article not found.');
        header('Location: admin.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'Self Love');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $imageUrl = trim($_POST['image_url'] ?? '');
    $existingImageUrl = trim($_POST['existing_image_url'] ?? '');
    $sourceType = ($_POST['source_type'] ?? 'internal') === 'external' ? 'external' : 'internal';
    $externalUrl = normalize_article_url($_POST['external_url'] ?? '');
    $sourceName = trim($_POST['source_name'] ?? '');
    $embedUrl = normalize_article_url($_POST['embed_url'] ?? '');
    $readMinutes = max(1, (int) ($_POST['read_minutes'] ?? 4));
    $status = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
    $slug = slugify_article($title);

    try {
        $uploadedImageUrl = handle_article_upload($_FILES['image_file'] ?? null);
        if ($uploadedImageUrl) {
            $imageUrl = $uploadedImageUrl;
        } elseif ($imageUrl === '') {
            $imageUrl = $existingImageUrl;
        }

        if ($sourceType === 'external' && $externalUrl === '') {
            throw new RuntimeException('External article URL is required.');
        }

        if ($sourceType === 'external' && $content === '') {
            $content = 'Artikel ini berasal dari sumber eksternal. Gunakan preview di bawah untuk membaca versi aslinya.';
        }

        if ($title === '' || $excerpt === '' || $content === '') {
            throw new RuntimeException('Title, excerpt, and content are required.');
        }

        if ($articleId > 0) {
            $stmt = $conn->prepare('UPDATE articles SET title=?, slug=?, category=?, excerpt=?, content=?, image_url=?, read_minutes=?, status=?, source_type=?, external_url=?, source_name=?, embed_url=? WHERE id=?');
            $stmt->bind_param('ssssssisssssi', $title, $slug, $category, $excerpt, $content, $imageUrl, $readMinutes, $status, $sourceType, $externalUrl, $sourceName, $embedUrl, $articleId);
        } else {
            $stmt = $conn->prepare('INSERT INTO articles (title, slug, category, excerpt, content, image_url, read_minutes, status, source_type, external_url, source_name, embed_url, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('ssssssisssssi', $title, $slug, $category, $excerpt, $content, $imageUrl, $readMinutes, $status, $sourceType, $externalUrl, $sourceName, $embedUrl, $userId);
        }

        $stmt->execute();
        set_flash('success', 'Article saved.');
        header('Location: admin.php');
        exit;
    } catch (RuntimeException $error) {
        set_flash('error', $error->getMessage());
        $article = array_merge($article ?? [], $_POST, ['image_url' => $imageUrl ?: $existingImageUrl]);
    }
}

$flash = consume_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo $article ? 'Edit Article' : 'New Article'; ?> - TemanPulih</title>
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

  <main class="admin-article-shell">
    <a class="back-link" href="admin.php">&larr; Back to Admin</a>

    <section class="admin-article-hero">
      <div>
        <p class="section-kicker">ARTICLE EDITOR</p>
        <h1><?php echo $article ? 'Edit library article' : 'Create a library article'; ?></h1>
        <p>Gunakan gambar 1200 x 800 px, rasio 3:2, maksimal 1MB. Format yang didukung: JPG, PNG, atau WebP.</p>
      </div>
      <div class="admin-image-preview">
        <span>Preview</span>
        <img src="<?php echo e(($article['image_url'] ?? '') ?: 'mascott/jounaling.png'); ?>" alt="" />
      </div>
    </section>

    <?php if ($flash): ?>
      <p class="auth-message <?php echo $flash['type'] === 'success' ? 'auth-message-success' : ''; ?>"><?php echo e($flash['message']); ?></p>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="admin-article-form">
      <input type="hidden" name="id" value="<?php echo (int) ($article['id'] ?? 0); ?>" />
      <input type="hidden" name="existing_image_url" value="<?php echo e($article['image_url'] ?? ''); ?>" />
      <input type="hidden" name="MAX_FILE_SIZE" value="1048576" />

      <section class="dashboard-card admin-editor-card">
        <div class="admin-form-grid">
          <label>Article type
            <select name="source_type" id="source-type">
              <option value="internal" <?php echo (($article['source_type'] ?? 'internal') === 'internal') ? 'selected' : ''; ?>>Internal article</option>
              <option value="external" <?php echo (($article['source_type'] ?? 'internal') === 'external') ? 'selected' : ''; ?>>External preview</option>
            </select>
          </label>

          <label>Status
            <select name="status">
              <option value="published" <?php echo (($article['status'] ?? 'published') === 'published') ? 'selected' : ''; ?>>Published</option>
              <option value="draft" <?php echo (($article['status'] ?? 'published') === 'draft') ? 'selected' : ''; ?>>Draft</option>
            </select>
          </label>

          <label class="is-wide">Title
            <input name="title" value="<?php echo e($article['title'] ?? ''); ?>" required />
          </label>

          <label>Category
            <select name="category">
              <?php foreach ($categoryOptions as $categoryOption): ?>
                <option <?php echo (($article['category'] ?? 'Self Love') === $categoryOption) ? 'selected' : ''; ?>><?php echo e($categoryOption); ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label>Read minutes
            <input type="number" name="read_minutes" value="<?php echo (int) ($article['read_minutes'] ?? 4); ?>" min="1" />
          </label>

          <label class="is-wide">Excerpt / preview text
            <textarea name="excerpt" required><?php echo e($article['excerpt'] ?? ''); ?></textarea>
          </label>
        </div>
      </section>

      <section class="dashboard-card admin-editor-card external-fields">
        <h2>Link artikel luar</h2>
        <p class="admin-editor-hint">Untuk artikel dari website lain, cukup isi link artikel aslinya. Bagian tambahan di bawah biasanya tidak perlu diisi.</p>
        <div class="admin-form-grid">
          <label class="is-wide">Link artikel asli
            <input name="external_url" type="url" placeholder="https://www.detik.com/..." value="<?php echo e($article['external_url'] ?? ''); ?>" />
            <small>Contoh Detik, Kompas, atau sumber lain. Link ini akan dibuka lewat tombol Buka artikel asli.</small>
          </label>

          <label class="is-wide">Nama sumber opsional
            <input name="source_name" placeholder="Contoh: detikHealth" value="<?php echo e($article['source_name'] ?? ''); ?>" />
            <small>Boleh dikosongkan, sistem akan mencoba membaca nama sumber dari link.</small>
          </label>

          <details class="admin-advanced-field">
            <summary>Tampilkan artikel langsung di halaman opsional</summary>
            <label>Link tampilan langsung
              <input name="embed_url" type="url" placeholder="Biasanya dikosongkan" value="<?php echo e($article['embed_url'] ?? ''); ?>" />
              <small>Untuk Detik dan banyak website berita lain, kosongkan saja. Nanti pembaca tetap bisa klik tombol Buka artikel asli.</small>
            </label>
          </details>
        </div>
      </section>

      <section class="dashboard-card admin-editor-card">
        <h2>Article image</h2>
        <div class="admin-form-grid">
          <label class="is-wide">Upload image
            <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp" />
            <small>Recommended 1200 x 800 px, rasio 3:2, maksimal 1MB.</small>
          </label>

          <label class="is-wide">Or paste image URL
            <input name="image_url" value="<?php echo e($article['image_url'] ?? ''); ?>" placeholder="https://..." />
            <small>Jika upload gambar diisi, URL ini akan diganti dengan file upload.</small>
          </label>
        </div>
      </section>

      <section class="dashboard-card admin-editor-card">
        <h2>Content</h2>
        <label class="admin-content-field">Content / note
          <textarea name="content" required><?php echo e($article['content'] ?? ''); ?></textarea>
        </label>
      </section>

      <div class="admin-editor-actions">
        <a class="pill-button soft-pill" href="admin.php">Cancel</a>
        <button class="pill-button primary-pill" type="submit">Save Article</button>
      </div>
    </form>
  </main>

  <script>
    const sourceType = document.getElementById("source-type");
    const externalFields = document.querySelector(".external-fields");

    function syncSourceFields() {
      externalFields.hidden = sourceType.value !== "external";
    }

    sourceType.addEventListener("change", syncSourceFields);
    syncSourceFields();
  </script>
</body>
</html>
