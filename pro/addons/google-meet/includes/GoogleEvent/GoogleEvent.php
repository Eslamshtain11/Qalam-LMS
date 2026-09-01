<?php
/**
 * Manage Google Event operations
 *
 * @since v2.1.0
 *
 * @package TutorPro\GoogleMeet\GoogleEvent
 */

namespace TutorPro\GoogleMeet\GoogleEvent;

use Qalam\Security\PrivateSecretStore;
use Tutor\Helpers\UrlHelper;
use TUTOR\Input;
use Tutor\Traits\JsonResponse;
use TUTOR_PRO\Dashboard;
use TutorPro\GoogleMeet\GoogleMeet;
use TutorPro\GoogleMeet\Utilities\Utilities;
use TutorPro\GoogleMeet\Validator\Validator;

/**
 * Manage google events
 */
class GoogleEvent {

	use JsonResponse;

	/**
	 * Credential filename.
	 *
	 * @var string
	 */
	private $credential_filename = null;

	/**
	 * API credential path
	 *
	 * @var string
	 */
	private $credential_path = null;

	/**
	 * Access token path
	 *
	 * @var string
	 */
	private $token_path = null;

	/**
	 * Tutor JSON directory.
	 *
	 * @var string
	 */
	private $tutor_json_dir;

	/**
	 * Access token path
	 *
	 * @var string
	 */
	public $upload_dir = null;

	/**
	 * Init google service
	 *
	 * @var mixed
	 */
	public $service;

	/**
	 * Authorized client
	 *
	 * @var mixed
	 */
	public $client;

	/**
	 * App name
	 *
	 * @var string
	 */
	private $app_name;

	/**
	 * Redirect URI
	 *
	 * @var array
	 */
	public $google_callback_url;

	/**
	 * Set current username
	 *
	 * @var string
	 */
	public $username;

	/**
	 * Scopes required to make API request
	 *
	 * @var array
	 */
	private $required_scopes = array(
		\Google_Service_Calendar::CALENDAR,
		\Google_Service_Calendar::CALENDAR_EVENTS,
	);

	/**
	 * Current calendar type
	 *
	 * @var string
	 */
	public $current_calendar;

