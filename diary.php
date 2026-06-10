<?php
require __DIR__ . '/config/auth.php';
require_login();
require __DIR__ . '/config/db.php';
require __DIR__ . '/config/thisilabs.php';

date_default_timezone_set('Asia/Jakarta');

$userId = (int) $_SESSION['user_id'];
$today = date('Y-m-d');
$requestedDate = $_GET['date'] ?? $today;

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedDate)) {
    $requestedDate = $today;
}

function temanpulih_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $path = __DIR__ . '/temanpulih.json';
    $json = is_file($path) ? file_get_contents($path) : '';
    $config = json_decode((string) $json, true);

    return is_array($config) ? $config : [];
}

function clean_level_title(string $title): string
{
    $title = explode(' ð', $title)[0];
    $title = trim($title);

    return ucwords(strtolower($title));
}

function journal_program_phases(): array
{
    $levels = temanpulih_config()['chatbot_configuration']['intervention_program']['levels'] ?? [];
    $phases = [];

    foreach ($levels as $level) {
        foreach (($level['phases'] ?? []) as $phase) {
            $phases[] = [
                'level' => (int) ($level['level'] ?? 1),
                'level_title' => clean_level_title((string) ($level['title'] ?? 'Healing Journey')),
                'level_goal' => (string) ($level['goal'] ?? ''),
                'phase_id' => (string) ($phase['phase_id'] ?? ''),
                'name' => (string) ($phase['name'] ?? 'Journaling'),
                'description' => (string) ($phase['description'] ?? ''),
                'prompts' => $phase['prompts'] ?? [],
            ];
        }
    }

    if ($phases) {
        return $phases;
    }

    return [
        [
            'level' => 1,
            'level_title' => 'Understand Your Feelings',
            'level_goal' => 'Membantu pengguna mengenali emosi dan kondisi dirinya.',
            'phase_id' => '1.1',
            'name' => 'Mood Check',
            'description' => 'User memilih mood harian dan menjawab pertanyaan sederhana.',
            'prompts' => ['Apa yang paling kamu rasakan hari ini?'],
        ],
    ];
}

function journal_step_for_day(int $day): array
{
    $phases = journal_program_phases();
    $phaseIndexByDay = [
        1 => 0,
        2 => 1,
        3 => 2,
        4 => 3,
        5 => 3,
        6 => 4,
        7 => 4,
        8 => 5,
        9 => 6,
        10 => 6,
        11 => 7,
        12 => 7,
        13 => 8,
        14 => 8,
    ];
    $index = $phaseIndexByDay[min(max($day, 1), 14)] ?? 0;

    return $phases[min($index, count($phases) - 1)];
}

function journal_phase_label(string $phase): string
{
    $labels = [
        'Fase Kesadaran' => 'Awareness Phase',
        'Fase Pemulihan' => 'Healing Phase',
        'Fase Penguatan' => 'Strengthening Phase',
    ];

    return $labels[$phase] ?? $phase;
}

