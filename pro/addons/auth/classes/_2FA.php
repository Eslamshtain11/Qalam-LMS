<?php
/**
 * Handle 2FA Login logic.
 *
 * @package TutorPro\Auth
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.1.9
 */

namespace TutorPro\Auth;

use Tutor\Helpers\SessionHelper;
use TUTOR\Input;
use Qalam\Security\OtpSecurity;
use Qalam\Security\TotpService;

/**
 * Two Factor Auth Class.
 *
 * @since 2.1.9
 */
class _2FA {

	const MINUTE_IN_SECONDS = 60;

	/**
	 * Register hooks.
	 *
	 * @since 2.1.9
	 *
	 * @return void
	 */
	public function __construct() {
		/**
		 * Hook `template_redirect` to `template_include`
		 * for elementor custom header footer support.
		 *
		 * @since 2.4.0
		 */
		add_filter( 'template_include', array( $this, 'get_login_otp_page' ), 999 );

		add_filter( 'wp_authenticate_user', array( $this, 'check_login' ), 11, 2 );
		add_action( 'wp_ajax_nopriv_tutor_verify_login_otp', array( $this, 'verify_login_otp' ) );
	}

	/**
	 * OTP verify page
	 *
	 * @since 2.1.9
	 *
	 * @param string $template template path.
	 *
	 * @return string template path.
	 */
	public function get_login_otp_page( $template ) {
		if ( 'tutor-2fa' === Input::get( 'step' ) && null !== SessionHelper::get( 'tutor_login_otp' ) ) {
			$template = tutor_auth()->views . 'login-otp.php';
			if ( file_exists( $template ) ) {
				remove_all_filters( 'template_include' );
				return $template;
			}
		}

		return $template;
	}

	/**
	 * Get OTP page URL.
	 *
	 * @since 2.1.9
	 *
	 * @return string
	 */
	public function get_login_otp_page_url() {
		return get_home_url() . '?step=tutor-2fa';
	}

	/**
	 * E-mail OTP handler.
	 *
	 * @since 2.1.9
	 *
	 * @param \WP_User $user user object.
	 *
	 * @return void
	 */
	private function handle_email_otp( \WP_User $user ) {
		$result = OtpSecurity::challenge( $user, 'email', Input::has( 'rememberme' ), true );
		if ( is_wp_error( $result ) ) { return $result; }
		wp_safe_redirect( $this->get_login_otp_page_url() );
		exit;
	}

	private function handle_totp( \WP_User $user ) {
		if ( ! TotpService::is_enrolled( $user->ID ) ) {
			return new \WP_Error( 'invalid_totp_setup', __( 'Authenticator app is required but is not configured for this account.', 'tutor-pro' ) );
		}
		$result = OtpSecurity::challenge( $user, 'totp', Input::has( 'rememberme' ), true );
		if ( is_wp_error( $result ) ) { return $result; }
		wp_safe_redirect( $this->get_login_otp_page_url() );
		exit;
	}

	/**
	 * Check login.
	 *
	 * @since 2.1.9
	 *
	 * @param mixed  $user      user object or WP Error.
	 * @param string $password  provided password.
	 *
	 * @return mixed
	 */
	public function check_login( $user, $password ) {

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( wp_check_password( $password, $user->user_pass ) ) {

			$enabled = Settings::is_2fa_enabled();
			if ( ! $enabled ) {
				return $user;
			}

			$location = Settings::get_2fa_location();
			$method   = Settings::get_2fa_method();

			if ( 'email' === $method ) {
				if ( ! tutor_utils()->is_addon_enabled( 'tutor-email' ) ) {
					return new \WP_Error( 'invalid_2fa_setup', __( 'Login failed due to incorrect 2FA setup. Please contact the site administrator.', 'tutor-pro' ) );
				}
				if ( 'both' === $location ) {
					return $this->handle_email_otp( $user );
				}

				if ( 'wp_login' === $location && Utils::is_request_from_wp_login() ) {
					return $this->handle_email_otp( $user );
				}

				if ( 'tutor_login' === $location && Utils::is_request_from_tutor() ) {
					return $this->handle_email_otp( $user );
				}
			}
			if ( 'totp' === $method ) {
				if ( 'both' === $location || ( 'wp_login' === $location && Utils::is_request_from_wp_login() ) || ( 'tutor_login' === $location && Utils::is_request_from_tutor() ) ) {
					return $this->handle_totp( $user );
				}
			}
		}

		return $user;
	}

	/**
	 * Do login
	 *
	 * @since 2.1.9
	 *
	 * @param \WP_User $user     WP_User object.
	 * @param boolean  $remember  remember.
	 *
	 * @return void
	 */
	private function do_login( \WP_User $user, bool $remember = false ) {
		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID, $remember );

		apply_filters( 'authenticate', $user, $user->user_login, '' );
		do_action( 'wp_login', $user->user_login, $user );
	}

	/**
	 * Verify login OTP.
	 *
	 * @since 2.1.9
	 *
	 * @return void
	 */
	public function verify_login_otp() {
		tutor_utils()->checking_nonce();
		$code = sanitize_text_field( (string) Input::post( 'otp', '' ) );
		if ( '' === $code ) { wp_send_json_error( array( 'message' => __( 'OTP code required', 'tutor-pro' ) ) ); }
		$data = SessionHelper::get( 'tutor_login_otp' );
		if ( OtpSecurity::expired( $data ) || empty( $data->user ) || ! $data->user instanceof \WP_User ) {
			SessionHelper::unset( 'tutor_login_otp' );
			wp_send_json_error( array( 'message' => __( 'Verification request expired. Please login again.', 'tutor-pro' ) ) );
		}
		$method = isset( $data->method ) ? (string) $data->method : 'email'; $matched=false;
		if ( 'totp' === $method ) { $matched = TotpService::verify_user_code( $data->user->ID, $code ) || TotpService::consume_recovery_code( $data->user->ID, $code ); }
		else { $matched = OtpSecurity::verify_code( $code, isset( $data->code_hash ) ? $data->code_hash : '' ); }
		if ( $matched ) {
			$this->do_login( $data->user, ! empty( $data->remember ) ); SessionHelper::unset( 'tutor_login_otp' ); SessionHelper::unset( 'resent_otp_at' );
			$url=tutor_utils()->tutor_dashboard_url(); if(function_exists('qalam_210_user_is_managed')&&qalam_210_user_is_managed())$url=qalam_210_dashboard_url(); elseif(current_user_can('administrator'))$url=get_admin_url(); wp_send_json_success(array('message'=>__( 'OTP matched. Redirecting...', 'tutor-pro' ),'redirect_url'=>$url));
		}
		OtpSecurity::bump( $data ); wp_send_json_error(array('message'=>__( 'OTP not matched.', 'tutor-pro' )));
	}

}
