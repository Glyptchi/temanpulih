<?php
require __DIR__ . '/config/auth.php';
require_login();

require __DIR__ . '/config/db.php';
require __DIR__ . '/config/thisilabs.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (int) $_SESSION['user_id'];

function temanpulih_chat_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $path = __DIR__ . '/temanpulih.json';
    $json = is_file($path) ? file_get_contents($path) : '';
    $config = json_decode((string) $json, true);

    return is_array($config) ? ($config['chatbot_configuration'] ?? []) : [];
}

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
$userMessage = trim($input['message'] ?? '');

if ($userMessage === '') {
    echo json_encode([
        'ok' => false,
        'reply' => 'Kak, boleh tuliskan dulu apa yang sedang kamu rasakan?'
    ]);
    exit;
}

$config = temanpulih_chat_config();
$crisisKeywords = $config['safety_guardrails']['crisis_keywords'] ?? [
    'bunuh diri',
    'ingin mati',
    'mengakhiri hidup',
    'tidak mau hidup',
    'menyakiti diri',
    'overdosis',
    'percuma hidup'
];
$crisisKeywords = array_unique(array_merge($crisisKeywords, [
    'mau mati',
    'pengen mati',
    'pengin mati',
    'capek hidup',
    'cape hidup',
    'pengen hilang',
    'pengin hilang',
    'ingin hilang',
    'mati aja',
    'lebih baik mati',
    'mending mati',
    'hidupku percuma',
    'self harm',
    'self-harm',
    'nyakitin diri',
    'melukai diri',
    'lukain diri',
    'minum obat banyak',
    'minum banyak obat',
]));

$lowerMessage = mb_strtolower($userMessage, 'UTF-8');

foreach ($crisisKeywords as $keyword) {
    if (str_contains($lowerMessage, $keyword)) {
        $reply = "Kak, aku mendengar bahwa rasa sakit yang kamu alami saat ini sangat berat. Keselamatanmu adalah hal yang paling penting.\n\n"
            . "Tolong jangan hadapi ini sendirian. Hubungi orang terdekat yang kamu percaya, keluarga, guru BK MAN 1 Jombang, atau bantuan profesional.\n\n"
            . "Kontak bantuan yang bisa dicoba:\n"
            . "- Layanan Krisis Nasional SEJIWA: 119 ext. 8\n"
            . "- Jika Kakak merasa dalam bahaya sekarang, segera hubungi layanan darurat setempat atau pergi ke tempat yang aman.\n\n"
            . "Untuk saat ini, coba jauhkan benda yang bisa membahayakan, tetap bersama orang lain, lalu tarik napas pelan: hirup 4 hitungan, tahan 7 hitungan, hembuskan 8 hitungan.";

        $stmt = $conn->prepare('INSERT INTO safe_space_messages (user_id, role, message) VALUES (?, "user", ?), (?, "model", ?)');
        $stmt->bind_param('isis', $userId, $userMessage, $userId, $reply);
        $stmt->execute();

        echo json_encode([
            'ok' => true,
            'reply' => $reply
        ]);
        exit;
    }
}