function journal_phase_description(string $phase, ?array $step = null): string
{
    $phase = journal_phase_label($phase);

    $descriptions = [
        'Mood Check' => 'Di fase ini, Puli akan membantu kamu menyadari suasana hati hari ini dan memberi nama pada perasaan yang sedang muncul.',
        'Emotional Awareness' => 'Di fase ini, Puli akan menemanimu mengenali emosi yang sering kamu pendam dan memahami apa yang sebenarnya ingin didengar oleh hatimu.',
        'Safe Expression' => 'Di fase ini, Puli akan membantu kamu menulis perasaan yang sulit diucapkan dengan aman, jujur, dan tanpa takut dihakimi.',
        'Deep Journaling' => 'Di fase ini, Puli akan mengajak kamu menyelami pengalaman yang membekas pelan-pelan, supaya hal yang berat tidak terus tersimpan sendirian.',
        'Self Reflection' => 'Di fase ini, Puli akan membantumu melihat bagaimana pengalaman itu memengaruhi dirimu, hubunganmu, dan rasa amanmu.',
        'Emotional Release' => 'Di fase ini, Puli akan menemani kamu melepaskan beban emosi lewat tulisan, termasuk hal-hal yang mungkin belum sempat kamu katakan.',
        'Self Compassion' => 'Di fase ini, Puli akan membantu kamu berbicara pada diri sendiri dengan lebih lembut dan mengingat bahwa kamu layak diperlakukan baik.',
        'Positive Growth' => 'Di fase ini, Puli akan mendukung kamu membangun kebiasaan kecil yang menenangkan dan memilih satu langkah baik untuk merawat diri.',
        'Healing Reflection' => 'Di fase ini, Puli akan mengajak kamu melihat kembali perjalananmu, menghargai keberanianmu, dan menyadari pertumbuhan kecil yang sudah terjadi.',
        'Awareness Phase' => 'Di fase ini, Puli akan membantu kamu mengenali perasaan, luka, dan pola yang mungkin selama ini terasa membingungkan.',
        'Healing Phase' => 'Di fase ini, Puli akan menemanimu memproses luka dengan lebih lembut, memahami kebutuhan emosional, dan memberi ruang untuk dirimu sendiri.',
        'Strengthening Phase' => 'Di fase ini, Puli akan mendukung kamu membangun kekuatan kecil, batasan yang sehat, dan cara baru untuk memilih dirimu sendiri.',
    ];

    if (isset($descriptions[$phase])) {
        return $descriptions[$phase];
    }

    return $descriptions[$phase] ?? 'Puli akan menemanimu menulis pelan-pelan sesuai tahap perjalananmu hari ini.';
}

