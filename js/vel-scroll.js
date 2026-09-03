(function () {
  'use strict';

  /* ─── Scroll velocity tracker ─────────────────────────── */
  var lastScrollY = window.scrollY || window.pageYOffset || 0;
  var rawVel = 0;
  var smoothVel = 0;

  window.addEventListener('scroll', function () {
    var y = window.scrollY || window.pageYOffset || 0;
    rawVel = y - lastScrollY;
    lastScrollY = y;
  }, { passive: true });

  /* ─── Init one strip ───────────────────────────────────── */
  // track    : the scrolling element (flex container of items)
  // speed    : base pixels/second
  // ltr      : true = left-to-right, false = right-to-left (default)
  function initStrip(track, speed, ltr) {
    // Kill CSS animation; we drive it from JS
    track.style.animation = 'none';
    track.style.willChange = 'transform';

    // Wait one frame so layout is stable before measuring
    requestAnimationFrame(function () {
      // Measure the width of a single set of items
      var singleW = track.scrollWidth;

      // Clone items until we have ≥ 4× the viewport width
      var origHTML = track.innerHTML;
      while (track.scrollWidth < window.innerWidth * 4) {
        track.insertAdjacentHTML('beforeend', origHTML);
      }

      // Direction: -1 = moving left (offset decreases), 1 = moving right
      var baseDir = ltr ? 1 : -1;

      // Offset starts so visible content begins correctly
      // Moving left: start at 0, go to -singleW then wrap
      // Moving right: start at -singleW, go to 0 then wrap
      var offset = ltr ? -singleW : 0;

      var lastT = null;

      function tick(ts) {
        if (!lastT) { lastT = ts; requestAnimationFrame(tick); return; }
        var dt = Math.min((ts - lastT) / 1000, 0.05);
        lastT = ts;

        // Spring-smooth the scroll velocity
        smoothVel += (rawVel - smoothVel) * 0.1;

        // Map scroll velocity to a speed multiplier [-5, 5]
        var velFactor = Math.max(-5, Math.min(5, smoothVel * 0.06));

        // Scroll down → speed in current direction; scroll up → reverse briefly
        var currentDir;
        if (velFactor > 0.05) currentDir = 1;
        else if (velFactor < -0.05) currentDir = -1;
        else currentDir = baseDir;

        var move = currentDir * speed * dt * (1 + Math.abs(velFactor));
        offset += move;

        // Wrap within [-singleW, 0)
        if (offset <= -singleW) offset += singleW;
        if (offset >= 0)        offset -= singleW;

        track.style.transform = 'translateX(' + offset + 'px)';
        requestAnimationFrame(tick);
      }

      requestAnimationFrame(tick);
    });
  }

  /* ─── Wire up strips on DOMContentLoaded ──────────────── */
  document.addEventListener('DOMContentLoaded', function () {
    var heroTrack = document.querySelector('.ticker-track');
    if (heroTrack) initStrip(heroTrack, 90, false);   // hero ticker → left

    var clientTrack = document.querySelector('.marquee-track');
    if (clientTrack) initStrip(clientTrack, 65, false); // clients → left
  });
})();