$stmt = $conn->prepare(
    'SELECT role, message
     FROM safe_space_messages
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT 8'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$history = [];
while ($row = $result->fetch_assoc()) {
    $history[] = $row;
}
$history = array_reverse($history);

$persona = $config['persona'] ?? [];
$directives = $config['core_directives'] ?? [];
$ragKeywords = implode(', ', $config['rag_knowledge_retrieval']['trigger_keywords'] ?? []);
$sourcePriority = implode('; ', $config['rag_knowledge_retrieval']['retrieval_logic']['step_3_source_priority'] ?? []);
$directiveText = $directives ? "- " . implode("\n- ", $directives) : "- Validasi perasaan pengguna dan fasilitasi journaling dengan aman.";
$personaName = $persona['name'] ?? 'TemanPulih';
$personaRole = $persona['role'] ?? 'Pendamping Self-Healing & Fasilitator Journaling';
$personaTone = $persona['tone'] ?? 'Empatik, hangat, suportif, dan tidak menghakimi.';
$personaLanguage = $persona['language_style'] ?? 'Bahasa Indonesia santai namun sopan.';

$userName = trim((string) ($_SESSION['user_name'] ?? ''));
if ($userName === '') {
    $userName = 'Kak';
}

$journalCountStmt = $conn->prepare("SELECT COUNT(*) total FROM journal_entries WHERE user_id = ? AND content IS NOT NULL AND TRIM(content) <> ''");
$journalCountStmt->bind_param('i', $userId);
$journalCountStmt->execute();
$journalCount = (int) ($journalCountStmt->get_result()->fetch_assoc()['total'] ?? 0);

$moodContext = 'belum ada mood terbaru yang tercatat';
$moodStmt = $conn->prepare('SELECT mood, mood_date FROM moods WHERE user_id = ? ORDER BY mood_date DESC LIMIT 1');
$moodStmt->bind_param('i', $userId);
$moodStmt->execute();
$latestMood = $moodStmt->get_result()->fetch_assoc();
if ($latestMood) {
    $moodContext = $latestMood['mood'] . ' pada ' . $latestMood['mood_date'];
}

$userContext = <<<TEXT
Konteks ringan pengguna dari aplikasi:
- Nama/sapaan tersimpan: {$userName}
- Jumlah jurnal tersimpan: {$journalCount}
- Mood terakhir: {$moodContext}
Gunakan konteks ini secukupnya agar respons terasa personal. Jangan menyimpulkan hal besar hanya dari data ini.
TEXT;

$systemInstruction = <<<TEXT
Kamu adalah Puli dari TemanPulih, pendamping Safe Space dan fasilitator Expressive Writing Journaling untuk remaja/perempuan muda fatherless.

Persona dari konfigurasi:
- Nama: Puli
- Peran: {$personaRole}
- Tone: {$personaTone}
- Gaya bahasa: {$personaLanguage}

Arahan inti:
{$directiveText}

{$userContext}

Fokus personalisasi fatherless:
- Pahami fatherless sebagai pengalaman kehilangan, ketidakhadiran, jarak emosional, penolakan, perceraian, ayah yang tidak aman, atau figur ayah yang ada tapi tidak terasa hadir.
- Saat relevan, bantu pengguna memahami pola yang mungkin muncul: merasa ditinggalkan, takut tidak dipilih, sulit percaya, people pleasing, cepat merasa bersalah, marah tapi rindu, iri melihat keluarga utuh, mencari validasi, sulit menetapkan batasan, atau merasa harus kuat sendiri.
- Jangan menganggap semua masalah pasti karena ayah. Hubungkan hanya jika cerita pengguna memang mengarah ke sana.
- Jangan menyalahkan ayah, ibu, keluarga, atau pengguna. Bantu pengguna memberi nama pada luka dan kebutuhan emosionalnya.

Program intervensi:
- Fasilitasi Progressive Expressive Writing Journaling berbasis 3 Level Healing Journey dan 9 phase.
- Level 1: mengenali mood, emosi, kebutuhan, dan pola luka.
- Level 2: mengekspresikan pengalaman yang membekas, merefleksikan dampaknya, dan melepas emosi dengan aman.
- Level 3: membangun self-compassion, batasan sehat, self-worth, coping kecil, dan refleksi pertumbuhan.

Cara menjawab agar tidak muter-muter:
- Jangan memakai template berulang seperti "Kak, wajar banget" di setiap respons. Variasikan validasi dan buat validasi spesifik dari isi pesan terbaru.
- Kalimat awal harus menyebut detail konkret dari pesan pengguna, bukan validasi umum.
- Setelah validasi, beri satu pemahaman personal: emosi apa yang tampak, kebutuhan apa yang belum terpenuhi, atau pola fatherless apa yang mungkin sedang aktif.
- Beri satu bantuan nyata yang aman: langkah coping kecil, kalimat batasan, cara menamai emosi, reframing lembut, atau prompt journaling yang spesifik.
- Jika pengguna meminta solusi, boleh beri 2-3 langkah praktis. Jangan hanya menyuruh cerita lagi.
- Jika perlu bertanya, tanyakan hanya satu pertanyaan yang spesifik dan membantu arah berikutnya. Jangan akhiri semua respons dengan "mau cerita lagi?".
- Jangan mengulang isi chat panjang-panjang. Jangan memberi ceramah umum.
- Jawab 2 sampai 4 paragraf pendek, maksimal sekitar 180 kata kecuali krisis.
- Gunakan Bahasa Indonesia santai, sopan, dan hangat. Panggil pengguna "Kak".
- Hindari toxic positivity, bahasa klinis kaku, diagnosis medis/psikologis, dan klaim sebagai psikolog/dokter.
- Jangan gunakan tanda pisah panjang "—". Pakai koma, titik, atau tanda hubung biasa jika perlu.

Format respons yang disarankan:
1. Validasi spesifik terhadap cerita terbaru.
2. Pemahaman/pola yang mungkin terjadi, terutama terkait fatherless jika relevan.
3. Satu langkah kecil atau prompt journaling yang konkret.
4. Satu pertanyaan reflektif spesifik hanya jika benar-benar membantu.

Psikoedukasi/RAG:
- Jika pengguna bertanya hal seperti: {$ragKeywords}, jelaskan ringan dan mudah dipahami.
- Jika menyebut sumber, gunakan prioritas sumber berikut hanya jika tersedia di sistem: {$sourcePriority}.
- Jangan mengarang kutipan, angka, atau detail jurnal yang tidak diberikan.

Safety:
- Jika ada tanda krisis seperti ingin mati, bunuh diri, menyakiti diri, overdosis, atau merasa tidak aman, hentikan journaling reguler.
- Prioritaskan keselamatan, arahkan ke bantuan manusia/profesional, dan tawarkan grounding sederhana seperti napas 4-7-8.
- TemanPulih adalah media pendamping preventif/kuratif mandiri, bukan pengganti terapi profesional.
TEXT;

$messages = [
    [
        'role' => 'system',
        'content' => $systemInstruction,
    ],
];

foreach ($history as $item) {
    $messages[] = [
        'role' => $item['role'] === 'model' ? 'assistant' : 'user',
        'content' => $item['message'],
    ];
}

$messages[] = [
    'role' => 'user',
    'content' => $userMessage,
];

$payload = [
    'model' => $THISILABS_MODEL,
    'messages' => $messages,
    'temperature' => 0.68,
    'top_p' => 0.9,
    'max_tokens' => 700,
];

$url = rtrim($THISILABS_BASE_URL, '/') . '/chat/completions';

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $THISILABS_API_KEY
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($curlError || $httpCode < 200 || $httpCode >= 300) {
    echo json_encode([
        'ok' => false,
        'reply' => 'Maaf Kak, Puli sedang kesulitan terhubung. Coba lagi sebentar ya.',
        'debug' => $curlError ?: $response
    ]);
    exit;
}

$data = json_decode($response, true);

$reply = $data['choices'][0]['message']['content']
    ?? 'Aku di sini mendengarkanmu, Kak. Ceritakan pelan-pelan ya.';
$reply = str_replace('—', ',', $reply);

$stmt = $conn->prepare('INSERT INTO safe_space_messages (user_id, role, message) VALUES (?, "user", ?), (?, "model", ?)');
$stmt->bind_param('isis', $userId, $userMessage, $userId, $reply);
$stmt->execute();

echo json_encode([
    'ok' => true,
    'reply' => $reply
]);