function count_completed_journal_days(mysqli $conn, int $userId): int
{
    $countStmt = $conn->prepare(
        "SELECT COUNT(*) AS total
         FROM journal_entries
         WHERE user_id = ? AND content IS NOT NULL AND TRIM(content) <> ''"
    );
    $countStmt->bind_param('i', $userId);
    $countStmt->execute();

    return (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
}

function ensure_journal_prompt(mysqli $conn, string $question, string $phase): ?array
{
    $stmt = $conn->prepare('SELECT id, question, phase FROM journal_prompts WHERE question = ? LIMIT 1');
    $stmt->bind_param('s', $question);
    $stmt->execute();
    $prompt = $stmt->get_result()->fetch_assoc();

    if ($prompt) {
        if (($prompt['phase'] ?? '') !== $phase) {
            $update = $conn->prepare('UPDATE journal_prompts SET phase = ? WHERE id = ?');
            $promptId = (int) $prompt['id'];
            $update->bind_param('si', $phase, $promptId);
            $update->execute();
            $prompt['phase'] = $phase;
        }

        return $prompt;
    }

    $insert = $conn->prepare('INSERT INTO journal_prompts (question, phase) VALUES (?, ?)');
    $insert->bind_param('ss', $question, $phase);
    if (!$insert->execute()) {
        return null;
    }

    return [
        'id' => $conn->insert_id,
        'question' => $question,
        'phase' => $phase,
    ];
}

function find_prompt_for_user(mysqli $conn, int $userId, array $step): ?array
{
    $phase = (string) ($step['name'] ?? 'Journaling');
    $prompts = array_values(array_filter($step['prompts'] ?? []));
    shuffle($prompts);

    $phasePrompts = [];
    foreach ($prompts as $question) {
        $prompt = ensure_journal_prompt($conn, (string) $question, $phase);
        if ($prompt) {
            $phasePrompts[] = $prompt;
        }
    }

    foreach ($phasePrompts as $prompt) {
        $promptId = (int) $prompt['id'];
        $used = $conn->prepare('SELECT id FROM journal_entries WHERE user_id = ? AND prompt_id = ? LIMIT 1');
        $used->bind_param('ii', $userId, $promptId);
        $used->execute();

        if (!$used->get_result()->fetch_assoc()) {
            return $prompt;
        }
    }

    if ($phasePrompts) {
        return $phasePrompts[array_rand($phasePrompts)];
    }

    $fallback = $conn->query('SELECT id, question, phase FROM journal_prompts ORDER BY RAND() LIMIT 1');
    return $fallback ? $fallback->fetch_assoc() : null;
}

function generate_puli_tip(string $prompt, string $content, string $phase, string $level, int $programDay, bool $journeyCompleted): ?string
{
    global $THISILABS_API_KEY, $THISILABS_BASE_URL, $THISILABS_MODEL;

    if (trim($THISILABS_API_KEY ?? '') === '' || str_contains((string) $THISILABS_API_KEY, 'ISI_API_KEY')) {
        return null;
    }

    if ($journeyCompleted) {
        $journeyGuidance = 'User sudah menyelesaikan 14 hari. Jangan menyarankan mengejar atau menyelesaikan progress 14 hari lagi. Perlakukan sesi ini sebagai free journaling lanjutan: validasi, apresiasi konsistensi, dan ajak menjaga kebiasaan menulis dengan lembut.';
    } elseif ($programDay >= 14) {
        $journeyGuidance = 'User sedang berada di Day 14, fase penutup journey. Jangan menulis arahan untuk lanjut sampai 14 hari. Beri nuansa apresiasi bahwa perjalanan 14 hari sedang ditutup, lalu arahkan ke refleksi akhir dan kebiasaan menulis bebas setelah ini.';
    } else {
        $journeyGuidance = 'User belum menyelesaikan 14 hari. Boleh beri dorongan lembut untuk kembali menulis di sesi berikutnya, tanpa membuat user merasa dikejar target.';
    }

    $system = <<<TEXT
Kamu adalah Puli dari TemanPulih, pendamping expressive writing journaling untuk remaja perempuan fatherless.

Tugasmu membaca prompt jurnal dan jawaban user, lalu memberi saran personal yang lembut.
Aturan:
- Gunakan Bahasa Indonesia santai dan sopan.
- Panggil user "Kak".
- Validasi perasaannya dulu.
- Jangan memberi diagnosis medis atau psikologis.
- Jangan toxic positivity.
- Jangan gunakan tanda pisah panjang "—".
- Jangan mengulang isi jurnal secara terlalu detail.
- Berikan 2 paragraf pendek saja.
- Jangan akhiri dengan pertanyaan.
- Jangan meminta user menjawab pertanyaan tambahan di saran ini.
- Ikuti status journey berikut: {$journeyGuidance}
- Akhiri dengan satu langkah kecil yang bisa dilakukan hari ini dan dorongan lembut untuk kembali menulis di sesi berikutnya.
TEXT;

    $user = <<<TEXT
Level: {$level}
Phase: {$phase}
Prompt jurnal: {$prompt}

Jawaban user:
{$content}

Status journey: {$journeyGuidance}

Buat "Saran dari Puli" yang spesifik berdasarkan jawaban user dan prompt jurnal di atas.
Saran harus berupa arahan lembut, bukan pertanyaan. Kalau journey sudah selesai atau sedang Day 14, jangan menyuruh user melanjutkan progress sampai 14 hari lagi.
TEXT;

    $payload = [
        'model' => $THISILABS_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ],
        'temperature' => 0.72,
        'top_p' => 0.9,
        'max_tokens' => 260,
    ];

    $ch = curl_init(rtrim($THISILABS_BASE_URL, '/') . '/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $THISILABS_API_KEY,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError || $httpCode < 200 || $httpCode >= 300) {
        return null;
    }

    $data = json_decode((string) $response, true);
    $tip = trim((string) ($data['choices'][0]['message']['content'] ?? ''));

    return $tip !== '' ? str_replace('—', ',', $tip) : null;
}

