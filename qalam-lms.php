<?php
/**
 * Plugin Name: Qalam LMS
 * Description: منصة قلم التعليمية لإدارة الدورات والدروس والاختبارات والمعلمين والطلاب بتجربة عربية كاملة.
 * Author: مؤسسة قلم للخدمات الإلكترونية
 * Version: 0.32.0
 * Requires PHP: 7.4
 * Requires at least: 5.3
 * Tested up to: 7.1
 * License: GPLv2 or later
 * Text Domain: tutor
 *
 * @package Tutor
 */

use TUTOR\Tutor;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'QALAM_LMS_UNIFIED' ) ) {
    define( 'QALAM_LMS_UNIFIED', true );
}
if ( ! defined( 'QALAM_LMS_PRODUCT_VERSION' ) ) {
    define( 'QALAM_LMS_PRODUCT_VERSION', '0.32.0-mobile-push-bridge' );
}

/*
 * Qalam bootstrap notes:
 * - Keep Tutor internal namespaces/hooks for parity.
 * - Use a distinct install basename (qalam-lms/qalam-lms.php).
 * - Do NOT run the heavy Tutor activation routine inside WordPress' activation sandbox.
 *   It is deferred to the first authenticated admin request so a recoverable runtime
 *   issue cannot make WordPress report "fatal error during activation".
 */

// If another Tutor runtime is already active, do not redeclare its constants/runtime.
// Qalam can be installed beside Tutor, but only one Tutor-derived runtime may be active
// until the namespace isolation phase is completed.
if ( defined( 'TUTOR_FILE' ) && wp_normalize_path( (string) TUTOR_FILE ) !== wp_normalize_path( __FILE__ ) ) {
    add_action( 'admin_notices', static function () {
        echo '<div class="notice notice-error"><p><strong>Qalam LMS:</strong> عطّل أي نسخة قديمة أو مشتقة من نفس المحرك أولًا ثم فعّل Qalam LMS. لا يمكن تشغيل نسختين من نفس المحرك في نفس اللحظة.</p></div>';
    } );
    return;
}

