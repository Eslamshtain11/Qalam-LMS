<?php
/**
 * Package Downloader
 *
 * @package TutorPro\Ecommerce
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 1.0.0
 */

namespace TutorPro\Ecommerce;

use TUTOR\Input;
use Tutor\Helpers\HttpHelper;
use Tutor\Traits\JsonResponse;
use Tutor\Helpers\PluginInstaller;

/**
 * Handle payment gateway install/remove
 */
class PackageDownloader {

	use JsonResponse;

	/**
	 * Register hooks
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'wp_ajax_tutor_install_payment_gateway', array( $this, 'ajax_install_payment_gateway' ) );
		add_action( 'wp_ajax_tutor_remove_payment_gateway', array( $this, 'ajax_remove_payment_gateway' ) );
	}

	/**
	 * Handle ajax request for downloading a gateway
	 *
	 * @since 1.0.0
	 *
	 * @return void send wp_json response
	 */
	public function ajax_install_payment_gateway() {
		tutor_utils()->checking_nonce();
		tutor_utils()->check_current_user_capability();

		$message         = '';
		$success         = true;
		$default_err_msg = __( 'Payment gateway download failed', 'tutor-pro' );

		$slug        = Input::post( 'slug' );
		$action_type = Input::post( 'action_type' );
		if ( ! empty( $slug ) ) {
			try {
				$basename = "tutor-{$slug}/tutor-{$slug}.php";

				if ( file_exists( WP_PLUGIN_DIR . '/' . $basename && 'upgrade' !== $action_type ) ) {
					if ( ! is_plugin_active( $basename ) ) {
						$response = activate_plugin( $basename );
						if ( is_wp_error( $response ) ) {
							$success = false;
							$message = $response->get_error_message();
						} else {
							$message = __( 'Payment gateway activated successfully', 'tutor-pro' );
						}
					} else {
						$message = __( 'Payment gateway already activated', 'tutor-pro' );
					}
				} else {
					$success = false;
					$message = __( 'Remote payment gateway package installation is disabled by the Qalam security quarantine.', 'tutor-pro' );
				}
			} catch ( \Throwable $th ) {
				$success = false;
				$message = $th->getMessage();
			}
		} else {
			$success = false;
			$message = __( 'Payment gateway slug is required.', 'tutor-pro' );
		}

		if ( $success ) {
			$this->json_response(
				$message
			);
		} else {
			$this->json_response(
				$message,
				'',
				HttpHelper::STATUS_BAD_REQUEST
			);
		}
	}


	/**
	 * Ajax handler to remove a installed payment gateway
	 *
	 * @since 3.0.0
	 *
	 * @return void send wp_json response
	 */
	public function ajax_remove_payment_gateway() {
		tutor_utils()->checking_nonce();
		tutor_utils()->check_current_user_capability();

		$slug = Input::post( 'slug' );
		if ( ! $slug ) {
			$this->json_response(
				__( 'Payment gateway slug is required.', 'tutor-pro' ),
				'',
				HttpHelper::STATUS_BAD_REQUEST
			);
		}

		// Ensure the necessary WordPress functions are loaded.
		if ( ! function_exists( 'deactivate_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! function_exists( 'delete_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		// Get the plugin path using the slug.
		$plugin_path = "tutor-$slug/tutor-$slug.php";

		deactivate_plugins( $plugin_path );

		$result = delete_plugins( array( $plugin_path ) );
		if ( is_wp_error( $result ) ) {
			$this->json_response(
				__( 'Failed', 'tutor-pro' ),
				$result->get_error_message(),
				HttpHelper::STATUS_INTERNAL_SERVER_ERROR
			);
		} else {
			$this->json_response(
				__( 'Payment gateway successfully removed!', 'tutor-pro' )
			);
		}
	}
}