function load_entry(mysqli $conn, int $userId, string $date): ?array
{
    $stmt = $conn->prepare(
        'SELECT je.id, je.entry_date, je.content, je.puli_tip, jp.question, jp.phase
         FROM journal_entries je
         JOIN journal_prompts jp ON jp.id = je.prompt_id
         WHERE je.user_id = ? AND je.entry_date = ?
         LIMIT 1'
    );
    $stmt->bind_param('is', $userId, $date);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function translate_journal_prompt(array $entry): array
{
    $questions = [
        'Apa perasaan yang paling sering muncul saat kamu memikirkan figur ayah? Coba sebutkan tiga emosi dan ceritakan pelan-pelan.' => 'What feeling shows up most often when you think about a father figure? Name three emotions and describe them gently.',
        'Hari ini, bagian dirimu yang paling ingin didengar sedang berkata apa?' => 'What part of you wants to be heard the most today?',
        'Kapan kamu merasa harus terlihat kuat, padahal sebenarnya ingin dipeluk atau ditemani?' => 'When do you feel like you have to look strong, even though you want to be held or accompanied?',
        'Apa hal kecil hari ini yang membuatmu merasa aman, meski hanya sebentar?' => 'What small thing made you feel safe today, even if only for a moment?',
        'Kalau rasa rindumu bisa berbicara tanpa takut dihakimi, apa yang akan ia ucapkan?' => 'If your longing could speak without fear of judgment, what would it say?',
        'Bagaimana hubunganmu dengan kepercayaan: pada orang lain, pada cinta, dan pada dirimu sendiri?' => 'How do you relate to trust: trusting others, trusting love, and trusting yourself?',
        'Apa kalimat yang dulu ingin sekali kamu dengar dari ayah atau sosok pelindung?' => 'What words did you once wish you could hear from a father or protective figure?',
        'Saat seseorang menjauh, cerita apa yang otomatis muncul di kepalamu tentang dirimu?' => 'When someone pulls away, what story does your mind automatically tell you about yourself?',
        'Apa bentuk perhatian yang paling membuatmu merasa dicintai dan tidak sendirian?' => 'What kind of care makes you feel loved and less alone?',
        'Apa yang biasanya kamu lakukan untuk menenangkan diri saat merasa ditinggalkan?' => 'What do you usually do to soothe yourself when you feel abandoned?',
        'Bagian mana dari dirimu yang paling lelah mengejar validasi hari ini?' => 'Which part of you feels most tired from seeking validation today?',
        'Apa batasan kecil yang ingin kamu jaga minggu ini agar hatimu tidak terus terluka?' => 'What small boundary do you want to protect this week so your heart does not keep getting hurt?',
        'Ceritakan satu momen ketika kamu berhasil bertahan, walau saat itu terasa berat sekali.' => 'Write about one moment when you survived something that felt very heavy.',
        'Apa yang ingin kamu maafkan dari dirimu, bukan karena kamu salah, tapi karena kamu sudah terlalu lama keras pada diri sendiri?' => 'What do you want to forgive yourself for, not because you were wrong, but because you have been too hard on yourself?',
        'Jika kamu bisa memeluk versi kecil dirimu, apa yang ingin kamu bisikkan padanya?' => 'If you could hug your younger self, what would you whisper to her?',
        'Pola hubungan apa yang ingin kamu pahami, bukan untuk menyalahkan diri, tapi untuk mulai memilih dengan sadar?' => 'What relationship pattern do you want to understand, not to blame yourself, but to choose more consciously?',
        'Apa tanda bahwa kamu sedang merasa tidak aman secara emosional?' => 'What are your signs that you are feeling emotionally unsafe?',
        'Apa satu kebutuhan emosionalmu yang sering kamu kecilkan agar tidak dianggap merepotkan?' => 'What emotional need do you often minimize because you are afraid of being too much?',
        'Bagaimana kamu bisa memberi diri sendiri rasa aman yang dulu tidak selalu kamu dapatkan?' => 'How can you give yourself the sense of safety you did not always receive?',
        'Apa arti pulih bagimu hari ini, dalam bentuk yang paling sederhana?' => 'What does healing mean to you today, in the simplest form?',
        'Tulis surat pendek untuk dirimu yang sedang takut ditinggalkan.' => 'Write a short letter to the part of you that is afraid of being left.',
        'Apa hal yang ingin kamu berhenti cari dari orang yang tidak mampu memberikannya?' => 'What do you want to stop seeking from people who cannot give it to you?',
        'Apa kualitas dirimu yang tetap tumbuh meski kamu pernah merasa kurang didukung?' => 'What quality in you kept growing even when you felt unsupported?',
        'Kapan terakhir kali kamu memilih dirimu sendiri? Apa rasanya?' => 'When was the last time you chose yourself? What did it feel like?',
        'Apa bentuk cinta yang sehat menurutmu sekarang?' => 'What does healthy love mean to you now?',
        'Apa yang ingin kamu bangun di hidupmu tanpa harus menunggu seseorang menyelamatkanmu?' => 'What do you want to build in your life without waiting for someone to rescue you?',
        'Apa tiga hal yang membuktikan bahwa kamu layak dicintai tanpa harus sempurna?' => 'What are three things that prove you are worthy of love without being perfect?',
        'Jika hari ini kamu hanya punya energi sedikit, hal lembut apa yang tetap bisa kamu lakukan untuk diri sendiri?' => 'If you only have a little energy today, what gentle thing can you still do for yourself?',
        'Apa janji kecil yang ingin kamu buat untuk menjaga hatimu mulai hari ini?' => 'What small promise do you want to make to protect your heart starting today?',
        'Bayangkan dirimu 14 hari dari sekarang. Apa yang ingin ia syukuri dari keberanianmu menulis hari ini?' => 'Imagine yourself 14 days from now. What would she thank you for writing today?',
    ];

    $phases = [
        'Fase Kesadaran' => 'Awareness Phase',
        'Fase Pemulihan' => 'Healing Phase',
        'Fase Penguatan' => 'Strengthening Phase',
    ];

    $englishToIndonesian = array_flip($questions);
    if (isset($englishToIndonesian[$entry['question'] ?? ''])) {
        $entry['question'] = $englishToIndonesian[$entry['question']];
    }

    if (isset($phases[$entry['phase'] ?? ''])) {
        $entry['phase'] = $phases[$entry['phase']];
    }

    return $entry;
}

$completedDays = count_completed_journal_days($conn, $userId);

if ($requestedDate === $today && !load_entry($conn, $userId, $today)) {
    $promptDay = min($completedDays + 1, 14);
    $promptStep = journal_step_for_day($promptDay);
    $prompt = find_prompt_for_user($conn, $userId, $promptStep);

    if ($prompt) {
        $stmt = $conn->prepare('INSERT INTO journal_entries (user_id, prompt_id, entry_date) VALUES (?, ?, ?)');
        $promptId = (int) $prompt['id'];
        $stmt->bind_param('iis', $userId, $promptId, $today);
        $stmt->execute();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entryId = (int) ($_POST['entry_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    $tip = null;
    if ($content !== '') {
        $entryStmt = $conn->prepare(
            'SELECT jp.question, jp.phase
             FROM journal_entries je
             JOIN journal_prompts jp ON jp.id = je.prompt_id
             WHERE je.id = ? AND je.user_id = ?
             LIMIT 1'
        );
        $entryStmt->bind_param('ii', $entryId, $userId);
        $entryStmt->execute();
        $tipEntry = $entryStmt->get_result()->fetch_assoc();

        if ($tipEntry) {
            $tipDay = min(count_completed_journal_days($conn, $userId) + 1, 14);
            $tipStep = journal_step_for_day($tipDay);
            $tip = generate_puli_tip(
                (string) $tipEntry['question'],
                $content,
                journal_phase_label((string) $tipEntry['phase']),
                'Level ' . (int) $tipStep['level'] . ' - ' . $tipStep['level_title'],
                $tipDay,
                count_completed_journal_days($conn, $userId) >= 14
            );
        }
    }

    $stmt = $conn->prepare('UPDATE journal_entries SET content = ?, puli_tip = ? WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ssii', $content, $tip, $entryId, $userId);
    $stmt->execute();

    set_flash('success', $tip ? 'Jurnalmu sudah tersimpan. Puli juga menyiapkan saran kecil untukmu.' : 'Jurnalmu sudah tersimpan dengan aman.');
    header('Location: diary.php?date=' . urlencode($_POST['entry_date'] ?? $today));
    exit;
}

$entry = load_entry($conn, $userId, $requestedDate);

if ($entry) {
    $entry = translate_journal_prompt($entry);
}

if (!$entry && $requestedDate !== $today) {
    set_flash('error', 'Belum ada jurnal untuk tanggal itu.');
    header('Location: diary.php');
    exit;
}

$progressDays = min($completedDays, 14);
$progressPercent = (int) round(($progressDays / 14) * 100);
$programDay = min($completedDays + (($entry && trim((string) $entry['content']) === '') ? 1 : 0), 14);
$programDay = max(1, $programDay);
$journalStep = journal_step_for_day($programDay);
$displayPhase = journal_phase_label($entry['phase'] ?? $journalStep['name']);
$phaseDescription = journal_phase_description($displayPhase, $journalStep);
$displayLevel = 'Level ' . (int) $journalStep['level'] . ' - ' . $journalStep['level_title'];

$prevStmt = $conn->prepare('SELECT entry_date FROM journal_entries WHERE user_id = ? AND entry_date < ? ORDER BY entry_date DESC LIMIT 1');
$prevStmt->bind_param('is', $userId, $requestedDate);
$prevStmt->execute();
$prevDate = $prevStmt->get_result()->fetch_assoc()['entry_date'] ?? null;

$nextStmt = $conn->prepare('SELECT entry_date FROM journal_entries WHERE user_id = ? AND entry_date > ? ORDER BY entry_date ASC LIMIT 1');
$nextStmt->bind_param('is', $userId, $requestedDate);
$nextStmt->execute();
$nextDate = $nextStmt->get_result()->fetch_assoc()['entry_date'] ?? null;

$savedStmt = $conn->prepare(
    "SELECT je.entry_date, je.content, jp.phase
     FROM journal_entries je
     JOIN journal_prompts jp ON jp.id = je.prompt_id
     WHERE je.user_id = ? AND je.content IS NOT NULL AND TRIM(je.content) <> ''
     ORDER BY je.entry_date DESC
     LIMIT 7"
);
$savedStmt->bind_param('i', $userId);
$savedStmt->execute();
$savedEntries = $savedStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$flash = consume_flash();
$wordCount = $entry && trim((string) $entry['content']) !== '' ? str_word_count(strip_tags($entry['content'])) : 0;
$entryIsSaved = $wordCount > 0;
$dateLabel = date('d M Y', strtotime($requestedDate));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TemanPulih - Diary</title>
  <link rel="stylesheet" href="styles.css" />
  <link rel="icon" type="image/png" href="mascott/favicon.png" />
</head>
<body class="app-body">
  <header class="app-header">
    <a class="brand" href="home.php" aria-label="TemanPulih home">
      <span class="brand-badge">
        <img src="mascott/1-clean.png" alt="" />
      </span>
      <span>TEMANPULIH</span>
    </a>

    <nav class="top-nav" aria-label="Main navigation">
      <a href="home.php">The Hub</a>
      <a class="is-active" href="diary.php">Diary</a>
      <a href="library.php">Library</a>
      <a href="safe_space.php">Safe Space</a>
      <a class="profile-button" href="profile.php" aria-label="Profile">&#9825;</a>
    </nav>
  </header>

  <main class="diary-shell">
    <section class="diary-intro">
      <div>
        <p class="hello-pill">RUANG AMAN HARI INI</p>
        <h1>Tulis yang berat, pelan-pelan.</h1>
        <p>TemanPulih menemanimu menjalani 3-Level Healing Journey lewat expressive writing journaling yang lembut dan terstruktur.</p>
      </div>
      <img src="mascott/3-clean.png" alt="" />
    </section>

    <section class="diary-layout">
      <aside class="dashboard-card diary-progress-card">
        <span class="section-kicker">PROGRESS PROGRAM</span>
        <div class="progress-summary">
          <div class="progress-ring" style="--progress: <?php echo $progressPercent; ?>%;">
            <span><?php echo $progressPercent; ?>%</span>
          </div>
          <div>
            <strong><?php echo $progressDays; ?> dari 14 hari</strong>
            <p><?php echo e($displayLevel); ?></p>
          </div>
        </div>
        <div class="progress-track">
          <span style="width: <?php echo $progressPercent; ?>%;"></span>
        </div>
        <p class="progress-note">3 level, 9 phase, 14 hari. Tujuannya bukan sempurna, tapi hadir untuk dirimu sendiri.</p>
      </aside>

      <section class="dashboard-card diary-card">
        <div class="diary-card-header">
          <div>
            <div class="phase-kicker-row">
              <span class="section-kicker"><?php echo e($displayPhase); ?> - Day <?php echo $programDay; ?></span>
              <span class="phase-info-tooltip" tabindex="0" aria-label="<?php echo e($phaseDescription); ?>">
                <span class="phase-info-icon" aria-hidden="true">i</span>
                <span class="phase-tooltip-text" role="tooltip"><?php echo e($phaseDescription); ?></span>
              </span>
            </div>
            <h2>Prompt hari ini</h2>
          </div>
          <div class="diary-date-nav" aria-label="Journal navigation">
            <?php if ($prevDate): ?>
              <a href="diary.php?date=<?php echo e($prevDate); ?>" aria-label="Previous journal">&larr;</a>
            <?php else: ?>
              <span aria-hidden="true">&larr;</span>
            <?php endif; ?>
            <strong><?php echo e($dateLabel); ?></strong>
            <?php if ($nextDate): ?>
              <a href="diary.php?date=<?php echo e($nextDate); ?>" aria-label="Next journal">&rarr;</a>
            <?php else: ?>
              <span aria-hidden="true">&rarr;</span>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($flash): ?>
          <p class="auth-message <?php echo $flash['type'] === 'success' ? 'auth-message-success' : ''; ?>">
            <?php echo e($flash['message']); ?>
          </p>
        <?php endif; ?>

        <blockquote class="prompt-box">"<?php echo e($entry['question'] ?? 'What would you like to tell yourself today?'); ?>"</blockquote>

        <?php if (!empty($entry['puli_tip'])): ?>
          <section class="puli-message" aria-label="Pesan dari Puli">
            <button
              class="puli-message-trigger"
              type="button"
              aria-expanded="false"
              aria-controls="puli-message-body"
            >
              <span class="puli-envelope" aria-hidden="true">
                <span class="puli-message-dot"></span>
              </span>
              <span>
                <strong>Pesan dari Puli</strong>
                <small>Puli menyiapkan saran kecil dari jurnalmu. Ketuk untuk membuka.</small>
              </span>
            </button>
            <div class="puli-message-body" id="puli-message-body" hidden>
              <p><?php echo nl2br(e($entry['puli_tip'])); ?></p>
            </div>
          </section>
        <?php endif; ?>

        <form class="journal-form" action="diary.php" method="post">
          <input type="hidden" name="entry_id" value="<?php echo (int) ($entry['id'] ?? 0); ?>" />
          <input type="hidden" name="entry_date" value="<?php echo e($requestedDate); ?>" />
          <label for="content">Jurnal pribadimu</label>
          <textarea id="content" name="content" placeholder="Tulis perasaanmu di sini. Pelan-pelan saja, Kak."><?php echo e($entry['content'] ?? ''); ?></textarea>
          <div class="journal-meta">
            <div class="journal-meta-status">
              <span id="word-count"><?php echo $wordCount; ?> kata</span>
              <span
                id="save-status"
                class="save-status-pill <?php echo $entryIsSaved ? 'is-saved' : ''; ?>"
              >
                <?php echo $entryIsSaved ? 'Tersimpan' : 'Belum tersimpan'; ?>
              </span>
            </div>
            <button class="pill-button primary-pill" type="submit">Simpan Jurnal</button>
          </div>
        </form>
      </section>

      <aside class="dashboard-card saved-journals-card">
        <div class="saved-journals-heading">
          <span class="section-kicker">JOURNAL TERSIMPAN</span>
          <strong><?php echo $completedDays; ?></strong>
        </div>

        <?php if (empty($savedEntries)): ?>
          <p class="saved-journals-empty">Jurnal yang sudah kamu simpan akan muncul di sini.</p>
        <?php else: ?>
          <div class="saved-journal-list">
            <?php foreach ($savedEntries as $savedEntry): ?>
              <?php
                $savedDate = (string) $savedEntry['entry_date'];
                $savedContent = trim(strip_tags((string) $savedEntry['content']));
                $savedPreview = mb_strlen($savedContent, 'UTF-8') > 92
                    ? mb_substr($savedContent, 0, 92, 'UTF-8') . '...'
                    : $savedContent;
              ?>
              <a
                class="saved-journal-item <?php echo $savedDate === $requestedDate ? 'is-active' : ''; ?>"
                href="diary.php?date=<?php echo e($savedDate); ?>"
              >
                <span><?php echo e(date('d M Y', strtotime($savedDate))); ?></span>
                <strong><?php echo e(journal_phase_label($savedEntry['phase'])); ?></strong>
                <p><?php echo e($savedPreview); ?></p>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </aside>
    </section>
  </main>

  <nav class="bottom-nav" aria-label="Quick navigation">
    <a href="home.php"><span class="nav-icon nav-icon-home" aria-hidden="true"></span><span>Home</span></a>
    <a class="is-active" href="diary.php"><span class="nav-icon nav-icon-book" aria-hidden="true"></span><span>Journal</span></a>
    <a class="nav-plus" href="diary.php">+</a>
    <a href="library.php">&#10023;<span>Explore</span></a>
    <a href="safe_space.php">&#9825;<span>Safe</span></a>
  </nav>

  <script>
    const textarea = document.getElementById("content");
    const wordCount = document.getElementById("word-count");

    textarea.addEventListener("input", () => {
      const words = textarea.value.trim().split(/\s+/).filter(Boolean);
      wordCount.textContent = `${words.length} kata`;

      const saveStatus = document.getElementById("save-status");
      if (!saveStatus) return;

      saveStatus.classList.remove("is-saved");
      saveStatus.classList.add("is-dirty");
      saveStatus.textContent = words.length ? "Belum disimpan" : "Belum tersimpan";
    });

    const puliMessageTrigger = document.querySelector(".puli-message-trigger");
    const puliMessageBody = document.getElementById("puli-message-body");

    if (puliMessageTrigger && puliMessageBody) {
      puliMessageTrigger.addEventListener("click", () => {
        const isOpen = puliMessageTrigger.getAttribute("aria-expanded") === "true";
        puliMessageTrigger.setAttribute("aria-expanded", String(!isOpen));
        puliMessageBody.hidden = isOpen;
      });
    }

  </script>
</body>
</html>
