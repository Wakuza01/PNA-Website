// ── Sidebar mobile toggle ──
const toggle  = document.getElementById('sidebar-toggle');
const sidebar = document.querySelector('.sidebar');
const overlay = document.getElementById('sidebar-overlay');

function openSidebar() {
  sidebar.classList.add('open');
  if (overlay) overlay.classList.add('visible');
  document.body.style.overflow = 'hidden';
}

function closeSidebar() {
  sidebar.classList.remove('open');
  if (overlay) overlay.classList.remove('visible');
  document.body.style.overflow = '';
}

if (toggle && sidebar) {
  toggle.addEventListener('click', () => {
    sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
  });
}

if (overlay) {
  overlay.addEventListener('click', closeSidebar);
}

// Close sidebar on nav link click (mobile)
if (sidebar) {
  sidebar.querySelectorAll('.sidebar-nav a').forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth <= 768) closeSidebar();
    });
  });
}

// ── Auto-dismiss alerts after 4s ──
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => {
    el.style.transition = 'opacity 0.4s ease, transform 0.4s ease, margin 0.4s ease, padding 0.4s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateX(-8px)';
  }, 4000);
  setTimeout(() => el.remove(), 4400);
});

// ── Slug auto-generation from title ──
const titleInput = document.getElementById('post-title');
const slugInput  = document.getElementById('post-slug');
let slugManuallyEdited = false;

if (slugInput) {
  slugInput.addEventListener('input', () => { slugManuallyEdited = true; });
}

if (titleInput && slugInput) {
  titleInput.addEventListener('input', () => {
    if (!slugManuallyEdited) {
      slugInput.value = titleInput.value
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');
    }
  });
}

// ── Delete confirm ──
document.querySelectorAll('[data-confirm]').forEach(el => {
  el.addEventListener('click', e => {
    if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
  });
});

// ── Stat counter animation ──
document.querySelectorAll('.stat-num').forEach(el => {
  const target = parseInt(el.textContent.trim(), 10);
  if (isNaN(target) || target === 0) return;
  el.textContent = '0';
  const duration = 800;
  const start = performance.now();
  function step(now) {
    const progress = Math.min((now - start) / duration, 1);
    const ease = 1 - Math.pow(1 - progress, 3); // ease-out cubic
    el.textContent = Math.round(ease * target).toLocaleString();
    if (progress < 1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
});
