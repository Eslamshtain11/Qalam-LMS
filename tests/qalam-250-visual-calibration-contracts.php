<?php
/** Qalam 0.25 visual calibration source contracts. */
$root = dirname( __DIR__ );
$release = file_get_contents( $root . '/qalam/release-240.php' );
$css = file_get_contents( $root . '/assets/css/qalam-reference-system.css' );
$js = file_get_contents( $root . '/assets/js/qalam-reference-system.js' );
$main = file_get_contents( $root . '/qalam-lms.php' );

$contracts = array(
    'version bumped' => false !== strpos( $main, '0.32.0' ),
    'teacher image before logo fallback' => false !== strpos( $release, "\$brand['hero_image_url'] ?: ( \$brand['teacher_image_url'] ?: \$brand['logo_url'] )" ),
    'managed teacher card fallback' => false !== strpos( $release, 'q-ref-teacher-grid-managed' ),
    'mobile menu backdrop markup' => false !== strpos( $release, 'data-qalam-menu-backdrop' ),
    'menu is fixed overlay' => false !== strpos( $css, 'position:fixed' ) && false !== strpos( $css, '.q-ref-mobile-menu.is-open{display:grid;opacity:1' ),
    'menu body scroll lock' => false !== strpos( $css, 'html.qalam-menu-open,body.qalam-menu-open' ),
    'dark text contrast contract' => false !== strpos( $css, 'html[data-qalam-mode=dark] .q-ref-mini-stats strong{color:#fff!important}' ),
    'mobile hero line height contract' => false !== strpos( $css, 'line-height:1.24' ),
    'mobile header grid contract' => false !== strpos( $css, 'grid-template-columns:minmax(0,1fr) auto' ),
    'menu aria state JS' => false !== strpos( $js, "menu.setAttribute('aria-hidden',open?'false':'true')" ),
    'menu escape close JS' => false !== strpos( $js, "if(e.key==='Escape'){setMenu(false);}" ),
    'menu resize close JS' => false !== strpos( $js, 'if(window.innerWidth>1080){setMenu(false);}' ),
    'dark mode color scheme JS' => false !== strpos( $js, 'document.documentElement.style.colorScheme=mode' ),
);

$failed = array();
foreach ( $contracts as $name => $ok ) {
    echo ( $ok ? "PASS" : "FAIL" ) . " — {$name}\n";
    if ( ! $ok ) { $failed[] = $name; }
}
exit( $failed ? 1 : 0 );
