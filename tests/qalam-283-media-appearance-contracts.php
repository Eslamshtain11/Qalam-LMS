<?php
$root=dirname(__DIR__);
$main=file_get_contents($root.'/qalam-lms.php');
$studio=file_get_contents($root.'/qalam/release-230.php');
$home=file_get_contents($root.'/qalam/release-280.php');
$css=file_get_contents($root.'/assets/css/qalam-reference-fidelity.css');
$svg=file_get_contents($root.'/assets/images/qalam-admin-menu.svg');
$checks=[
 'version'=>strpos($main,'Version: 0.32.0')!==false,
 'release 283 loaded'=>strpos($main,"release-283.php")!==false,
 'about image default'=>strpos($studio,"'about_image_url'")!==false,
 'about image sanitized'=>strpos($studio,"'about_image_url','youtube'")!==false,
 'about image field in studio'=>strpos($studio,"صورة قسم النبذة (مستقلة عن صورة الـHero)")!==false,
 'hero prefers hero image'=>strpos($home,"\$portrait = \$brand['hero_image_url']")!==false,
 'about uses dedicated image'=>strpos($home,"! empty( \$brand['about_image_url'] )")!==false,
 'distinct about composition'=>strpos($home,'q28-about-media')!==false && strpos($home,'q28-about-caption')!==false,
 'light hero tokenized'=>strpos($css,'[data-qalam-mode=light] .q28-individual-hero')!==false,
 'dark hero tokenized'=>strpos($css,'[data-qalam-mode=dark] .q28-individual-hero')!==false,
 'about ratio distinct'=>strpos($css,'.q28-about-image{')!==false && strpos($css,'aspect-ratio:4/3')!==false,
 'sidebar icon visually padded'=>strpos($svg,'translate(2.5 2.5) scale(.75)')!==false,
];
$fail=[];foreach($checks as $k=>$v){echo($v?'PASS ':'FAIL ').$k."\n";if(!$v)$fail[]=$k;}exit($fail?1:0);
