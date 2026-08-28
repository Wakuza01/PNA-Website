'use strict';

(function () {
  // ============================================================
  // HELPERS
  // ============================================================
  function escapeHtml(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function renderCard(post) {
    var imgHtml = post.image_url
      ? '<img src="' + escapeHtml(post.image_url) + '" alt="' + escapeHtml(post.title) + '" loading="lazy">'
      : '<div class="blog-card-no-image"></div>';

    return [
      '<article class="blog-card card reveal">',
      '  <div class="blog-card-image img-hover-wrap">',
      '    ' + imgHtml,
      '  </div>',
      '  <div class="blog-card-body">',
      '    <div class="blog-card-meta">',
      '      <span class="blog-card-category">' + escapeHtml(post.category) + '</span>',
      '      <span class="blog-card-date">' + escapeHtml(post.date_human) + '</span>',
      '    </div>',
      '    <h3 class="blog-card-title">' + escapeHtml(post.title) + '</h3>',
      '    <p class="blog-card-excerpt">' + escapeHtml(post.excerpt) + '</p>',
      '    <a href="' + escapeHtml(post.linkedin_url) + '" class="card-link">',
      '      <span>Read More</span>',
      '      <svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>',
      '    </a>',
      '  </div>',
      '</article>'
    ].join('\n');
  }

  function renderSkeletons(container, count) {
    var skeletons = '';
    for (var i = 0; i < count; i++) {
      skeletons += [
        '<div class="blog-card skeleton skeleton-card">',
        '  <div style="height: 160px; background: rgba(255,255,255,0.04); border-radius: 4px 4px 0 0;"></div>',
        '  <div style="padding: 1.5rem;">',
        '    <div class="skeleton-line skeleton-line--short" style="height: 10px; margin-bottom: 1rem;"></div>',
        '    <div class="skeleton-line" style="height: 14px; margin-bottom: 0.5rem;"></div>',
        '    <div class="skeleton-line" style="height: 14px; width: 75%; margin-bottom: 1rem;"></div>',
        '    <div class="skeleton-line skeleton-line--short" style="height: 10px;"></div>',
        '  </div>',
        '</div>'
      ].join('\n');
    }
    container.innerHTML = skeletons;
  }

  function renderEmptyState(container) {
    container.innerHTML = [
      '<div class="blog-empty-state">',
      '  <p>No posts available at this time. Check back soon.</p>',
      '</div>'
    ].join('\n');
  }

  // ============================================================
  // BLOG PAGE: filter by category
  // ============================================================
  function initBlogFilters(posts, container) {
    var filterBar = document.querySelector('.blog-filter-bar');
    if (!filterBar) return;

    // Collect unique categories
    var categories = ['All'];
    posts.forEach(function (post) {
      if (post.category && categories.indexOf(post.category) === -1) {
        categories.push(post.category);
      }
    });

    // Render filter buttons
    filterBar.innerHTML = categories.map(function (cat) {
      return '<button class="filter-btn' + (cat === 'All' ? ' is-active' : '') + '" data-category="' + escapeHtml(cat) + '">' + escapeHtml(cat) + '</button>';
    }).join('');

    var filterBtns = filterBar.querySelectorAll('.filter-btn');

    filterBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var category = btn.dataset.category;

        filterBtns.forEach(function (b) { b.classList.remove('is-active'); });
        btn.classList.add('is-active');

        var cards = container.querySelectorAll('.blog-card');

        // First pass: hide non-matching cards immediately (removes them from grid flow)
        cards.forEach(function (card) {
          var postCat = card.dataset.postcategory;
          var show = (category === 'All' || postCat === category);
          if (!show) {
            card.style.transition = 'none';
            card.style.opacity = '0';
            card.style.display = 'none';
          }
        });

        // Second pass: stagger-animate matching cards back in
        var visibleIndex = 0;
        cards.forEach(function (card) {
          var postCat = card.dataset.postcategory;
          var show = (category === 'All' || postCat === category);
          if (show) {
            card.style.display = '';
            card.style.opacity = '0';
            card.style.transform = 'translateY(14px)';
            (function (c, delay) {
              setTimeout(function () {
                c.style.transition = 'opacity 0.35s ease, transform 0.35s ease';
                c.style.opacity = '1';
                c.style.transform = '';
              }, delay);
            })(card, visibleIndex * 70);
            visibleIndex++;
          }
        });
      });
    });
  }

  // ============================================================
  // TRIGGER REVEAL ANIMATIONS on new cards
  // ============================================================
  function triggerRevealOnCards(container) {
    var cards = container.querySelectorAll('.reveal');
    if (!cards.length) return;

    // Small delay to allow DOM to paint
    setTimeout(function () {
      cards.forEach(function (card, i) {
        setTimeout(function () {
          card.classList.add('is-visible');
        }, i * 100);
      });
    }, 100);
  }

  // ============================================================
  // FETCH AND RENDER
  // ============================================================
  function loadFeed(container, limit, isBlogPage) {
    renderSkeletons(container, Math.min(limit, 3));

    var apiUrl = '/api/linkedin-feed.php?limit=' + (limit === 'all' ? 'all' : limit);

    fetch(apiUrl)
      .then(function (res) {
        if (!res.ok) throw new Error('API error ' + res.status);
        return res.json();
      })
      .then(function (posts) {
        if (!Array.isArray(posts) || posts.length === 0) {
          throw new Error('No posts');
        }
        renderPosts(container, posts, isBlogPage);
      })
      .catch(function () {
        // Fallback: try cache directly
        fetch('/api/linkedin-cache.json')
          .then(function (res) {
            if (!res.ok) throw new Error('Cache error');
            return res.json();
          })
          .then(function (posts) {
            if (!Array.isArray(posts) || posts.length === 0) throw new Error('Empty cache');
            var sliced = (limit !== 'all') ? posts.slice(0, limit) : posts;
            renderPosts(container, sliced, isBlogPage);
          })
          .catch(function () {
            renderEmptyState(container);
          });
      });
  }

  function renderPosts(container, posts, isBlogPage) {
    var html = posts.map(renderCard).join('\n');
    container.innerHTML = html;

    // Add data attributes for filtering
    var cards = container.querySelectorAll('.blog-card');
    cards.forEach(function (card, i) {
      if (posts[i]) {
        card.dataset.postcategory = posts[i].category || '';
      }
    });

    if (isBlogPage) {
      initBlogFilters(posts, container);
    }

    triggerRevealOnCards(container);
  }

  // ============================================================
  // INIT
  // ============================================================
  function init() {
    var feedContainer = document.querySelector('#linkedin-feed-container');
    if (!feedContainer) return;

    var isBlogPage = !!document.querySelector('.blog-filter-bar');
    var limit = isBlogPage ? 'all' : 3;

    loadFeed(feedContainer, limit, isBlogPage);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