if ( ! defined( 'TUTOR_VERSION' ) ) {
    define( 'TUTOR_VERSION', '4.0.4' );
}
if ( ! defined( 'TUTOR_FILE' ) ) {
    define( 'TUTOR_FILE', __FILE__ );
}
if ( ! defined( 'QALAM_LMS_FILE' ) ) {
    define( 'QALAM_LMS_FILE', __FILE__ );
}
if ( ! defined( 'QALAM_LMS_PLUGIN_BASENAME' ) ) {
    define( 'QALAM_LMS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}
if ( ! defined( 'TUTOR_ENV' ) ) {
    define( 'TUTOR_ENV', 'PROD' );
}

require_once __DIR__ . '/vendor/autoload.php';
if ( ! function_exists( 'qalam_lms_dictionary' ) ) {
    require_once __DIR__ . '/qalam/branding.php';
}
require_once __DIR__ . '/qalam/security/TotpService.php';
require_once __DIR__ . '/qalam/security/SecurityBridge.php';
require_once __DIR__ . '/qalam/security/QuizRevealSecurity.php';
require_once __DIR__ . '/qalam/security/AssignmentSubmissionSecurity.php';
require_once __DIR__ . '/qalam/release-050.php';
require_once __DIR__ . '/qalam/release-060.php';
require_once __DIR__ . '/qalam/release-070.php';
require_once __DIR__ . '/qalam/release-080.php';
require_once __DIR__ . '/qalam/release-081.php';
require_once __DIR__ . '/qalam/release-090.php';
require_once __DIR__ . '/qalam/release-100.php';
require_once __DIR__ . '/qalam/release-140.php';
require_once __DIR__ . '/qalam/release-150.php';
require_once __DIR__ . '/qalam/release-160.php';
require_once __DIR__ . '/qalam/release-180.php';
require_once __DIR__ . '/qalam/release-190.php';
require_once __DIR__ . '/qalam/release-200.php';
require_once __DIR__ . '/qalam/release-210.php';
require_once __DIR__ . '/qalam/release-220.php';
require_once __DIR__ . '/qalam/release-230.php';
require_once __DIR__ . '/qalam/release-240.php';
require_once __DIR__ . '/qalam/release-260.php';
require_once __DIR__ . '/qalam/release-270.php';
require_once __DIR__ . '/qalam/release-280.php';
require_once __DIR__ . '/qalam/release-281.php';
require_once __DIR__ . '/qalam/release-283.php';
require_once __DIR__ . '/qalam/release-290.php';
require_once __DIR__ . '/qalam/release-300.php';

add_action( 'init', static fn () => load_plugin_textdomain( 'tutor', false, basename( __DIR__ ) . '/languages' ) );


/**
 * Preserve enabled/disabled Pro add-on states when Pro moves from the historical
 * qalam-lms-pro plugin directory into the bundled qalam-lms/pro runtime.
 * Old keys are intentionally retained as compatibility aliases; nothing is deleted.
 */
function qalam_lms_unified_migrate_addon_paths(): void {
    $target_version = '0.11.0-unified-rc1';
    if ( get_option( 'qalam_lms_unified_addon_migration' ) === $target_version ) {
        return;
    }

    $config = get_option( 'tutor_addons_config', array() );
    $config = is_array( $config ) ? $config : array();
    $next   = $config;
    $target_root = defined( 'TUTOR_PRO_FILE' ) ? dirname( plugin_basename( TUTOR_PRO_FILE ) ) : 'qalam-lms/pro';

    foreach ( $config as $key => $state ) {
        $normalized = str_replace( '\\', '/', (string) $key );
        $pos = strpos( $normalized, '/addons/' );
        if ( false === $pos ) {
            continue;
        }
        $suffix = substr( $normalized, $pos );
        $target = $target_root . $suffix;
        if ( ! array_key_exists( $target, $next ) ) {
            $next[ $target ] = $state;
        }
    }

    // Also seed current bundled keys from the two historical roots if only those exist.
    if ( defined( 'TUTOR_PRO_FILE' ) ) {
        $pro_path = trailingslashit( plugin_dir_path( TUTOR_PRO_FILE ) );
        $dirs = glob( $pro_path . 'addons/*', GLOB_ONLYDIR );
        $dirs = is_array( $dirs ) ? $dirs : array();
        foreach ( $dirs as $dir ) {
            $slug = sanitize_key( basename( $dir ) );
            $suffix = "/addons/{$slug}/{$slug}.php";
            $target = $target_root . $suffix;
            if ( isset( $next[ $target ] ) ) {
                continue;
            }
            foreach ( array( 'qalam-lms-pro' . $suffix, 'tutor-pro' . $suffix ) as $legacy ) {
                if ( isset( $config[ $legacy ] ) ) {
                    $next[ $target ] = $config[ $legacy ];
                    break;
                }
            }
        }
    }

    if ( $next !== $config ) {
        update_option( 'tutor_addons_config', $next, false );
    }
    update_option( 'qalam_lms_unified_addon_migration', $target_version, false );
}

/** Load the bundled Pro runtime after Core has completed its own bootstrap. */
function qalam_lms_load_bundled_pro(): void {
    $bundled = __DIR__ . '/pro/qalam-lms-pro.php';
    if ( ! is_file( $bundled ) ) {
        update_option( 'qalam_lms_unified_boot_error', 'Bundled Pro runtime is missing.', false );
        return;
    }

    // An older separately installed Pro must be deactivated before the unified package.
    // Do not redeclare the Pro runtime if it is already present in this request.
    if ( defined( 'TUTOR_PRO_FILE' ) ) {
        if ( wp_normalize_path( (string) TUTOR_PRO_FILE ) !== wp_normalize_path( $bundled ) ) {
            add_action( 'admin_notices', static function () {
                echo '<div class="notice notice-error"><p><strong>Qalam LMS:</strong> تم اكتشاف نسخة Qalam LMS Pro المنفصلة وهي مفعلة. عطّل النسخة المنفصلة مرة واحدة؛ النسخة الموحدة تحتوي Pro بداخلها بالفعل.</p></div>';
            } );
        }
        return;
    }

    require_once $bundled;
    qalam_lms_unified_migrate_addon_paths();
}

/** Lightweight activation: activation itself must never execute Tutor's heavy migration path. */
function qalam_lms_safe_activate(): void {
    update_option( 'qalam_lms_activation_pending', 1, false );
    update_option( 'qalam_lms_unified_activation_pending', 1, false );
    delete_option( 'qalam_lms_activation_error' );
    delete_option( 'qalam_lms_unified_boot_error' );
}
register_activation_hook( QALAM_LMS_FILE, 'qalam_lms_safe_activate' );
register_deactivation_hook( QALAM_LMS_FILE, array( Tutor::class, 'tutor_deactivation' ) );
register_uninstall_hook( QALAM_LMS_FILE, array( Tutor::class, 'tutor_uninstall' ) );

if ( ! function_exists( 'tutor_lms' ) ) {
    function tutor_lms() {
        return Tutor::get_instance();
    }
}
$GLOBALS['tutor'] = tutor_lms();

// Unified product: Core is ready, now load the bundled Pro runtime from the same plugin.
qalam_lms_load_bundled_pro();

/**
 * Complete activation after WordPress has successfully activated the plugin.
 * Any Throwable is captured as an admin-visible diagnostic instead of taking
 * the whole plugins screen down.
 */
function qalam_lms_finish_deferred_activation(): void {
    if ( ! get_option( 'qalam_lms_activation_pending' ) ) {
        return;
    }
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    try {
        Tutor::tutor_activate();
        qalam_lms_unified_migrate_addon_paths();
        if ( ! empty( $GLOBALS['qalam_lms_pro_runtime'] ) && is_object( $GLOBALS['qalam_lms_pro_runtime'] ) && method_exists( $GLOBALS['qalam_lms_pro_runtime'], 'tutor_pro_activate' ) ) {
            $GLOBALS['qalam_lms_pro_runtime']->tutor_pro_activate();
        }
        delete_option( 'qalam_lms_unified_activation_pending' );
        delete_option( 'qalam_lms_activation_pending' );
        delete_option( 'qalam_lms_activation_error' );
        update_option( 'qalam_lms_activation_ok', gmdate( 'c' ), false );
    } catch ( \Throwable $e ) {
        update_option( 'qalam_lms_activation_error', array(
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'time'    => gmdate( 'c' ),
        ), false );
        delete_option( 'qalam_lms_activation_pending' );
    }
}
add_action( 'admin_init', 'qalam_lms_finish_deferred_activation', 1 );

function qalam_lms_activation_error_notice(): void {
    $error = get_option( 'qalam_lms_activation_error' );
    if ( ! is_array( $error ) || empty( $error['message'] ) ) {
        return;
    }
    printf(
        '<div class="notice notice-error"><p><strong>Qalam LMS — خطأ تهيئة:</strong> %s <code>%s:%d</code></p></div>',
        esc_html( (string) $error['message'] ),
        esc_html( basename( (string) ( $error['file'] ?? '' ) ) ),
        (int) ( $error['line'] ?? 0 )
    );
}
add_action( 'admin_notices', 'qalam_lms_activation_error_notice' );
