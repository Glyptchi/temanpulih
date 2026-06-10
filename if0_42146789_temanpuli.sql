-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 10 Jun 2026 pada 12.26
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `temanpuli`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `if0_42146789_temanpuli`
--

CREATE TABLE `affirmations` (
  `id` int(10) UNSIGNED NOT NULL,
  `content` text NOT NULL,
  `note` varchar(160) NOT NULL DEFAULT 'A gentle whisper, just for today.',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `affirmations`
--

INSERT INTO `affirmations` (`id`, `content`, `note`, `created_at`) VALUES
(1, 'You are allowed to rest. Healing does not require you to rush.', 'A gentle whisper, just for today.', '2026-05-28 06:50:56'),
(2, 'Your feelings are real, and they deserve kindness before judgment.', 'A soft reminder for your heart.', '2026-05-28 06:50:56'),
(3, 'You are not behind. You are growing at the pace your soul can hold.', 'Take today one breath at a time.', '2026-05-28 06:50:56'),
(4, 'Even small steps count. You are still moving, even when it feels quiet.', 'A little light for the path.', '2026-05-28 06:50:56'),
(5, 'You do not have to earn care. You are worthy of tenderness now.', 'Keep this close today.', '2026-05-28 06:50:56'),
(6, 'The heavy moment will pass. Breathe slowly and stay with yourself.', 'A calm anchor for right now.', '2026-05-28 06:50:56'),
(7, 'You are more than what happened to you. There is still softness ahead.', 'A hopeful note from Puli.', '2026-05-28 06:50:56'),
(8, 'It is okay to ask for help. You were never meant to carry everything alone.', 'A reminder that support is allowed.', '2026-05-28 06:50:56'),
(9, 'Your worth is not defined by external presence. You are whole, enough, and growing.', 'A gentle whisper, just for today.', '2026-05-28 06:50:56'),
(10, 'Today, you can begin again gently, without punishing yourself for yesterday.', 'A fresh breath for today.', '2026-05-28 06:50:56');

-- --------------------------------------------------------

--
-- Struktur dari tabel `articles`
--

CREATE TABLE `articles` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `category` varchar(80) NOT NULL,
  `excerpt` text NOT NULL,
  `content` longtext NOT NULL,
  `image_url` text DEFAULT NULL,
  `read_minutes` int(10) UNSIGNED NOT NULL DEFAULT 4,
  `read_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('draft','published') NOT NULL DEFAULT 'published',
  `source_type` enum('internal','external') NOT NULL DEFAULT 'internal',
  `external_url` text DEFAULT NULL,
  `source_name` varchar(160) DEFAULT NULL,
  `embed_url` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `prompt_id` int(10) UNSIGNED NOT NULL,
  `entry_date` date NOT NULL,
  `content` text DEFAULT NULL,
  `puli_tip` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `journal_entries`
--

INSERT INTO `journal_entries` (`id`, `user_id`, `prompt_id`, `entry_date`, `content`, `puli_tip`, `created_at`, `updated_at`) VALUES
(4, 10, 23, '2026-05-31', NULL, NULL, '2026-05-31 10:01:26', '2026-05-31 10:01:26'),
(8, 10, 35, '2026-06-10', NULL, NULL, '2026-06-10 09:15:34', '2026-06-10 09:15:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `journal_prompts`
--

CREATE TABLE `journal_prompts` (
  `id` int(10) UNSIGNED NOT NULL,
  `question` text NOT NULL,
  `phase` varchar(100) NOT NULL DEFAULT 'Fase Kesadaran',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `journal_prompts`
--

INSERT INTO `journal_prompts` (`id`, `question`, `phase`, `created_at`) VALUES
(1, 'Apa perasaan yang paling sering muncul saat kamu memikirkan figur ayah? Coba sebutkan tiga emosi dan ceritakan pelan-pelan.', 'Fase Kesadaran', '2026-05-28 07:28:17'),
(2, 'Hari ini, bagian dirimu yang paling ingin didengar sedang berkata apa?', 'Fase Kesadaran', '2026-05-28 07:28:17'),
(3, 'Kapan kamu merasa harus terlihat kuat, padahal sebenarnya ingin dipeluk atau ditemani?', 'Fase Kesadaran', '2026-05-28 07:28:17'),
(4, 'Apa hal kecil hari ini yang membuatmu merasa aman, meski hanya sebentar?', 'Fase Kesadaran', '2026-05-28 07:28:17'),
(5, 'Kalau rasa rindumu bisa berbicara tanpa takut dihakimi, apa yang akan ia ucapkan?', 'Fase Kesadaran', '2026-05-28 07:28:17'),
(6, 'Bagaimana hubunganmu dengan kepercayaan: pada orang lain, pada cinta, dan pada dirimu sendiri?', 'Fase Kesadaran', '2026-05-28 07:28:17'),
(7, 'Apa kalimat yang dulu ingin sekali kamu dengar dari ayah atau sosok pelindung?', 'Fase Kesadaran', '2026-05-28 07:28:17'),
(8, 'Saat seseorang menjauh, cerita apa yang otomatis muncul di kepalamu tentang dirimu?', 'Fase Kesadaran', '2026-05-28 07:28:17'),
(9, 'Apa bentuk perhatian yang paling membuatmu merasa dicintai dan tidak sendirian?', 'Fase Kesadaran', '2026-05-28 07:28:17'),
(10, 'Apa yang biasanya kamu lakukan untuk menenangkan diri saat merasa ditinggalkan?', 'Fase Kesadaran', '2026-05-28 07:28:17'),
(11, 'Bagian mana dari dirimu yang paling lelah mengejar validasi hari ini?', 'Fase Pemulihan', '2026-05-28 07:28:17'),
(12, 'Apa batasan kecil yang ingin kamu jaga minggu ini agar hatimu tidak terus terluka?', 'Fase Pemulihan', '2026-05-28 07:28:17'),
(13, 'Ceritakan satu momen ketika kamu berhasil bertahan, walau saat itu terasa berat sekali.', 'Fase Pemulihan', '2026-05-28 07:28:17'),
(14, 'Apa yang ingin kamu maafkan dari dirimu, bukan karena kamu salah, tapi karena kamu sudah terlalu lama keras pada diri sendiri?', 'Fase Pemulihan', '2026-05-28 07:28:17'),
(15, 'Jika kamu bisa memeluk versi kecil dirimu, apa yang ingin kamu bisikkan padanya?', 'Fase Pemulihan', '2026-05-28 07:28:17'),
(16, 'Pola hubungan apa yang ingin kamu pahami, bukan untuk menyalahkan diri, tapi untuk mulai memilih dengan sadar?', 'Fase Pemulihan', '2026-05-28 07:28:17'),
(17, 'Apa tanda bahwa kamu sedang merasa tidak aman secara emosional?', 'Fase Pemulihan', '2026-05-28 07:28:17'),
(18, 'Apa satu kebutuhan emosionalmu yang sering kamu kecilkan agar tidak dianggap merepotkan?', 'Fase Pemulihan', '2026-05-28 07:28:17'),
(19, 'Bagaimana kamu bisa memberi diri sendiri rasa aman yang dulu tidak selalu kamu dapatkan?', 'Fase Pemulihan', '2026-05-28 07:28:17'),
(20, 'Apa arti pulih bagimu hari ini, dalam bentuk yang paling sederhana?', 'Fase Pemulihan', '2026-05-28 07:28:17'),
(21, 'Tulis surat pendek untuk dirimu yang sedang takut ditinggalkan.', 'Fase Penguatan', '2026-05-28 07:28:17'),
(22, 'Apa hal yang ingin kamu berhenti cari dari orang yang tidak mampu memberikannya?', 'Fase Penguatan', '2026-05-28 07:28:17'),
(23, 'Apa kualitas dirimu yang tetap tumbuh meski kamu pernah merasa kurang didukung?', 'Fase Penguatan', '2026-05-28 07:28:17'),
(24, 'Kapan terakhir kali kamu memilih dirimu sendiri? Apa rasanya?', 'Fase Penguatan', '2026-05-28 07:28:17'),
(25, 'Apa bentuk cinta yang sehat menurutmu sekarang?', 'Fase Penguatan', '2026-05-28 07:28:17'),
(26, 'Apa yang ingin kamu bangun di hidupmu tanpa harus menunggu seseorang menyelamatkanmu?', 'Fase Penguatan', '2026-05-28 07:28:17'),
(27, 'Apa tiga hal yang membuktikan bahwa kamu layak dicintai tanpa harus sempurna?', 'Fase Penguatan', '2026-05-28 07:28:17'),
(28, 'Jika hari ini kamu hanya punya energi sedikit, hal lembut apa yang tetap bisa kamu lakukan untuk diri sendiri?', 'Fase Penguatan', '2026-05-28 07:28:17'),
(29, 'Apa janji kecil yang ingin kamu buat untuk menjaga hatimu mulai hari ini?', 'Fase Penguatan', '2026-05-28 07:28:17'),
(30, 'Bayangkan dirimu 14 hari dari sekarang. Apa yang ingin ia syukuri dari keberanianmu menulis hari ini?', 'Fase Penguatan', '2026-05-28 07:28:17'),
(31, 'Apa yang paling sering kamu pendam?', 'Emotional Awareness', '2026-06-02 07:53:55'),
(32, 'Kapan terakhir kamu merasa dipahami?', 'Emotional Awareness', '2026-06-02 07:53:55'),
(33, 'Hal apa yang paling ingin kamu ungkapkan?', 'Safe Expression', '2026-06-10 08:28:26'),
(34, 'Apa yang paling menyakitkan untukmu?', 'Safe Expression', '2026-06-10 08:28:26'),
(35, 'Apa yang paling kamu rasakan hari ini?', 'Mood Check', '2026-06-10 09:15:34');

-- --------------------------------------------------------

--
-- Struktur dari tabel `moods`
--

CREATE TABLE `moods` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `mood` varchar(40) NOT NULL,
  `mood_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `moods`
--

INSERT INTO `moods` (`id`, `user_id`, `mood`, `mood_date`, `created_at`) VALUES
(1, 10, 'Lonely', '2026-05-31', '2026-05-31 10:14:27');

-- --------------------------------------------------------

--
-- Struktur dari tabel `safe_space_messages`
--

CREATE TABLE `safe_space_messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `role` enum('user','model') NOT NULL DEFAULT 'user',
  `sender` enum('user','puli') NOT NULL,
  `message` text NOT NULL,
  `is_crisis` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `saved_affirmations`
--

CREATE TABLE `saved_affirmations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `affirmation_content` text NOT NULL,
  `affirmation_note` varchar(160) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive','blocked') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `status`, `created_at`, `updated_at`) VALUES
(10, 'Admin Pulih', 'admin@pulih.com', '$2y$10$CR.tKN7ebPNdF01if1hsgeGvJ2jTK4xnPtu0NlP8tGA98nMlh7phC', 'admin', 'active', '2026-05-31 09:55:21', '2026-05-31 09:55:21');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `affirmations`
--
ALTER TABLE `affirmations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_affirmations_content` (`content`(255));

--
-- Indeks untuk tabel `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_articles_slug` (`slug`),
  ADD KEY `idx_articles_category_status` (`category`,`status`),
  ADD KEY `fk_articles_user` (`created_by`);

--
-- Indeks untuk tabel `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_journal_entries_user_date` (`user_id`,`entry_date`),
  ADD KEY `idx_journal_entries_user_prompt` (`user_id`,`prompt_id`),
  ADD KEY `fk_journal_entries_prompt` (`prompt_id`);

--
-- Indeks untuk tabel `journal_prompts`
--
ALTER TABLE `journal_prompts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_journal_prompts_question` (`question`(255));

--
-- Indeks untuk tabel `moods`
--
ALTER TABLE `moods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_moods_user_date` (`user_id`,`mood_date`),
  ADD KEY `idx_moods_user` (`user_id`);

--
-- Indeks untuk tabel `safe_space_messages`
--
ALTER TABLE `safe_space_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_safe_space_user_created` (`user_id`,`created_at`);

--
-- Indeks untuk tabel `saved_affirmations`
--
ALTER TABLE `saved_affirmations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_saved_affirmations_user` (`user_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_status` (`status`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `affirmations`
--
ALTER TABLE `affirmations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `journal_prompts`
--
ALTER TABLE `journal_prompts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT untuk tabel `moods`
--
ALTER TABLE `moods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `safe_space_messages`
--
ALTER TABLE `safe_space_messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT untuk tabel `saved_affirmations`
--
ALTER TABLE `saved_affirmations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `fk_articles_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD CONSTRAINT `fk_journal_entries_prompt` FOREIGN KEY (`prompt_id`) REFERENCES `journal_prompts` (`id`),
  ADD CONSTRAINT `fk_journal_entries_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `moods`
--
ALTER TABLE `moods`
  ADD CONSTRAINT `fk_moods_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `safe_space_messages`
--
ALTER TABLE `safe_space_messages`
  ADD CONSTRAINT `fk_safe_space_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `saved_affirmations`
--
ALTER TABLE `saved_affirmations`
  ADD CONSTRAINT `fk_saved_affirmations_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
