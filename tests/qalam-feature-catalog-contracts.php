<?php
/** Source-level contracts for Qalam 0.18 SaaS-ready feature catalog. No WordPress bootstrap required. */
$root = dirname(__DIR__);
$fail = array();
$ok = static function ($cond, $label) use (&$fail) { if (!$cond) { $fail[] = $label; } };
$read = static function ($rel) use ($root) { $p=$root.'/'.$rel; return is_file($p)?file_get_contents($p):''; };
$main=$read('qalam-lms.php');
$r180=$read('qalam/release-180.php');
$r150=$read('qalam/release-150.php');
$r070=$read('qalam/release-070.php');
$r080=$read('qalam/release-080.php');
$proInit=$read('pro/classes/Init.php');
$branding=$read('qalam/branding.php');
$playerTpl=$read('templates/single/video/youtube.php');
$addonsJs=$read('assets/js/qalam-180-addons.js');

$ok(strpos($main, 'Version: 0.32.0') !== false, 'plugin version');
$ok(strpos($main, "require_once __DIR__ . '/qalam/release-180.php';") !== false, 'release 180 bootstrap');
$ok(strpos($r180, "QALAM_180_FEATURE_SCHEMA_VERSION = '0.18.0'") !== false, 'feature schema version');
$ok(strpos($r180, "apply_filters( 'qalam_saas_feature_access'") !== false, 'SaaS entitlement seam');
$ok(strpos($r180, 'function qalam_feature_enabled') !== false, 'canonical feature gate');
$ok(strpos($r180, 'function qalam_feature_catalog') !== false, 'catalog API');
$ok(strpos($r180, 'function qalam_180_catalog_for_saas') !== false, 'SaaS catalog export');
$ok(strpos($r180, "add_action( 'wp_ajax_qalam_180_toggle_feature'") !== false, 'unified toggle endpoint');
$ok(strpos($addonsJs, 'qalam_180_toggle_feature') !== false, 'catalog UI toggle client');
$ok(strpos($r180, "remove_action( 'wp_ajax_qalam_050_toggle_addon'") !== false, 'legacy Qalam toggle endpoint retired');
$ok(strpos($r180, "add_action( 'wp_ajax_addon_enable_disable', 'qalam_180_guard_native_addon_toggle', 0 )") !== false, 'native Tutor toggle SaaS guard');

// Count stable feature keys in the two static definition functions.
$internalBlock = explode('function qalam_180_packaged_features', explode('function qalam_180_internal_features', $r180, 2)[1] ?? '', 2)[0] ?? '';
$packagedBlock = explode('function qalam_180_feature_definitions', explode('function qalam_180_packaged_features', $r180, 2)[1] ?? '', 2)[0] ?? '';
preg_match_all("/^\\s{2,}'([a-z0-9_]+)'\\s*=>\\s*array\\(/m", $internalBlock, $im);
preg_match_all("/^\\s{2,}'([a-z0-9_]+)'\\s*=>\\s*array\\(/m", $packagedBlock, $pm);
$internalKeys = $im[1] ?? array();
$packagedKeys = $pm[1] ?? array();
$allKeys = array_values(array_unique(array_merge($internalKeys, $packagedKeys)));
$ok(count($internalKeys) === 17, '17 Qalam-owned optional capabilities');
$ok(count($packagedKeys) === 28, '28 packaged optional capabilities');
$ok(count($allKeys) === 45, '45 stable SaaS feature keys');

$expectedInternal = array('question_bank','standalone_exams','randomized_exams','dynamic_exams','ai_question_generation','pdf_question_generation','ai_background_worker','qalam_video_player','video_subtitles','video_ads','student_analytics','certificate_builder','instructor_marketplace','gift_courses','lesson_notes','progress_reset','email_update');
$expectedPackaged = array('course_bundles','subscriptions','content_bank','social_login','content_drip','multi_instructor','assignments','course_preview','course_attachments','google_meet','advanced_reports','email_notifications','calendar','notifications','google_classroom','zoom','quiz_import_export','manual_enrollments','certificates','gradebook','course_prerequisites','buddypress','wc_subscriptions','pmpro','restrict_content_pro','weglot','wpml','h5p');
foreach($expectedInternal as $key){$ok(in_array($key,$internalKeys,true),'internal key '.$key);}
foreach($expectedPackaged as $key){$ok(in_array($key,$packagedKeys,true),'packaged key '.$key);}

