(() => {
  'use strict';

  const cfg = window.Qalam080 || {};
  const qs = (s, root = document) => root.querySelector(s);
  const qsa = (s, root = document) => Array.from(root.querySelectorAll(s));

  const esc = (value) => String(value ?? '').replace(/[&<>'"]/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
  }[c]));

  function messageFromPayload(payload, fallback = 'حصل خطأ غير متوقع.') {
    if (!payload) return fallback;
    if (typeof payload === 'string') return payload;
    if (payload.data && typeof payload.data.message === 'string') return payload.data.message;
    if (typeof payload.message === 'string') return payload.message;
    return fallback;
  }

  function sleep(ms) { return new Promise((resolve) => setTimeout(resolve, ms)); }

  async function postFormData(formData, attempts = 2) {
    let lastError;
    for (let i = 0; i < attempts; i++) {
      try {
        const res = await fetch(cfg.ajaxUrl, {
          method: 'POST',
          credentials: 'same-origin',
          body: formData,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const text = await res.text();
        let json;
        try { json = JSON.parse(text); } catch (e) {
          throw new Error(res.status === 504 ? 'الخادم أنهى الدفعة قبل اكتمالها. هنحاول الدفعة تاني تلقائيًا.' : `استجابة غير متوقعة من الخادم (${res.status}).`);
        }
        if (!res.ok || !json.success) {
          throw new Error(messageFromPayload(json, `تعذر إكمال الطلب (${res.status}).`));
        }
        return json.data || {};
      } catch (e) {
        lastError = e;
        if (i + 1 < attempts) await sleep(1200 * (i + 1));
      }
    }
    throw lastError || new Error('تعذر الاتصال بالخادم.');
  }

  function createProgressUI(form) {
    let box = form.parentElement.querySelector('.qalam-080-progress');
    if (box) return box;
    box = document.createElement('div');
    box.className = 'qalam-080-progress';
    box.hidden = true;
    box.innerHTML = `
      <div class="qalam-080-progress-head">
        <div>
          <strong>جاري إنشاء الأسئلة</strong>
          <span data-q080-status>بنجهز المهمة...</span>
        </div>
        <strong data-q080-percent>0%</strong>
      </div>
      <div class="qalam-080-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
        <span data-q080-bar></span>
      </div>
      <div class="qalam-080-progress-stats">
        <span>تم: <b data-q080-created>0</b> / <b data-q080-total>0</b></span>
        <span>مرفوض أثناء الفحص: <b data-q080-rejected>0</b></span>
      </div>
      <div class="qalam-080-progress-message" data-q080-message></div>`;
    form.parentElement.insertBefore(box, form);
    return box;
  }

  function updateProgress(box, data = {}) {
    const total = Number(data.total || 0);
    const created = Number(data.created || 0);
    const rejected = Number(data.rejected || 0);
    const percent = total ? Math.min(100, Math.round((created / total) * 100)) : 0;
    const track = qs('.qalam-080-progress-track', box);
    qs('[data-q080-bar]', box).style.width = `${percent}%`;
    qs('[data-q080-percent]', box).textContent = `${percent}%`;
    qs('[data-q080-created]', box).textContent = String(created);
    qs('[data-q080-total]', box).textContent = String(total);
    qs('[data-q080-rejected]', box).textContent = String(rejected);
    if (track) track.setAttribute('aria-valuenow', String(percent));
    const message = data.message || (data.done ? 'اكتمل إنشاء الأسئلة.' : 'جاري إنشاء دفعة جديدة...');
    qs('[data-q080-message]', box).textContent = message;
    qs('[data-q080-status]', box).textContent = data.done
      ? 'اكتمل التوليد والحفظ'
      : data.paused ? 'التوليد متوقف مؤقتًا' : `باقي ${Math.max(0, Number(data.remaining ?? total - created))} سؤال`;
  }

  function setGeneratorBusy(form, busy) {
    qsa('button, input, select, textarea', form).forEach((el) => {
      if (el.type === 'hidden') return;
      if (!busy && el.dataset.q080WasDisabled === '1') {
        el.disabled = true;
        delete el.dataset.q080WasDisabled;
      } else if (!busy) {
        el.disabled = false;
      } else {
        if (el.disabled) el.dataset.q080WasDisabled = '1';
        el.disabled = true;
      }
    });
  }

  async function processGeneration(form, box, jobId, initialTotal) {
    box.hidden = false;
    box.dataset.jobId = jobId;
    updateProgress(box, { total: initialTotal, created: Number(box.dataset.created || 0), rejected: Number(box.dataset.rejected || 0), message: 'بدأت المهمة في الخلفية. الصفحة هتتابع التقدم تلقائيًا من غير ما تستنى استجابة الذكاء الاصطناعي.' });
    let consecutiveErrors = 0;
    while (true) {
      await sleep(1800);
      const fd = new FormData();
      fd.set('action', 'qalam_080_process_generation');
      fd.set('nonce', cfg.processNonce || '');
      fd.set('job_id', jobId);
      try {
        const data = await postFormData(fd, 2);
        consecutiveErrors = 0;
        box.dataset.created = String(data.created || 0);
        box.dataset.rejected = String(data.rejected || 0);
        updateProgress(box, data);
        if (data.done) {
          setGeneratorBusy(form, false);
          delete form.dataset.q080Running;
          setTimeout(() => window.location.reload(), 900);
          return;
        }
        if (data.failed || data.paused) {
          setGeneratorBusy(form, false);
          delete form.dataset.q080Running;
          return;
        }
      } catch (e) {
        consecutiveErrors++;
        qs('[data-q080-status]', box).textContent = 'جاري إعادة الاتصال';
        qs('[data-q080-message]', box).textContent = `فقدنا اتصال المتابعة مؤقتًا (${e.message})، لكن التوليد الخلفي مستمر. هنحاول تاني تلقائيًا.`;
        if (consecutiveErrors >= 30) {
          qs('[data-q080-status]', box).textContent = 'المهمة محفوظة وتنتظر استجابة الخادم';
          qs('[data-q080-message]', box).textContent = 'التوليد لا يتم داخل الصفحة نفسها. تقدر تسيب الصفحة مفتوحة؛ هنواصل محاولة قراءة التقدم تلقائيًا.';
          consecutiveErrors = 0;
        }
      }
    }
  }

  function initAsyncGenerator(form) {
    const box = createProgressUI(form);
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (form.dataset.q080Running === '1') return;
      const total = qsa('[data-qalam-question-count]', form).reduce((sum, input) => sum + Math.max(0, Number(input.value || 0)), 0);
      if (!total) { window.alert('حدد عدد سؤال واحد على الأقل.'); return; }
      // IMPORTANT: snapshot the form before disabling its controls. Disabled HTML inputs
      // are intentionally omitted from FormData, which previously made type_counts and
      // pdf_file disappear and the server saw the requested question total as zero.
      const fd = new FormData(form);
      fd.set('action', 'qalam_080_start_generation');
      form.dataset.q080Running = '1';
      setGeneratorBusy(form, true);
      box.hidden = false;
      updateProgress(box, { total, created: 0, rejected: 0, message: 'بنرفع المصدر ونجهز مهمة التوليد...' });
      try {
        const start = await postFormData(fd, 2);
        box.dataset.created = '0';
        box.dataset.rejected = '0';
        await processGeneration(form, box, start.job_id, start.total || total);
      } catch (e) {
        qs('[data-q080-status]', box).textContent = 'تعذر بدء التوليد';
        qs('[data-q080-message]', box).textContent = e.message;
        setGeneratorBusy(form, false);
        delete form.dataset.q080Running;
      }
    });
  }

  function addStudentPreviewButtons() {
    qsa('.qalam-question-main .qalam-050-table tbody tr').forEach((row) => {
      if (row.dataset.q080PreviewAdded === '1') return;
      const hiddenId = qs('input[name="question_id"]', row);
      const firstCell = qs('td', row);
      if (!hiddenId || !firstCell || !cfg.previewBase) return;
      const actions = document.createElement('div');
      actions.className = 'qalam-080-row-actions';
      actions.innerHTML = `<a class="button button-small qalam-080-preview" target="_blank" rel="noopener" href="${esc(cfg.previewBase + encodeURIComponent(hiddenId.value))}">معاينة الطالب</a>`;
      firstCell.appendChild(actions);
      row.dataset.q080PreviewAdded = '1';
    });
  }

  function flattenCategories(categories) {
    const byParent = new Map();
    (categories || []).forEach((c) => {
      const p = Number(c.parent || 0);
      if (!byParent.has(p)) byParent.set(p, []);
      byParent.get(p).push(c);
    });
    const out = [];
    const walk = (parent, depth) => {
      (byParent.get(parent) || []).sort((a, b) => String(a.name).localeCompare(String(b.name), 'ar')).forEach((c) => {
        out.push({ ...c, depth });
        walk(Number(c.id), depth + 1);
      });
    };
    walk(0, 0);
    return out;
  }

  function categoryOptions(selected = 0) {
    const cats = flattenCategories(cfg.categories || []);
    return `<option value="0">كل التصنيفات</option>` + cats.map((c) => {
      const prefix = '— '.repeat(Math.max(0, Number(c.depth || 0)));
      return `<option value="${Number(c.id)}" ${Number(selected) === Number(c.id) ? 'selected' : ''}>${esc(prefix + c.name)}</option>`;
    }).join('');
  }

  function difficultyOptions(selected = 'any') {
    const options = [['any','كل المستويات'],['easy','سهل'],['medium','متوسط'],['hard','صعب']];
    return options.map(([value,label]) => `<option value="${value}" ${selected === value ? 'selected' : ''}>${label}</option>`).join('');
  }

  function injectQuizTools() {
    const quizId = Number(cfg.quizId || 0);
    if (!quizId || !cfg.quizToolsNonce) return;
    const pickerSection = qsa('.qalam-050-panel').find((section) => qs('.qalam-bank-picker', section));
    if (!pickerSection || document.getElementById('qalam-080-quiz-tools')) return;
    const rules = cfg.dynamicRules || {};
    if (!cfg.randomizedFeatureEnabled && !cfg.dynamicFeatureEnabled) return;
    const panel = document.createElement('section');
    panel.id = 'qalam-080-quiz-tools';
    panel.className = 'qalam-050-panel qalam-080-quiz-tools';
    const randomCard = cfg.randomizedFeatureEnabled ? `
        <form method="post" action="${esc(cfg.adminPost)}" class="qalam-080-tool-card">
          <input type="hidden" name="action" value="qalam_080_random_fill_quiz">
          <input type="hidden" name="quiz_id" value="${quizId}">
          <input type="hidden" name="qalam_080_quiz_nonce" value="${esc(cfg.quizToolsNonce)}">
          <h3>اختيار عشوائي ثابت</h3>
          <p>تختار قلم الأسئلة مرة واحدة وتضيفها للاختبار الحالي.</p>
          <label><span>التصنيف / الدرس</span><select name="category_id">${categoryOptions(0)}</select></label>
          <label><span>عدد الأسئلة</span><input name="question_count" type="number" min="1" max="100" value="10"></label>
          <label><span>المستوى</span><select name="difficulty">${difficultyOptions('any')}</select></label>
          <button class="button button-primary" type="submit">اختيار وإضافة عشوائيًا</button>
        </form>` : '';
    const dynamicCard = cfg.dynamicFeatureEnabled ? `
        <form method="post" action="${esc(cfg.adminPost)}" class="qalam-080-tool-card is-dynamic">
          <input type="hidden" name="action" value="qalam_080_save_dynamic_rules">
          <input type="hidden" name="quiz_id" value="${quizId}">
          <input type="hidden" name="qalam_080_quiz_nonce" value="${esc(cfg.quizToolsNonce)}">
          <div class="qalam-080-dynamic-title"><div><h3>امتحان ديناميكي</h3><span>جديد</span></div><label class="qalam-080-switch"><input type="checkbox" name="dynamic_enabled" value="1" ${cfg.dynamicEnabled ? 'checked' : ''}><i></i></label></div>
          <p>كل محاولة بتاخد نسخة حقيقية مستقلة بأسئلة عشوائية من البنك، مع أولوية للأسئلة اللي الطالب ماشفهاش والأقل استخدامًا عند باقي الطلاب.</p>
          <label><span>التصنيف / الدرس</span><select name="category_id">${categoryOptions(Number(rules.category_id || 0))}</select></label>
          <label><span>عدد الأسئلة لكل محاولة</span><input name="question_count" type="number" min="1" max="100" value="${Number(rules.question_count || 10)}"></label>
          <label><span>المستوى</span><select name="difficulty">${difficultyOptions(String(rules.difficulty || 'any'))}</select></label>
          <small>لو البنك خلصت منه الأسئلة الجديدة، قلم هتعيد استخدام الأقل ظهورًا. لذلك عدم التكرار مضمون بقدر حجم البنك المتاح.</small>
          <button class="button button-primary" type="submit">حفظ إعدادات الامتحان الديناميكي</button>
        </form>` : '';
    panel.innerHTML = `
      <div class="qalam-050-section-head"><div><h2>اختيار الأسئلة تلقائيًا</h2><p>بدل تحديد الأسئلة واحدة واحدة، اختار التصنيف والعدد والمستوى وخلي قلم تختار من البنك.</p></div></div>
      <div class="qalam-080-tool-grid">${randomCard}${dynamicCard}</div>`;
    pickerSection.parentNode.insertBefore(panel, pickerSection);
    return;
/* legacy template retained below for source parity; unreachable after the feature-aware render above. */
    panel.innerHTML = `
      <div class="qalam-050-section-head"><div><h2>اختيار الأسئلة تلقائيًا</h2><p>بدل تحديد الأسئلة واحدة واحدة، اختار التصنيف والعدد والمستوى وخلي قلم تختار من البنك.</p></div></div>
      <div class="qalam-080-tool-grid">
        <form method="post" action="${esc(cfg.adminPost)}" class="qalam-080-tool-card">
          <input type="hidden" name="action" value="qalam_080_random_fill_quiz">
          <input type="hidden" name="quiz_id" value="${quizId}">
          <input type="hidden" name="qalam_080_quiz_nonce" value="${esc(cfg.quizToolsNonce)}">
          <h3>اختيار عشوائي ثابت</h3>
          <p>تختار قلم الأسئلة مرة واحدة وتضيفها للاختبار الحالي.</p>
          <label><span>التصنيف / الدرس</span><select name="category_id">${categoryOptions(0)}</select></label>
          <label><span>عدد الأسئلة</span><input name="question_count" type="number" min="1" max="100" value="10"></label>
          <label><span>المستوى</span><select name="difficulty">${difficultyOptions('any')}</select></label>
          <button class="button button-primary" type="submit">اختيار وإضافة عشوائيًا</button>
        </form>
        <form method="post" action="${esc(cfg.adminPost)}" class="qalam-080-tool-card is-dynamic">
          <input type="hidden" name="action" value="qalam_080_save_dynamic_rules">
          <input type="hidden" name="quiz_id" value="${quizId}">
          <input type="hidden" name="qalam_080_quiz_nonce" value="${esc(cfg.quizToolsNonce)}">
          <div class="qalam-080-dynamic-title"><div><h3>امتحان ديناميكي</h3><span>جديد</span></div><label class="qalam-080-switch"><input type="checkbox" name="dynamic_enabled" value="1" ${cfg.dynamicEnabled ? 'checked' : ''}><i></i></label></div>
          <p>كل محاولة بتاخد نسخة حقيقية مستقلة بأسئلة عشوائية من البنك، مع أولوية للأسئلة اللي الطالب ماشفهاش والأقل استخدامًا عند باقي الطلاب.</p>
          <label><span>التصنيف / الدرس</span><select name="category_id">${categoryOptions(Number(rules.category_id || 0))}</select></label>
          <label><span>عدد الأسئلة لكل محاولة</span><input name="question_count" type="number" min="1" max="100" value="${Number(rules.question_count || 10)}"></label>
          <label><span>المستوى</span><select name="difficulty">${difficultyOptions(String(rules.difficulty || 'any'))}</select></label>
          <small>لو البنك خلصت منه الأسئلة الجديدة، قلم هتعيد استخدام الأقل ظهورًا. لذلك عدم التكرار مضمون بقدر حجم البنك المتاح.</small>
          <button class="button button-primary" type="submit">حفظ إعدادات الامتحان الديناميكي</button>
        </form>
      </div>`;
    pickerSection.parentNode.insertBefore(panel, pickerSection);
  }

  function init() {
    qsa('.qalam-ai-question-form').forEach(initAsyncGenerator);
    addStudentPreviewButtons();
    injectQuizTools();
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
