document.addEventListener("DOMContentLoaded", () => {
  // --- Sidebar Toggle for Mobile ---
  window.toggleSidebar = function() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
      sidebar.classList.toggle('open');
    }
  };

  // --- Mobile Nav Menu Toggle (Landing Page) ---
  window.toggleMobileMenu = function() {
    const navMenu = document.getElementById('navMenu');
    const menuIcon = document.getElementById('menuIcon');
    if (navMenu) {
      navMenu.classList.toggle('mobile-open');
      if (menuIcon) {
        if (navMenu.classList.contains('mobile-open')) {
          menuIcon.className = 'ri-close-line';
        } else {
          menuIcon.className = 'ri-menu-line';
        }
      }
    }
  };

  // Close sidebar and user dropdown when clicking outside
  document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    const mobileToggle = document.querySelector('.mobile-toggle');
    const userDropdown = document.getElementById('userDropdown');
    const userProfile = document.querySelector('.user-profile');

    // Sidebar Close Logic
    if (sidebar && sidebar.classList.contains('open')) {
      if (!sidebar.contains(e.target) && (!mobileToggle || !mobileToggle.contains(e.target))) {
        sidebar.classList.remove('open');
      }
    }

    // User Dropdown Close Logic
    if (userDropdown && userDropdown.classList.contains('show')) {
      if (!userProfile || !userProfile.contains(e.target)) {
        userDropdown.classList.remove('show');
      }
    }

    // Mobile nav menu close on link click
    const navMenu = document.getElementById('navMenu');
    const menuIcon = document.getElementById('menuIcon');
    if (navMenu && navMenu.classList.contains('mobile-open')) {
      if (e.target.classList.contains('nav-link') || e.target.classList.contains('btn-primary')) {
        navMenu.classList.remove('mobile-open');
        if (menuIcon) menuIcon.className = 'ri-menu-line';
      }
    }
  });

  // --- User Dropdown Toggle ---
  window.toggleUserDropdown = function(event) {
    // Only stop propagation for the toggle logic to prevent immediate document click
    event.stopPropagation();
    
    const dropdown = document.getElementById('userDropdown');
    if (!dropdown) return;

    // If clicking inside the dropdown content (links, header etc), do not toggle
    if (dropdown.contains(event.target)) {
        return;
    }
    
    dropdown.classList.toggle('show');
  };

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
        // Animate all stat items dynamically using data-target attributes
        document.querySelectorAll(".stats-strip [data-target]").forEach((el) => {
          const target = parseInt(el.getAttribute("data-target")) || 0;
          animateValue(el.id, 0, target, 2000);
        });
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
      const current = Math.floor(progress * (end - start) + start);
      obj.innerHTML = current + (end > 0 ? "+" : "");
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
