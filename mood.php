<?php
require __DIR__ . '/config/auth.php';
require_login();
require __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Jakarta');

$userId = (int) $_SESSION['user_id'];
$moodOptions = [
    'Sangat Buruk' => ['score' => 1, 'emoji' => '&#128557;'],
    'Buruk' => ['score' => 2, 'emoji' => '&#128543;'],
    'Biasa' => ['score' => 3, 'emoji' => '&#128528;'],
    'Baik' => ['score' => 4, 'emoji' => '&#128578;'],
    'Sangat Baik' => ['score' => 5, 'emoji' => '&#128522;'],
];
$moodScores = [
    'Sangat Buruk' => 1,
    'Buruk' => 2,
    'Biasa' => 3,
    'Baik' => 4,
    'Sangat Baik' => 5,
    'Heavy' => 2,
    'Lonely' => 2,
    'Soft' => 3,
    'Healing' => 3,
    'Calm' => 4,
    'Peace' => 5,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mood = $_POST['mood'] ?? '';

    if (!array_key_exists($mood, $moodOptions)) {
        set_flash('error', 'Pilih mood yang tersedia dulu ya.');
        header('Location: mood.php');
        exit;
    }

    $today = date('Y-m-d');
    $stmt = $conn->prepare('INSERT INTO moods (user_id, mood, mood_date) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE mood = VALUES(mood), created_at = CURRENT_TIMESTAMP');
    $stmt->bind_param('iss', $userId, $mood, $today);
    $stmt->execute();

    set_flash('success', 'Mood hari ini sudah tersimpan.');
    header('Location: mood.php');
    exit;
}

function mood_trend_percent(array $scores): int
{
    if (count($scores) < 2) {
        return 0;
    }

    $first = (int) reset($scores);
    $last = (int) end($scores);

    if ($first <= 0) {
        return 0;
    }

    return (int) round((($last - $first) / $first) * 100);
}

$moodStmt = $conn->prepare(
    'SELECT mood, mood_date
     FROM moods
     WHERE user_id = ? AND mood_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     ORDER BY mood_date ASC'
);
$moodStmt->bind_param('i', $userId);
$moodStmt->execute();
$moodRows = $moodStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$moodsByDate = [];
$trackedScores = [];
foreach ($moodRows as $row) {
    $moodsByDate[$row['mood_date']] = $row['mood'];
    if (isset($moodScores[$row['mood']])) {
        $trackedScores[] = $moodScores[$row['mood']];
    }
}

$averageMood = $trackedScores ? array_sum($trackedScores) / count($trackedScores) : 0;
$moodTrendPercent = mood_trend_percent($trackedScores);
$moodTrendClass = $moodTrendPercent < 0 ? 'is-down' : ($moodTrendPercent > 0 ? 'is-up' : 'is-flat');
$moodTrendPrefix = $moodTrendPercent > 0 ? '&uarr;' : ($moodTrendPercent < 0 ? '&darr;' : '');
$averageMoodLabel = $trackedScores ? number_format($averageMood, 1) : '0.0';
$trackedDays = count($trackedScores);
$consistencyLabel = $trackedDays . ' hari';

$weekHistory = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $mood = $moodsByDate[$date] ?? null;
    $score = $mood ? ($moodScores[$mood] ?? 0) : 0;
    $weekHistory[] = [
        'date' => $date,
        'day' => mb_substr(date('D', strtotime($date)), 0, 1, 'UTF-8'),
        'score' => $score,
        'height' => $score > 0 ? 28 + ($score * 10) : 18,
    ];
}

$flash = consume_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TemanPulih - Mood Tracker</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="icon" type="image/png" href="mascott/favicon.png" />
</head>
<body class="app-body">
  <header class="app-header">
    <a class="brand" href="home.php" aria-label="TemanPulih home">
      <span class="brand-badge">
        <img src="mascott/3-clean.png" alt="" />
      </span>
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

  <main class="mood-page-shell">
    <section class="mood-page-card dashboard-card">
      <div class="card-heading">
        <div>
          <span class="section-kicker">MOOD TRACKER</span>
          <h1>Bagaimana perasaanmu hari ini?</h1>
        </div>
        <a href="home.php">KEMBALI &rarr;</a>
      </div>

      <?php if ($flash): ?>
        <p class="auth-message <?php echo $flash['type'] === 'success' ? 'auth-message-success' : ''; ?>">
          <?php echo e($flash['message']); ?>
        </p>
      <?php endif; ?>

      <form class="mood-grid mood-scale-grid mood-page-scale" action="mood.php" method="post">
        <?php foreach ($moodOptions as $mood => $option): ?>
          <button type="submit" name="mood" value="<?php echo e($mood); ?>">
            <?php echo $option['emoji']; ?>
            <span><?php echo e($mood); ?></span>
          </button>
        <?php endforeach; ?>
      </form>

      <section class="mood-stat-grid" aria-label="Ringkasan mood">
        <div class="mood-stat-card">
          <strong><?php echo e($averageMoodLabel); ?></strong>
          <span>Mood rata-rata</span>
        </div>
        <div class="mood-stat-card">
          <strong><?php echo e($consistencyLabel); ?></strong>
          <span>Konsistensi</span>
        </div>
        <div class="mood-stat-card">
          <strong class="<?php echo e($moodTrendClass); ?>"><?php echo $moodTrendPrefix; ?><?php echo e(abs($moodTrendPercent)); ?>%</strong>
          <span>Peningkatan</span>
        </div>
      </section>

      <section class="mood-history" aria-label="Riwayat mood 7 hari">
        <span class="section-kicker">RIWAYAT 7 HARI</span>
        <div class="mood-bars mood-page-bars">
          <?php foreach ($weekHistory as $item): ?>
            <div class="mood-bar-item">
              <span
                class="mood-bar <?php echo $item['score'] === 0 ? 'is-empty' : ''; ?>"
                style="height: <?php echo (int) $item['height']; ?>px;"
                title="<?php echo e(date('d M', strtotime($item['date']))); ?>"
              ></span>
              <small><?php echo e($item['day']); ?></small>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </section>
  </main>

  <nav class="bottom-nav" aria-label="Quick navigation">
    <a href="home.php"><span class="nav-icon nav-icon-home" aria-hidden="true"></span><span>Home</span></a>
    <a href="diary.php"><span class="nav-icon nav-icon-book" aria-hidden="true"></span><span>Journal</span></a>
    <a class="nav-plus" href="diary.php">+</a>
    <a href="library.php">&#10023;<span>Explore</span></a>
    <a href="safe_space.php">&#9825;<span>Safe</span></a>
  </nav>
</body>
</html>
