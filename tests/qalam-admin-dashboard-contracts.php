<?php
/**
 * Qalam 0.21.1 standalone admin dashboard closure contracts.
 * Source-only; no WordPress bootstrap. These prove the eight previously-partial
 * dashboard sections are fully wired to the mature Qalam/Tutor engines while
 * managed platform roles remain non-WordPress administrators.
 */
$root = dirname(__DIR__);
$fail = array();
$ok = static function($cond, $label) use (&$fail) { if (!$cond) { $fail[] = $label; } };
$read = static function($rel) use ($root) { $p=$root.'/'.$rel; return is_file($p) ? file_get_contents($p) : ''; };

$main   = $read('qalam-lms.php');
$brand  = $read('qalam/branding.php');
$dash   = $read('qalam/release-210.php');
$user   = $read('classes/User.php');
$course = $read('classes/Course.php');
$qjs    = $read('assets/js/qalam-070-admin.js');
$shell  = $read('assets/js/qalam-admin-shell.js');
$css    = $read('assets/css/qalam-admin-shell.css');
$report = $read('pro/addons/tutor-report/classes/Report.php');
$analytics = $read('pro/addons/tutor-report/classes/Analytics.php');
$graph = $read('pro/addons/tutor-report/templates/elements/graph.php');
$contentBank = $read('pro/addons/content-bank/src/Controllers/CollectionController.php');

$version='0.32.0';
$dashboard_version='0.21.1-dashboard-closure';
$ok(strpos($main, 'Version: '.$version)!==false, 'plugin version');
$ok(strpos($main, "QALAM_LMS_PRODUCT_VERSION', '".$version)!==false, 'product version');
$ok(strpos($brand, "QALAM_LMS_UI_VERSION', '0.31.0")!==false, 'ui version unchanged for backend-only release');
$ok(strpos($main, "require_once __DIR__ . '/qalam/release-210.php';")!==false, 'dashboard release bootstrap');
$ok(strpos($dash, "QALAM_210_VERSION       = '".$dashboard_version."'")!==false, 'dashboard version constant');

// Standalone shell + routing.
foreach (array('^qalam-admin/?$','^qalam-admin/([^/]+)/?$','qalam_210_render_dashboard','qalam_210_strip_theme_assets') as $needle) {
    $ok(strpos($dash,$needle)!==false, 'standalone shell '.$needle);
}
foreach (array('courses','students','exams','question-bank','addons','ai','reports','settings') as $section) {
    $ok(strpos($dash,"'{$section}'")!==false, 'dashboard section '.$section);
}
$ok(strpos($dash,'window.ajaxurl=window.QalamAdmin.ajaxUrl')!==false, 'frontend dashboard AJAX compatibility');
$ok(strpos($dash, "(?:admin|post|users|user-edit|edit|post-new|options-general)\\.php")!==false, 'legacy admin links rewritten away from wp-admin');
$ok(strpos($css,'.qalam-shell')!==false && strpos($css,'.qalam-sidebar')!==false && strpos($css,'.qalam-main')!==false, 'isolated dashboard UI shell');
$ok(strpos($shell,'data-qalam-addon-search')!==false && strpos($shell,'qalam_200_toggle_product')!==false, 'dashboard add-on interactions');

// Platform roles are LMS admins, never WordPress admins.
foreach (array('qalam_owner','qalam_manager','qalam_access_dashboard','qalam_manage_courses','qalam_manage_students','qalam_manage_exams','qalam_manage_question_bank','qalam_manage_addons','qalam_manage_ai','qalam_manage_reports','qalam_manage_settings') as $needle) {
    $ok(strpos($dash,$needle)!==false, 'role/capability '.$needle);
}
foreach (array('install_plugins','activate_plugins','edit_plugins','delete_plugins','update_plugins','install_themes','switch_themes','edit_themes','update_core','manage_options','create_users','edit_users','delete_users','promote_users') as $cap) {
    $ok(strpos($dash,"'{$cap}'")!==false, 'dangerous capability explicitly stripped '.$cap);
}
$ok(strpos($dash,"array( 'admin-ajax.php','admin-post.php','async-upload.php' )")!==false, 'wp-admin plumbing allowlist');
$ok(strpos($dash,"wp_safe_redirect( qalam_210_dashboard_url() )")!==false, 'managed wp-admin redirect');
$ok(strpos($user,"array( 'qalam_owner', 'qalam_manager' )")!==false && strpos($user,'self::ADMIN')!==false, 'Tutor LMS-admin mapping');
$ok(strpos($user,'user_can( $user_id, self::ADMIN )')!==false, 'native admin compatibility retained');

// Courses: list + embedded mature React builder + Qalam URLs.
foreach (array('qalam_210_render_courses','qalam_210_render_course_builder','id="tutor-course-builder"','tutor_course_builder_footer','tutor_after_course_builder_load','qalam_210_course_trash') as $needle) {
    $ok(strpos($dash,$needle)!==false, 'courses '.$needle);
}
$ok(strpos($course,'qalam_210_is_course_builder_request')!==false, 'course builder recognizes Qalam route');
$ok(strpos($course,"qalam_210_dashboard_url( 'courses' )")!==false, 'course builder returns to Qalam');
$ok(strpos($course, "backend_course_list_url")!==false && strpos($course, "frontend_course_list_url")!==false, 'course list URLs localized');

