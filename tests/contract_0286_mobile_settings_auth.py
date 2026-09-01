from pathlib import Path
root=Path(__file__).resolve().parents[1]
admin=(root/'assets/css/qalam-admin-shell.css').read_text(encoding='utf-8')
public=(root/'qalam/release-240.php').read_text(encoding='utf-8')
public_css=(root/'assets/css/qalam-reference-system.css').read_text(encoding='utf-8')
shell=(root/'qalam/release-210.php').read_text(encoding='utf-8')
assert 'Version: 0.32.0' in (root/'qalam-lms.php').read_text(encoding='utf-8')
assert "qalam-mark.svg" in shell and "images/qalam-logo.svg" not in shell[shell.find('qalam-logo-mark'):shell.find('qalam-logo-mark')+500]
assert '.qalam-settings-fields .tutor-option-field-row{display:grid!important;grid-template-columns:1fr!important' in admin
assert '.qalam-settings-fields .tutor-option-main-title{display:grid!important' in admin
assert 'لوحة التحكم' in public and 'تسجيل الخروج' in public and 'إنشاء حساب' in public and 'تسجيل الدخول' in public
assert 'q-ref-mobile-auth' in public and 'q-ref-mobile-auth' in public_css
print('contract_0286_mobile_settings_auth: PASS')
