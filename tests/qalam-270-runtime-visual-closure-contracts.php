<?php
$root=dirname(__DIR__);$fail=0;
function c270($ok,$label){global $fail;echo ($ok?'PASS':'FAIL')." | $label\n";if(!$ok)$fail++;}
$r=file_get_contents($root.'/qalam/release-270.php');
c270(strpos($r,'qalam_270_manager_settings_tabs')!==false,'manager allowlist exists');
c270(strpos($r,"'general'")!==false && strpos($r,"'course'")!==false,'manager daily settings retained');
c270(strpos($r,'qalam_270_runtime_probe')===false,'QA runtime probe absent');
c270(strpos($r,'qalam-qa.com')===false,'QA host references absent');
c270(strpos($r,'qalam_270_enqueue_precision_assets')!==false,'reference precision assets retained');
exit($fail?1:0);
