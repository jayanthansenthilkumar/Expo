document.addEventListener("DOMContentLoaded", () => {
  // --- Smooth Scrolling ---
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      document.querySelector(this.getAttribute("href")).scrollIntoView({
        behavior: "smooth",
      });
    });
  });

  // --- Simple Scroll Fade In ---
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("visible");
          entry.target.style.opacity = 1;
          entry.target.style.transform = "translateY(0)";
        }
      });
    },
    { threshold: 0.1 },
  );

  const fadeElements = document.querySelectorAll(
    ".feature-card, .track-card, .t-item, .cta-box",
  );

  fadeElements.forEach((el) => {
    el.style.opacity = 0;
    el.style.transform = "translateY(20px)";
    el.style.transition = "all 0.6s ease-out";
    observer.observe(el);
  });

  // --- Stats Counter Animation ---
  const statsSection = document.querySelector(".stats-strip");
  let counted = false;

  if (statsSection) {
    const statsObserver = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && !counted) {
        counted = true;
        animateValue("stat-stud", 0, 500, 2000);
        animateValue("stat-proj", 0, 120, 2000);
        animateValue("stat-prize", 0, 50, 2000); // 50k
      }
    });
    statsObserver.observe(statsSection);
  }

  function animateValue(id, start, end, duration) {
    const obj = document.getElementById(id);
    if (!obj) return;
    let startTimestamp = null;
    const step = (timestamp) => {
      if (!startTimestamp) startTimestamp = timestamp;
      const progress = Math.min((timestamp - startTimestamp) / duration, 1);
      obj.innerHTML =
        Math.floor(progress * (end - start) + start) +
        (id === "stat-prize" ? "k+" : "+");
      if (progress < 1) {
        window.requestAnimationFrame(step);
      }
    };
    window.requestAnimationFrame(step);
  }

  // --- Tracks Accordion ---
  const panels = document.querySelectorAll(".track-panel");

  if (panels.length > 0) {
    panels.forEach((panel) => {
      panel.addEventListener("click", () => {
        removeActiveClasses();
        panel.classList.add("active");
      });
    });

    function removeActiveClasses() {
      panels.forEach((panel) => {
        panel.classList.remove("active");
      });
    }
  }
});
