'use strict';

(function () {
  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var RAF_ID = null;
  var lastFrameTime = 0;
  var TARGET_FPS = 30;
  var FRAME_INTERVAL = 1000 / TARGET_FPS;

  // ============================================================
  // HERO MOUSE PARALLAX
  // ============================================================
  function initHeroParallax() {
    if (prefersReducedMotion) return;

    var hero = document.querySelector('.hero');
    var heroBg = document.querySelector('.hero .hero-bg');
    if (!hero || !heroBg) return;

    var targetX = 0;
    var targetY = 0;
    var currentX = 0;
    var currentY = 0;

    hero.addEventListener('mousemove', function (e) {
      var rect = hero.getBoundingClientRect();
      var cx = rect.left + rect.width / 2;
      var cy = rect.top + rect.height / 2;
      targetX = (e.clientX - cx) * 0.012;
      targetY = (e.clientY - cy) * 0.012;
    });

    hero.addEventListener('mouseleave', function () {
      targetX = 0;
      targetY = 0;
    });

    function animateParallax() {
      currentX += (targetX - currentX) * 0.08;
      currentY += (targetY - currentY) * 0.08;
      heroBg.style.transform = 'translate(' + currentX + 'px, ' + currentY + 'px) scale(1.06)';
      requestAnimationFrame(animateParallax);
    }

    animateParallax();
  }

  // ============================================================
  // WELDING SPARK CANVAS EFFECT
  // ============================================================
  function initSparkCanvas() {
    if (prefersReducedMotion) return;

    var canvasEl = document.querySelector('.hero-canvas');
    if (!canvasEl) return;

    var ctx = canvasEl.getContext('2d');
    var particles = [];
    var MAX_PARTICLES = 40;
    var animating = true;

    function resizeCanvas() {
      var hero = canvasEl.parentElement;
      if (!hero) return;
      canvasEl.width = hero.offsetWidth;
      canvasEl.height = hero.offsetHeight;
    }

    resizeCanvas();

    var resizeObserver = window.ResizeObserver
      ? new ResizeObserver(resizeCanvas)
      : null;
    if (resizeObserver && canvasEl.parentElement) {
      resizeObserver.observe(canvasEl.parentElement);
    } else {
      window.addEventListener('resize', resizeCanvas);
    }

    // Spark colors: white and blue tones
    var sparkColors = [
      'rgba(255,255,255,',
      'rgba(200,225,255,',
      'rgba(38,157,204,',
      'rgba(32,87,133,'
    ];

    function createParticle() {
      var w = canvasEl.width;
      var h = canvasEl.height;
      return {
        x: Math.random() * w,
        y: h * 0.3 + Math.random() * h * 0.6,
        vx: (Math.random() - 0.5) * 1.8,
        vy: -(Math.random() * 1.5 + 0.5),
        life: 0,
        maxLife: Math.random() * 40 + 20,
        size: Math.random() * 2.5 + 0.5,
        color: sparkColors[Math.floor(Math.random() * sparkColors.length)],
        trail: []
      };
    }

    function updateAndDraw(timestamp) {
      if (!animating || window.tabHidden) {
        RAF_ID = requestAnimationFrame(updateAndDraw);
        return;
      }

      var elapsed = timestamp - lastFrameTime;
      if (elapsed < FRAME_INTERVAL) {
        RAF_ID = requestAnimationFrame(updateAndDraw);
        return;
      }
      lastFrameTime = timestamp;

      ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);

      // Spawn new particles
      if (particles.length < MAX_PARTICLES && Math.random() < 0.4) {
        particles.push(createParticle());
        if (Math.random() < 0.3) particles.push(createParticle());
      }

      // Update and draw
      particles = particles.filter(function (p) {
        p.life++;
        p.x += p.vx;
        p.y += p.vy;
        p.vx += (Math.random() - 0.5) * 0.15;
        p.vy -= 0.02; // slight upward acceleration

        var alpha = (1 - p.life / p.maxLife) * 0.7;
        var size = p.size * (1 - p.life / p.maxLife * 0.5);

        if (size > 0.1 && alpha > 0) {
          ctx.beginPath();
          ctx.arc(p.x, p.y, size, 0, Math.PI * 2);
          ctx.fillStyle = p.color + alpha + ')';
          ctx.fill();
        }

        return p.life < p.maxLife;
      });

      RAF_ID = requestAnimationFrame(updateAndDraw);
    }

    RAF_ID = requestAnimationFrame(updateAndDraw);

    // Pause when tab hidden
    document.addEventListener('visibilitychange', function () {
      animating = !document.hidden;
    });
  }

  // ============================================================
  // MARQUEE DUPLICATE — ensures seamless loop
  // ============================================================
  function initMarqueeDuplicate() {
    document.querySelectorAll('.ticker-track, .marquee-track').forEach(function (track) {
      var originalContent = track.innerHTML;
      track.innerHTML = originalContent + originalContent;
    });
  }

  // ============================================================
  // SCROLL PROGRESS BAR
  // ============================================================
  function initScrollProgress() {
    var bar = document.querySelector('.scroll-progress');
    if (!bar) return;

    function updateProgress() {
      var scrollTop = window.scrollY || document.documentElement.scrollTop;
      var docHeight = document.documentElement.scrollHeight - window.innerHeight;
      var progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
      bar.style.width = progress + '%';
    }

    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
  }

  // ============================================================
  // INIT
  // ============================================================
  function init() {
    initHeroParallax();
    initSparkCanvas();
    initMarqueeDuplicate();
    initScrollProgress();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
