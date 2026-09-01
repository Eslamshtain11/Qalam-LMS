<?php
/**
 * Bundled Runtime: Qalam LMS Pro features
 * Description: الميزات المتقدمة لمنصة قلم: الشهادات والفصول المباشرة والواجبات وسجل الدرجات والتقارير والتكاملات.
 * Author: مؤسسة قلم للخدمات الإلكترونية
 * Runtime Version: 4.0.4 (bundled)
 * Requires PHP: 7.4
 * Requires at least: 5.3
 * Tested up to: 7.0
 * Text Domain: tutor-pro
 * Domain Path: /languages/
 *
 * @package TutorPro
 */

use TUTOR_PRO\Init as TutorProPlugin;

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/vendor/autoload.php';

// Qalam security quarantine: donor license injection and HTTP proxy removed.


/**
 * Tutor Pro dependency on Tutor core
 *
 * Define Tutor core version on that Tutor Pro is dependent to run,
 * without require version pro will just show admin notice to install require core version.
 *
 * @since 2.0.0
 */
define( 'TUTOR_CORE_REQ_VERSION', '4.0.4' );
define( 'TUTOR_PRO_VERSION', '4.0.4' );
define( 'TUTOR_PRO_FILE', __FILE__ );
define( 'QALAM_LMS_PRO_FILE', __FILE__ );
define( 'QALAM_LMS_PRO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load tutor-pro text domain for translation
 *
 * @since 1.0.0
 */
add_action(
	'init',
	function () {
		load_plugin_textdomain( 'tutor-pro', false, dirname( plugin_basename( TUTOR_PRO_FILE ) ) . '/languages' );
	}
);


/**
 * Qalam recovery profile.
 * 0.3.3 forced every Pro add-on on before the Pro runtime had completed booting.
 * That made one failing add-on capable of taking down the whole WordPress request.
 *
 * 0.3.4 never auto-enables add-ons. On first activation it clears only the
 * development-era forced flags, then lets the normal Add-ons screen enable each
 * feature intentionally after the Pro runtime is healthy.
 */
function qalam_pro_recovery_reset_forced_addons(): void {
    if ( get_option( 'qalam_lms_pro_recovery_reset_034' ) ) {
        return;
    }

    $config = get_option( 'tutor_addons_config', array() );
    $config = is_array( $config ) ? $config : array();
    foreach ( $config as $basename => &$addon ) {
        $normalized = str_replace( '\\\\', '/', (string) $basename );
        if ( false !== strpos( $normalized, '/addons/' ) &&
            ( 0 === strpos( $normalized, 'qalam-lms-pro/' ) || 0 === strpos( $normalized, 'tutor-pro/' ) ) ) {
            if ( ! is_array( $addon ) ) {
                $addon = array();
            }
            $addon['is_enable'] = 0;
        }
    }
    unset( $addon );

    update_option( 'tutor_addons_config', $config, false );
    update_option( 'qalam_lms_pro_recovery_reset_034', 1, false );
}

register_activation_hook( __FILE__, 'qalam_pro_recovery_reset_forced_addons' );

$GLOBALS['qalam_lms_pro_runtime'] = new TutorProPlugin();
$GLOBALS['qalam_lms_pro_runtime']->run();
