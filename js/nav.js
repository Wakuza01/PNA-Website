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

  // ─── Character Hover Effect ───────────────────────────────────────────────────
  function initCharHover() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    var links = document.querySelectorAll('.nav-links a');
    if (!links.length) return;

    links.forEach(function (link) {
      // Store original text
      var originalText = link.textContent;

      function buildSpans() {
        link.textContent = '';
        Array.from(originalText).forEach(function (char, i) {
          var span = document.createElement('span');
          span.className = 'nav-char';
          span.textContent = char === ' ' ? '\u00a0' : char;
          span.style.transitionDelay = (i * 30) + 'ms';
          link.appendChild(span);
        });
      }

      function teardownSpans() {
        link.textContent = originalText;
      }

      link.addEventListener('mouseenter', function () {
        buildSpans();
        // Trigger the colour animation next frame
        requestAnimationFrame(function () {
          link.querySelectorAll('.nav-char').forEach(function (span) {
            span.classList.add('char-reveal');
          });
        });
      });

      link.addEventListener('mouseleave', function () {
        var spans = link.querySelectorAll('.nav-char');
        spans.forEach(function (span) {
          span.classList.remove('char-reveal');
        });
        // Wait for the last transition to finish before restoring plain text
        var maxDelay = (originalText.length - 1) * 30;
        setTimeout(teardownSpans, maxDelay + 200);
      });
    });
  }

  // ─── Boot ────────────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function () {
    initStickyNav();
    initMobileNav();
    initActiveLink();
  });

}());
