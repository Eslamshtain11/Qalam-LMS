(() => {
  'use strict';
  const cfg = window.Qalam180 || {};
  const qsa = (s, r = document) => Array.from(r.querySelectorAll(s));
  function toast(message, error) {
    if (window.tutor_toast) { window.tutor_toast('', message, error ? 'error' : 'success'); return; }
    window.alert(message);
  }
  async function post(body) {
    const res = await fetch(cfg.ajaxUrl, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams(body).toString()
    });
    return res.json();
  }
  function filterCards() {
    const search = document.querySelector('[data-qalam-feature-search]');
    const category = document.querySelector('[data-qalam-feature-category]');
    const q = (search && search.value ? search.value : '').trim().toLowerCase();
    const cat = category ? category.value : '';
    qsa('[data-qalam-feature-card]').forEach((card) => {
      const text = (card.getAttribute('data-qalam-search-text') || '').toLowerCase();
      const cardCat = card.getAttribute('data-category') || '';
      card.hidden = Boolean((q && !text.includes(q)) || (cat && cat !== cardCat));
    });
    qsa('[data-qalam-feature-section]').forEach((section) => {
      const visible = qsa('[data-qalam-feature-card]', section).some((card) => !card.hidden);
      section.hidden = !visible;
    });
  }
  function init() {
    const search = document.querySelector('[data-qalam-feature-search]');
    const category = document.querySelector('[data-qalam-feature-category]');
    if (search) search.addEventListener('input', filterCards);
    if (category) category.addEventListener('change', filterCards);
    qsa('[data-qalam-feature-toggle]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        if (btn.disabled) return;
        const enable = btn.getAttribute('data-enable') === '1';
        const old = btn.textContent;
        btn.disabled = true;
        btn.textContent = enable ? 'جاري التفعيل...' : 'جاري التعطيل...';
        try {
          const json = await post({ action: 'qalam_180_toggle_feature', nonce: cfg.nonce, feature: btn.getAttribute('data-feature'), enable: enable ? '1' : '0' });
          if (!json || !json.success) throw new Error((json && json.data && json.data.message) || cfg.toggleFailed || 'تعذر الحفظ.');
          toast((json.data && json.data.message) || 'تم الحفظ.', false);
          window.location.reload();
        } catch (e) {
          toast(e.message || cfg.toggleFailed || 'تعذر الحفظ.', true);
          btn.disabled = false;
          btn.textContent = old;
        }
      });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
