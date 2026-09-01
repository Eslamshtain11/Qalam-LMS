<?php
/** Qalam 0.20 product-level add-on contracts. Source-only; no WordPress bootstrap. */
$root=dirname(__DIR__);$fail=[];
$ok=static function($cond,$label)use(&$fail){if(!$cond)$fail[]=$label;};
$read=static function($rel)use($root){$p=$root.'/'.$rel;return is_file($p)?file_get_contents($p):'';};
$main=$read('qalam-lms.php');$r180=$read('qalam/release-180.php');$r190=$read('qalam/release-190.php');$r200=$read('qalam/release-200.php');$js=$read('assets/js/qalam-200-addons.js');$css=$read('assets/css/qalam-200-addons.css');
$ok(strpos($main,'Version: 0.32.0')!==false,'plugin version');
$ok(strpos($main,"require_once __DIR__ . '/qalam/release-200.php';")!==false,'release 200 bootstrap');
$ok(strpos($r200,'function qalam_200_feature_groups')!==false,'group registry');
$ok(strpos($r200,"'artificial_intelligence'")!==false,'single AI product');
foreach(['ai_question_generation','pdf_question_generation','ai_background_worker'] as $k){$ok(strpos($r200,"'{$k}'")!==false,'AI child '.$k);}
$ok(strpos($r200,"'question_bank_suite'")!==false && strpos($r200,"'content_bank'")!==false && strpos($r200,"'quiz_import_export'")!==false,'question bank unified product');
$ok(strpos($r200,"'advanced_exams'")!==false && strpos($r200,"'standalone_exams'")!==false && strpos($r200,"'randomized_exams'")!==false && strpos($r200,"'dynamic_exams'")!==false,'advanced exams unified product');
$ok(strpos($r200,"'video_player'")!==false && strpos($r200,"'video_subtitles'")!==false,'video player unified product');
$ok(strpos($r200,"'certificates_suite'")!==false && strpos($r200,"'certificate_builder'")!==false,'certificates unified product');
$ok(strpos($r200,"'reports_suite'")!==false && strpos($r200,"'student_analytics'")!==false && strpos($r200,"'progress_reset'")!==false,'reports unified product');
$ok(strpos($r200,"'communications_suite'")!==false && strpos($r200,"'email_notifications'")!==false,'communications unified product');
$ok(strpos($r200,"'account_access_suite'")!==false && strpos($r200,"'social_login'")!==false && strpos($r200,"'email_update'")!==false,'account unified product');
$ok(strpos($r200,'function qalam_200_toggle_product')!==false && strpos($js,"qalam_200_toggle_product")!==false,'single product toggle endpoint/client');
$ok(strpos($r200,'function qalam_200_block_legacy_child_toggle')!==false,'legacy child toggle blocked');
$ok(strpos($r200,'function qalam_200_block_native_group_child_toggle')!==false,'native child split blocked');
$ok(strpos($r180,'qalam_200_group_for_child')!==false && strpos($r180,'qalam_200_group_enabled')!==false,'canonical runtime gate respects product groups');
$ok(strpos($r190,"'qalam_200_toggle_product' === \$action")!==false,'disabled packaged child can boot only for parent transition');
$ok(strpos($r190,'qalam_200_group_for_child')!==false,'packaged runtime respects group');
$ok(strpos($r200,'qalam_200_render_product_icon')!==false && strpos($r200,'<img src=')!==false,'catalog uses image icons');
$ok(strpos($r200,'qalam-180-key')===false,'technical keys not rendered by 0.20 catalog');
$ok(strpos($css,'.qalam-200-product-icon img')!==false,'product image CSS');
$icons=['ai.svg','exams.svg','question-bank.svg','video-player.svg','video-ads.svg','gift.svg','lesson-notes.svg'];
foreach($icons as $i){$ok(is_file($root.'/assets/images/qalam-addons/'.$i),'image icon '.$i);}
// Count top-level group definitions. Product catalog should be 9 grouped services + 23 independent leaves = 32 cards.
$groupsBlock=explode('function qalam_200_single_icon_overrides',explode('function qalam_200_feature_groups',$r200,2)[1]??'',2)[0]??'';
preg_match_all("/^\t\t'([a-z0-9_]+)'\s*=>\s*array\(/m",$groupsBlock,$gm);
$ok(count(array_unique($gm[1]??[]))===9,'9 product groups');
$ok(strpos($r200,"'children'=>array_values")!==false || strpos($r200,"'children'=>array_values")!==false,'SaaS export retains child metadata');
if($fail){fwrite(STDERR,"FAIL qalam-product-addon-contracts\n - ".implode("\n - ",array_unique($fail))."\n");exit(1);}echo "PASS qalam-product-addon-contracts (9 groups, image cards, binary product gates)\n";