// Never expose security-critical fixes as plan-switchable capabilities.
foreach(array('idor','nonce','security','private_file_security','quiz_reveal_security','assignment_security','ssrf','webhook_security') as $forbidden){
    $ok(!in_array($forbidden,$allKeys,true), 'security is not optional: '.$forbidden);
}
$ok(!in_array('auth',$packagedKeys,true), 'auth/security addon not exposed as SaaS toggle');

// Packaged add-on source backing must exist for every mapped add-on.
preg_match_all("/'addon'\\s*=>\\s*'([^']+)'/", $packagedBlock, $am);
$addonSlugs = array_values(array_unique($am[1] ?? array()));
$ok(count($addonSlugs) === 28, '28 unique packaged addon slugs');
foreach($addonSlugs as $slug){
    $ok(is_dir($root.'/pro/addons/'.$slug), 'addon dir '.$slug);
    $ok(is_file($root.'/pro/addons/'.$slug.'/'.$slug.'.php'), 'addon bootstrap '.$slug);
}

// Sidebar moves: routes remain registered, shortcuts are removed and direct requests gated.
foreach(array('qalam-question-bank','qalam-quiz-builder','qalam-certificate-builder','qalam-video-ads','tutor-content-bank','tutor-assignments','tutor_gradebook','tutor_report','enrollments','tutor_zoom','google-meet','tutor-google-classroom','tutor_h5p','tutor-subscriptions','tutor-instructors','tutor_withdraw_requests') as $slug){
    $ok(strpos($r180, "'{$slug}'") !== false, 'sidebar/route map '.$slug);
}
$ok(strpos($r180, "remove_submenu_page( 'tutor-pro', 'course-bundle' )") !== false, 'course bundle shortcut hidden');
$ok(strpos($r180, "'qalam-video-ads' === \$page") !== false && strpos($r180, "qalam_feature_enabled( 'video_ads' )") !== false && strpos($r180, "qalam_feature_enabled( 'video_subtitles' )") !== false, 'shared media page composite gate');

// Runtime/SaaS enforcement, not UI-only locking.
$ok(strpos($proInit, 'qalam_190_packaged_runtime_should_boot') !== false, 'packaged add-ons obey local and SaaS state before runtime bootstrap');
$ok(strpos($r180, 'qalam_180_option_entitlement_filter') !== false && strpos($r180, 'current_filter()') !== false, 'option-backed feature SaaS gate');
$ok(strpos($branding, "add_action( 'plugins_loaded', 'qalam_prepare_marketplace_defaults', 1 )") === false, 'marketplace no longer force-enabled');
$ok(strpos($r180, "'enable_course_marketplace'") !== false, 'marketplace controlled by catalog');

// Concrete Qalam feature gates.
$ok(strpos($playerTpl, "qalam_feature_enabled('qalam_video_player')") !== false || strpos($playerTpl, "qalam_feature_enabled( 'qalam_video_player' )") !== false, 'custom player fallback gate');
$ok(strpos($r150, "qalam_feature_enabled('video_ads')") !== false, 'video ads runtime gate');
$ok(strpos($r150, "qalam_feature_enabled('video_subtitles')") !== false, 'subtitles runtime gate');
$ok(strpos($r150, '$ads_enabled') !== false && strpos($r150, '$subtitles_enabled') !== false, 'shared media page section gates');
$ok(strpos($r150, "qalam_feature_enabled( 'student_analytics' )") !== false, 'student analytics gate');
$ok(strpos($r070, "qalam_feature_enabled( 'ai_question_generation' )") !== false, 'AI generator UI gate');
$ok(strpos($r070, "qalam_feature_enabled( 'pdf_question_generation' )") !== false, 'PDF generator UI gate');
$ok(strpos($r080, 'randomizedFeatureEnabled') !== false && strpos($r080, 'dynamicFeatureEnabled') !== false, 'random/dynamic builder feature flags');

// Action and route enforcement for important internally-owned capabilities.
foreach(array('qalam_081_bank_bulk_delete','qalam_070_create_general_quiz','qalam_080_random_fill_quiz','qalam_080_save_dynamic_rules','qalam_080_start_generation','qalam_080_process_generation','qalam_150_save_video_ad','qalam_150_save_subtitle') as $action){
    $ok(strpos($r180, "'{$action}'") !== false, 'request guard '.$action);
}
$ok(strpos($r180, 'qalam_180_front_feature_guard') !== false, 'frontend feature gate');

if($fail){
    fwrite(STDERR, "FAIL qalam-feature-catalog-contracts\n - ".implode("\n - ", array_values(array_unique($fail)))."\n");
    exit(1);
}
echo "PASS qalam-feature-catalog-contracts (45 features: 17 internal + 28 packaged)\n";
