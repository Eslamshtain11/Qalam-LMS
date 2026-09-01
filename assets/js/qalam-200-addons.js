(() => {
  'use strict';
  const cfg = window.Qalam200 || {};
  const all = (s, r = document) => Array.from(r.querySelectorAll(s));
  const toast = (m, e) => window.tutor_toast ? window.tutor_toast('', m, e ? 'error' : 'success') : window.alert(m);
  async function post(body) {
    const res = await fetch(cfg.ajaxUrl, {method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:new URLSearchParams(body).toString()});
    return res.json();
  }
  function init() {
    all('[data-qalam-product-toggle]').forEach((btn) => btn.addEventListener('click', async () => {
      if (btn.disabled) return;
      const enable = btn.dataset.enable === '1';
      const old = btn.textContent;
      btn.disabled = true;
      btn.textContent = enable ? 'جاري التفعيل...' : 'جاري التعطيل...';
      try {
        const json = await post({action:'qalam_200_toggle_product',nonce:cfg.nonce,feature:btn.dataset.feature,enable:enable?'1':'0'});
        if (!json || !json.success) throw new Error(json?.data?.message || cfg.toggleFailed || 'تعذر الحفظ.');
        toast(json.data?.message || 'تم الحفظ.', false);
        location.reload();
      } catch (err) {
        toast(err.message || cfg.toggleFailed || 'تعذر الحفظ.', true);
        btn.disabled = false; btn.textContent = old;
      }
    }));
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
