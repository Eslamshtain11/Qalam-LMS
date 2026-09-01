<?php
$root=dirname(__DIR__);$fail=array();$ok=function($c,$m)use(&$fail){if(!$c)$fail[]=$m;};$read=function($r)use($root){return file_get_contents($root.'/'.$r);};
require_once $root.'/qalam/security/TotpService.php';
$ok(\Qalam\Security\TotpService::code_at('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ',59,30,8)==='94287082','TOTP RFC vector');
$auth=$read('pro/addons/auth/classes/_2FA.php');$ok(strpos($auth,'code_hash')!==false && strpos($auth,'TotpService::verify_user_code')!==false,'2FA hashed/TOTP');
$social=$read('pro/addons/social-login/includes/Authentication/Authentication.php');$ok(strpos($social,'file_get_contents( "https://')===false,'social safe HTTP');$ok(strpos($social,'hash_equals($client_id')!==false,'Google aud');$ok(strpos($social,'SocialIdentityService::bind_or_validate')!==false,'social binding');$ok(strpos($social,'prepare_social_2fa')!==false,'social 2FA');
$twitter=$read('pro/addons/social-login/includes/Lib/TwitterOauthService.php');$ok(strpos($twitter,'qalam_twitter_oauth_issued')!==false,'twitter state timestamp');
$h5p=$read('pro/addons/h5p/src/Quiz.php');$ok(strpos($h5p,"official Tutor grade MUST come from H5P's server-persisted result")!==false,'H5P trusted grade');
$drip=$read('pro/addons/content-drip/classes/ContentDrip.php');$ok(strpos($drip,'drip configuration is invalid')!==false && strpos($drip,'configured prerequisite is missing')!==false,'drip fail closed');
$bridge=$read('qalam/security/SecurityBridge.php');$ok(strpos($bridge,'qalam_private_file')!==false && strpos($bridge,"tutor/posts/attachments")!==false,'private file signed route');$ok(strpos($bridge,'_qalam_certificate_revoked')!==false,'certificate revoke');

$gift=$read('pro/gift-course/GiftScheduler.php').$read('pro/gift-course/GiftEnrollment.php').$read('pro/gift-course/EventHandler.php');$ok(strpos($gift,'idempotency key')!==false && strpos($gift,'_qalam_gift_reference')!==false && strpos($gift,'revoke_gifts_for_order')!==false,'gift entitlement/idempotency');
$cert=$read('pro/addons/tutor-certificate/classes/Certificate.php').$read('qalam/release-150.php');$ok(strpos($cert,"'is_revoked'")!==false && strpos($cert,'qalam_certificate_revoke')!==false,'certificate revoke UI/integrity');
$ai=$read('pro/tutorai/Helper.php');$ok(strpos($ai,'sanitize_custom_base_url')!==false && strpos($ai,'wp_safe_remote_get')!==false,'AI SSRF');
$assign=$read('pro/addons/tutor-assignments/classes/Assignments.php');$ok(strpos($assign,'can_user_manage')!==false || strpos($assign,'can_update_assignment')!==false,'assignment ownership');
foreach(array('tutor.gpltimes.com',"'sslverify' => false",'themeum-products/v1') as $bad){$found=false;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS)) as $f){if($f->getExtension()==='php' && strpos($f->getPathname(),DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR)===false && strpos(file_get_contents($f->getPathname()),$bad)!==false){$found=true;break;}}$ok(!$found,'quarantine '.$bad);}
if($fail){fwrite(STDERR,"FAIL\n - ".implode("\n - ",$fail)."\n");exit(1);}echo "PASS qalam-security-source-contracts\n";
