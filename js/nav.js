'use strict';

(function () {

  // ─── Sticky Nav Scroll Effect ────────────────────────────────────────────────
  function initStickyNav() {
    var nav = document.querySelector('#site-nav');
    if (!nav) return;

    // Apply initial state (e.g. page loaded while already scrolled)
    if (window.scrollY > 50) {
      nav.classList.add('scrolled');
    }

    window.addEventListener('scroll', function () {
      if (window.scrollY > 50) {
        nav.classList.add('scrolled');
      } else {
        nav.classList.remove('scrolled');
      }
    }, { passive: true });
  }

  // ─── Mobile Nav / Hamburger ──────────────────────────────────────────────────
  function initMobileNav() {
    var hamburger = document.querySelector('.nav-hamburger');
    var overlay   = document.querySelector('.nav-mobile-overlay');
    if (!hamburger || !overlay) return;

    var focusableSelectors = [
      'a[href]',
      'button:not([disabled])',
      'input:not([disabled])',
      'textarea:not([disabled])',
      'select:not([disabled])',
      '[tabindex]:not([tabindex="-1"])'
    ].join(', ');

    function getFocusables() {
      return Array.from(overlay.querySelectorAll(focusableSelectors)).filter(function (el) {
        return el.offsetParent !== null;
      });
    }

    function openNav() {
      hamburger.classList.add('is-open');
      overlay.classList.add('is-open');
      hamburger.setAttribute('aria-expanded', 'true');
      overlay.removeAttribute('aria-hidden');
      document.body.style.overflow = 'hidden';

      // Move focus to first focusable inside overlay
      var focusables = getFocusables();
      if (focusables.length) focusables[0].focus();
    }

    function closeNav() {
      hamburger.classList.remove('is-open');
      overlay.classList.remove('is-open');
      hamburger.setAttribute('aria-expanded', 'false');
      overlay.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      hamburger.focus();
    }

    // Trap focus inside overlay
    overlay.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab') return;
      var focusables = getFocusables();
      if (!focusables.length) return;

      var first = focusables[0];
      var last  = focusables[focusables.length - 1];

      if (e.shiftKey) {
        if (document.activeElement === first) {
          e.preventDefault();
          last.focus();
        }
      } else {
        if (document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    });

    // Close button (X icon inside overlay)
    var closeBtn = overlay.querySelector('.nav-mobile-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', closeNav);
    }

    // Hamburger click
    hamburger.addEventListener('click', function () {
      if (hamburger.classList.contains('is-open')) {
        closeNav();
      } else {
        openNav();
      }
    });

    // ESC key
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
        closeNav();
      }
    });

    // Click on overlay background (not on nav links / children)
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) {
        closeNav();
      }
    });

    // Click a nav link that goes to another page
    overlay.querySelectorAll('a[href]').forEach(function (link) {
      link.addEventListener('click', function () {
        var href = link.getAttribute('href');
        // Close if it's an external page link (not just an anchor)
        if (href && !href.startsWith('#')) {
          closeNav();
        } else {
          // Also close for anchor links (single-page nav)
          closeNav();
        }
      });
    });
  }

  // ─── Active Nav Link ─────────────────────────────────────────────────────────
  function initActiveLink() {
    var links = document.querySelectorAll('.nav-links a');
    if (!links.length) return;

    var currentPath = window.location.pathname.replace(/\/$/, '').replace(/\.html$/, '') || '/';

    // Clear all hardcoded active states first
    links.forEach(function (link) {
      link.classList.remove('is-active');
      link.removeAttribute('aria-current');
    });

    links.forEach(function (link) {
      var linkPath = link.getAttribute('href');
      if (!linkPath) return;

      try {
        var url = new URL(linkPath, window.location.href);
        var path = url.pathname.replace(/\/$/, '').replace(/\.html$/, '') || '/';
        var normPath = path === '/index' ? '/' : path;
        if (normPath === currentPath) {
          link.classList.add('is-active');
          link.setAttribute('aria-current', 'page');
        }
      } catch (err) {
        // Ignore malformed hrefs
      }
    });
  }

  // ─── Random Letter Swap Hover ─────────────────────────────────────────────────
  function initCharHover() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var POOL = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    var DURATION = 550; // ms for all chars to resolve

    var links = document.querySelectorAll('.nav-links a');
    if (!links.length) return;

    links.forEach(function (link) {
      var originalText = link.textContent;
      var rafId = null;
      var startTime = null;

      function scramble(ts) {
        if (!startTime) startTime = ts;
        var elapsed = ts - startTime;
        var chars = Array.from(originalText);
        var out = '';

        chars.forEach(function (char, i) {
          if (char === ' ') { out += '\u00a0'; return; }
          // Each character settles proportionally left-to-right
          var settleAt = DURATION * (i / chars.length);
          if (elapsed >= settleAt) {
            out += char;
          } else {
            out += POOL[Math.floor(Math.random() * POOL.length)];
          }
        });

        link.textContent = out;

        if (elapsed < DURATION) {
          rafId = requestAnimationFrame(scramble);
        } else {
          link.textContent = originalText;
          rafId = null;
        }
      }

      link.addEventListener('mouseenter', function () {
        if (rafId) cancelAnimationFrame(rafId);
        startTime = null;
        rafId = requestAnimationFrame(scramble);
      });

      link.addEventListener('mouseleave', function () {
        if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
        link.textContent = originalText;
      });
    });
  }

  // ─── Boot ────────────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function () {
    initStickyNav();
    initMobileNav();
    initActiveLink();
    initCharHover();
  });

}());
