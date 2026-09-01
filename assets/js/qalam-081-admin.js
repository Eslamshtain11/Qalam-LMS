(() => {
  'use strict';
  const qs = (s, r = document) => r.querySelector(s);
  const qsa = (s, r = document) => Array.from(r.querySelectorAll(s));

  function initBankBulk() {
    qsa('[data-qalam-bank-bulk]').forEach((form) => {
      const all = qs('[data-qalam-select-all]', form);
      const boxes = () => qsa('[data-qalam-bank-check]', form);
      const counter = qs('[data-qalam-selected-count]', form);
      const deleteSelected = qs('[data-qalam-delete-selected]', form);
      const sync = () => {
        const list = boxes();
        const selected = list.filter((x) => x.checked).length;
        if (all) {
          all.checked = list.length > 0 && selected === list.length;
          all.indeterminate = selected > 0 && selected < list.length;
        }
        if (counter) counter.textContent = `تم تحديد ${selected} سؤال`;
        if (deleteSelected) deleteSelected.disabled = selected === 0;
      };
      if (all) all.addEventListener('change', () => { boxes().forEach((box) => { box.checked = all.checked; }); sync(); });
      boxes().forEach((box) => box.addEventListener('change', sync));
      sync();
    });
  }

  function initAccessPassword() {
    const checkbox = document.querySelector('input[name="require_password"]');
    const field = document.querySelector('.qalam-access-password');
    if (!checkbox || !field) return;
    const sync = () => field.classList.toggle('is-disabled', !checkbox.checked);
    checkbox.addEventListener('change', sync);
    sync();
  }

  function init() {
    initBankBulk();
    initAccessPassword();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
