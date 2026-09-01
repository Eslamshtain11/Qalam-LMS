<?php
$root=dirname(__DIR__);$fail=array();
$ok=static function($cond,$label)use(&$fail){if(!$cond)$fail[]=$label;};
$read=static function($rel)use($root){$p=$root.'/'.$rel;return is_file($p)?file_get_contents($p):'';};
$main=$read('qalam-lms.php');
$r210=$read('qalam/release-210.php');
$r220=$read('qalam/release-220.php');
$loginCss=$read('assets/css/qalam-login.css');
$shellCss=$read('assets/css/qalam-admin-shell.css');
$shellJs=$read('assets/js/qalam-admin-shell.js');
$user=$read('classes/User.php');
$utils=$read('classes/Utils.php');
$order=$read('ecommerce/OrderController.php');
$withdraw=$read('classes/Withdraw_Requests_List.php');
$students=$read('classes/Students_List.php');
$courses=$read('classes/Course_List.php');
$twofa=$read('pro/addons/auth/classes/_2FA.php');
$bundleBuilder=$read('pro/addons/course-bundle/src/Frontend/BundleBuilder.php');
$bundleUtils=$read('pro/addons/course-bundle/src/Utils.php');
$bundleModel=$read('pro/addons/course-bundle/src/Models/BundleModel.php');
$gmeet=$read('pro/addons/google-meet/includes/GoogleEvent/GoogleEvent.php');

$ok(strpos($main,'Version: 0.32.0')!==false,'0.22 product version');
$ok(strpos($main,"require_once __DIR__ . '/qalam/release-220.php';")!==false,'release 220 bootstrapped');
foreach(array('qalam_220_surface_registry','qalam_220_product_manage_url','qalam_220_find_native_menu_item','qalam_220_prepare_manage_assets','qalam_220_render_manage','qalam_220_login_url','qalam_220_render_login','qalam_220_render_commerce','qalam_220_native_ecommerce_enabled','qalam_220_marketplace_enabled') as $needle)$ok(strpos($r220,$needle)!==false,$needle);

$products=array(
'question_bank_suite','advanced_exams','artificial_intelligence','video_player','certificates_suite','instructor_suite','reports_suite','communications_suite','account_access_suite',
'video_ads','gift_courses','lesson_notes','course_bundles','subscriptions','assignments','content_drip','course_preview','course_attachments','google_meet','calendar','google_classroom','zoom','manual_enrollments','gradebook','course_prerequisites','buddypress','wc_subscriptions','pmpro','restrict_content_pro','weglot','wpml','h5p'
);
foreach($products as $key)$ok(strpos($r220,"'{$key}'")!==false,'surface '.$key);
$ok(count($products)===32,'32 product surface contract');

$ok(strpos($r210,"'manage'=>array")!==false && strpos($r210,"'hidden'=>true")!==false,'hidden manage route');
$ok(strpos($r210,"'commerce'=>array")!==false,'standalone commerce route');
$ok(strpos($r210,"case'manage'")!==false && strpos($r210,"case'commerce'")!==false,'manage + commerce renderers wired');
$ok(strpos($r210,'qalam_220_prepare_manage_assets')!==false && strpos($r210,'qalam_220_prepare_commerce_assets')!==false,'surface assets wired');
$ok(strpos($r210,'qalam_220_product_manage_url')!==false,'catalog manage routes centralized');
$ok(strpos($r210,'qalam_220_legacy_url_to_dashboard( $requested )')!==false,'server-side wp-admin last-resort remap');
$ok(strpos($r210,'user-edit|edit|post-new')!==false,'expanded legacy URL rewrite');

$ok(strpos($r220,"check_admin_referer( 'qalam_220_create_bundle'")!==false,'bundle create nonce');
$ok(strpos($r220,"check_admin_referer( 'qalam_220_trash_bundle_'")!==false,'bundle delete nonce');
$ok(strpos($r220,"current_user_can( 'qalam_manage_addons' )")!==false,'Qalam capability used for managed surfaces');
$ok(strpos($r220,"\$_POST['tutor_action'] = 'tutor_login'")!==false && strpos($r220,'wp_signon')!==false,'standalone login preserves Tutor Auth/2FA pipeline');
$ok(strpos($r220,"name=\"tutor_action\" value=\"tutor_login\"")!==false,'login form marks Tutor login source');
$ok(strpos($twofa,"qalam_210_user_is_managed")!==false && strpos($twofa,'qalam_210_dashboard_url()')!==false,'2FA returns managed accounts to Qalam');

$ok(strpos($user,"array( 'qalam_owner', 'qalam_manager' )")!==false && strpos($user,'public static function is_admin')!==false,'LMS-admin semantics without WP administrator role');
$ok(strpos($utils, "'manage_options' === \$capability && User::is_admin()")!==false,'Tutor internal capability bridge');
$ok(strpos($order,'User::is_admin()')!==false,'orders CRUD recognizes Qalam LMS admin');
$ok(strpos($withdraw,'if ( ! User::is_admin() )')!==false,'withdrawal CRUD recognizes Qalam LMS admin');
$ok(strpos($students,'if ( ! User::is_admin() )')!==false,'student bulk CRUD recognizes Qalam LMS admin');
$ok(strpos($courses,'! User::is_admin()')!==false,'course list recognizes Qalam LMS admin');
$ok(strpos($bundleBuilder,"current_user_can( 'qalam_manage_addons' )")!==false && strpos($bundleUtils,"current_user_can( 'qalam_manage_addons' )")!==false,'bundle builder Qalam capability bridge');
$ok(strpos($bundleModel,'\\TUTOR\\User::is_admin()')!==false,'bundle list sees all bundles for Qalam admin');

$ok(strpos($r220,"qalam-google-meet-callback")!==false && strpos($r220,'untrailingslashit')!==false,'Qalam Google Meet callback route');
$ok(strpos($gmeet,'qalam_220_google_meet_callback_url')!==false,'Google Meet OAuth uses Qalam callback');
$ok(strpos($r220, "'shop_order' === \$post_type")!==false,'Woo order links stay out of wp-admin');
$ok(strpos($shellJs,'user-edit\\.php')!==false && strpos($shellJs,"postType==='shop_order'")!==false,'client runtime wp-admin guard covers instructor/Woo links');
$ok(strpos($shellJs,'admin-ajax|admin-post|async-upload')!==false,'background WordPress plumbing is not intercepted');

$ok(strpos($r220,'qalam_220_native_ecommerce_enabled()')!==false && strpos($r220,'محرك التجارة الحالي ليس محرك قلم')!==false,'commerce fails closed for non-native engine');
$ok(strpos($r220,'qalam_220_marketplace_enabled()')!==false && strpos($r220,'سوق المعلمين غير مفعّل')!==false,'marketplace fails closed with Qalam settings path');
$ok(strpos($loginCss,'.qalam-login-shell')!==false,'login UI');
$ok(strpos($shellCss,'.qalam-native-surface')!==false,'embedded surface UI');

if($fail){fwrite(STDERR,"FAIL qalam-220-admin-surfaces-contracts\n - ".implode("\n - ",array_unique($fail))."\n");exit(1);}echo "PASS qalam-220-admin-surfaces-contracts (32 product surfaces + Qalam login/2FA + LMS-admin CRUD + commerce isolation)\n";