	/**
	 * Init props & resolve dependencies
	 *
	 * @since v2.1.0
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_google_callback' ), 99 );

		$owner_id               = 0;
		$this->current_calendar = 'primary';
		$this->tutor_json_dir   = 'tutor-json'; // Legacy directory name retained only for one-time migration.

		if ( ! function_exists( 'wp_get_current_user' ) ) {
			include ABSPATH . 'wp-includes/pluggable.php';
		}

		$current_user              = \wp_get_current_user();
		$owner_id                  = (int) $current_user->ID;
		$this->username            = md5( (string) $current_user->user_login );
		$this->credential_filename = "{$this->username}-credential.json";
		$this->upload_dir          = $owner_id > 0 ? trailingslashit( (string) PrivateSecretStore::directory( 'google-meet', $owner_id ) ) : '';

		$credential_path = $this->upload_dir ? $this->upload_dir . 'credential.json' : '';
		$token_path      = $this->upload_dir ? $this->upload_dir . 'token.json' : '';

		// One-time migration from Tutor's historical public uploads directory.
		$legacy_dir = trailingslashit( wp_upload_dir()['basedir'] ) . trailingslashit( $this->tutor_json_dir );
		if ( $this->upload_dir ) {
			foreach ( array(
				$legacy_dir . $this->credential_filename => $credential_path,
				$legacy_dir . "{$this->username}-token.json" => $token_path,
			) as $legacy => $private ) {
				if ( is_file( $legacy ) && ! is_file( $private ) ) {
					$data = json_decode( (string) @file_get_contents( $legacy ), true );
					if ( is_array( $data ) && PrivateSecretStore::write_json( $private, $data ) ) {
						@unlink( $legacy );
					}
				}
			}
		}

		if ( $credential_path && is_file( $credential_path ) ) {
			$this->credential_path = $credential_path;
		}
		if ( $token_path && is_file( $token_path ) ) {
			$this->token_path = $token_path;
		}

		$is_qalam_surface = function_exists( 'qalam_220_is_qalam_surface_context' ) && qalam_220_is_qalam_surface_context( 'google_meet' );
		$is_qalam_callback = function_exists( 'qalam_220_is_google_meet_callback_request' ) && qalam_220_is_google_meet_callback_request();
		if ( ( $is_qalam_surface || $is_qalam_callback ) && function_exists( 'qalam_220_google_meet_callback_url' ) ) {
			$this->google_callback_url = array( untrailingslashit( qalam_220_google_meet_callback_url() ) );
		} else {
			$this->google_callback_url = array( untrailingslashit( admin_url() . 'admin.php?page=google-meet&tab=set-api' ) );
		}

		if ( ! is_admin() && ! $is_qalam_surface && ! $is_qalam_callback ) {
			global $wp_rewrite;

			if ( null === $wp_rewrite ) {
				$wp_rewrite = new \WP_Rewrite();
			}

			$this->google_callback_url = array(
				untrailingslashit( tutor_utils()->tutor_dashboard_url( 'google-meet/set-api' ) ),
				untrailingslashit( UrlHelper::add_query_params(
					tutor_utils()->tutor_dashboard_url( Dashboard::LIVE_CLASSES_MENU ),
					array(
						'nav' => Utilities::GOOGLE_MEET_TAB,
						'tab' => 'set-api',
					)
				) ),
			);
		}

		if ( $this->is_credential_loaded() ) {
			try {
				$this->validate_json_service_account_file( $credential_path );

				$json          = PrivateSecretStore::read_json( $credential_path );
				if ( ! is_array( $json ) ) { throw new \RuntimeException( __( 'Invalid JSON file', 'tutor-pro' ) ); }
				$web           = $json['web'] ?? array();
				$redirect_uris = $web['redirect_uris'] ?? array();

				$has_redirect_uri = array_intersect( $this->google_callback_url, $redirect_uris );
				$has_redirect_uri = array_values( $has_redirect_uri );

				$this->client = new \Google_Client();
				$this->client->setApplicationName( $this->app_name );
				$this->client->setAuthConfig( $this->credential_path );
				$this->client->setRedirectUri( $has_redirect_uri[0] ?? $this->google_callback_url[0] );
				$this->client->addScope( $this->required_scopes );
				$this->client->setAccessType( 'offline' );
				$this->client->setApprovalPrompt( 'force' );
				$assigned = ! ( $this->assign_token_to_client() === false );

				if ( $assigned ) {
					// Create service if the token assigned.
					$this->service = new \Google_Service_Calendar( $this->client );
				}
			} catch ( \Throwable $th ) {
				if ( file_exists( $this->credential_path ) ) {
					unlink( $this->credential_path );
				}

				if ( is_admin() ) {
					add_action(
						'admin_notices',
						function () use ( $th ) {
							printf(
								'<div class="%1$s"><p>%2$s</p></div>',
								esc_attr( 'notice notice-error is-dismissible' ),
								esc_html( $th->getMessage() )
							);
						}
					);
				}
			}
		}

		add_action( 'wp_ajax_tutor_pro_google_meet_credential_upload', array( $this, 'upload_credentials' ) );
	}

	/**
	 * Handle google redirect uri after getting token.
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function handle_google_callback() {
		$url       = get_pagenum_link( 1, false );
		$match_url = explode( '?', $url )[0];
		if ( ! in_array( rtrim( $match_url, '/' ), $this->google_callback_url, true ) ) {
			return;
		}
		$code = Input::get( 'code', '' );

		if ( empty( $code ) ) {
			return;
		}

		if ( function_exists( 'qalam_220_is_google_meet_callback_request' ) && qalam_220_is_google_meet_callback_request() && function_exists( 'qalam_220_manage_url' ) ) {
			$call_back_url = qalam_220_manage_url( 'google_meet', array( 'tab'=>'set-api' ) );
		} else {
			$call_back_url = UrlHelper::add_query_params(
				tutor_utils()->tutor_dashboard_url( Dashboard::LIVE_CLASSES_MENU ),
				array(
					'nav' => Utilities::GOOGLE_MEET_TAB,
					'tab' => 'set-api',
				)
			);
		}

		$state = sanitize_text_field( (string) Input::get( 'state', '' ) );
		$call_back_url = UrlHelper::add_query_params( $call_back_url, array( 'code' => $code, 'state' => $state ) );
		wp_safe_redirect( $call_back_url );

		exit;
	}

	/**
	 * Check valid service account JSON config file
	 *
	 * @param string $file_path file path.
	 * @return boolean
	 * @throws \Exception If invalid file or file does not exist.
	 *
	 * @since 2.1.3
	 */
	public function validate_json_service_account_file( $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			throw new \Exception( __( 'File does not exist', 'tutor-pro' ) );
		}

