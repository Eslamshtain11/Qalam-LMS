<?php
/** Qalam 0.26 internal surfaces + settings role matrix source contracts. */
$root = dirname( __DIR__ );
$main = file_get_contents( $root . '/qalam-lms.php' );
$r260 = file_get_contents( $root . '/qalam/release-260.php' );
$r230 = file_get_contents( $root . '/qalam/release-230.php' );
$r210 = file_get_contents( $root . '/qalam/release-210.php' );
$r240 = file_get_contents( $root . '/qalam/release-240.php' );
$dash = file_get_contents( $root . '/templates/dashboard.php' );
$managed = file_get_contents( $root . '/templates/qalam-managed-page.php' );
$css = file_get_contents( $root . '/assets/css/qalam-reference-system.css' );

$contracts = array(
    '0.26 version' => false !== strpos( $main, '0.32.0' ),
    '0.26 bootstrap' => false !== strpos( $main, "require_once __DIR__ . '/qalam/release-260.php';" ),
    'owner role matrix' => false !== strpos( $r260, "in_array( 'qalam_owner', \$roles, true )" ),
    'manager role matrix' => false !== strpos( $r260, "in_array( 'qalam_manager', \$roles, true )" ),
    'manager sensitive denial' => false !== strpos( $r260, "! qalam_260_sensitive_setting_tab( \$tab )" ),
    'design remains maintenance-only' => false !== strpos( $r260, "'design'" ) && false !== strpos( $r230, "QALAM_230_DESIGN_CAP" ),
    'compat settings delegates to 260' => false !== strpos( $r230, "qalam_260_user_can_manage_settings_tab" ),
    'settings save still checks role matrix' => false !== strpos( $r210, "qalam_230_user_can_manage_settings_tab" ),
    'settings access badge' => false !== strpos( $r210, 'qalam-settings-access-badge' ),
    'course archive surface' => false !== strpos( $r260, "return 'course-archive'" ),
    'single course surface' => false !== strpos( $r260, "return 'course-single'" ),
    'learning surface' => false !== strpos( $r260, "return 'learning'" ),
    'quiz surface' => false !== strpos( $r260, "return 'quiz'" ),
    'assignment surface' => false !== strpos( $r260, "return 'assignment'" ),
    'checkout surface' => false !== strpos( $r260, "return 'checkout'" ),
    'dashboard surface' => false !== strpos( $r260, "return 'dashboard'" ),
    'managed page template' => false !== strpos( $r260, 'qalam_260_managed_page_template' ) && false !== strpos( $managed, 'qalam_240_render_shell_header' ),
    'dashboard owns Qalam shell' => false !== strpos( $dash, 'QALAM_260_DASHBOARD_SHELL' ) && false !== strpos( $dash, 'qalam_240_render_shell_footer' ),
    'duplicate dashboard header prevented' => false !== strpos( $r240, 'QALAM_260_DASHBOARD_SHELL' ),
    'course internal CSS' => false !== strpos( $css, '.qalam-surface-course-single' ),
    'dashboard internal CSS' => false !== strpos( $css, '.qalam-surface-dashboard' ),
    'checkout internal CSS' => false !== strpos( $css, '.qalam-surface-checkout' ),
    'dark internal CSS' => false !== strpos( $css, 'html[data-qalam-mode=dark] .qalam-internal-surface' ),
    'academy/individual internal variants' => false !== strpos( $css, '.qalam-platform-academy.qalam-internal-surface' ) && false !== strpos( $css, '.qalam-platform-individual.qalam-internal-surface' ),
);
$failed = array();
foreach ( $contracts as $name => $ok ) {
    echo ( $ok ? 'PASS' : 'FAIL' ) . " — {$name}\n";
    if ( ! $ok ) { $failed[] = $name; }
}
exit( $failed ? 1 : 0 );
