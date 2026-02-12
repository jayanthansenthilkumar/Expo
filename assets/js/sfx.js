/* =============================================
   SPARK'26 — Global Sound Effects & Click Ripple
   Works on every page (dashboards, auth, landing)
   Volume: 500% · Scroll · Nav · Loading · Table · Typing
   ============================================= */

(function () {
  'use strict';

  var audioCtx = null;
  // Master volume multiplier — 500% of original base
  var VOL = 5.0;

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

  // Helper: clamp gain to [0, 1] for safety
  function v(base) {
    return Math.min(base * VOL, 1.0);
  }

  /* ==========================================
     SOUND LIBRARY — 500% volume via v()
  ========================================== */

  // 1. Soft pop — general page clicks
  function playSoftPop() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(600, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(200, ctx.currentTime + 0.08);
      g.gain.setValueAtTime(v(0.06), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.1);
    } catch (e) {}
  }

  // 2. Button click — brighter tone
  function playButtonClick() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(800, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(400, ctx.currentTime + 0.1);
      g.gain.setValueAtTime(v(0.10), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.12);
    } catch (e) {}
  }

  // 3. Hover tick — nav links, cards, sidebar
  function playHoverTick() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(1000, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(1200, ctx.currentTime + 0.04);
      g.gain.setValueAtTime(v(0.03), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.05);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.05);
    } catch (e) {}
  }

  // 4. Table row hover — gentle sweep
  function playTableRowHover() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'triangle';
      o.frequency.setValueAtTime(500, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(700, ctx.currentTime + 0.06);
      g.gain.setValueAtTime(v(0.04), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.08);
    } catch (e) {}
  }

  // 5. Scroll whoosh — wind burst
  function playScrollWhoosh(direction) {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var bufferSize = ctx.sampleRate * 0.1;
      var buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
      var data = buffer.getChannelData(0);
      for (var i = 0; i < bufferSize; i++) data[i] = (Math.random() * 2 - 1) * 0.4;
      var noise = ctx.createBufferSource();
      noise.buffer = buffer;
      var filter = ctx.createBiquadFilter();
      filter.type = 'bandpass';
      filter.frequency.setValueAtTime(direction === 'down' ? 600 : 900, ctx.currentTime);
      filter.frequency.exponentialRampToValueAtTime(direction === 'down' ? 250 : 1400, ctx.currentTime + 0.1);
      filter.Q.value = 2.5;
      var g = ctx.createGain();
      g.gain.setValueAtTime(v(0.08), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
      noise.connect(filter); filter.connect(g); g.connect(ctx.destination);
      noise.start(ctx.currentTime); noise.stop(ctx.currentTime + 0.12);
    } catch (e) {}
  }

  // 6. Form input focus — warm chime
  function playInputFocus() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(880, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(660, ctx.currentTime + 0.12);
      g.gain.setValueAtTime(v(0.06), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.15);
    } catch (e) {}
  }

  // 7. Toggle/switch — chirp up or down
  function playToggle(on) {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(on ? 400 : 900, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(on ? 900 : 400, ctx.currentTime + 0.1);
      g.gain.setValueAtTime(v(0.07), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.12);
    } catch (e) {}
  }

  // 8. Success ding — two ascending notes (C5 → E5)
  function playSuccessDing() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o1 = ctx.createOscillator(), g1 = ctx.createGain();
      o1.connect(g1); g1.connect(ctx.destination);
      o1.type = 'sine';
      o1.frequency.setValueAtTime(523, ctx.currentTime);
      g1.gain.setValueAtTime(v(0.08), ctx.currentTime);
      g1.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
      o1.start(ctx.currentTime); o1.stop(ctx.currentTime + 0.15);
      var o2 = ctx.createOscillator(), g2 = ctx.createGain();
      o2.connect(g2); g2.connect(ctx.destination);
      o2.type = 'sine';
      o2.frequency.setValueAtTime(659, ctx.currentTime + 0.12);
      g2.gain.setValueAtTime(0.001, ctx.currentTime);
      g2.gain.setValueAtTime(v(0.08), ctx.currentTime + 0.12);
      g2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
      o2.start(ctx.currentTime + 0.12); o2.stop(ctx.currentTime + 0.3);
    } catch (e) {}
  }

  // 9. Error buzz — low rumble
  function playErrorBuzz() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sawtooth';
      o.frequency.setValueAtTime(150, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(80, ctx.currentTime + 0.2);
      g.gain.setValueAtTime(v(0.06), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.25);
    } catch (e) {}
  }

  // 10. Notification ping — bright bell
  function playNotificationPing() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(1320, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15);
      g.gain.setValueAtTime(v(0.07), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.2);
    } catch (e) {}
  }

  // 11. Sidebar slide — expand/collapse whoosh
  function playSidebarSlide(opening) {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'triangle';
      o.frequency.setValueAtTime(opening ? 200 : 500, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(opening ? 500 : 200, ctx.currentTime + 0.15);
      g.gain.setValueAtTime(v(0.06), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.18);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.18);
    } catch (e) {}
  }

  // 12. Keystroke tick — typing
  function playKeystroke() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'square';
      o.frequency.setValueAtTime(800 + Math.random() * 400, ctx.currentTime);
      g.gain.setValueAtTime(v(0.015), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.03);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.03);
    } catch (e) {}
  }

  // 12b. Erase tick — descending tone for delete
  function playEraseTick() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'triangle';
      o.frequency.setValueAtTime(600 + Math.random() * 200, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(250, ctx.currentTime + 0.05);
      g.gain.setValueAtTime(v(0.02), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.06);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.06);
    } catch (e) {}
  }

  // 12c. Clear sweep — long descend for clearing an entire field
  function playClearSweep() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(700, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(150, ctx.currentTime + 0.15);
      g.gain.setValueAtTime(v(0.05), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.18);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.18);
    } catch (e) {}
  }

  // 13. Modal/Popup open — ascending chime
  function playModalOpen() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(350, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(700, ctx.currentTime + 0.15);
      g.gain.setValueAtTime(v(0.06), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.2);
    } catch (e) {}
  }

  // 14. Dropdown blip
  function playDropdown(opening) {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(opening ? 500 : 700, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(opening ? 700 : 500, ctx.currentTime + 0.06);
      g.gain.setValueAtTime(v(0.05), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.08);
    } catch (e) {}
  }

  // 15. PAGE LOAD — warm welcome chime (3 ascending notes: C5 → E5 → G5)
  function playPageLoadChime() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var notes = [523, 659, 784]; // C5, E5, G5
      notes.forEach(function (freq, i) {
        var o = ctx.createOscillator(), g = ctx.createGain();
        o.connect(g); g.connect(ctx.destination);
        o.type = 'sine';
        var t = ctx.currentTime + i * 0.1;
        o.frequency.setValueAtTime(freq, t);
        g.gain.setValueAtTime(0.001, ctx.currentTime);
        g.gain.setValueAtTime(v(0.07), t);
        g.gain.exponentialRampToValueAtTime(0.001, t + 0.2);
        o.start(t); o.stop(t + 0.2);
      });
    } catch (e) {}
  }

  // 16. NAVIGATION — departing swoosh (plays when leaving page)
  function playNavigationSwoosh() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      // Descending tone
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(800, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(200, ctx.currentTime + 0.15);
      g.gain.setValueAtTime(v(0.08), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.18);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.18);
      // Noise tail
      var bufSize = ctx.sampleRate * 0.12;
      var buf = ctx.createBuffer(1, bufSize, ctx.sampleRate);
      var d = buf.getChannelData(0);
      for (var i = 0; i < bufSize; i++) d[i] = (Math.random() * 2 - 1) * 0.25;
      var n = ctx.createBufferSource(); n.buffer = buf;
      var f = ctx.createBiquadFilter();
      f.type = 'highpass'; f.frequency.value = 500;
      var gn = ctx.createGain();
      gn.gain.setValueAtTime(v(0.04), ctx.currentTime);
      gn.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
      n.connect(f); f.connect(gn); gn.connect(ctx.destination);
      n.start(ctx.currentTime); n.stop(ctx.currentTime + 0.15);
    } catch (e) {}
  }

  // 17. SCROLL EDGE — thud when reaching top or bottom of page
  function playScrollEdge() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(120, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(60, ctx.currentTime + 0.1);
      g.gain.setValueAtTime(v(0.08), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.15);
    } catch (e) {}
  }

  // 18. SECTION ENTER — subtle ding when scrolling into a new section
  function playSectionEnter() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(660, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.08);
      g.gain.setValueAtTime(v(0.04), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.12);
    } catch (e) {}
  }

  // 19. FORM SUBMIT — confirmation whoosh
  function playFormSubmit() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(400, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(1000, ctx.currentTime + 0.15);
      g.gain.setValueAtTime(v(0.08), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.2);
      // Second harmonic
      var o2 = ctx.createOscillator(), g2 = ctx.createGain();
      o2.connect(g2); g2.connect(ctx.destination);
      o2.type = 'sine';
      o2.frequency.setValueAtTime(600, ctx.currentTime + 0.05);
      g2.gain.setValueAtTime(0.001, ctx.currentTime);
      g2.gain.setValueAtTime(v(0.05), ctx.currentTime + 0.05);
      g2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.22);
      o2.start(ctx.currentTime + 0.05); o2.stop(ctx.currentTime + 0.22);
    } catch (e) {}
  }

  // 20. WARNING tone — for caution/attention
  function playWarningTone() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      // Two quick pulses
      for (var p = 0; p < 2; p++) {
        var o = ctx.createOscillator(), g = ctx.createGain();
        o.connect(g); g.connect(ctx.destination);
        o.type = 'triangle';
        var t = ctx.currentTime + p * 0.12;
        o.frequency.setValueAtTime(440, t);
        g.gain.setValueAtTime(0.001, ctx.currentTime);
        g.gain.setValueAtTime(v(0.07), t);
        g.gain.exponentialRampToValueAtTime(0.001, t + 0.08);
        o.start(t); o.stop(t + 0.08);
      }
    } catch (e) {}
  }

  // 21. COPY — clipboard copy sound
  function playCopySound() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(1200, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(800, ctx.currentTime + 0.06);
      g.gain.setValueAtTime(v(0.05), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.08);
      // Second blip
      var o2 = ctx.createOscillator(), g2 = ctx.createGain();
      o2.connect(g2); g2.connect(ctx.destination);
      o2.type = 'sine';
      o2.frequency.setValueAtTime(1400, ctx.currentTime + 0.06);
      g2.gain.setValueAtTime(0.001, ctx.currentTime);
      g2.gain.setValueAtTime(v(0.04), ctx.currentTime + 0.06);
      g2.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
      o2.start(ctx.currentTime + 0.06); o2.stop(ctx.currentTime + 0.12);
    } catch (e) {}
  }

  // 22. PASTE — clipboard paste sound
  function playPasteSound() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(800, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(1200, ctx.currentTime + 0.06);
      g.gain.setValueAtTime(v(0.05), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.1);
    } catch (e) {}
  }

  // 23. ESCAPE — dismissal tone
  function playEscapeSound() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(600, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(300, ctx.currentTime + 0.1);
      g.gain.setValueAtTime(v(0.06), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.12);
    } catch (e) {}
  }

  // 24. ENTER — confirmation press
  function playEnterKey() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(500, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(750, ctx.currentTime + 0.06);
      g.gain.setValueAtTime(v(0.06), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.1);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.1);
    } catch (e) {}
  }

  // 25. PAGE VISIBILITY — return to tab ping
  function playReturnToTab() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(440, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(660, ctx.currentTime + 0.1);
      g.gain.setValueAtTime(v(0.05), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.15);
    } catch (e) {}
  }

  // 26. LEAVE TAB — descending tone
  function playLeaveTab() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(660, ctx.currentTime);
      o.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + 0.1);
      g.gain.setValueAtTime(v(0.04), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.12);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.12);
    } catch (e) {}
  }

  // 27. TABLE CELL micro-tick
  function playCellTick() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var o = ctx.createOscillator(), g = ctx.createGain();
      o.connect(g); g.connect(ctx.destination);
      o.type = 'sine';
      o.frequency.setValueAtTime(1100 + Math.random() * 200, ctx.currentTime);
      g.gain.setValueAtTime(v(0.012), ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.025);
      o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.025);
    } catch (e) {}
  }

  // 28. FILE UPLOAD — ascending sparkle
  function playFileUpload() {
    var ctx = getAudioCtx(); if (!ctx) return;
    try {
      var notes = [600, 800, 1100];
      notes.forEach(function (freq, i) {
        var o = ctx.createOscillator(), g = ctx.createGain();
        o.connect(g); g.connect(ctx.destination);
        o.type = 'sine';
        var t = ctx.currentTime + i * 0.07;
        o.frequency.setValueAtTime(freq, t);
        g.gain.setValueAtTime(0.001, ctx.currentTime);
        g.gain.setValueAtTime(v(0.04), t);
        g.gain.exponentialRampToValueAtTime(0.001, t + 0.1);
        o.start(t); o.stop(t + 0.1);
      });
    } catch (e) {}
  }

  // 29. LOADING TICK — rhythmic loading indicator
  var loadingInterval = null;
  function startLoadingSound() {
    if (loadingInterval) return;
    var tick = 0;
    loadingInterval = setInterval(function () {
      var ctx = getAudioCtx(); if (!ctx) return;
      try {
        var o = ctx.createOscillator(), g = ctx.createGain();
        o.connect(g); g.connect(ctx.destination);
        o.type = 'sine';
        var freq = tick % 2 === 0 ? 500 : 600;
        o.frequency.setValueAtTime(freq, ctx.currentTime);
        g.gain.setValueAtTime(v(0.025), ctx.currentTime);
        g.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.05);
        o.start(ctx.currentTime); o.stop(ctx.currentTime + 0.05);
        tick++;
      } catch (e) {}
    }, 200);
  }
  function stopLoadingSound() {
    if (loadingInterval) { clearInterval(loadingInterval); loadingInterval = null; }
  }

  /* ==========================================
     VISUAL RIPPLE
  ========================================== */
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

  /* ==========================================
     EVENT LISTENERS
  ========================================== */

  // ==== PAGE LOAD — welcome chime when DOM is ready ====
  if (document.readyState === 'loading') {
    startLoadingSound();
    document.addEventListener('DOMContentLoaded', function () {
      stopLoadingSound();
      playPageLoadChime();
    });
  } else {
    // Already loaded (script at bottom of body)
    playPageLoadChime();
  }
  window.addEventListener('load', function () {
    stopLoadingSound();
  });

  // ==== NAVIGATION — swoosh when clicking links that navigate away ====
  document.addEventListener('click', function (e) {
    var link = e.target.closest('a[href]');
    if (!link) return;
    var href = link.getAttribute('href');
    // Skip anchors, javascript:, and external links opened in new tabs
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
    if (link.target === '_blank') return;
    // This is a real navigation — play departing swoosh
    playNavigationSwoosh();
  });

  // ==== BROWSER BACK/FORWARD — popstate navigation sound ====
  window.addEventListener('popstate', function () {
    playNavigationSwoosh();
  });

  // ==== GLOBAL CLICK — sound + ripple on every click ====
  document.addEventListener('click', function (e) {
    var isButton = e.target.closest(
      'a, button, .btn-primary, .btn-submit, .btn-outline, .btn-view, .btn-success, ' +
      '.btn-danger, .menu-item, .action-card, .stat-card, .feature-card, .sponsor-card, ' +
      '.announcement-card, .team-card, .department-card, .nav-link, .chat-toggle-btn, ' +
      '.chat-send-btn, .chat-option-btn, .chat-suggestion-btn, .tab-link, .sfx-btn, ' +
      '.swal2-confirm, .swal2-cancel, .track-panel, input[type="submit"]'
    );
    if (isButton) {
      playButtonClick();
    } else {
      playSoftPop();
    }
    createRipple(e.clientX, e.clientY);
  });

  // ==== HOVER — interactive elements ====
  document.addEventListener('mouseenter', function (e) {
    var hovered = e.target.closest(
      '.menu-item, .action-card, .stat-card, .feature-card, .sponsor-card, ' +
      '.announcement-card, .team-card, .department-card, .nav-link, ' +
      '.btn-primary, .btn-submit, .btn-outline, .btn-view, .btn-success, ' +
      '.btn-danger, .tab-link, .chat-toggle-btn, .chat-option-btn, .chat-suggestion-btn, ' +
      '.notification-item, .dropdown-item, .leaderboard-item, .auth-card, .welcome-card'
    );
    if (hovered) playHoverTick();
  }, true);

  // ==== TABLE ROW HOVER ====
  document.addEventListener('mouseenter', function (e) {
    var row = e.target.closest('table tbody tr, .data-table tbody tr, .table tbody tr');
    if (row) playTableRowHover();
  }, true);

  // ==== TABLE CELL HOVER ====
  document.addEventListener('mouseenter', function (e) {
    if (e.target.tagName === 'TD' || e.target.tagName === 'TH') playCellTick();
  }, true);

  // ==== SCROLL — whoosh (throttled) ====
  var lastScrollY = window.scrollY || window.pageYOffset;
  var scrollTimeout = null;
  var scrollCooldown = false;
  var prevAtTop = true;
  var prevAtBottom = false;

  window.addEventListener('scroll', function () {
    if (scrollCooldown) return;
    scrollCooldown = true;

    var currentY = window.scrollY || window.pageYOffset;
    var delta = currentY - lastScrollY;
    var maxScroll = document.documentElement.scrollHeight - window.innerHeight;
    var atTop = currentY <= 5;
    var atBottom = currentY >= maxScroll - 5;

    // Scroll whoosh for movement
    if (Math.abs(delta) > 20) {
      playScrollWhoosh(delta > 0 ? 'down' : 'up');
    }

    // Edge thud when hitting top or bottom
    if (atTop && !prevAtTop) playScrollEdge();
    if (atBottom && !prevAtBottom) playScrollEdge();

    prevAtTop = atTop;
    prevAtBottom = atBottom;
    lastScrollY = currentY;

    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(function () {
      scrollCooldown = false;
    }, 120);
  }, { passive: true });

  // ==== SCROLLABLE CONTAINERS — sidebar, chat, modals ====
  document.addEventListener('scroll', function (e) {
    if (e.target === document || e.target === document.documentElement) return;
    if (scrollCooldown) return;
    scrollCooldown = true;
    playScrollWhoosh('down');
    clearTimeout(scrollTimeout);
    scrollTimeout = setTimeout(function () { scrollCooldown = false; }, 120);
  }, true);

  // ==== SECTION INTERSECTION — ding when scrolling into content sections ====
  var sectionObserved = new Set();
  function observeSections() {
    var sections = document.querySelectorAll(
      '.dashboard-content, .welcome-card, .stat-card, .auth-card, ' +
      'section, .content-section, .card, .data-table'
    );
    if (!sections.length) return;
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting && !sectionObserved.has(entry.target)) {
          sectionObserved.add(entry.target);
          playSectionEnter();
        }
      });
    }, { threshold: 0.3 });
    sections.forEach(function (s) { io.observe(s); });
  }
  // Run after DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', observeSections);
  } else {
    setTimeout(observeSections, 100);
  }

  // ==== INPUT FOCUS — warm chime ====
  document.addEventListener('focusin', function (e) {
    var isInput = e.target.matches(
      'input[type="text"], input[type="email"], input[type="password"], input[type="search"], ' +
      'input[type="tel"], input[type="url"], input[type="number"], textarea, ' +
      '.form-input, .form-select, .chat-input, select'
    );
    if (isInput) playInputFocus();
  });

  // ==== KEYSTROKE + ERASE ====
  var INPUT_SEL = 'input[type="text"], input[type="email"], input[type="password"], input[type="search"], ' +
    'input[type="tel"], input[type="url"], input[type="number"], textarea, .chat-input';

  document.addEventListener('keydown', function (e) {
    if (!e.target.matches(INPUT_SEL)) return;
    if (e.key === 'Backspace' || e.key === 'Delete') {
      playEraseTick();
    } else if (e.key === 'Enter') {
      playEnterKey();
    } else if (e.key === 'Escape') {
      playEscapeSound();
    } else if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'C')) {
      playCopySound();
    } else if ((e.ctrlKey || e.metaKey) && (e.key === 'v' || e.key === 'V')) {
      playPasteSound();
    } else if ((e.ctrlKey || e.metaKey) && (e.key === 'x' || e.key === 'X')) {
      playClearSweep();
    } else if (e.key.length === 1) {
      playKeystroke();
    }
  });

  // ==== GLOBAL KEYBOARD — Escape, Copy, Paste outside inputs too ====
  document.addEventListener('keydown', function (e) {
    if (e.target.matches(INPUT_SEL)) return; // handled above
    if (e.key === 'Escape') {
      playEscapeSound();
    } else if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'C')) {
      playCopySound();
    } else if ((e.ctrlKey || e.metaKey) && (e.key === 'v' || e.key === 'V')) {
      playPasteSound();
    }
  });

  // ==== INPUT CLEARED (value becomes empty) ====
  document.addEventListener('input', function (e) {
    if (e.target.matches(INPUT_SEL) && e.target.value === '') {
      playClearSweep();
    }
  });

  // ==== FILE INPUT — upload sparkle ====
  document.addEventListener('change', function (e) {
    if (e.target.type === 'file' && e.target.files && e.target.files.length > 0) {
      playFileUpload();
    } else if (e.target.type === 'checkbox') {
      playToggle(e.target.checked);
    } else if (e.target.type === 'radio') {
      playToggle(true);
    } else if (e.target.tagName === 'SELECT') {
      playDropdown(true);
    }
  });

  // ==== FORM SUBMIT ====
  document.addEventListener('submit', function () {
    playFormSubmit();
  });

  // ==== SIDEBAR TOGGLE (mobile hamburger) ====
  document.addEventListener('click', function (e) {
    var toggler = e.target.closest('.mobile-toggle, .hamburger, .sidebar-toggle, #menuToggle');
    if (toggler) {
      var sidebar = document.querySelector('.sidebar');
      if (sidebar) {
        var isOpen = sidebar.classList.contains('open') || sidebar.classList.contains('active');
        setTimeout(function () { playSidebarSlide(!isOpen); }, 50);
      }
    }
  });

  // ==== NOTIFICATION BELL ====
  document.addEventListener('click', function (e) {
    var bell = e.target.closest('.notification-btn, .notification-bell, [class*="notification"] > button, .chat-notification-btn');
    if (bell) playNotificationPing();
  });

  // ==== SWEETALERT2 / CHAT MODAL OBSERVER ====
  var swalObserver = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      mutation.addedNodes.forEach(function (node) {
        if (node.nodeType !== 1) return;
        if (node.classList && node.classList.contains('swal2-container')) {
          playModalOpen();
          setTimeout(function () {
            var icon = node.querySelector('.swal2-icon');
            if (icon) {
              if (icon.classList.contains('swal2-success')) playSuccessDing();
              else if (icon.classList.contains('swal2-error')) playErrorBuzz();
              else if (icon.classList.contains('swal2-warning')) playWarningTone();
            }
          }, 100);
        }
        if (node.classList && node.classList.contains('chat-window')) playModalOpen();
      });
    });
  });
  swalObserver.observe(document.body, { childList: true, subtree: true });

  // ==== DROPDOWN / PROFILE MENU ====
  document.addEventListener('click', function (e) {
    var dd = e.target.closest('.user-profile, .dropdown-toggle, .profile-dropdown, [data-toggle="dropdown"]');
    if (dd) playDropdown(true);
  });

  // ==== CHAT TOGGLE ====
  document.addEventListener('click', function (e) {
    var chatToggle = e.target.closest('.chat-toggle-btn');
    if (chatToggle) {
      var cw = document.querySelector('.chat-window');
      if (cw) {
        var vis = cw.style.display !== 'none' && cw.offsetParent !== null;
        playSidebarSlide(!vis);
      }
    }
  });

  // ==== TAB SWITCH ====
  document.addEventListener('click', function (e) {
    var tab = e.target.closest('.tab-link, .nav-tab, [role="tab"]');
    if (tab) playToggle(true);
  });

  // ==== PAGE VISIBILITY — sound when user returns to tab ====
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      playLeaveTab();
    } else {
      playReturnToTab();
    }
  });

  // ==== LOADING INDICATOR — fetch/XHR interception ====
  // Monkey-patch fetch to play loading sounds during AJAX
  if (window.fetch) {
    var origFetch = window.fetch;
    window.fetch = function () {
      startLoadingSound();
      return origFetch.apply(this, arguments).then(function (resp) {
        stopLoadingSound();
        return resp;
      }).catch(function (err) {
        stopLoadingSound();
        throw err;
      });
    };
  }

  // Monkey-patch XMLHttpRequest for jQuery $.ajax calls
  var origOpen = XMLHttpRequest.prototype.open;
  var origSend = XMLHttpRequest.prototype.send;
  XMLHttpRequest.prototype.open = function () {
    this._sfxOpened = true;
    return origOpen.apply(this, arguments);
  };
  XMLHttpRequest.prototype.send = function () {
    var xhr = this;
    if (xhr._sfxOpened) {
      startLoadingSound();
      xhr.addEventListener('loadend', function () {
        stopLoadingSound();
      });
    }
    return origSend.apply(this, arguments);
  };

})();
