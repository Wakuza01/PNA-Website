(function () {
  // Don't track admin users (if they have a session cookie named pa_admin)
  if (document.cookie.indexOf('pa_admin') !== -1) return;
  fetch('/admin/track.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'path=' + encodeURIComponent(window.location.pathname) +
          '&referrer=' + encodeURIComponent(document.referrer),
    keepalive: true
  }).catch(function () {});
})();
