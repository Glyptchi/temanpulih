<?php
require __DIR__ . '/config/auth.php';
require_login();
require __DIR__ . '/config/db.php';

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'clear_history') {
    $stmt = $conn->prepare('DELETE FROM safe_space_messages WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    set_flash('success', 'History chat sudah dibersihkan.');
    header('Location: safe_space.php');
    exit;
}

$stmt = $conn->prepare(
    'SELECT role, message, created_at
     FROM safe_space_messages
     WHERE user_id = ?
     ORDER BY id ASC
     LIMIT 50'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$flash = consume_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>TemanPulih - Safe Space</title>
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
      <a class="is-active" href="safe_space.php">Safe Space</a>
      <a class="profile-button" href="profile.php" aria-label="Profile">&#9825;</a>
    </nav>
  </header>

  <main class="safe-space-shell" aria-label="Safe Space chat">
    <section class="safe-space-panel">
      <header class="safe-space-header">
        <div>
          <p class="hello-pill">SAFE SPACE WITH PULI</p>
          <h1>Apa yang sedang kamu rasakan, Kak?</h1>
          <p>Puli akan membantumu memahami perasaan, luka, dan pola yang sering muncul pelan-pelan. Cerita pendek pun cukup.</p>
        </div>
        <img src="mascott/3-clean.png" alt="" />
      </header>

      <section class="consultation-cta" aria-label="Konsultasi gratis">
        <div>
          <span>KONSULTASI GRATIS</span>
          <strong>Butuh ditemani langsung?</strong>
          <p>Hubungi pendamping TemanPulih lewat WhatsApp untuk mulai berkonsultasi dengan lebih personal.</p>
        </div>
        <a
          href="https://wa.me/6282221998155?text=Permisi%2C%20saya%20dari%20aplikasi%20Teman%20Pulih%20ingin%20berkonsultasi..."
          target="_blank"
          rel="noopener noreferrer"
        >Chat WhatsApp</a>
      </section>

      <section class="chat-card">
        <div class="chat-card-header">
          <div>
            <span>Teman ngobrolmu</span>
            <strong>Puli</strong>
          </div>
          <div class="chat-card-actions">
            <p>Ruang ini privat untuk bercerita, refleksi, dan menenangkan diri.</p>
            <?php if (!empty($messages)): ?>
              <form method="post" onsubmit="return confirm('Bersihkan semua history chat dengan Puli?')">
                <input type="hidden" name="action" value="clear_history" />
                <button type="submit">Bersihkan</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($flash): ?>
          <p class="auth-message <?php echo $flash['type'] === 'success' ? 'auth-message-success' : ''; ?>">
            <?php echo e($flash['message']); ?>
          </p>
        <?php endif; ?>

        <div class="chat-messages" id="chat-messages" aria-live="polite">
          <?php if (empty($messages)): ?>
            <div class="chat-bubble bot">
              <span>Puli</span>
              <p>Hai Kak, aku Puli. Ruang ini aman untuk cerita tentang rindu, marah, kosong, atau hal yang sulit kamu jelaskan soal keluarga dan figur ayah. Kita pelan-pelan cari polanya, bukan cuma dipendam.</p>
            </div>
          <?php endif; ?>

          <?php foreach ($messages as $message): ?>
            <div class="chat-bubble <?php echo $message['role'] === 'user' ? 'user' : 'bot'; ?>">
              <span><?php echo $message['role'] === 'user' ? 'Kamu' : 'Puli'; ?></span>
              <p><?php echo nl2br(e($message['message'])); ?></p>
            </div>
          <?php endforeach; ?>
        </div>

        <form class="chat-form" id="chat-form">
          <div class="chat-prompts" aria-label="Contoh awal cerita">
            <button type="button" data-prompt="Hari ini aku merasa berat karena hal tentang ayah atau keluarga yang masih kepikiran...">Tentang ayah</button>
            <button type="button" data-prompt="Aku bingung ini sebenarnya sedih, marah, kecewa, atau rindu. Bisa bantu aku mengurainya?">Urai perasaan</button>
            <button type="button" data-prompt="Aku sering merasa harus kuat sendiri. Bisa bantu aku cari langkah kecil yang aman hari ini?">Langkah kecil</button>
          </div>

          <label for="chat-input">Tulis ceritamu</label>
          <textarea
            id="chat-input"
            rows="2"
            maxlength="1200"
            placeholder="Tulis pelan-pelan di sini, Kak..."
            required
          ></textarea>
          <div class="chat-form-footer">
            <span><strong id="chat-count">0</strong>/1200 &middot; Enter untuk kirim</span>
            <button type="submit" id="chat-submit">Kirim</button>
          </div>
        </form>
      </section>
    </section>
  </main>

  <nav class="bottom-nav" aria-label="Quick navigation">
    <a href="home.php"><span class="nav-icon nav-icon-home" aria-hidden="true"></span><span>Home</span></a>
    <a href="diary.php"><span class="nav-icon nav-icon-book" aria-hidden="true"></span><span>Journal</span></a>
    <a class="nav-plus" href="safe_space.php">+</a>
    <a href="library.php">&#10023;<span>Explore</span></a>
    <a class="is-active" href="safe_space.php">&#9825;<span>Safe</span></a>
  </nav>

  <script>
    const form = document.getElementById("chat-form");
    const input = document.getElementById("chat-input");
    const messages = document.getElementById("chat-messages");
    const submitButton = document.getElementById("chat-submit");
    const counter = document.getElementById("chat-count");
    const promptButtons = document.querySelectorAll("[data-prompt]");

    function syncInputState() {
      counter.textContent = input.value.length;
      input.style.height = "auto";
      input.style.height = `${Math.min(input.scrollHeight, 190)}px`;
      submitButton.disabled = input.value.trim().length === 0;
    }

    function addBubble(text, type) {
      const bubble = document.createElement("div");
      bubble.className = `chat-bubble ${type}`;

      const sender = document.createElement("span");
      sender.textContent = type === "user" ? "Kamu" : "Puli";

      const paragraph = document.createElement("p");
      paragraph.textContent = text;

      bubble.appendChild(sender);
      bubble.appendChild(paragraph);
      messages.appendChild(bubble);
      messages.scrollTop = messages.scrollHeight;

      return bubble;
    }

    form.addEventListener("submit", async (event) => {
      event.preventDefault();

      const text = input.value.trim();
      if (!text) return;

      addBubble(text, "user");
      input.value = "";
      syncInputState();
      submitButton.disabled = true;

      const loadingBubble = addBubble("Puli sedang mendengarkan...", "bot");
      loadingBubble.classList.add("is-typing");

      try {
        const response = await fetch("ayam.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json"
          },
          body: JSON.stringify({ message: text })
        });

        const data = await response.json();
        loadingBubble.classList.remove("is-typing");
        loadingBubble.querySelector("p").textContent =
          data.reply || "Aku di sini mendengarkanmu, Kak.";
      } catch (error) {
        loadingBubble.classList.remove("is-typing");
        loadingBubble.querySelector("p").textContent =
          "Maaf Kak, Puli sedang kesulitan merespons. Coba lagi sebentar ya.";
      } finally {
        syncInputState();
        input.focus();
      }
    });

    input.addEventListener("input", syncInputState);

    input.addEventListener("keydown", (event) => {
      if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        form.requestSubmit();
      }
    });

    promptButtons.forEach((button) => {
      button.addEventListener("click", () => {
        input.value = button.dataset.prompt;
        syncInputState();
        input.focus();
      });
    });

    syncInputState();
    messages.scrollTop = messages.scrollHeight;
  </script>
</body>
</html>
