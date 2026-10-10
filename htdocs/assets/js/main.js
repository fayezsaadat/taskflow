/* ============================================================
   TaskFlow — client-side JavaScript (premium)
   ============================================================ */
(function () {
  'use strict';

  const $  = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
  const esc = s => { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; };

  /* ---------- Theme ---------- */
  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    const meta = $('meta[name=theme-color]'); if (meta) meta.content = theme === 'dark' ? '#0a0f1f' : '#6366f1';
  }
  applyTheme(document.documentElement.getAttribute('data-theme') || 'light');

  $('#themeToggle')?.addEventListener('click', function () {
    const cur = document.documentElement.getAttribute('data-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    fetch('api/user_save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': window.CSRF_TOKEN },
      body: 'theme=' + next
    }).catch(() => {});
  });

  /* ---------- Sidebar (mobile) ---------- */
  const sidebar = $('#sidebar'), backdrop = $('#sidebarBackdrop');
  $('#menuToggle')?.addEventListener('click', () => { sidebar?.classList.add('is-open'); backdrop?.classList.add('is-open'); });
  function closeSidebar() { sidebar?.classList.remove('is-open'); backdrop?.classList.remove('is-open'); }
  $('#sidebarClose')?.addEventListener('click', closeSidebar);
  backdrop?.addEventListener('click', closeSidebar);

  /* ---------- User dropdown ---------- */
  const userBtn = $('#userMenuBtn'), userDrop = $('#userDropdown');
  userBtn?.addEventListener('click', e => { e.stopPropagation(); userDrop.classList.toggle('is-open'); });
  document.addEventListener('click', () => userDrop?.classList.remove('is-open'));
  userDrop?.addEventListener('click', e => e.stopPropagation());

  /* ---------- Toasts (with icons) ---------- */
  const TOAST_ICONS = {
    success: '<svg viewBox="0 0 24 24" class="ic"><path d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z"/></svg>',
    error:   '<svg viewBox="0 0 24 24" class="ic"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm5 13.6L15.6 17 12 13.4 8.4 17 7 15.6 10.6 12 7 8.4 8.4 7 12 10.6 15.6 7 17 8.4 13.4 12 17 15.6z"/></svg>',
    warning: '<svg viewBox="0 0 24 24" class="ic"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
    info:    '<svg viewBox="0 0 24 24" class="ic"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>'
  };
  window.toast = function (msg, type = 'info', title = '') {
    const wrap = $('#toastWrap'); if (!wrap) { return; }
    const el = document.createElement('div');
    el.className = 'toast toast--' + type;
    el.innerHTML =
      '<div class="toast__icon">' + (TOAST_ICONS[type] || TOAST_ICONS.info) + '</div>' +
      '<div>' + (title ? '<strong>' + esc(title) + '</strong>' : '') + esc(msg) + '</div>';
    wrap.appendChild(el);
    setTimeout(() => { el.style.transition = 'all .3s'; el.style.opacity = '0'; el.style.transform = 'translateX(50px)'; setTimeout(() => el.remove(), 300); }, 3400);
  };

  /* ---------- Confirm dialog ---------- */
  let confirmCb = null;
  window.confirmDialog = function (opts) {
    const o = Object.assign({ title: 'Confirm', message: 'Are you sure?', okText: 'Delete', okClass: 'btn--danger' }, opts);
    $('#confirmTitle').textContent = o.title;
    $('#confirmMsg').textContent = o.message;
    const okBtn = $('#confirmOk');
    okBtn.textContent = o.okText;
    okBtn.className = 'btn ' + o.okClass;
    $('#confirmOverlay').classList.add('is-open');
    confirmCb = o.onConfirm || null;
  };
  $('#confirmCancel')?.addEventListener('click', () => { $('#confirmOverlay').classList.remove('is-open'); confirmCb = null; });
  $('#confirmOk')?.addEventListener('click', () => { $('#confirmOverlay').classList.remove('is-open'); if (typeof confirmCb === 'function') confirmCb(); confirmCb = null; });
  $('#confirmOverlay')?.addEventListener('click', function (e) { if (e.target === this) { this.classList.remove('is-open'); confirmCb = null; } });

  /* ---------- Generic delete handler ---------- */
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-delete-url]');
    if (!btn) return;
    e.preventDefault();
    const idInput = btn.querySelector('input[name=id]');
    confirmDialog({
      title: btn.dataset.confirmTitle || 'Delete',
      message: btn.dataset.confirmMsg || 'Are you sure you want to delete this item? This cannot be undone.',
      onConfirm: function () {
        const body = idInput ? 'id=' + idInput.value : '';
        fetch(btn.dataset.deleteUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': window.CSRF_TOKEN },
          body: body
        }).then(r => r.json()).then(res => {
          if (res.success) {
            toast(res.message || 'Deleted', 'success');
            const row = btn.closest('[data-row]');
            if (row) { row.style.transition = 'all .25s'; row.style.opacity = '0'; row.style.transform = 'scale(.95)'; setTimeout(() => { row.remove(); if (res.reload) location.reload(); }, 250); }
            else if (res.redirect) setTimeout(() => location.href = res.redirect, 500);
            else if (res.reload) setTimeout(() => location.reload(), 700);
          } else { toast(res.message || 'Failed', 'error'); }
        }).catch(() => toast('Network error', 'error'));
      }
    });
  });

  /* ---------- Modal helpers ---------- */
  window.openModal = function (id) { $('#' + id)?.classList.add('is-open'); document.body.style.overflow = 'hidden'; };
  window.closeModal = function (id) { $('#' + id)?.classList.remove('is-open'); document.body.style.overflow = ''; };
  document.addEventListener('click', function (e) {
    if (e.target.classList?.contains('modal-overlay')) { e.target.classList.remove('is-open'); document.body.style.overflow = ''; }
    const closer = e.target.closest('[data-close-modal]');
    if (closer) { closer.closest('.modal-overlay')?.classList.remove('is-open'); document.body.style.overflow = ''; }
    const opener = e.target.closest('[data-open-modal]');
    if (opener) { $('#' + opener.getAttribute('data-open-modal'))?.classList.add('is-open'); document.body.style.overflow = 'hidden'; }
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') $$('.modal-overlay.is-open').forEach(m => { m.classList.remove('is-open'); document.body.style.overflow = ''; });
  });

  /* ---------- AJAX forms ---------- */
  $$('form[data-ajax]').forEach(form => {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();
      const btn = form.querySelector('[type=submit]');
      const orig = btn?.innerHTML;
      if (btn) { btn.disabled = true; btn.dataset.origHtml = orig; btn.innerHTML = '<span class="spinner"></span> Saving…'; }
      try {
        const fd = new FormData(form);
        fd.append('csrf_token', window.CSRF_TOKEN);
        const res = await fetch(form.action || location.href, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          toast(data.message || 'Saved', 'success');
          if (data.redirect) setTimeout(() => location.href = data.redirect, 600);
          else if (form.dataset.resetOnSuccess !== undefined) form.reset();
          if (data.modalClose) closeModal(data.modalClose);
          if (data.reload) setTimeout(() => location.reload(), 600);
        } else { toast(data.message || 'Could not save', 'error'); }
      } catch (err) { toast('Network error', 'error'); }
      finally { if (btn) { btn.disabled = false; btn.innerHTML = orig; } }
    });
  });

  /* ---------- Task status quick change ---------- */
  document.addEventListener('change', function (e) {
    const sel = e.target.closest('[data-task-status]');
    if (!sel) return;
    fetch('api/task_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': window.CSRF_TOKEN },
      body: 'task_id=' + sel.dataset.taskStatus + '&status=' + sel.value
    }).then(r => r.json()).then(res => {
      if (res.success) { toast('Task updated', 'success'); if (res.reload) setTimeout(() => location.reload(), 700); }
      else toast(res.message || 'Failed', 'error');
    }).catch(() => toast('Network error', 'error'));
  });

  /* ---------- Progress slider live label ---------- */
  $$('input[data-progress-input]').forEach(input => {
    const out = $(input.dataset.progressInput);
    if (out) input.addEventListener('input', () => out.textContent = input.value + '%');
  });

  /* ---------- Global search ---------- */
  const searchInput = $('#globalSearch'), searchResults = $('#searchResults');
  let searchTimer = null;
  searchInput?.addEventListener('input', function () {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if (q.length < 2) { searchResults.classList.remove('is-open'); return; }
    searchTimer = setTimeout(() => {
      fetch('api/search.php?q=' + encodeURIComponent(q), { headers: { 'X-CSRF-Token': window.CSRF_TOKEN } })
        .then(r => r.json()).then(data => {
          searchResults.innerHTML = data.html || '<div class="search-results__head">No results</div>';
          searchResults.classList.add('is-open');
        }).catch(() => {});
    }, 220);
  });
  document.addEventListener('click', e => { if (!e.target.closest('.navbar__search')) searchResults?.classList.remove('is-open'); });

  /* "/" focuses search */
  document.addEventListener('keydown', e => {
    if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
      e.preventDefault(); searchInput?.focus();
    }
  });

  /* ---------- Animated number counters ---------- */
  window.animateCounters = function () {
    $$('[data-count]').forEach(el => {
      if (el.dataset.counted) return;
      const target = parseFloat(el.dataset.count);
      const decimals = (el.dataset.decimals | 0);
      const prefix = el.dataset.prefix || '';
      const suffix = el.dataset.suffix || '';
      const dur = 900; const start = performance.now();
      function tick(now) {
        const p = Math.min(1, (now - start) / dur);
        const eased = 1 - Math.pow(1 - p, 3);
        const val = (target * eased).toFixed(decimals);
        el.textContent = prefix + Number(val).toLocaleString() + suffix;
        if (p < 1) requestAnimationFrame(tick); else el.dataset.counted = '1';
      }
      const io = new IntersectionObserver(entries => {
        entries.forEach(en => { if (en.isIntersecting) { requestAnimationFrame(tick); io.disconnect(); } });
      }, { threshold: 0.4 });
      io.observe(el);
    });
  };
  animateCounters();

  /* ---------- Auto-dismiss alerts ---------- */
  $$('.alert').forEach(a => setTimeout(() => { a.style.transition = 'all .4s'; a.style.opacity = '0'; a.style.transform = 'translateY(-6px)'; setTimeout(() => a.remove(), 400); }, 5500));

  /* ---------- Password show/hide ---------- */
  $$('.toggle-pass').forEach(btn => btn.addEventListener('click', function () {
    const inp = this.parentElement.querySelector('input');
    inp.type = inp.type === 'password' ? 'text' : 'password';
    this.dataset.shown = inp.type === 'text' ? '1' : '';
  }));

})();
