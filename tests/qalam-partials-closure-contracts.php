<?php
/** Source-level contracts for Qalam 0.16 partial-work closure. No WordPress bootstrap required. */
$root=dirname(__DIR__);
$fail=[];
$ok=function($cond,$label)use(&$fail){if(!$cond)$fail[]=$label;};
$read=function($rel)use($root){$p=$root.'/'.$rel;return is_file($p)?file_get_contents($p):'';};
$main=$read('qalam-lms.php');
$r140=$read('qalam/release-140.php');$r150=$read('qalam/release-150.php');$r160=$read('qalam/release-160.php');
$r080=$read('qalam/release-080.php');$r081=$read('qalam/release-081.php');$r100=$read('qalam/release-100.php');
$player=$read('assets/js/qalam-video-player.js');$layers=$read('assets/js/qalam-160-closure.js');$css=$read('assets/css/qalam-160-closure.css');
$security=$read('qalam/security/SecurityBridge.php');$totp=$read('qalam/security/TotpService.php');

$ok(strpos($main,'QALAM_LMS_PRODUCT_VERSION')!==false && strpos($r160,'0.16.0-partials-closure')!==false,'closure baseline/version marker');
$ok(strpos($main,'qalam/security/TotpService.php')!==false && strpos($main,'qalam/security/SecurityBridge.php')!==false,'security bootstrap');
$ok(strpos($read('classes/Tutor.php'),"__( 'معلم', 'tutor' )")!==false,'instructor visible label');
$ok(strpos($read('views/onboarding.php'),'qalam-logo.svg')!==false,'onboarding Qalam logo');
$ok(strpos($read('views/pages/welcome.php'),'tutorlms.com/pricing')===false,'welcome no donor pricing');
$ok(strpos($r140,'qalam_140_exam_share_url')!==false && strpos($r150,'qalam_150_front_exam_entry')!==false,'standalone exam frontend gate');
$ok(strpos($r080,'qalam_080_dynamic_last_attempt')!==false && strpos($r080,"'last'=>")!==false,'dynamic exam last-attempt avoidance');
$ok(strpos($r081,'qalam_081_bank_bulk_delete')!==false && strpos($r081,'qalam_081_save_bank_question_basic')!==false,'question bank CRUD');
$ok(strpos($r081,'qalam_081_generation_worker_ping')!==false && strpos($r080,'qalam_080_ajax_start_generation')!==false,'AI async generation');
$ok(strpos($r100,'qalam_100_force_single_choice')!==false,'single choice');
$ok(strpos($player,'setQalamCaptions')!==false && strpos($player,'trackAdEvent')!==false,'player captions/ad tracking');
$ok(strpos($r150,"qalam_student")!==false && strpos($r160,'qalam_160_sync_existing_students_batch')!==false,'Qalam student role');
$ok(strpos($r150,'qalam_150_render_student_profile')!==false,'student admin record');
$ok(strpos($r150,'qalam_150_currency_symbol')!==false,'currency resolver');
$ok(strpos($layers,'qalam-select-open')!==false && strpos($css,'--q16-drop')!==false,'dropdown layering');
$ok(strpos($security,"return '';\n    }")!==false && strpos($security,'qalam_private_file')!==false,'private files fail closed/signed');
$ok(strpos($security,'qalam_totp_nonce')!==false && strpos($totp,'LAST_COUNTER_META')!==false,'TOTP nonce/replay');
$ok(strpos($read('pro/addons/auth/classes/_2FA.php'),"if ( 'email' === \$method )")!==false,'2FA email/totp method split');
$ok(strpos($read('pro/addons/h5p/src/Quiz.php'),'server')!==false,'H5P trusted-score source path');
$ok(strpos($read('pro/addons/content-drip/classes/ContentDrip.php'),'missing or invalid')!==false,'drip fail closed');
$ok(strpos($read('pro/gift-course/GiftScheduler.php'),'reference_id')!==false && strpos($read('pro/gift-course/EventHandler.php'),'revoke_gifts_for_order')!==false,'gift idempotency/revoke');
$ok(strpos($read('pro/addons/social-login/includes/Authentication/Authentication.php'),'wp_safe_remote_get')!==false && strpos($read('pro/addons/social-login/includes/Authentication/Authentication.php'),'SocialIdentityService')!==false,'social auth hardening');

// Add-on source preservation contract.
$addons=['auth','buddypress','calendar','content-bank','content-drip','course-bundle','enrollments','google-classroom','google-meet','gradebook','h5p','pmpro','quiz-import-export','restrict-content-pro','social-login','subscription','tutor-assignments','tutor-certificate','tutor-course-attachments','tutor-course-preview','tutor-email','tutor-multi-instructors','tutor-notifications','tutor-prerequisites','tutor-report','tutor-weglot','tutor-wpml','tutor-zoom','wc-subscriptions'];
foreach($addons as $addon){$dir=$root.'/pro/addons/'.$addon;$php=glob($dir.'/*.php');$ok(is_dir($dir) && ($php || iterator_count(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS)))>0),'addon '.$addon);}

// Quarantine + no exact visible old product/upsell strings outside Qalam compatibility dictionaries/tests/vendor.
$badSecurity=['tutor.gpltimes.com',"'sslverify' => false",'themeum-products/v1'];
$visible=['Tutor Instructor','Tutor LMS Pro','Upgrade to Pro','Get Tutor LMS Pro'];
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
foreach($it as $f){if(!$f->isFile()||!in_array(strtolower($f->getExtension()),['php','js'],true))continue;$path=$f->getPathname();if(strpos($path,DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)!==false||strpos($path,DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR)!==false)continue;$data=file_get_contents($path);
 foreach($badSecurity as $bad){if(strpos($data,$bad)!==false)$fail[]='quarantine '.$bad.' in '.substr($path,strlen($root)+1);}
 foreach($visible as $bad){if(strpos($path,DIRECTORY_SEPARATOR.'qalam'.DIRECTORY_SEPARATOR.'release-140.php')!==false)continue;if(strpos($data,$bad)!==false)$fail[]='visible old brand '.$bad.' in '.substr($path,strlen($root)+1);}
}

if($fail){fwrite(STDERR,"FAIL qalam-partials-closure-contracts\n - ".implode("\n - ",array_values(array_unique($fail)))."\n");exit(1);}echo "PASS qalam-partials-closure-contracts\n";
