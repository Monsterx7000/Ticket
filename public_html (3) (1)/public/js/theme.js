
// public/js/theme.js — Light/Dark mode controller
(function () {
  const KEY = 'theme';
  const body = document.body;

  function apply(theme) {
    body.classList.remove('theme-light','theme-dark');
    body.classList.add(theme);
  }

  // Init: default = light (as requested)
  const saved = localStorage.getItem(KEY);
  apply(saved === 'theme-dark' ? 'theme-dark' : 'theme-light');

  // Expose toggle for button
  window.toggleTheme = function () {
    const current = body.classList.contains('theme-dark') ? 'theme-dark' : 'theme-light';
    const next = current === 'theme-dark' ? 'theme-light' : 'theme-dark';
    apply(next);
    try { localStorage.setItem(KEY, next); } catch (e) {}
    // Update toggle icon/label if present
    const el = document.getElementById('themeToggleLabel');
    if (el) {
      el.textContent = (next === 'theme-dark') ? (el.dataset.darkLabel || 'Light') : (el.dataset.lightLabel || 'Dark');
    }
    const icon = document.getElementById('themeToggleIcon');
    if (icon) {
      icon.textContent = (next === 'theme-dark') ? '☀️' : '🌙';
    }
  };
})();
