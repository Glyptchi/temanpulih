<?php
require __DIR__ . '/config/auth.php';
require_login();
require __DIR__ . '/config/db.php';

$userId = (int) $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT name, email, role, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$entriesStmt = $conn->prepare("SELECT COUNT(*) total FROM journal_entries WHERE user_id = ? AND content IS NOT NULL AND TRIM(content) <> ''");
$entriesStmt->bind_param('i', $userId);
$entriesStmt->execute();
$entries = (int) ($entriesStmt->get_result()->fetch_assoc()['total'] ?? 0);

$moodStmt = $conn->prepare('SELECT COUNT(*) total FROM moods WHERE user_id = ?');
$moodStmt->bind_param('i', $userId);
$moodStmt->execute();
$moods = (int) ($moodStmt->get_result()->fetch_assoc()['total'] ?? 0);

$savedAffStmt = $conn->prepare("SELECT affirmation_content, affirmation_note FROM saved_affirmations WHERE user_id = ? ORDER BY created_at DESC");
$savedAffStmt->bind_param('i', $userId);
$savedAffStmt->execute();
$savedAffirmations = $savedAffStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$datesStmt = $conn->prepare("SELECT DISTINCT entry_date FROM journal_entries WHERE user_id = ? AND content IS NOT NULL AND TRIM(content) <> '' ORDER BY entry_date DESC");
$datesStmt->bind_param('i', $userId);
$datesStmt->execute();
$dates = array_column($datesStmt->get_result()->fetch_all(MYSQLI_ASSOC), 'entry_date');
$streak = 0;
$cursor = new DateTime('today');
if (!empty($dates) && $dates[0] === date('Y-m-d', strtotime('-1 day'))) {
    $cursor = new DateTime('yesterday');
}
foreach ($dates as $date) {
    if ($date === $cursor->format('Y-m-d')) {
        $streak++;
        $cursor->modify('-1 day');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" /><title>TemanPulih - Profile</title><link rel="stylesheet" href="styles.css" /><link rel="icon" type="image/png" href="mascott/favicon.png" />
</head>
<body class="app-body">
<header class="app-header"><a class="brand" href="home.php"><span class="brand-badge"><img src="mascott/2-clean.png" alt="" /></span><span>TEMANPULIH</span></a><nav class="top-nav"><a href="home.php">The Hub</a><a href="diary.php">Diary</a><a href="library.php">Library</a><a href="safe_space.php">Safe Space</a><a class="profile-button is-active" href="profile.php">♙</a></nav></header>
<main class="profile-shell">
  <section class="profile-hero dashboard-card">
    <div class="profile-mascot"><img src="mascott/self%20love.png" alt="" /><span>LV. <?php echo min(3, max(1, (int) floor($entries / 5) + 1)); ?><br>BLOOM</span></div>
    <div><p class="section-kicker">HELLO, DEAR</p><h1><?php echo e($user['name'] ?? 'Teman'); ?></h1><p>You've been gentle with yourself for <?php echo $streak; ?> days in a row 🌷</p><div class="profile-actions"><a class="soft-pill pill-button" href="settings.php">Settings</a><?php if (($user['role'] ?? 'user') === 'admin'): ?><a class="soft-pill pill-button" href="admin.php">Admin</a><?php endif; ?><a class="soft-pill pill-button" href="logout.php">Logout</a></div></div>
  </section>
  <section class="profile-stats"><div class="dashboard-card stat-card"><span>STREAK</span><strong>🔥 <?php echo $streak; ?> days</strong></div><div class="dashboard-card stat-card"><span>ENTRIES</span><strong>📔 <?php echo $entries; ?></strong></div><div class="dashboard-card stat-card"><span>MOODS TRACKED</span><strong>🌺 <?php echo $moods; ?></strong></div></section>
  <section class="dashboard-card journey-card"><h2>Your emotional journey</h2><div class="journey-item is-done"><span>✓</span><div><strong>First journal entry</strong><p><?php echo $entries > 0 ? 'completed' : 'soon'; ?></p></div></div><div class="journey-item <?php echo $streak >= 7 ? 'is-done' : ''; ?>"><span><?php echo $streak >= 7 ? '✓' : '...'; ?></span><div><strong>7-day streak</strong><p><?php echo $streak >= 7 ? 'completed' : 'soon'; ?></p></div></div><div class="journey-item <?php echo $moods >= 30 ? 'is-done' : ''; ?>"><span><?php echo $moods >= 30 ? '✓' : '...'; ?></span><div><strong>30 mood check-ins</strong><p><?php echo $moods >= 30 ? 'completed' : 'soon'; ?></p></div></div></section>

  <?php if (!empty($savedAffirmations)): ?>
    <section class="dashboard-card journey-card" style="margin-top: 1.5rem;">
      <h2>Whispers in your heart 🌷</h2>
      <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
        <?php foreach ($savedAffirmations as $aff): ?>
          <div style="border-left: 3px solid var(--primary, #a78bfa); padding-left: 0.75rem;">
            <p style="font-style: italic; font-size: 0.95rem; margin: 0; line-height: 1.4;">"<?php echo e($aff['affirmation_content']); ?>"</p>
            <small style="color: var(--text-muted, #9ca3af); font-size: 0.8rem; display: block; margin-top: 0.25rem;">— <?php echo e($aff['affirmation_note']); ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>
</main>
</body>
</html>
