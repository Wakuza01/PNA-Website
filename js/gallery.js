'use strict';

(function () {
  // ============================================================
  // FILTER CHIPS — filter product cards by category
  // ============================================================
  function initFilters() {
    const filterChips = document.querySelectorAll('.filter-chip[data-category]');
    const productCards = document.querySelectorAll('.product-card[data-category]');

    if (!filterChips.length) return;

    filterChips.forEach(function (chip) {
      chip.addEventListener('click', function () {
        const category = chip.dataset.category;

        // Update active chip
        filterChips.forEach(function (c) { c.classList.remove('is-active'); });
        chip.classList.add('is-active');

        // Hide non-matching cards immediately so they leave the grid flow
        productCards.forEach(function (card) {
          const show = (category === 'all' || card.dataset.category === category);
          if (!show) {
            card.style.transition = 'none';
            card.style.opacity = '0';
            card.style.display = 'none';
          }
        });

        // Stagger-animate matching cards back in
        let visibleIndex = 0;
        productCards.forEach(function (card) {
          const show = (category === 'all' || card.dataset.category === category);
          if (show) {
            card.style.display = '';
            card.style.opacity = '0';
            card.style.transform = 'translateY(14px) scale(0.97)';
            (function (c, delay) {
              setTimeout(function () {
                c.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                c.style.opacity = '1';
                c.style.transform = '';
                c.style.pointerEvents = '';
              }, delay);
            })(card, visibleIndex * 60);
            visibleIndex++;
          }
        });
      });
    });
  }

  // ============================================================
  // DRAG-TO-SCROLL for horizontal containers
  // ============================================================
  function initDragScroll(container) {
    if (!container) return;

    let isDown = false;
    let startX = 0;
    let scrollLeft = 0;
    let hasDragged = false;

    container.addEventListener('mousedown', function (e) {
      isDown = true;
      hasDragged = false;
      startX = e.pageX - container.offsetLeft;
      scrollLeft = container.scrollLeft;
      container.style.cursor = 'grabbing';
      container.style.userSelect = 'none';
    });

    container.addEventListener('mouseleave', function () {
      isDown = false;
      container.style.cursor = '';
      container.style.userSelect = '';
    });

    container.addEventListener('mouseup', function () {
      isDown = false;
      container.style.cursor = '';
      container.style.userSelect = '';
    });

    container.addEventListener('mousemove', function (e) {
      if (!isDown) return;
      e.preventDefault();
      const x = e.pageX - container.offsetLeft;
      const walk = (x - startX) * 1.5;
      if (Math.abs(walk) > 5) hasDragged = true;
      container.scrollLeft = scrollLeft - walk;
    });

    // Prevent click events on children if we dragged
    container.addEventListener('click', function (e) {
      if (hasDragged) {
        e.preventDefault();
        e.stopPropagation();
      }
    }, true);
  }

  // ============================================================
  // LAZY LOAD product images via Intersection Observer
  // ============================================================
  function initLazyImages() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    if (!lazyImages.length) return;

    if ('IntersectionObserver' in window) {
      const imgObserver = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            const img = entry.target;
            img.src = img.dataset.src;
            if (img.dataset.srcset) img.srcset = img.dataset.srcset;
            img.removeAttribute('data-src');
            imgObserver.unobserve(img);
          });
        },
        { rootMargin: '200px 0px' }
      );

      lazyImages.forEach(function (img) { imgObserver.observe(img); });
    } else {
      // Fallback: load all
      lazyImages.forEach(function (img) {
        img.src = img.dataset.src;
        if (img.dataset.srcset) img.srcset = img.dataset.srcset;
      });
    }
  }

  // ============================================================
  // INIT
  // ============================================================
  function init() {
    initFilters();
    initLazyImages();

    // Drag scroll on all .products-scroll containers
    document.querySelectorAll('.products-scroll').forEach(initDragScroll);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
