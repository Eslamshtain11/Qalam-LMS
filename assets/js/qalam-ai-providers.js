/* Qalam LMS AI Provider UI 0.4.2 */
(function () {
    'use strict';

    var config = window.QalamAIConfig || {};
    var presets = config.presets || {};
    var initialProvider = config.provider || 'openai';
    var initialModel = config.model || ((presets[initialProvider] || {}).model || '');
    window.QalamAIProviderDraft = window.QalamAIProviderDraft || {
        provider: initialProvider,
        model: initialModel,
        base_url: config.base_url || ''
    };

    function el(tag, cls, text) {
        var node = document.createElement(tag);
        if (cls) node.className = cls;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function replaceText(root) {
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
        var replacements = [
            ['Set OpenAI API key', 'إعداد مزود الذكاء الاصطناعي'],
            ['OpenAI API key', 'مفتاح API للمزود'],
            ['Enter your OpenAI API key', 'أدخل مفتاح API للمزود المختار'],
            ['Enable OpenAI', 'تفعيل الذكاء الاصطناعي'],
            ['API is not connected', 'مزود الذكاء الاصطناعي غير متصل'],
            ['Please, ask your Admin to connect the API with Qalam LMS.', 'اطلب من مدير المنصة إعداد مزود الذكاء الاصطناعي في قلم.'],
            ['The page will reload after submission. Make sure to save the course information.', 'سيتم تحديث الصفحة بعد الحفظ. احفظ تغييرات الدورة أولًا.']
        ];
        var nodes = [];
        while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach(function (node) {
            var value = node.nodeValue || '';
            replacements.forEach(function (pair) { value = value.split(pair[0]).join(pair[1]); });
            if (value !== node.nodeValue) node.nodeValue = value;
        });
    }

    function providerDescription(provider) {
        var map = {
            openai: 'OpenAI — النماذج الأصلية عبر API الرسمي.',
            deepseek: 'DeepSeek — اتصال OpenAI-compatible عبر api.deepseek.com.',
            openrouter: 'OpenRouter — اختر أي Model ID مدعوم من كتالوج OpenRouter.',
            google: 'Google AI Studio — Gemini عبر واجهة OpenAI compatibility.',
            custom: 'مزود مخصص — أدخل Base URL متوافقًا مع OpenAI وModel ID.'
        };
        return map[provider] || '';
    }

    function enhance(dialog) {
        if (!dialog || dialog.dataset.qalamAiEnhanced === '1') return;
        var text = dialog.textContent || '';
        if (text.indexOf('OpenAI') === -1 && text.indexOf('مزود الذكاء الاصطناعي') === -1 && text.indexOf('API is not connected') === -1) return;
        var apiInput = dialog.querySelector('input[type="password"], input[placeholder*="OpenAI"], input[placeholder*="API key"], input[placeholder*="API Key"]');
        if (!apiInput) return;

        dialog.dataset.qalamAiEnhanced = '1';
        dialog.setAttribute('dir', 'rtl');
        replaceText(dialog);

        var panel = el('div', 'qalam-ai-provider-panel');
        panel.innerHTML = '<div class="qalam-ai-heading"><strong>مزود الذكاء الاصطناعي</strong><span>اختر الخدمة والنموذج المستخدمين في إنشاء الأسئلة والمحتوى.</span></div>';

        var grid = el('div', 'qalam-ai-grid');
        var providerField = el('label', 'qalam-ai-field');
        providerField.appendChild(el('span', '', 'المزود'));
        var select = el('select', 'tutor-input-field qalam-ai-select');
        [
            ['openai','OpenAI'], ['deepseek','DeepSeek'], ['openrouter','OpenRouter'],
            ['google','Google AI Studio'], ['custom','مزود مخصص (OpenAI-compatible)']
        ].forEach(function (item) {
            var option = document.createElement('option'); option.value=item[0]; option.textContent=item[1]; select.appendChild(option);
        });
        select.value = window.QalamAIProviderDraft.provider || initialProvider;
        providerField.appendChild(select);

        var modelField = el('label', 'qalam-ai-field');
        modelField.appendChild(el('span', '', 'Model ID'));
        var model = el('input', 'tutor-input-field qalam-ai-model');
        model.type='text'; model.autocomplete='off'; model.value=window.QalamAIProviderDraft.model || initialModel;
        model.placeholder='مثال: deepseek-v4-flash';
        modelField.appendChild(model);

        var customField = el('label', 'qalam-ai-field qalam-ai-custom-url');
        customField.appendChild(el('span', '', 'OpenAI Base URL'));
        var base = el('input', 'tutor-input-field qalam-ai-base-url');
        base.type='url'; base.autocomplete='off'; base.value=window.QalamAIProviderDraft.base_url || '';
        base.placeholder='https://provider.example.com/v1'; customField.appendChild(base);

        var description = el('div', 'qalam-ai-provider-description');
        grid.appendChild(providerField); grid.appendChild(modelField); grid.appendChild(customField);
        panel.appendChild(grid); panel.appendChild(description);

        function sync(resetModel) {
            var p = select.value || 'openai';
            var preset = presets[p] || {};
            if (resetModel && preset.model) model.value = preset.model;
            customField.style.display = p === 'custom' ? '' : 'none';
            description.textContent = providerDescription(p);
            window.QalamAIProviderDraft = {
                provider: p,
                model: (model.value || preset.model || '').trim(),
                base_url: p === 'custom' ? (base.value || '').trim() : ''
            };
            // Keep the existing React API-key field, but make it provider-neutral.
            apiInput.setAttribute('placeholder', 'أدخل مفتاح API لـ ' + (preset.label || select.options[select.selectedIndex].text));
        }
        select.addEventListener('change', function(){ sync(true); });
        model.addEventListener('input', function(){ sync(false); });
        base.addEventListener('input', function(){ sync(false); });
        sync(false);

        var apiWrapper = apiInput.closest('.tutor-input-field') || apiInput.parentElement;
        var form = apiInput.closest('form');
        if (form) {
            var firstInputBlock = apiInput.parentElement;
            while (firstInputBlock && firstInputBlock.parentElement !== form) firstInputBlock = firstInputBlock.parentElement;
            if (firstInputBlock && firstInputBlock.parentElement === form) form.insertBefore(panel, firstInputBlock);
            else form.insertBefore(panel, form.firstChild);
        } else if (apiWrapper && apiWrapper.parentElement) {
            apiWrapper.parentElement.insertBefore(panel, apiWrapper);
        }

        var styleId='qalam-ai-provider-style';
        if (!document.getElementById(styleId)) {
            var style=document.createElement('style'); style.id=styleId; style.textContent='\
            .qalam-ai-provider-panel{background:#faf7ff;border:1px solid #e8ddff;border-radius:14px;padding:14px;display:flex;flex-direction:column;gap:12px;text-align:right}\
            .qalam-ai-heading{display:flex;flex-direction:column;gap:3px}.qalam-ai-heading strong{font-size:14px;color:#28134f}.qalam-ai-heading span,.qalam-ai-provider-description{font-size:12px;line-height:1.6;color:#6b6475}\
            .qalam-ai-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}.qalam-ai-field{display:flex;flex-direction:column;gap:6px;font-size:12px;color:#3e3650}.qalam-ai-field input,.qalam-ai-field select{width:100%;min-height:42px;border-radius:10px!important}\
            .qalam-ai-custom-url{grid-column:1/-1}@media(max-width:640px){.qalam-ai-grid{grid-template-columns:1fr}}'; document.head.appendChild(style);
        }
    }

    function scan() {
        document.querySelectorAll('[role="dialog"], .tutor-modal, .tutor-modal-content').forEach(enhance);
    }
    new MutationObserver(scan).observe(document.documentElement, {childList:true, subtree:true});
    document.addEventListener('DOMContentLoaded', scan);
    window.setTimeout(scan, 500);
})();
