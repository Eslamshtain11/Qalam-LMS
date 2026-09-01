<?php
$root=dirname(__DIR__);$main=file_get_contents($root.'/qalam-lms.php');$r230=file_get_contents($root.'/qalam/release-230.php');$r270=file_get_contents($root.'/qalam/release-270.php');$r280=file_get_contents($root.'/qalam/release-280.php');$css=file_get_contents($root.'/assets/css/qalam-reference-fidelity.css');$js=file_get_contents($root.'/assets/js/qalam-reference-fidelity.js');
$checks=array(
'version'=>strpos($main,'0.32.0')!==false,
'bootstrap'=>strpos($main,"require_once __DIR__ . '/qalam/release-280.php';")!==false,
'academy palette'=>strpos($r230,"'academy-sky'")!==false&&strpos($r230,"'#14B8E6'")!==false,
'individual palette'=>strpos($r230,"'individual-crimson'")!==false&&strpos($r230,"'#D9284D'")!==false,
'migration preserves operator colors'=>strpos($r280,'$has_custom')!==false&&strpos($r280,"array( '', 'royal-purple' )")!==false,
'css dependency'=>strpos($r280,"array( 'qalam-reference-precision' )")!==false,
'js dependency'=>strpos($r280,"array( 'qalam-reference-system' )")!==false,
'academy discovery'=>strpos($r280,'q28-academy-picker-section')!==false&&strpos($r280,'q28-academy-teacher-card')!==false,
'individual hero'=>strpos($r280,'q28-individual-portrait')!==false,
'no fake honor placeholder'=>strpos($r280,'q28-honor-shell')===false,
'homepage takeover'=>strpos($r280,"remove_action( 'template_redirect', 'qalam_270_render_home', 1 )")!==false,
'archive calibration'=>strpos($r280,'qalam_280_course_archive_intro')!==false,
'picker js'=>strpos($js,'data-q280-picker-go')!==false,
'dark variants'=>strpos($css,'data-qalam-mode=dark')!==false,
'mobile breakpoint'=>strpos($css,'@media(max-width:720px)')!==false,
'production runtime clean'=>strpos($r270,'qalam_270_runtime_probe')===false,
'mandatory branding'=>strpos($r230,'مؤسسة قلم للخدمات الإلكترونية')!==false&&strpos($r230,'بكل فخر')!==false,
'no remote reference assets'=>preg_match('#https?://(?:www\\.)?(?:bassthalk\\.com|ahmed-elgohary\\.com)#i',$r280.$css.$js)===0,
);
$fail=array_keys(array_filter($checks,fn($v)=>!$v));if($fail){fwrite(STDERR,'FAIL: '.implode(', ',$fail)."\n");exit(1);}echo "Qalam 0.28 reference fidelity contracts: PASS\n";
