<?php
require __DIR__ . '/config/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Welcome to TemanPulih - Your healing journey begins</title>
  <meta name="theme-color" content="#a78bfa" />
  <meta
    name="description"
    content="A gentle introduction to TemanPulih: journaling, moods, affirmations and a quiet companion named Puli."
  />
  <link rel="preload" as="image" href="mascott/1-clean.png" />
  <link rel="stylesheet" href="styles.css" />
  <link rel="icon" type="image/png" href="mascott/favicon.png" />
</head>
<body>
  <main class="onboarding-shell" aria-label="TemanPulih onboarding">
    <div class="onboarding-glow onboarding-glow-purple"></div>
    <div class="onboarding-glow onboarding-glow-rose"></div>

    <section class="onboarding-card" aria-live="polite">
      <img
        class="onboarding-mascot"
        src="mascott/1-clean.png"
        alt=""
        width="128"
        height="128"
      />

      <h1 id="onboarding-title">You don't have to carry it alone.</h1>
      <p id="onboarding-copy">
        Some days feel heavier than others. TemanPulih is a soft, quiet place to put down what your heart has been holding.
      </p>

      <div class="onboarding-dots" aria-label="Onboarding progress">
        <span class="is-active" aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
      </div>

      <div class="onboarding-actions">
        <a href="register.php" class="onboarding-skip">Skip</a>
        <button class="onboarding-continue" type="button">Continue</button>
      </div>
    </section>
  </main>

  <script>
    const slides = [
      {
        title: "You don't have to carry it alone.",
        copy: "Some days feel heavier than others. TemanPulih is a soft, quiet place to put down what your heart has been holding."
      },
      {
        title: "Notice what your heart is saying.",
        copy: "Track your mood, write a few honest lines, and let small reflections help you understand the day more gently."
      },
      {
        title: "Come back whenever you need warmth.",
        copy: "Puli is here with quiet affirmations, simple prompts, and a calm space that grows with your healing journey."
      }
    ];

    const title = document.getElementById("onboarding-title");
    const copy = document.getElementById("onboarding-copy");
    const button = document.querySelector(".onboarding-continue");
    const dots = Array.from(document.querySelectorAll(".onboarding-dots span"));
    let currentSlide = 0;

    button.addEventListener("click", () => {
      if (currentSlide === slides.length - 1) {
        window.location.href = "login.php";
        return;
      }

      currentSlide += 1;
      title.textContent = slides[currentSlide].title;
      copy.textContent = slides[currentSlide].copy;
      button.textContent = currentSlide === slides.length - 1 ? "Get started" : "Continue";

      dots.forEach((dot, index) => {
        dot.classList.toggle("is-active", index === currentSlide);
      });
    });
  </script>
</body>
</html>
