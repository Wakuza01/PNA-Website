'use strict';

(function () {

  // ─── Page Load Class ────────────────────────────────────────────────────────
  function initPageLoad() {
    setTimeout(function () {
      document.body.classList.add('page-loaded');
    }, 100);
  }

  // ─── Scroll-to-Top Button ────────────────────────────────────────────────────
  function initScrollToTop() {
    var btn = document.querySelector('.scroll-top-btn');
    if (!btn) return;

    window.addEventListener('scroll', function () {
      if (window.scrollY > 400) {
        btn.classList.add('visible');
      } else {
        btn.classList.remove('visible');
      }
    }, { passive: true });

    btn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  // ─── Reduced Motion ─────────────────────────────────────────────────────────
  function initReducedMotion() {
    var mq = window.matchMedia('(prefers-reduced-motion: reduce)');

    function applyReducedMotion(e) {
      if (e.matches) {
        document.body.classList.add('reduce-motion');
      } else {
        document.body.classList.remove('reduce-motion');
      }
    }

    applyReducedMotion(mq);

    if (typeof mq.addEventListener === 'function') {
      mq.addEventListener('change', applyReducedMotion);
    } else if (typeof mq.addListener === 'function') {
      // Safari < 14 fallback
      mq.addListener(applyReducedMotion);
    }
  }

  // ─── Page Visibility API ─────────────────────────────────────────────────────
  function initPageVisibility() {
    window.tabHidden = false;

    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        window.tabHidden = true;
      } else {
        window.tabHidden = false;
      }
    });
  }

  // ─── Smooth Scroll for Anchor Links ─────────────────────────────────────────
  function initSmoothScroll() {
    var NAV_HEIGHT = 80;

    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
      anchor.addEventListener('click', function (e) {
        var href = anchor.getAttribute('href');
        if (!href || href === '#') return;

        var target = document.querySelector(href);
        if (!target) return;

        e.preventDefault();

        var top = target.getBoundingClientRect().top + window.scrollY - NAV_HEIGHT;

        window.scrollTo({ top: top, behavior: 'smooth' });
      });
    });
  }

  // ─── Stagger Indices ─────────────────────────────────────────────────────────
  function initStaggerIndices() {
    document.querySelectorAll('.stagger-children').forEach(function (parent) {
      var children = Array.from(parent.children);
      children.forEach(function (child, i) {
        child.style.setProperty('--stagger-index', i);
      });
    });
  }

  // ─── Animated Accordion (details/summary) ───────────────────────────────────
  function initAnimatedAccordion() {
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('details.service-item').forEach(function (details) {
      var summary = details.querySelector('summary');
      var body    = details.querySelector('.service-item-body');
      if (!summary || !body) return;

      var animation = null;
      var isClosing = false;
      var isOpening = false;

      summary.addEventListener('click', function (e) {
        e.preventDefault();
        details.style.overflow = 'hidden';

        if (isClosing || !details.open) {
          open();
        } else if (isOpening || details.open) {
          close();
        }
      });

      function open() {
        isOpening = true;
        details.open = true;

        if (reducedMotion) { isOpening = false; return; }

        var startH = 0;
        var endH   = body.scrollHeight;

        if (animation) animation.cancel();
        animation = body.animate(
          [{ height: startH + 'px', opacity: 0, transform: 'translateY(-8px)' },
           { height: endH   + 'px', opacity: 1, transform: 'translateY(0)'   }],
          { duration: 320, easing: 'cubic-bezier(0.4, 0, 0.2, 1)' }
        );
        animation.onfinish = function () {
          body.style.height = '';
          details.style.overflow = '';
          isOpening = false;
          animation = null;
        };
        animation.oncancel = function () { isOpening = false; };
      }

      function close() {
        isClosing = true;

        if (reducedMotion) { details.open = false; isClosing = false; return; }

        var startH = body.scrollHeight;

        if (animation) animation.cancel();
        animation = body.animate(
          [{ height: startH + 'px', opacity: 1, transform: 'translateY(0)'   },
           { height: 0      + 'px', opacity: 0, transform: 'translateY(-8px)' }],
          { duration: 280, easing: 'cubic-bezier(0.4, 0, 0.2, 1)' }
        );
        animation.onfinish = function () {
          details.open = false;
          details.style.overflow = '';
          isClosing = false;
          animation = null;
        };
        animation.oncancel = function () { isClosing = false; };
      }
    });
  }

  // ─── Lightbox ────────────────────────────────────────────────────────────────
  function initLightbox() {
    var lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.setAttribute('role', 'dialog');
    lightbox.setAttribute('aria-modal', 'true');
    lightbox.setAttribute('aria-label', 'Image viewer');
    lightbox.innerHTML = '<button class="lightbox-close" aria-label="Close image viewer"><svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button><img class="lightbox-img" src="" alt="">';
    document.body.appendChild(lightbox);

    var lbImg   = lightbox.querySelector('.lightbox-img');
    var lbClose = lightbox.querySelector('.lightbox-close');

    function openLightbox(src, alt) {
      lbImg.src = src;
      lbImg.alt = alt || '';
      lightbox.classList.add('is-open');
      document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
      lightbox.classList.remove('is-open');
      document.body.style.overflow = '';
      setTimeout(function () { lbImg.src = ''; }, 300);
    }

    document.querySelectorAll('.project-card').forEach(function (card) {
      card.addEventListener('click', function () {
        var img = card.querySelector('img');
        if (img) openLightbox(img.src, img.alt);
      });
    });

    lbClose.addEventListener('click', closeLightbox);

    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) closeLightbox();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && lightbox.classList.contains('is-open')) closeLightbox();
    });
  }

  // ─── Boot ────────────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function () {
    initPageLoad();
    initScrollToTop();
    initReducedMotion();
    initPageVisibility();
    initSmoothScroll();
    initStaggerIndices();
    initAnimatedAccordion();
    initLightbox();
  });

}());
