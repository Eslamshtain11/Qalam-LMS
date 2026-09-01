<?php
$root = dirname(__DIR__);
$fail = 0;
function check281($ok,$label){global $fail;echo ($ok?"PASS":"FAIL")." | $label\n"; if(!$ok){$fail++;}}
$main=file_get_contents($root.'/qalam-lms.php');
$r270=file_get_contents($root.'/qalam/release-270.php');
$r281=file_get_contents($root.'/qalam/release-281.php');
$readme=file_get_contents($root.'/readme.txt');
check281(strpos($main,"Version: 0.32.0")!==false,'production version header');
check281(strpos($main,"QALAM_LMS_PRODUCT_VERSION', '0.32.0")!==false,'production product constant');
check281(strpos($main,"release-281.php")!==false,'production cleanup release loaded');
check281(strpos($r270,'qalam_270_runtime_probe')===false,'runtime probe removed');
check281(strpos($r270,'qalam-qa.com')===false,'QA host lock removed');
check281(strpos($r281,"delete_option( 'qalam_270_runtime_report_v1' )")!==false,'staging report option cleanup');
check281(strpos($readme,'Tutor LMS')===false,'root readme Qalam-only');
foreach(['qalam-qa.com','tutor.gpltimes.com',"'sslverify' => false",'BEGIN PRIVATE KEY'] as $bad){
  $found=false;
  $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
  foreach($it as $f){
    if(!$f->isFile())continue;
    $path=$f->getPathname();
    if(strpos($path,DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR)!==false)continue;
    if(strpos($path,DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)!==false)continue;
    if('qalam-qa.com'===$bad && basename($path)==='release-290.php')continue; // Approved production Cloud endpoint.
    if($f->getSize()>2*1024*1024)continue;
    $ext=strtolower($f->getExtension());
    if(!in_array($ext,['php','js','css','json','txt','md','xml','yml','yaml','html','htm','po','pot'],true))continue;
    $c=@file_get_contents($path);
    if($c!==false && strpos($c,$bad)!==false){$found=true;break;}
  }
  check281(!$found,'runtime quarantine '.$bad);
}
exit($fail?1:0);
