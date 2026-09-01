<?php
$root=dirname(__DIR__);
$main=file_get_contents($root.'/qalam-lms.php');
$dash=file_get_contents($root.'/qalam/release-210.php');
$css=file_get_contents($root.'/assets/css/qalam-admin-shell.css');
$js=file_get_contents($root.'/assets/js/qalam-admin-shell.js');
$checks=array(
 'version'=>false!==strpos($main,'Version: 0.32.0') && false!==strpos($main,"QALAM_LMS_PRODUCT_VERSION', '0.32.0"),
 'settings engine fallback'=>false!==strpos($dash,'function qalam_210_options_engine()') && false!==strpos($dash,"new \\Tutor\\Options_V2( false )"),
 'settings renderer uses engine'=>false!==strpos($dash,'function qalam_210_render_native_settings_section') && false!==strpos($dash,'qalam_210_render_native_settings_section($section)'),
 'settings blocks fallback'=>false!==strpos($dash,"method_exists( \$options, 'blocks' )"),
 'settings empty failure visible'=>false!==strpos($dash,'qalam-settings-render-error'),
 'dashboard uses real qalam logo'=>false!==strpos($dash,"images/qalam-mark.svg") && false===strpos($dash,'<div class="qalam-logo-mark">ق</div>'),
 'avatar is actionable'=>false!==strpos($dash,'data-qalam-user-toggle') && false!==strpos($dash,'data-qalam-user-menu'),
 'logout visible in account menu'=>false!==strpos($dash,'class="is-logout"') && substr_count($dash,'تسجيل الخروج')>=2,
 'sidebar foot remains reachable'=>false!==strpos($css,'flex:1 1 auto;min-height:0;overflow-y:auto') && false!==strpos($css,'height:100dvh'),
 'user menu styles'=>false!==strpos($css,'.qalam-user-menu.is-open{display:block}'),
 'user menu javascript'=>false!==strpos($js,"document.querySelector('[data-qalam-user-toggle]')") && false!==strpos($js,"userMenu.classList.toggle('is-open')"),
);
$fail=array();foreach($checks as $name=>$ok){echo($ok?'PASS ':'FAIL ').$name."\n";if(!$ok)$fail[]=$name;}exit($fail?1:0);
