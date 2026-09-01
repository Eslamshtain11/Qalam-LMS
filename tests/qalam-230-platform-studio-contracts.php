<?php
$root=dirname(__DIR__);$fail=array();
$ok=static function($cond,$label)use(&$fail){if(!$cond)$fail[]=$label;};
$read=static function($rel)use($root){$p=$root.'/'.$rel;return is_file($p)?file_get_contents($p):'';};
$main=$read('qalam-lms.php');$r230=$read('qalam/release-230.php');$r210=$read('qalam/release-210.php');$r220=$read('qalam/release-220.php');$studio=$read('assets/css/qalam-design-studio.css');$public=$read('assets/css/qalam-platform.css');$js=$read('assets/js/qalam-design-studio.js');
$version='0.32.0';
$ok(strpos($main,'Version: '.$version)!==false,'product version');
$ok(strpos($main,"require_once __DIR__ . '/qalam/release-230.php';")!==false,'230 bootstrap');
$ok(strpos($r230,"QALAM_230_DESIGN_CAP    = 'qalam_manage_platform_design'")!==false,'separate design capability');
foreach(array('qalam_owner','qalam_manager','tutor_instructor','subscriber') as $role){$ok(strpos($r230,"'{$role}'")!==false,'design denial role '.$role);}
$ok(strpos($r230,'$role->remove_cap( QALAM_230_DESIGN_CAP )')!==false,'design cap stripped from customer roles');
$ok(strpos($r230,"add_menu_page(")!==false && strpos($r230,"'استوديو تصميم قلم'")!==false,'protected design studio menu');
$ok(strpos($r230,"unset( \$all['design'] )")!==false,'native design tab removed from customer dashboard');
$ok(strpos($r210,"qalam_230_filter_settings_for_user")!==false,'operational settings filtered by role');
$ok(strpos($r210,"qalam_230_user_can_manage_settings_tab")!==false,'settings save checks role matrix');
foreach(array('royal-purple','deep-navy','emerald','ruby','midnight') as $palette){$ok(strpos($r230,"'{$palette}'")!==false,'palette '.$palette);}
foreach(array('platform_name','teacher_name','teacher_bio','whatsapp','whatsapp_message','logo_url','hero_image_url','teacher_image_url') as $key){$ok(strpos($r230,"'{$key}'")!==false,'brand field '.$key);}
$ok(substr_count($r230,'مؤسسة قلم للخدمات الإلكترونية')>=2,'mandatory Qalam company branding');
$ok(substr_count($r230,'بكل فخر ❤️ صنع في مصر')>=2,'mandatory Made in Egypt branding');
$ok(strpos($r230,"update_option( 'blogname'")!==false,'platform name syncs WordPress public identity');
$ok(strpos($r230,"\$tutor_option['brand_color']")!==false,'palette syncs native LMS brand color');
$ok(strpos($r230,"add_action( 'template_redirect', 'qalam_230_render_public_home', 1 )")!==false,'theme-independent homepage');
$ok(strpos($r230,'qalam_230_strip_theme_assets_from_home')!==false && strpos($r230,'get_stylesheet_directory_uri')!==false,'active theme assets stripped from managed homepage');
$ok(strpos($r230,"new WP_Query")!==false && strpos($r230,"'post_type' => 'courses'")!==false,'published courses on homepage');
$ok(strpos($r230,'qalam_230_whatsapp_url')!==false,'WhatsApp CTA');
$ok(strpos($r220,"qalam_230_brand")!==false,'login uses tenant brand');
$ok(strpos($r210,"qalam_230_brand")!==false,'dashboard uses tenant platform name');
$ok(strpos($studio,'.qalam-design-grid')!==false && strpos($studio,'.qalam-palette-list')!==false,'design studio responsive UI');
$ok(strpos($public,'.qalam-hero')!==false && strpos($public,'.qalam-course-grid')!==false && strpos($public,'.qalam-public-footer')!==false,'public platform design system');
$ok(strpos($js,'wp.media')!==false,'media library integration');
if($fail){fwrite(STDERR,"FAIL qalam-230-platform-studio-contracts\n - ".implode("\n - ",array_unique($fail))."\n");exit(1);}echo "PASS qalam-230-platform-studio-contracts (role-gated settings + protected design studio + managed public homepage)\n";
