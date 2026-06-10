<?php
require __DIR__ . '/config/auth.php';
require_login();
require __DIR__ . '/config/db.php';

date_default_timezone_set('Asia/Jakarta');

$name = e($_SESSION['user_name'] ?? 'TemanPulih User');
$result = $conn->query('SELECT content, note FROM affirmations ORDER BY RAND() LIMIT 1');
$affirmation = $result ? $result->fetch_assoc() : null;
$affirmationContent = $affirmation['content'] ?? 'Kamu tetap layak dicintai, bahkan saat ada ruang kosong yang belum terisi.';
$affirmationNote = $affirmation['note'] ?? 'Pengingat lembut untuk hati yang sedang belajar pulih.';

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

function mood_summary_label(float $average): string
{
    if ($average <= 0) {
        return 'Belum tercatat';
    }

    if ($average <= 1.5) {
        return 'Butuh ditemani';
    }

    if ($average <= 2.5) {
        return 'Sedang berat';
    }

    if ($average <= 3.5) {
        return 'Cukup stabil';
    }

    if ($average <= 4.5) {
        return 'Cenderung baik';
    }

    return 'Sangat baik';
}

function mood_trend_label(array $scores): string
{
    if (count($scores) < 2) {
        return 'Mulai tercatat';
    }

    $first = reset($scores);
    $last = end($scores);
    $change = $last - $first;

    if ($change >= 1) {
        return 'Mulai membaik';
    }

    if ($change > 0) {
        return 'Sedikit naik';
    }

    if ($change === 0) {
        return 'Stabil';
    }

    return 'Perlu lebih lembut';
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
$moodSummary = mood_summary_label($averageMood);
$moodTrend = mood_trend_label($trackedScores);
$trackedDays = count($trackedScores);
$consistencyLabel = $trackedDays === 0 ? 'Belum mulai' : ($trackedDays === 1 ? 'Mulai konsisten' : $trackedDays . ' hari tercatat');

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TemanPulih - Home</title>
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
      <a class="is-active" href="home.php">The Hub</a>
      <a href="diary.php">Diary</a>
      <a href="library.php">Library</a>
      <a href="safe_space.php">Safe Space</a>
      <a class="profile-button" href="profile.php" aria-label="Profile">&#9825;</a>
    </nav>
  </header>

  <main class="hub-shell" aria-label="TemanPulih home">
    <section class="hub-hero">
      <div class="hub-copy">
        <p class="hello-pill">HELLO, <?php echo strtoupper($name); ?> &#127799;</p>
        <h1>How is your <em>inner world</em> today?</h1>
        <p>Puli ada di sini untuk mendengarkanmu. Pelan atau berat, semua ceritamu boleh punya ruang di sini.</p>

        <div class="hub-actions">
          <a class="pill-button primary-pill" href="diary.php">Start Journaling</a>
          <a class="pill-button soft-pill" href="mood.php">Track My Mood</a>
        </div>
      </div>

      <div class="hub-mascot" aria-hidden="true">
        <span class="floating-sparkle">&#10024;</span>
        <img src="mascott/2-clean.png" alt="" />
        <span class="floating-flower">&#127802;</span>
      </div>
    </section>

    <section class="hub-grid">
      <a class="dashboard-card affirmation-card" href="affirmation.php" aria-label="Open daily affirmation">
        <span class="section-kicker">AFIRMASI HARI INI</span>
        <blockquote>"<?php echo e($affirmationContent); ?>"</blockquote>
        <p><span>&#127802;</span><?php echo e($affirmationNote); ?></p>
      </a>

      <section class="dashboard-card mood-card" id="mood-bubble" aria-label="Mood Tracker">
        <div class="card-heading">
          <h2>Mood Tracker</h2>
          <a href="profile.php">LIHAT PERJALANAN &rarr;</a>
        </div>

        <a class="mood-summary-link" href="mood.php">
          <div class="mood-summary-main">
            <span>Hari ini</span>
            <strong><?php echo e($moodSummary); ?></strong>
            <p><?php echo e($consistencyLabel); ?> · <?php echo e($moodTrend); ?></p>
          </div>
          <div class="mood-mini-bars" aria-hidden="true">
            <?php foreach ($weekHistory as $item): ?>
              <span
                class="<?php echo $item['score'] === 0 ? 'is-empty' : ''; ?>"
                style="height: <?php echo (int) max(14, round($item['height'] * 0.62)); ?>px;"
              ></span>
            <?php endforeach; ?>
          </div>
          <span class="mood-summary-cta">Isi mood hari ini &rarr;</span>
        </a>
      </section>
    </section>
  </main>

  <nav class="bottom-nav" aria-label="Quick navigation">
    <a class="is-active" href="home.php"><span class="nav-icon nav-icon-home" aria-hidden="true"></span><span>Home</span></a>
    <a href="diary.php"><span class="nav-icon nav-icon-book" aria-hidden="true"></span><span>Journal</span></a>
    <a class="nav-plus" href="diary.php">+</a>
    <a href="library.php">&#10023;<span>Explore</span></a>
    <a href="safe_space.php">&#9825;<span>Safe</span></a>
  </nav>
</body>
</html>
