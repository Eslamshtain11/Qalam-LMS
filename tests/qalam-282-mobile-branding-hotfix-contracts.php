<?php
$root=dirname(__DIR__);
$admin=file_get_contents($root.'/classes/Admin.php');
$css=file_get_contents($root.'/assets/css/qalam-reference-fidelity.css');
$main=file_get_contents($root.'/qalam-lms.php');
$svg=file_get_contents($root.'/assets/images/qalam-admin-menu.svg');
$checks=[
 'version-family'=>preg_match('/Version: 0\.32\.0/',$main)===1,
 'qalam menu svg referenced'=>strpos($admin,"qalam-admin-menu.svg")!==false,
 'legacy tutor menu svg removed'=>strpos($admin,'viewBox="0 0 1000 1139"')===false,
 'academy mobile portrait crop'=>strpos($css,'.q28-academy-hero-media>img')!==false && strpos($css,'object-fit:cover')!==false,
 'academy mobile feature stack'=>strpos($css,'.q-ref-feature-grid-academy{grid-template-columns:1fr!important')!==false,
 'individual hero contrast'=>strpos($css,'.q28-individual-copy h1{color:#fff!important')!==false,
 'individual portrait crop'=>strpos($css,'.q28-individual-portrait img')!==false,
 'menu svg valid'=>strpos($svg,'<svg')!==false && strpos($svg,'Qalam')!==false,
];
$fail=[];foreach($checks as $k=>$v){echo($v?'PASS ':'FAIL ').$k."\n";if(!$v)$fail[]=$k;}exit($fail?1:0);
