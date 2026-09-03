'use strict';

(function () {
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function easeOutQuart(t) {
    return 1 - Math.pow(1 - t, 4);
  }

  function animateCounter(el, target, duration, suffix, prefix) {
    if (prefersReducedMotion) {
      el.textContent = prefix + target + suffix;
      return;
    }

    const startTime = performance.now();

    function update(currentTime) {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const easedProgress = easeOutQuart(progress);
      const current = Math.round(easedProgress * target);

      el.textContent = prefix + current + suffix;

      if (progress < 1) {
        requestAnimationFrame(update);
      } else {
        el.textContent = prefix + target + suffix;
      }
    }

    requestAnimationFrame(update);
  }

  function initCounters() {
    const allStats = document.querySelectorAll('.stat-number[data-target]');
    if (!allStats.length) return;

    // Split: hero stats (visible on load) vs below-fold stats
    const heroStats   = document.querySelectorAll('.hero-stats-strip .stat-number[data-target]');
    const scrollStats = document.querySelectorAll('.stat-number[data-target]');

    // Initialise all to zero
    allStats.forEach(function (el) {
      const suffix = el.dataset.suffix || '';
      const prefix = el.dataset.prefix || '';
      el.textContent = prefix + '0' + suffix;
    });

    // ── Hero counters: start after hero text animation completes (~1.4s) ──
    if (heroStats.length) {
      setTimeout(function () {
        heroStats.forEach(function (el, i) {
          const target = parseInt(el.dataset.target, 10);
          if (isNaN(target)) return;
          const suffix = el.dataset.suffix || '';
          const prefix = el.dataset.prefix || '';
          // Stagger each stat slightly for drama
          setTimeout(function () {
            animateCounter(el, target, 2200, suffix, prefix);
          }, i * 120);
        });
      }, 1400);
    }

    // ── Below-fold counters: trigger on scroll into view ──
    const belowFold = Array.from(scrollStats).filter(function (el) {
      return !el.closest('.hero-stats-strip');
    });

    if (belowFold.length) {
      const observer = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            const target = parseInt(el.dataset.target, 10);
            const suffix = el.dataset.suffix || '';
            const prefix = el.dataset.prefix || '';
            if (!isNaN(target)) animateCounter(el, target, 2000, suffix, prefix);
            observer.unobserve(el);
          });
        },
        { threshold: 0.5 }
      );
      belowFold.forEach(function (el) { observer.observe(el); });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCounters);
  } else {
    initCounters();
  }
})();
