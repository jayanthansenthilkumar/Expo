/* =============================================
   SPARK'26 — Light Theme Landing Page JS
   Particles · Global Click SFX · Typing · Countdown
   ============================================= */

(function () {
  'use strict';

  /* ------------------------------------------
     1. PARTICLE CANVAS — warm subtle dots
  ------------------------------------------ */
  var canvas = document.getElementById('particleCanvas');
  if (canvas) {
    var ctx = canvas.getContext('2d');
    var particles = [];
    var mouse = { x: null, y: null };
    var PARTICLE_COUNT = 50;
    var CONNECT_DIST = 120;
    var MOUSE_DIST = 160;

    function resize() {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    document.addEventListener('mousemove', function (e) {
      mouse.x = e.clientX;
      mouse.y = e.clientY;
    });

    function Particle() {
      this.x = Math.random() * canvas.width;
      this.y = Math.random() * canvas.height;
      this.vx = (Math.random() - 0.5) * 0.35;
      this.vy = (Math.random() - 0.5) * 0.35;
      this.r = Math.random() * 1.8 + 0.8;
    }

    Particle.prototype.update = function () {
      this.x += this.vx;
      this.y += this.vy;
      if (this.x < 0 || this.x > canvas.width) this.vx *= -1;
      if (this.y < 0 || this.y > canvas.height) this.vy *= -1;
    };

    Particle.prototype.draw = function () {
      ctx.beginPath();
      ctx.arc(this.x, this.y, this.r, 0, Math.PI * 2);
      ctx.fillStyle = 'rgba(217, 119, 6, 0.18)';
      ctx.fill();
    };

    for (var i = 0; i < PARTICLE_COUNT; i++) {
      particles.push(new Particle());
    }

    function connectParticles() {
      for (var a = 0; a < particles.length; a++) {
        for (var b = a + 1; b < particles.length; b++) {
          var dx = particles[a].x - particles[b].x;
          var dy = particles[a].y - particles[b].y;
          var dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < CONNECT_DIST) {
            ctx.beginPath();
            ctx.strokeStyle = 'rgba(217, 119, 6,' + (0.06 * (1 - dist / CONNECT_DIST)) + ')';
            ctx.lineWidth = 0.5;
            ctx.moveTo(particles[a].x, particles[a].y);
            ctx.lineTo(particles[b].x, particles[b].y);
            ctx.stroke();
          }
        }
        // Mouse attraction lines
        if (mouse.x !== null) {
          var mdx = particles[a].x - mouse.x;
          var mdy = particles[a].y - mouse.y;
          var mdist = Math.sqrt(mdx * mdx + mdy * mdy);
          if (mdist < MOUSE_DIST) {
            ctx.beginPath();
            ctx.strokeStyle = 'rgba(217, 119, 6,' + (0.12 * (1 - mdist / MOUSE_DIST)) + ')';
            ctx.lineWidth = 0.6;
            ctx.moveTo(particles[a].x, particles[a].y);
            ctx.lineTo(mouse.x, mouse.y);
            ctx.stroke();
          }
        }
      }
    }

    function animateParticles() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      for (var i = 0; i < particles.length; i++) {
        particles[i].update();
        particles[i].draw();
      }
      connectParticles();
      requestAnimationFrame(animateParticles);
    }
    animateParticles();
  }

  /* ------------------------------------------
     2. WEB AUDIO — GLOBAL CLICK SOUND FX
     Plays a soft pop on every click anywhere.
     Buttons get a slightly different tone.
  ------------------------------------------ */
  var audioCtx = null;

  function getAudioCtx() {
    if (!audioCtx) {
      try {
        audioCtx = new (window.AudioContext || window.webkitAudioContext)();
      } catch (e) {
        return null;
      }
    }
    return audioCtx;
  }

  // Soft pop — for general page clicks
  function playSoftPop() {
    var ctx = getAudioCtx();
    if (!ctx) return;
    try {
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(600, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(200, ctx.currentTime + 0.08);
      gain.gain.setValueAtTime(0.30, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.1);
    } catch (e) {}
  }

  // Brighter click — for buttons & interactive elements
  function playButtonClick() {
    var ctx = getAudioCtx();
    if (!ctx) return;
    try {
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(800, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(400, ctx.currentTime + 0.1);
      gain.gain.setValueAtTime(0.50, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.12);
    } catch (e) {}
  }

  // Subtle hover tick — for nav links and cards
  function playHoverTick() {
    var ctx = getAudioCtx();
    if (!ctx) return;
    try {
      var osc = ctx.createOscillator();
      var gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(1000, ctx.currentTime);
      osc.frequency.exponentialRampToValueAtTime(1200, ctx.currentTime + 0.04);
      gain.gain.setValueAtTime(0.15, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.05);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.05);
    } catch (e) {}
  }

  // Create visual ripple at click position
  function createRipple(x, y) {
    var ripple = document.createElement('div');
    ripple.className = 'click-ripple';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    document.body.appendChild(ripple);
    setTimeout(function () {
      if (ripple.parentNode) ripple.parentNode.removeChild(ripple);
    }, 500);
  }

  // GLOBAL click handler — sound + ripple on every click
  document.addEventListener('click', function (e) {
    var tag = e.target.tagName.toLowerCase();
    var isButton = e.target.closest('a, button, .btn-tech-primary, .btn-tech-outline, .btn-cta, .sfx-btn, .track-panel');

    if (isButton) {
      playButtonClick();
    } else {
      playSoftPop();
    }

    createRipple(e.clientX, e.clientY);
  });

  // Hover sounds on interactive elements
  var hoverTargets = document.querySelectorAll(
    '.tech-nav-link, .btn-tech-primary, .btn-tech-outline, .btn-cta, ' +
    '.tech-card, .tech-schedule-card, .tech-sponsor, .tech-faq, .tech-stat, .track-panel'
  );
  hoverTargets.forEach(function (el) {
    el.addEventListener('mouseenter', playHoverTick);
  });

  /* ------------------------------------------
     3. TYPING TEXT ANIMATION
  ------------------------------------------ */
  document.querySelectorAll('.typing-text').forEach(function (el) {
    var text = el.getAttribute('data-text');
    if (!text) return;
    el.textContent = '';
    var idx = 0;

    function typeChar() {
      if (idx < text.length) {
        el.textContent += text.charAt(idx);
        idx++;
        setTimeout(typeChar, 55 + Math.random() * 35);
      }
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          setTimeout(typeChar, 400);
          observer.unobserve(el);
        }
      });
    }, { threshold: 0.5 });
    observer.observe(el);
  });

  /* ------------------------------------------
     4. COUNTDOWN TIMER
  ------------------------------------------ */
  function updateCountdown() {
    var eventDate = window.SPARK_EVENT_DATE;
    if (!eventDate) return;

    var target = new Date(eventDate).getTime();
    var now = new Date().getTime();
    var diff = target - now;
    if (diff < 0) diff = 0;

    var days = Math.floor(diff / (1000 * 60 * 60 * 24));
    var hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    var secs = Math.floor((diff % (1000 * 60)) / 1000);

    var dEl = document.getElementById('cd-days');
    var hEl = document.getElementById('cd-hours');
    var mEl = document.getElementById('cd-mins');
    var sEl = document.getElementById('cd-secs');

    if (dEl) dEl.textContent = String(days).padStart(2, '0');
    if (hEl) hEl.textContent = String(hours).padStart(2, '0');
    if (mEl) mEl.textContent = String(mins).padStart(2, '0');
    if (sEl) sEl.textContent = String(secs).padStart(2, '0');
  }

  updateCountdown();
  setInterval(updateCountdown, 1000);

  /* ------------------------------------------
     5. GLITCH TEXT — just set data-text attr
  ------------------------------------------ */
  document.querySelectorAll('.glitch-text').forEach(function (el) {
    if (!el.getAttribute('data-text')) {
      el.setAttribute('data-text', el.textContent);
    }
  });

  /* ------------------------------------------
     6. SCROLL-TRIGGERED REVEALS
  ------------------------------------------ */
  var revealObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

  document.querySelectorAll(
    '.feature-card, .schedule-card, .sponsor-card, .faq-item, .stat-item'
  ).forEach(function (el) {
    revealObserver.observe(el);
  });

  /* ------------------------------------------
     7. BAR-FILL ANIMATION ON SCROLL
  ------------------------------------------ */
  var barObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.style.animationPlayState = 'running';
        barObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  document.querySelectorAll('.bar-fill').forEach(function (bar) {
    bar.style.animationPlayState = 'paused';
    barObserver.observe(bar);
  });

  /* ------------------------------------------
     8. SMOOTH SCROLL
  ------------------------------------------ */
  document.querySelectorAll('a[href^="#"]').forEach(function (link) {
    link.addEventListener('click', function (e) {
      var id = this.getAttribute('href');
      if (id && id.length > 1) {
        var target = document.querySelector(id);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }
    });
  });

  /* ------------------------------------------
     9. NAVBAR SCROLL EFFECT
  ------------------------------------------ */
  var navbar = document.querySelector('.tech-navbar');
  if (navbar) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 60) {
        navbar.style.background = 'rgba(250, 249, 246, 0.97)';
        navbar.style.boxShadow = '0 1px 8px rgba(28, 25, 23, 0.06)';
      } else {
        navbar.style.background = 'rgba(250, 249, 246, 0.92)';
        navbar.style.boxShadow = 'none';
      }
    });
  }

  /* ------------------------------------------
     10. MOBILE MENU TOGGLE
  ------------------------------------------ */
  var mobileToggle = document.querySelector('.mobile-menu-toggle');
  var mobileOverlay = document.querySelector('.mobile-menu-overlay');
  if (mobileToggle && mobileOverlay) {
    mobileToggle.addEventListener('click', function () {
      mobileOverlay.classList.toggle('active');
      var icon = mobileToggle.querySelector('i');
      if (icon) {
        icon.className = mobileOverlay.classList.contains('active')
          ? 'ri-close-line'
          : 'ri-menu-3-line';
      }
    });

    mobileOverlay.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        mobileOverlay.classList.remove('active');
        var icon = mobileToggle.querySelector('i');
        if (icon) icon.className = 'ri-menu-3-line';
      });
    });
  }

})();