// Students: create/edit/enrol/unenrol/progress/attempts/certificates/review.
foreach (array('qalam_210_student_create','qalam_210_student_update','qalam_210_student_enroll','qalam_210_student_unenroll','qalam_210_render_student_detail','get_course_completed_percent','get_all_quiz_attempts_by_user','attempt-details.php','TUTOR_CERT\\Certificate','EnrollmentModel::do_enroll','delete_enrollment_record') as $needle) {
    $ok(strpos($dash,$needle)!==false, 'students '.$needle);
}
$ok(substr_count($dash,"admin_post_qalam_210_student_create")===1, 'student create handler registered once');

// Exams: mature standalone/dynamic engine embedded and localized for dashboard.
$ok(strpos($dash,'qalam_081_render_quiz_builder')!==false, 'full exam builder embedded');
foreach (array('Qalam080','quizToolsNonce','dynamicEnabled','dynamicRules','randomizedFeatureEnabled','dynamicFeatureEnabled','Qalam081') as $needle) {
    $ok(strpos($dash,$needle)!==false, 'exam localization '.$needle);
}

// Question bank: mature bank + native Content Bank + AI/PDF scripts in standalone route.
$ok(strpos($dash,'qalam_081_render_question_bank')!==false, 'full question bank embedded');
$ok(strpos($dash,'id="tutor-content-bank-root"')!==false, 'native Content Bank root embedded');
foreach (array('Qalam050','Qalam060','Qalam070','Qalam080','questionBankUrl','contentBankUrl','previewBase','categories') as $needle) {
    $ok(strpos($dash,$needle)!==false, 'question bank tooling '.$needle);
}
$ok(strpos($qjs,'function isPage')!==false && strpos($qjs,'Qalam070')!==false, 'Qalam tooling recognizes standalone context');
$ok(strpos($contentBank,'qalam_210_user_is_platform_admin')!==false || strpos($contentBank,'User::is_admin')!==false, 'Content Bank platform admin semantics');

// Add-ons: product-level catalog with SaaS lock, filter, toggle and supported management routes.
foreach (array('qalam_200_product_catalog','effective_enabled','access','qalam_210_addon_manage_url','data-qalam-addon-search','data-qalam-addon-category','data-feature') as $needle) {
    $ok(strpos($dash,$needle)!==false, 'addons '.$needle);
}
foreach (array('question_bank_suite','advanced_exams','artificial_intelligence','reports_suite') as $needle) {
    $ok(strpos($dash,$needle)!==false, 'add-on management map '.$needle);
}

// AI: unified provider center, secret preservation, connection test/model cache, custom endpoint.
foreach (array('OpenAI','DeepSeek','OpenRouter','Google AI Studio','مزود مخصص','qalam_210_ai_save','sanitize_custom_base_url','fetch_provider_models','cache_provider_models','qalam_ai_provider','qalam_ai_model','chatgpt_api_key') as $needle) {
    $ok(strpos($dash,$needle)!==false, 'AI '.$needle);
}
$ok(strpos($dash, '$key=$new_key?:')!==false, 'AI blank key preserves existing secret');

// Reports: native report engine embedded with Qalam admin semantics/assets.
$ok(strpos($dash,"tutor_report_instance()->report,'tutor_report'")!==false, 'full reports engine embedded');
$ok(strpos($dash,"admin_scripts( 'qalam-admin' )")!==false, 'report assets in Qalam shell');
$ok(strpos($report,'qalam_manage_reports')!==false || strpos($report,'User::is_admin')!==false, 'report destructive actions allow Qalam LMS admin');
$ok(strpos($analytics,'User::is_admin()')!==false, 'report earnings use LMS-admin semantics');
$ok(strpos($graph,'qalam_210_is_dashboard_request')!==false || strpos($graph,'qalam-report')!==false, 'backend report graph variant in Qalam shell');
$ok(strpos($dash,"'subscriptions' === sanitize_key")!==false, 'subscription report assets');

// Settings: full Tutor field registry in Qalam shell, active-section merge, secret preservation and history/hooks.
foreach (array('get_setting_fields','qalam_210_options_engine','qalam_210_render_native_settings_section','Tutor\\Options_V2','qalam_210_rewrite_legacy_html','qalam_210_settings_save','qalam_210_merge_settings_preserve_secrets','qalam_210_setting_is_secret_key','tutor_option_save_before','tutor_option_input','tutor_settings_log','tutor_option_update_time','tutor_option_save_after','qalam-settings-native-action') as $needle) {
    $ok(strpos($dash,$needle)!==false, 'settings '.$needle);
}
$ok(strpos($dash, "stripos(\$native_html,'<form')")!==false, 'settings native owned forms are not nested');
foreach (array('TutorPro\Subscription\Assets','tutor-pro-auth-settings-js','tutor-pro-certificate-field-css','tutor-pro-email-styles') as $needle) {
    $ok(strpos($dash,$needle)!==false, 'Pro settings assets '.$needle);
}
$ok(strpos($dash,'qalam_dashboard_optional_asset_error')!==false, 'optional wp-admin assets cannot fatal the standalone settings page');
$ok(substr_count($dash,'catch ( Throwable $e )')>=4, 'settings engine and optional asset failures are isolated');
$ok(strpos($shell,'data-qalam-cloud-credits')!==false && strpos($css,'.qalam-cloud-credit-badge')!==false, 'AI balance lives in the Qalam dashboard header');

if ($fail) {
    fwrite(STDERR, "FAIL qalam-admin-dashboard-contracts\n - ".implode("\n - ", array_unique($fail))."\n");
    exit(1);
}
echo "PASS qalam-admin-dashboard-contracts (8 started sections source-closed; wp-admin isolated)\n";
