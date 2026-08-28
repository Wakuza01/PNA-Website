'use strict';

(function () {

  var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ─── Core Intersection Observer ──────────────────────────────────────────────
  var observer = null;

  function createObserver() {
    if (!('IntersectionObserver' in window)) return null;

    return new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, {
      threshold: 0,
      rootMargin: '0px 0px 0px 0px'
    });
  }

  // Public: lets linkedin-feed.js re-observe newly rendered cards
  function observeElement(el) {
    if (!el) return;
    if (reducedMotion) {
      el.classList.add('is-visible');
      return;
    }
    if (observer) observer.observe(el);
  }

  window.initObserver = observeElement;

  // ─── Observe Standard Reveal Classes ─────────────────────────────────────────
  function initRevealElements() {
    var selectors = [
      '.reveal',
      '.reveal-left',
      '.reveal-right',
      '.reveal-heading',
      '.reveal-image'
    ];

    selectors.forEach(function (sel) {
      document.querySelectorAll(sel).forEach(function (el) {
        if (reducedMotion) {
          el.classList.add('is-visible');
        } else {
          observeElement(el);
        }
      });
    });
  }

  // ─── Timeline Line Drawing ────────────────────────────────────────────────────
  function initTimelineLine() {
    var timelineLine = document.querySelector('.timeline-line');
    if (!timelineLine) {
      // Fall back to the timeline element itself
      timelineLine = document.querySelector('.timeline');
    }
    if (!timelineLine) return;

    if (reducedMotion) {
      timelineLine.classList.add('is-drawing');
      return;
    }

    if (!('IntersectionObserver' in window)) return;

    var lineObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-drawing');
        lineObserver.unobserve(entry.target);
      });
    }, { threshold: 0.05 });

    lineObserver.observe(timelineLine);
  }

  // ─── Auto .reveal-heading on Section h2 Elements ─────────────────────────────
  function initHeadingClasses() {
    document.querySelectorAll('section h2').forEach(function (h2) {
      if (!h2.classList.contains('reveal-heading')) {
        h2.classList.add('reveal-heading');
        if (reducedMotion) {
          h2.classList.add('is-visible');
        } else {
          observeElement(h2);
        }
      }
    });
  }

  // ─── Stagger Groups ───────────────────────────────────────────────────────────
  function initStaggerGroups() {
    var groups = document.querySelectorAll('.stagger-children');
    if (!groups.length) return;

    if (reducedMotion) {
      groups.forEach(function (parent) {
        Array.from(parent.children).forEach(function (child) {
          child.classList.add('is-visible');
        });
      });
      return;
    }

    if (!('IntersectionObserver' in window)) return;

    var staggerObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;

        var children = Array.from(entry.target.children);
        children.forEach(function (child, i) {
          setTimeout(function () {
            child.classList.add('is-visible');
          }, 80 * i);
        });

        staggerObserver.unobserve(entry.target);
      });
    }, {
      threshold: 0.15,
      rootMargin: '0px 0px -50px 0px'
    });

    groups.forEach(function (parent) {
      staggerObserver.observe(parent);
    });
  }

  // ─── Safety net: force-reveal anything still hidden after 2s ─────────────────
  function forceRevealAll() {
    var selectors = '.reveal, .reveal-left, .reveal-right, .reveal-heading, .reveal-image';
    document.querySelectorAll(selectors).forEach(function (el) {
      if (!el.classList.contains('is-visible')) {
        el.classList.add('is-visible');
      }
    });
  }

  // ─── Image Reveal ─────────────────────────────────────────────────────────────
  function initImageReveal() {
    document.querySelectorAll('.img-hover-wrap').forEach(function (wrap) {
      if (!wrap.classList.contains('reveal-image')) {
        wrap.classList.add('reveal-image');
        if (reducedMotion) {
          wrap.classList.add('is-visible');
        } else {
          observeElement(wrap);
        }
      }
    });
  }

  // ─── Hero Headline Text Split ─────────────────────────────────────────────────
  function initHeroTextSplit() {
    var headline = document.querySelector('.hero-headline');
    if (!headline) return;

    // Don't double-process
    if (headline.querySelector('.line')) return;

    var rawText = headline.textContent.trim();

    // Split into lines by <br> if present, otherwise treat as one line
    var lines;
    if (headline.innerHTML.indexOf('<br') !== -1) {
      lines = headline.innerHTML
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<[^>]+>/g, '')
        .split('\n')
        .map(function (l) { return l.trim(); })
        .filter(Boolean);
    } else {
      lines = [rawText];
    }

    headline.innerHTML = lines.map(function (lineText) {
      var words = lineText.split(/\s+/).filter(Boolean);
      var wordSpans = words.map(function (word) {
        return '<span class="word">' + word + '</span>';
      }).join(' ');
      return '<span class="line">' + wordSpans + '</span>';
    }).join('');

    if (reducedMotion) {
      headline.querySelectorAll('.line').forEach(function (line) {
        line.style.clipPath = 'inset(0 0 0 0)';
        line.style.opacity = '1';
      });
    } else {
      headline.querySelectorAll('.line').forEach(function (line, i) {
        line.style.clipPath = 'inset(0 100% 0 0)';
        line.style.opacity = '0';
        line.style.transition = 'clip-path 0.7s cubic-bezier(0.77,0,0.175,1) ' + (0.1 + i * 0.15) + 's, opacity 0.1s ' + (0.1 + i * 0.15) + 's';

        // Trigger after a short delay so transition fires
        requestAnimationFrame(function () {
          requestAnimationFrame(function () {
            line.style.clipPath = 'inset(0 0% 0 0)';
            line.style.opacity = '1';
          });
        });
      });
    }
  }

  // ─── Boot ────────────────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function () {
    observer = createObserver();

    initHeadingClasses();
    initRevealElements();
    initTimelineLine();
    initStaggerGroups();
    initImageReveal();
    initHeroTextSplit();
    setTimeout(forceRevealAll, 1500);
  });

}());
