<?php
/** Source-level contracts for Qalam 0.19 feature management. No WordPress bootstrap required. */
$root=dirname(__DIR__);$fail=array();
$ok=static function($cond,$label)use(&$fail){if(!$cond)$fail[]=$label;};
$read=static function($rel)use($root){$p=$root.'/'.$rel;return is_file($p)?file_get_contents($p):'';};
$main=$read('qalam-lms.php');$r070=$read('qalam/release-070.php');$r081=$read('qalam/release-081.php');$r180=$read('qalam/release-180.php');$r190=$read('qalam/release-190.php');$pro=$read('pro/classes/Init.php');$css=$read('assets/css/qalam-190-feature-management.css');
$ok(strpos($main,'Version: 0.32.0')!==false,'plugin version');
$ok(strpos($main,"require_once __DIR__ . '/qalam/release-190.php';")!==false,'release 190 bootstrap');
$ok(strpos($r190,'function qalam_190_feature_icons')!==false,'icon registry');
$ok(strpos($r190,'function qalam_190_render_feature_manager')!==false,'feature manager renderer');
$ok(strpos($r190,"'qalam-feature-settings'")!==false,'hidden feature manager page');
$ok(strpos($r190,'function qalam_190_feature_destinations')!==false,'real management destinations');
$ok(strpos($r190,"'gift_courses'           => 'course'")!==false && strpos($r190,"'email_update'           => 'advanced'")!==false,'option-backed services route to their exact settings tabs');
$ok(strpos($r190,"'workspace_url'")!==false && strpos($r190,"'settings_url'")!==false,'workspace and settings destinations');
$ok(strpos($r190,"\$feature['manage_url_resolved'] = \$primary ?: \$feature['details_url']")!==false && strpos($r190,"'per_course' === \$dest['mode']")!==false,'Manage routes directly to the correct real settings/workspace destination');
$ok(strpos($r190,'function qalam_190_packaged_runtime_should_boot')!==false,'runtime disappearance decision');
$ok(strpos($pro,'qalam_190_packaged_runtime_should_boot( $addon_dir_name )')!==false,'Pro loader obeys local/SaaS feature state');
$ok(strpos($r190,"'qalam_180_toggle_feature' === \$action")!==false && strpos($r190,"hash_equals( \$feature_key, \$target )")!==false,'disabled packaged addon boots only for its own state-change request');
$ok(strpos($r190,'function qalam_190_guard_feature_manager')!==false,'disabled manager direct-link guard');
$ok(strpos($r190,"qalam_feature_unavailable")!==false,'disabled manager redirects to catalog');
$ok(strpos($r180,"function_exists('qalam_190_render_icon')")!==false,'catalog uses 0.19 icon renderer');
$ok(strpos($css,'.qalam-190-manager')!==false && strpos($css,'.qalam-190-icon')!==false,'manager/icon CSS');
$ok(strpos($r180,"'question_bank' => array(")!==false && strpos($r180,"'depends' => array( 'content_bank' )")!==false,'question bank declares content-bank dependency');
$ok(strpos($r180,"'qalam_070_import_questions_to_quiz' === \$action && ! qalam_feature_enabled( 'question_bank' )")!==false,'bank import action blocked when bank disabled');
$ok(strpos($r081,"\$question_bank_enabled = ! function_exists( 'qalam_feature_enabled' ) || qalam_feature_enabled( 'question_bank' )")!==false,'active standalone editor gates question bank UI');
$ok(strpos($r081,'<?php if($question_bank_enabled):?><section class="qalam-050-panel"><h2>إضافة من بنك الأسئلة</h2>')!==false,'bank picker disappears when bank disabled');
$ok(strpos($r081,'if($ai_enabled){qalam_070_render_ai_generator')!==false,'AI generator disappears when AI disabled');
$ok(strpos($r070,"\$question_bank_enabled=!function_exists('qalam_feature_enabled')||qalam_feature_enabled('question_bank')")!==false,'legacy standalone renderer also gates question bank UI');
$ok(strpos($r190,'function qalam_190_hide_disabled_internal_settings')!==false,'disabled internal Tutor setting fields are removed');
$ok(strpos($r190,"add_filter( 'tutor/options/attr', 'qalam_190_hide_disabled_internal_settings'")!==false && strpos($r190,"add_filter( 'tutor/options/extend/attr', 'qalam_190_hide_disabled_internal_settings'")!==false,'core and Pro setting surfaces obey disabled state');
$ok(strpos($r180,"'tutor_change_email'=>'email_update'")!==false && strpos($r180,"'tutor_reset_student_course_progress'=>'progress_reset'")!==false,'internal feature AJAX actions are guarded');
$ok(strpos($r180,"'tutor_pro_save_lesson_note'=>'lesson_notes'")!==false && strpos($r180,"'tutor_pro_gift_proceed_to_checkout'=>'gift_courses'")!==false,'notes and gift AJAX actions are guarded');
$ok(strpos($r180,'function qalam_180_external_missing')!==false && strpos($r180,"'external_requires_any'")!==false,'external dependencies remain visible without booting disabled add-ons');


// Count static icon keys and verify every 0.18 capability has a fallback icon.
$internalBlock=explode('function qalam_180_packaged_features',explode('function qalam_180_internal_features',$r180,2)[1]??'',2)[0]??'';
$packagedBlock=explode('function qalam_180_feature_definitions',explode('function qalam_180_packaged_features',$r180,2)[1]??'',2)[0]??'';
preg_match_all("/^\\s{2,}'([a-z0-9_]+)'\\s*=>\\s*array\\(/m",$internalBlock,$im);
preg_match_all("/^\\s{2,}'([a-z0-9_]+)'\\s*=>\\s*array\\(/m",$packagedBlock,$pm);
$features=array_values(array_unique(array_merge($im[1]??array(),$pm[1]??array())));
$iconsBlock=explode('function qalam_190_feature_manager_url',explode('function qalam_190_feature_icons',$r190,2)[1]??'',2)[0]??'';
preg_match_all("/^\\s{2,}'([a-z0-9_]+)'\\s*=>\\s*'dashicons-/m",$iconsBlock,$xm);
$icons=array_values(array_unique($xm[1]??array()));
$ok(count($features)===45,'45 feature definitions remain');
$ok(substr_count($internalBlock, "'manage_page'") + substr_count($internalBlock, "'manage_url'") === 17,'all internal features have management destinations');
$ok(substr_count($packagedBlock, "'manage_page'") + substr_count($packagedBlock, "'manage_url'") === 28,'all packaged features have management destinations');
$ok(count($icons)===45,'45 explicit icon fallbacks');
foreach($features as $key){$ok(in_array($key,$icons,true),'icon for '.$key);}

// The only way to re-enable a disabled feature should remain the add-ons catalog.
$ok(strpos($r180,'data-qalam-feature-toggle')!==false,'catalog retains activation control');
$ok(strpos($r180,"\$feature['effective_enabled']")!==false,'manage button conditioned on effective enabled state');

if($fail){fwrite(STDERR,"FAIL qalam-feature-management-contracts\n - ".implode("\n - ",array_values(array_unique($fail)))."\n");exit(1);}echo "PASS qalam-feature-management-contracts (45 icons + manager + runtime disappearance)\n";
