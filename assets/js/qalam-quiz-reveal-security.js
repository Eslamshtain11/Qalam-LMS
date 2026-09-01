(function () {
  'use strict';
  var cfg = window.QalamQuizReveal || {};
  var supported = ['true_false', 'single_choice', 'multiple_choice'];
  var busy = false;

  function reverseJson(ids) {
    return JSON.stringify(ids || []).split('').reverse().join('');
  }
  function wrapQuestion(el) { return el && el.closest ? el.closest('.quiz-attempt-single-question') : null; }
  function questionId(wrap) {
    var m = wrap && String(wrap.id || '').match(/(\d+)$/); return m ? parseInt(m[1], 10) : 0;
  }
  function isSupported(wrap) { return wrap && supported.indexOf(String(wrap.dataset.questionType || '')) !== -1; }
  function selectedValues(wrap) {
    if (!wrap) return [];
    return Array.prototype.map.call(wrap.querySelectorAll('input[type="radio"]:checked,input[type="checkbox"]:checked'), function (i) { return parseInt(i.value, 10) || 0; }).filter(Boolean);
  }
  function lockQuestion(wrap) {
    if (!wrap) return;
    wrap.dataset.qalamRevealCommitted = '1';
    Array.prototype.forEach.call(wrap.querySelectorAll('input[type="radio"],input[type="checkbox"]'), function (i) { i.disabled = true; });
  }
  function initialLock() {
    (cfg.committedQuestions || []).forEach(function (qid) {
      qid = parseInt(qid, 10);
      var wrap = document.getElementById('quiz-attempt-single-question-' + qid);
      var selected = (cfg.committedSelections && cfg.committedSelections[qid]) || [];
      selected.forEach(function (answerId) {
        var input = wrap && wrap.querySelector('input[type=\"radio\"][value=\"' + parseInt(answerId, 10) + '\"],input[type=\"checkbox\"][value=\"' + parseInt(answerId, 10) + '\"]');
        if (input) input.checked = true;
      });
      lockQuestion(wrap);
    });
  }
  function commit(responses) {
    var body = new URLSearchParams();
    body.set('action', 'qalam_quiz_reveal_commit');
    body.set('nonce', cfg.nonce || '');
    body.set('attempt_id', cfg.attemptId || 0);
    body.set('quiz_id', cfg.quizId || 0);
    body.set('responses', JSON.stringify(responses || {}));
    return fetch(cfg.ajaxurl, { method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'}, body: body.toString() })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (json) { if (!json || !json.success) throw new Error((json && json.data && json.data.message) || 'تعذر تأمين كشف الإجابة.'); return json.data || {}; });
  }
  function replayClick(target, data) {
    window.tutor_quiz_context = reverseJson(data.correct_ids || []);
    (data.committed_questions || []).forEach(function (qid) { lockQuestion(document.getElementById('quiz-attempt-single-question-' + parseInt(qid, 10))); });
    target.dataset.qalamRevealReady = '1';
    target.click();
  }
  function collectForTarget(target) {
    var responses = {};
    var below = window._tutorobject && window._tutorobject.quiz_options && String(window._tutorobject.quiz_options.question_layout_view) === 'question_below_each_other';
    var finalButton = target.classList.contains('tutor-quiz-submit-btn') || target.getAttribute('name') === 'quiz_answer_submit_btn';
    if (below && finalButton) {
      Array.prototype.forEach.call(document.querySelectorAll('.quiz-attempt-single-question'), function (wrap) {
        if (isSupported(wrap) && wrap.dataset.qalamRevealCommitted !== '1') responses[questionId(wrap)] = selectedValues(wrap);
      });
    } else {
      var wrap = wrapQuestion(target);
      if (isSupported(wrap) && wrap.dataset.qalamRevealCommitted !== '1') responses[questionId(wrap)] = selectedValues(wrap);
    }
    return responses;
  }
  function revealEnabled() {
    return !!(window._tutorobject && window._tutorobject.quiz_options && Number(window._tutorobject.quiz_options.enable_answer_reveal || 0) === 1);
  }
  document.addEventListener('click', function (ev) {
    if (!revealEnabled() || busy) return;
    var target = ev.target && ev.target.closest ? ev.target.closest('.tutor-quiz-answer-next-btn,.tutor-quiz-submit-btn,button[name="quiz_answer_submit_btn"]') : null;
    if (!target) return;
    if (target.dataset.qalamRevealReady === '1') { delete target.dataset.qalamRevealReady; return; }
    var responses = collectForTarget(target);
    if (!Object.keys(responses).length) return;
    ev.preventDefault(); ev.stopPropagation(); ev.stopImmediatePropagation(); busy = true;
    commit(responses).then(function (data) { replayClick(target, data); }).catch(function (err) {
      window.alert(err && err.message ? err.message : 'تعذر تأمين كشف الإجابة. حاول مرة أخرى.');
    }).finally(function () { busy = false; });
  }, true);
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialLock); else initialLock();
})();