		$data = PrivateSecretStore::read_json( $file_path );
		if ( null === $data ) {
			$size = @filesize( $file_path );
			if ( false === $size || $size < 1 || $size > 2097152 ) { throw new \Exception( __( 'Invalid JSON file', 'tutor-pro' ) ); }
			$data = json_decode( (string) @file_get_contents( $file_path ), true );
		}
		$json = is_array( $data ) ? json_decode( wp_json_encode( $data ) ) : null;

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			throw new \Exception( __( 'Invalid JSON file', 'tutor-pro' ) );
		}

		if ( ! isset( $json->web ) ) {
			throw new \Exception( __( 'Invalid config file', 'tutor-pro' ) );
		}

		$required_key = array(
			'client_id',
			'client_secret',
			'project_id',
			'auth_uri',
			'token_uri',
		);

		$config_arr = (array) $json->web;

		foreach ( $required_key as $key ) {
			if ( ! array_key_exists( $key, $config_arr ) ) {
				throw new \Exception( $key . __( ' does not exist in your JSON file', 'tutor-pro' ) );
			}
		}

		return true;
	}

	/**
	 * Return consent screen url
	 *
	 * @since v2.1.0
	 *
	 * @return string  consent screen URL
	 */
	private function oauth_state_meta_key() { return '_qalam_google_meet_oauth_state'; }

	private function issue_oauth_state() {
		$user_id = get_current_user_id();
		if ( $user_id < 1 || ! Validator::current_user_has_access() ) { return ''; }
		$state = wp_generate_password( 40, false, false );
		update_user_meta( $user_id, $this->oauth_state_meta_key(), array(
			'hash' => hash_hmac( 'sha256', $state, wp_salt( 'auth' ) ),
			'exp'  => time() + 600,
		) );
		return $state;
	}

	private function verify_oauth_state() {
		$user_id = get_current_user_id();
		if ( $user_id < 1 || ! Validator::current_user_has_access() ) { return false; }
		$state = sanitize_text_field( (string) Input::get( 'state', '' ) );
		$saved = get_user_meta( $user_id, $this->oauth_state_meta_key(), true );
		if ( ! $state || ! is_array( $saved ) || empty( $saved['hash'] ) || empty( $saved['exp'] ) || (int) $saved['exp'] < time() ) { return false; }
		$valid = hash_equals( (string) $saved['hash'], hash_hmac( 'sha256', $state, wp_salt( 'auth' ) ) );
		if ( $valid ) { delete_user_meta( $user_id, $this->oauth_state_meta_key() ); }
		return $valid;
	}

	public function get_consent_screen_url() {
		$state = $this->issue_oauth_state();
		if ( ! $state ) { return ''; }
		$this->client->setState( $state );
		return $this->client->createAuthUrl();
	}

	/**
	 * Filter upload directory.
	 *
	 * @since 3.0.0
	 *
	 * @param array $param param.
	 *
	 * @return array.
	 */
	public function filter_upload_dir( $param ) {
		$param['path'] = \trailingslashit( $param['basedir'] ) . $this->tutor_json_dir;
		$param['url']  = \trailingslashit( $param['baseurl'] ) . $this->tutor_json_dir;
		return $param;
	}

	/**
	 * Save JSON credentials
	 *
	 * @since 3.0.0 used wp_handle_upload function instead of move_uploaded_file.
	 * @since 2.1.0
	 *
	 * @param string $file  $_FILES.
	 *
	 * @return void
	 */
	public function upload_credentials( $file ) {
		tutor_utils()->checking_nonce();
		if ( ! Validator::current_user_has_access() ) {
			$this->response_bad_request( tutor_utils()->error_message() );
		}
		if ( ! $this->upload_dir ) {
			$this->response_bad_request( __( 'Private credential storage is unavailable', 'tutor-pro' ) );
		}

		try {
			$upload = $_FILES['file'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! is_array( $upload ) || empty( $upload['tmp_name'] ) || ! is_uploaded_file( $upload['tmp_name'] ) ) {
				$this->response_bad_request( __( 'Credential upload failed, please try again!', 'tutor-pro' ) );
			}
			$size = (int) ( $upload['size'] ?? 0 );
			if ( $size < 1 || $size > 2097152 ) {
				$this->response_bad_request( __( 'Invalid JSON file', 'tutor-pro' ) );
			}
			$this->validate_json_service_account_file( $upload['tmp_name'] );
			$data = json_decode( (string) file_get_contents( $upload['tmp_name'] ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$web  = is_array( $data ) ? ( $data['web'] ?? array() ) : array();
			foreach ( array( 'auth_uri' => 'accounts.google.com', 'token_uri' => 'oauth2.googleapis.com' ) as $key => $expected_host ) {
				$parts = wp_parse_url( (string) ( $web[ $key ] ?? '' ) );
				if ( ! is_array( $parts ) || 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) ) || $expected_host !== strtolower( (string) ( $parts['host'] ?? '' ) ) ) {
					$this->response_bad_request( __( 'Invalid config file', 'tutor-pro' ) );
				}
			}
			$credential_path = $this->upload_dir . 'credential.json';
			if ( ! PrivateSecretStore::write_json( $credential_path, $data ) ) {
				$this->response_bad_request( __( 'Credential upload failed, please try again!', 'tutor-pro' ) );
			}
			$this->credential_path = $credential_path;
			$this->response_success( __( 'Credential uploaded successfully!', 'tutor-pro' ) );
		} catch ( \Throwable $th ) {
			$this->response_bad_request( $th->getMessage() );
		}
	}

	/**
	 * Check if credentials available
	 *
	 * @since v2.1.0
	 *
	 * @return bool
	 */
	public function is_credential_loaded() {
		return $this->upload_dir && is_file( $this->upload_dir . 'credential.json' );
	}

	/**
	 * Assign the existing token, or try to refresh if expired
	 *
	 * @since v2.1.0
	 *
	 * @return mixed
	 */
	public function assign_token_to_client() {
		try {
			if ( isset( $this->token_path ) && file_exists( $this->token_path ) ) {
				$access_token = PrivateSecretStore::read_json( $this->token_path );
				if ( ! is_array( $access_token ) ) { return false; }
				$this->client->setAccessToken( $access_token );
			}
			// Check if token expired.
			if ( $this->client->isAccessTokenExpired() ) {
				$refresh_token = $this->client->getRefreshToken();

				if ( ! $refresh_token ) {
					return false;
				}

				$new_token = null;

				try {
					$new_token = $this->client->fetchAccessTokenWithRefreshToken( $refresh_token );
				} catch ( \Exception $e ) {
					if ( $e ) {
						return false;
					}
				}
				return $this->save_token( null, $new_token );
			}
		} catch ( \Throwable $th ) {
			return false;
		}
	}


	/**
	 * Save token provided by google
	 *
	 * @since v2.1.0
	 *
	 * @param mixed $code  google after authenticated.
	 * @param mixed $token access token.
	 */
	public function save_token( $code = null, $token = null ) {
		if ( Validator::current_user_has_access() ) {
			try {
				if ( ! $token ) {
					if ( ! $this->verify_oauth_state() ) { return false; }
					$token = $this->client->fetchAccessTokenWithAuthCode( sanitize_text_field( (string) $code ) );
					$this->client->setAccessToken( $token );
					$token = $this->client->getAccessToken();
				}
				$token_path = $this->upload_dir ? $this->upload_dir . 'token.json' : '';
				if ( ! $token_path || ! is_array( $token ) || ! PrivateSecretStore::write_json( $token_path, $token ) ) { return false; }
				$this->token_path = $token_path;
				return true;
			} catch ( \Throwable $th ) {
				return false;
			}
		}
		return false;
	}


	/** Remove both private Google credential artifacts for the current owner. */
	public function reset_credentials() {
		$removed = false;
		foreach ( array( $this->credential_path, $this->token_path ) as $path ) {
			if ( $path && is_file( $path ) ) { $removed = @unlink( $path ) || $removed; }
		}
		$this->credential_path = null;
		$this->token_path = null;
		return $removed;
	}

	/**
	 * Check if the app is permitted by user via consent screen
	 *
	 * @since v2.1.0
	 *
	 * @return bool
	 */
	public function is_app_permitted() {
		if ( is_null( $this->credential_path ) || is_null( $this->token_path ) ) {
			return false;
		}
		return $this->assign_token_to_client() === false ? false : true;
	}
}
