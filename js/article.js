'use strict';
(function () {
  var body = document.querySelector('.art-body');
  var page = document.querySelector('.art-page');
  if (!body || !page) return;

  // Text size (A- / A+), remembered per browser
  var sizes = [0.9375, 1.0625, 1.1875, 1.3125], idx = 1;
  try { var s = parseInt(localStorage.getItem('pna-art-size'), 10); if (s >= 0 && s < sizes.length) idx = s; } catch (e) {}
  function apply() { page.style.setProperty('--art-fs', sizes[idx] + 'rem'); try { localStorage.setItem('pna-art-size', idx); } catch (e) {} }
  apply();
  document.querySelectorAll('[data-fs]').forEach(function (b) {
    b.addEventListener('click', function () {
      idx = Math.max(0, Math.min(sizes.length - 1, idx + (b.dataset.fs === '+' ? 1 : -1)));
      apply();
    });
  });

  // Share links
  var url = encodeURIComponent(location.href.split('#')[0]);
  var title = encodeURIComponent(document.querySelector('.art-title').textContent.trim());
  var map = {
    facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + url,
    x: 'https://twitter.com/intent/tweet?url=' + url + '&text=' + title,
    linkedin: 'https://www.linkedin.com/sharing/share-offsite/?url=' + url,
    whatsapp: 'https://wa.me/?text=' + title + '%20' + url,
    email: 'mailto:?subject=' + title + '&body=' + url
  };
  document.querySelectorAll('[data-share]').forEach(function (a) { a.href = map[a.dataset.share] || '#'; });

  var copy = document.querySelector('[data-copy]');
  if (copy) copy.addEventListener('click', function () {
    var done = function () { copy.setAttribute('data-copied', ''); setTimeout(function () { copy.removeAttribute('data-copied'); }, 1800); };
    if (navigator.clipboard) navigator.clipboard.writeText(location.href).then(done, done); else done();
  });
  var pr = document.querySelector('[data-print]');
  if (pr) pr.addEventListener('click', function () { window.print(); });

  // Reading time
  var rt = document.querySelector('[data-readtime]');
  if (rt) rt.textContent = Math.max(1, Math.round(body.textContent.trim().split(/\s+/).length / 200)) + ' min read';
})();

// Keep "Blog" highlighted in the navbar while reading an article
document.addEventListener('DOMContentLoaded', function () {
  setTimeout(function () {
    var a = document.querySelector('.nav-links a[href="blog.html"]');
    if (a) { a.classList.add('is-active'); a.setAttribute('aria-current', 'page'); }
  }, 0);
});
