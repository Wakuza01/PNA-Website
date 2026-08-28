// ── Sidebar mobile toggle ──
const toggle = document.getElementById('sidebar-toggle');
const sidebar = document.querySelector('.sidebar');
const overlay = document.querySelector('.sidebar-overlay');

if (toggle && sidebar) {
  toggle.addEventListener('click', () => {
    sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('visible');
  });
}

if (overlay && sidebar) {
  overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('visible');
  });
}

// ── Auto-dismiss alerts after 4s ──
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => { el.style.opacity = '0'; }, 4000);
  setTimeout(() => { el.remove(); }, 4400);
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
