<?php
$root=dirname(__DIR__);$fail=0;
function c271($ok,$label){global $fail;echo ($ok?'PASS':'FAIL')." | $label\n";if(!$ok)$fail++;}
$r210=file_get_contents($root.'/qalam/release-210.php');
$r230=file_get_contents($root.'/qalam/release-230.php');
$r270=file_get_contents($root.'/qalam/release-270.php');
c271(strpos($r210,'qalam_owner')!==false && strpos($r210,'qalam_manager')!==false,'platform roles retained');
c271(strpos($r230,'qalam_230_user_can_manage_settings_tab')!==false,'settings permission function retained');
c271(strpos($r270,'qalam_270_manager_settings_tabs')!==false,'manager allowlist retained');
c271(strpos($r270,"'monetization'")===false || strpos($r270,'qalam_270_manager_settings_tabs') < strpos($r270,"'monetization'"),'manager allowlist remains narrow');
exit($fail?1:0);
