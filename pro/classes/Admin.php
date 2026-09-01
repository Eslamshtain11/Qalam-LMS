<?php
/**
 * Handle Admin Menu for PRO
 *
 * @package TutorPro\Classes
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TUTOR_PRO;

use TUTOR\Permalink;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin
 */
class Admin {

	/**
	 * Register hooks
	 */
	public function __construct() {
		/**
		 * Load Conditional Constructor based on if Qalam LMS wordpress.org plugin installed or not
		 *
		 * @since v.1.0.0
		 *
		 * @updated v.1.4.9
		 */
		if ( defined( 'TUTOR_VERSION' ) && defined( 'TUTOR_FILE' ) ) {
			$this->load_constructor();
		} else {
			$this->load_constructor_if_no_tutor_installed();
		}
	}

	/**
	 * Constructor When TutorLMS regular version exists
	 */
	public function load_constructor() {
		add_action( 'upgrader_process_complete', array( $this, 'set_permalink_flag_on_upgrade' ), 10, 2 );
		add_action( 'tutor_addon_after_enable', array( $this, 'set_permalink_flag_on_addon_enable' ) );

		// Plugin Row Meta.
		add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
	}

	/**
	 * Constructor for when TutorLMS regular version not installed...
	 */
	public function load_constructor_if_no_tutor_installed() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_action_activate_tutor_free', array( $this, 'activate_tutor_free' ) );
		add_action( 'admin_init', array( $this, 'check_tutor_free_installed' ) );
		add_action( 'wp_ajax_install_tutor_plugin', array( $this, 'install_tutor_plugin' ) );
	}

	/**
	 * Register menu
	 *
	 * @return void
	 */
	public function register_menu() {
		if ( ! defined( 'TUTOR_VERSION' ) ) {
			add_menu_page( __( 'Qalam LMS', 'tutor-pro' ), __( 'Qalam LMS', 'tutor-pro' ), 'manage_tutor_instructor', 'tutor-install', array( $this, 'install_activate_tutor_free' ), 'dashicons-welcome-learn-more', 2 );
		}
	}

	/**
	 * Install active tutor free
	 *
	 * @return void
	 */
	public function install_activate_tutor_free() {
		include tutor_pro()->path . 'views/install-tutor.php';
	}

	/**
	 * Check tutor free is installed
	 *
	 * @return void
	 */
	public function check_tutor_free_installed() {
		if ( ! defined( 'TUTOR_VERSION' ) || ! defined( 'TUTOR_FILE' ) ) {
			add_action( 'admin_notices', array( $this, 'free_plugin_not_installed' ) );
		}
	}

	/**
	 * Free plugin installed but inactive notice.
	 *
	 * @return void
	 */
	public function free_plugin_installed_but_inactive_notice() {
		$this->free_plugin_not_installed();
	}

	/**
	 * When free plugin is not installed.
	 *
	 * @return void
	 */
	public function free_plugin_not_installed() {
		?>
		<div class="notice notice-error tutor-install-notice" style="direction:rtl;text-align:right">
			<div class="tutor-install-notice-inner">
				<div class="tutor-install-notice-icon"><img src="<?php echo esc_url( tutor_pro()->url . 'assets/images/qalam-logo.svg' ); ?>" alt="Qalam LMS"></div>
				<div class="tutor-install-notice-content"><h2>Qalam LMS Core مطلوب</h2><p>فعّل إضافة Qalam LMS Core أولًا ثم فعّل الميزات المتقدمة.</p></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Active tutor free.
	 *
	 * @return void
	 */
	public function activate_tutor_free() {
		if ( defined( 'QALAM_LMS_PLUGIN_BASENAME' ) ) {
			activate_plugin( QALAM_LMS_PLUGIN_BASENAME );
		}
	}


	/**
	 * Install tutor plugin.
	 *
	 * @return void
	 */
	public function install_tutor_plugin() {
		tutor_utils()->checking_nonce();

		include ABSPATH . 'wp-admin/includes/plugin-install.php';
		include ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			include ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		}
		if ( ! class_exists( 'Plugin_Installer_Skin' ) ) {
			include ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php';
		}

		$plugin = 'tutor';

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin,
				'fields' => array(
					'short_description' => false,
					'sections'          => false,
					'requires'          => false,
					'rating'            => false,
					'ratings'           => false,
					'downloaded'        => false,
					'last_updated'      => false,
					'added'             => false,
					'tags'              => false,
					'compatibility'     => false,
					'homepage'          => false,
					'donate_link'       => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			wp_die( esc_html( $api ) );
		}

		$title = sprintf(
			// translators: %s: plugin name.
			__( 'Installing Plugin: %s', 'tutor-pro' ),
			$api->name . ' ' . $api->version
		);
		$nonce = 'install-plugin_' . $plugin;
		$url   = 'update.php?action=install-plugin&plugin=' . urlencode( $plugin );

		$upgrader = new \Plugin_Upgrader( new \Plugin_Installer_Skin( compact( 'title', 'url', 'nonce', 'plugin', 'api' ) ) );
		$upgrader->install( $api->download_link );
		die();
	}

	/**
	 * Flush Tutor permalink rewrite rules after updates.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed $upgrader_object Upgrader instance.
	 * @param array $options      Extra arguments passed to the hook.
	 *
	 * @return void
	 */
	public function set_permalink_flag_on_upgrade( $upgrader_object, $options ) {
		Permalink::set_permalink_reset_flag(
			$upgrader_object,
			$options,
			tutor_pro()->basename,
			'tutor-pro'
		);
	}



	/**
	 * Set/reset the Tutor permalink flag when the addon is enabled.
	 *
	 * @since 4.0.0
	 *
	 * @param string $addon Addon file string.
	 *
	 * @return void
	 */
	public function set_permalink_flag_on_addon_enable( string $addon ): void {
		$addons = array(
			'tutor-pro/addons/tutor-assignments/tutor-assignments.php',
			'tutor-pro/addons/tutor-zoom/tutor-zoom.php',
			'tutor-pro/addons/google-meet/google-meet.php',
		);

		if ( in_array( $addon, $addons, true ) ) {
			Permalink::set_permalink_flag();
		}
	}

	/**
	 * Add plugin meta data in WP plugins list page
	 *
	 * @since 4.0.0
	 *
	 * @param array  $plugin_meta plugin meta data.
	 * @param string $plugin_file plugin file.
	 *
	 * @return array
	 */
	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( tutor_pro()->basename === $plugin_file ) {
			$plugin_meta[] = sprintf(
				'<a href="%s"><strong style="color: #03bd24">%s</strong></a>',
				esc_url( 'https://tutorlms.com/docs/' ),
				esc_html__( 'Documentation', 'tutor-pro' )
			);
			$plugin_meta[] = sprintf(
				'<a href="%s"><strong style="color: #03bd24">%s</strong></a>',
				esc_url( 'https://tutorlms.com/free-vs-pro/' ),
				esc_html__( 'Pro Features', 'tutor-pro' )
			);
			$plugin_meta[] = sprintf(
				'<a href="%s"><strong style="color: #03bd24">%s</strong></a>',
				esc_url( 'https://www.themeum.com/support/' ),
				esc_html__( 'Priority Support', 'tutor-pro' )
			);
		}

		return $plugin_meta;
	}
}
