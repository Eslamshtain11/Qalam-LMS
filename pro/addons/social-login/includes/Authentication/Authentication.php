<?php
/**
 * Handle social authentication
 *
 * @package TutorPro\SocialLogin\Authentication
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 2.1.9
 */

namespace TutorPro\SocialLogin\Authentication;

use TUTOR\Ajax;
use Tutor\Helpers\SessionHelper;
use TUTOR\Input;
use TUTOR\Instructor;
use TutorPro\SocialLogin\Lib\TwitterOauthService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage all social authentication
 */
class Authentication {

	/**
	 * Register hooks
	 *
	 * @since 2.1.9
	 */
	public function __construct() {
		add_action( 'wp_ajax_nopriv_tutor_pro_social_authentication', __CLASS__ . '::authenticate' );
		add_action( 'template_redirect', array( $this, 'twitter_oauth_verify' ) );
		add_action( 'template_redirect', array( $this, 'process_twitter_login' ) );
	}

	/**
	 * Verify access token;
	 *
	 * @since 2.7.1
	 *
	 * @param string $token token.
	 *
	 * @return mixed false when invalid token, return object when verification success.
	 */
	public static function verify_google_token( $token ) {
		$url = add_query_arg( 'id_token', rawurlencode( (string) $token ), 'https://oauth2.googleapis.com/tokeninfo' );
		$response = wp_safe_remote_get( $url, array( 'timeout'=>10, 'redirection'=>0, 'sslverify'=>true ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) { return false; }
		$data = json_decode( wp_remote_retrieve_body( $response ) ); return is_object( $data ) ? $data : false;
	}

	/**
	 * Verify facebook access token;
	 *
	 * @since 2.7.1
	 * @since 4.0.2 Add fields to request. See https://developers.facebook.com/docs/graph-api/overview#me
	 *
	 * @param string $token token.
	 *
	 * @return mixed false when invalid token, return object when verification success.
	 */
	public static function verify_facebook_token( $token ) {
		$url = add_query_arg( array( 'fields'=>'id,name,email', 'access_token'=>(string)$token ), 'https://graph.facebook.com/me' );
		$response = wp_safe_remote_get( $url, array( 'timeout'=>10, 'redirection'=>0, 'sslverify'=>true ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) { return false; }
		$data = json_decode( wp_remote_retrieve_body( $response ) ); return is_object( $data ) && ! empty( $data->id ) ? $data : false;
	}

	/**
	 * Handle authentication
	 *
	 * @return void
	 */
	public static function authenticate() {
		$auth_success_msg = __( 'You are logging in!', 'tutor-pro' );
		$auth_failed_msg  = __( 'Something went wrong, please try again!', 'tutor-pro' );

		tutor_utils()->checking_nonce();

		// Sanitize user data.
		$request = Input::sanitize_array(
			wp_unslash( $_POST ),//phpcs:ignore
			array(
				'email'       => 'sanitize_email',
				'profile_url' => 'sanitize_url',
			)
		);

		$providers     = array( 'google', 'facebook' );
		$auth_provider = $request['auth'] ?? '';

		if ( ! in_array( $auth_provider, $providers, true ) ) {
			wp_send_json_error( 'Invalid auth request' );
		}

		$token = $request['token'] ?? '';

		// Verify provider token server-side. Never trust client-supplied identity fields.
		$verification = false; $subject='';
		if ( 'google' === $auth_provider ) {
			$verification = self::verify_google_token( $token ); if ( ! $verification ) { wp_send_json_error( 'Invalid login request' ); }
			$client_id=(string)tutor_utils()->get_option('google_client_ID'); $issuer=(string)($verification->iss??'');
			if ( ''===$client_id || !isset($verification->aud) || !hash_equals($client_id,(string)$verification->aud) || !in_array($issuer,array('accounts.google.com','https://accounts.google.com'),true) || !in_array((string)($verification->email_verified??''),array('true','1'),true) ) { wp_send_json_error( 'Invalid Google identity token' ); }
			$subject=(string)($verification->sub??'');
		} elseif ( 'facebook' === $auth_provider ) {
			$verification=self::verify_facebook_token($token); if(!$verification){wp_send_json_error('Invalid login request');} $subject=(string)($verification->id??'');
		}
		$email=sanitize_email((string)($verification->email??''));
		if ( ''===$subject || empty($email) || !is_email($email) ) { wp_send_json_error( __( 'Invalid email', 'tutor-pro' ) ); }
		$binding=\Qalam\Security\SocialIdentityService::bind_or_validate($auth_provider,$subject,$email); if(is_wp_error($binding))wp_send_json_error($binding->get_error_message());

		// User already exists.
		if ( $binding instanceof \WP_User || email_exists( $email ) ) {
			$userdata = $binding instanceof \WP_User ? $binding : get_user_by( 'email', $email );

			if ( is_a( $userdata, 'WP_User' ) ) {
				// Logged-in the user.
				$is_error = self::logged_in( $userdata );
				if ( $is_error ) {
					wp_send_json_error( $is_error );
				}
				wp_send_json_success( $auth_success_msg );
			}
			wp_send_json_error( $auth_failed_msg );
		} else {
			$is_registration_enabled = get_option( 'users_can_register', false );
			if ( ! $is_registration_enabled ) {
				wp_send_json_error( 'Registration is not enabled, please contact with site owner!', 'tutor-pro' );
			}

			/**
			 * Fix - Social login google provider Non-english username problem
			 *
			 * @since 2.2.0
			 */
			if ( 'google' === $request['auth'] ) {
				$request['user_login'] = tutor_utils()->create_unique_username( $email );
			}

			if ( ! empty( $request['user_login'] ) ) {
				$request['user_login'] = tutor_utils()->create_unique_username( $request['user_login'] );
			}

			// Prepare registration.
			$prepare_user_data = array(
				'user_login' => $request['user_login'] ?? '',
				'user_email' => $email,
				'first_name' => $request['first_name'] ?? '',
				'last_name'  => $request['last_name'] ?? '',
				'user_pass'  => wp_generate_password( 32, true, true ),
			);

			$insert = wp_insert_user( $prepare_user_data );
			if ( is_wp_error( $insert ) ) {
				wp_send_json_error( 'User registration failed', 'tutor-pro' );
			} else {
				$userdata = get_userdata( $insert );
				if ( is_a( $userdata, 'WP_User' ) ) {
					\Qalam\Security\SocialIdentityService::bind_new_user( $userdata->ID, $auth_provider, $subject );
					// Check if wanted to be a instructor.
					if ( 'tutor_register_instructor' === $request['attempt'] ) {
						( new Instructor( false ) )->update_instructor_meta( $userdata->ID );
					} else {
						do_action( 'tutor_after_student_signup', $insert );
					}

					// Logged-in the user.
					$is_error = self::logged_in( $userdata );
					if ( $is_error ) {
						wp_send_json_error( $is_error );
					}
					wp_send_json_success( $auth_success_msg );
				}
				wp_send_json_error( $auth_failed_msg );
			}
		}
	}

	/**
	 * Function for Twitter Oauth Service
	 *
	 * @since 2.1.10
	 *
	 * @return void
	 */
	public function twitter_oauth_verify() {
		if ( tutor_utils()->get_option( 'enable_twitter_login' ) && ! get_current_user_id() && Input::has( 'twitter_oauth_verify' ) && Input::get( 'twitter_oauth_verify' ) == 'true' ) {
			$api_key               = tutor_utils()->get_option( 'twitter_app_key' );
			$api_key_secret        = tutor_utils()->get_option( 'twitter_app_key_secret' );
			$oauth_callback        = rtrim( tutor_utils()->tutor_dashboard_url(), '/' ) . '?tutor_twitter_login=true';
			$twitter_oauth_service = new TwitterOauthService( $api_key, $api_key_secret, $oauth_callback );
			$redirect_url          = $twitter_oauth_service->get_oauth_verifier();
			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Process Twitter login after redirect
	 *
	 * @since 2.1.10
	 *
	 * @return void
	 */
	public function process_twitter_login() {
		if ( tutor_utils()->get_option( 'enable_twitter_login' ) && ! get_current_user_id() && Input::has( 'tutor_twitter_login' ) && Input::get( 'tutor_twitter_login' ) === 'true' ) {
			$oauth_token_secret    = SessionHelper::get( 'oauth_token_secret' );
			$is_instructor         = get_transient( 'twitter_login_is_instructor' );
			$api_key               = tutor_utils()->get_option( 'twitter_app_key' );
			$api_key_secret        = tutor_utils()->get_option( 'twitter_app_key_secret' );
			$oauth_callback        = rtrim( tutor_utils()->tutor_dashboard_url(), '/' ) . '?tutor_twitter_login=true';
			$twitter_oauth_service = new TwitterOauthService( $api_key, $api_key_secret, $oauth_callback );

			if ( ! empty( Input::get( 'oauth_verifier' ) ) && ! empty( Input::get( 'oauth_token' ) ) ) {
				$expected_token=(string)SessionHelper::get('oauth_token'); $issued=(int)SessionHelper::get('qalam_twitter_oauth_issued');
				if(''===$expected_token || !hash_equals($expected_token,(string)Input::get('oauth_token')) || $issued<=0 || $issued<time()-600){set_transient(Ajax::LOGIN_ERRORS_TRANSIENT_KEY,array(__( 'Invalid or expired social login request', 'tutor-pro' )));return;}
				SessionHelper::unset('oauth_token'); SessionHelper::unset('oauth_token_secret'); SessionHelper::unset('qalam_twitter_oauth_issued');
				$response_user_data = $twitter_oauth_service->get_user_data( Input::get( 'oauth_verifier' ), Input::get( 'oauth_token' ), $oauth_token_secret );
				$response_user_data = json_decode( $response_user_data, true );

				if ( is_array( $response_user_data ) && count( $response_user_data ) && ! array_key_exists( 'errors', $response_user_data ) ) {
					$name_chunks = explode( ' ', trim( $response_user_data['name'] ) );
					$max_index   = count( $name_chunks ) - 1;
					$first_name  = '';
					$last_name   = '';

					if ( $max_index < 1 ) {
						$first_name = $response_user_data['name'];
						$last_name  = '';
					} else {
						foreach ( $name_chunks as $key => $value ) {
							if ( $key < $max_index ) {
								$first_name .= $value . ' ';
							}
						}
						$first_name = trim( $first_name );
						$last_name  = $name_chunks[ $max_index ];
					}

					$response_user_data['screen_name'] = tutor_utils()->create_unique_username( $response_user_data['screen_name'] );

					$email           = $response_user_data['email'];
					$user_login      = $response_user_data['screen_name'];
					$auth_failed_msg = __( 'Something went wrong, please try again!', 'tutor-pro' );

					// Validate emails.
					if ( empty( $email ) || ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
						\set_transient( Ajax::LOGIN_ERRORS_TRANSIENT_KEY, array( __( 'Invalid email', 'tutor-pro' ) ) );
						return;
					}
					$twitter_subject=(string)($response_user_data['id_str']??$response_user_data['id']??''); $binding=\Qalam\Security\SocialIdentityService::bind_or_validate('twitter',$twitter_subject,$email); if(is_wp_error($binding)){set_transient(Ajax::LOGIN_ERRORS_TRANSIENT_KEY,array($binding->get_error_message()));return;}

					// User already exists.
					if ( $binding instanceof \WP_User || email_exists( $email ) ) {
						$userdata = $binding instanceof \WP_User ? $binding : get_user_by( 'email', $email );

						if ( is_a( $userdata, 'WP_User' ) ) {
							// Logged-in the user.
							$is_error = self::logged_in( $userdata );

							if ( is_wp_error( $is_error ) ) {
								$error_msg = $is_error->get_error_message();
								if ( $error_msg ) {
									\set_transient( Ajax::LOGIN_ERRORS_TRANSIENT_KEY, array( $error_msg ) );
									return;
								}
							}
						}
					} else {
						$is_registration_enabled = get_option( 'users_can_register', false );
						if ( ! $is_registration_enabled ) {
							\set_transient( Ajax::LOGIN_ERRORS_TRANSIENT_KEY, array( __( 'Registration is not enabled, please contact with site owner!', 'tutor-pro' ) ) );
							return;
						}

						// Prepare registration.
						$prepare_user_data = array(
							'user_login' => $user_login,
							'user_email' => $email,
							'first_name' => $first_name,
							'last_name'  => $last_name,
							'user_pass'  => wp_generate_password( 32, true, true ),
						);

						$insert = wp_insert_user( $prepare_user_data );

						if ( is_wp_error( $insert ) ) {
							\set_transient( Ajax::LOGIN_ERRORS_TRANSIENT_KEY, array( __( 'User registration failed', 'tutor-pro' ) ) );
							return;
						} else {
							$userdata = get_userdata( $insert );
							if ( is_a( $userdata, 'WP_User' ) ) {
								\Qalam\Security\SocialIdentityService::bind_new_user( $userdata->ID, 'twitter', $twitter_subject );
								// Check if wanted to be a instructor.
								if ( $is_instructor ) {
									( new Instructor( false ) )->update_instructor_meta( $userdata->ID );
								} else {
									do_action( 'tutor_after_student_signup', $insert );
								}

								// Logged-in the user.
								$is_error = self::logged_in( $userdata );

								if ( is_wp_error( $is_error ) ) {
									$error_msg = $is_error->get_error_message();
									if ( $error_msg ) {
										\set_transient( Ajax::LOGIN_ERRORS_TRANSIENT_KEY, array( $error_msg ) );
										return;
									}
								}
							} else {
								\set_transient( Ajax::LOGIN_ERRORS_TRANSIENT_KEY, array( $auth_failed_msg ) );
								return;
							}
						}
					}
				} else {
					if ( isset( $response_user_data['errors'] ) ) {
						if ( is_array( $response_user_data['errors'] ) ) {
							foreach ( $response_user_data['errors'] as $error ) {
								\set_transient( Ajax::LOGIN_ERRORS_TRANSIENT_KEY, array( $error['message'] ) );
							}
							return;
						} else {
							\set_transient( Ajax::LOGIN_ERRORS_TRANSIENT_KEY, array( __( 'Something went wrong! Please try again', 'tutor-pro' ) ) );
							return;
						}
					}
				}
			} else {
				\set_transient( Ajax::LOGIN_ERRORS_TRANSIENT_KEY, array( __( 'Something went wrong! Please try again', 'tutor-pro' ) ) );
				return;
			}

			delete_transient( 'twitter_login_is_instructor' );
			wp_safe_redirect( tutor_utils()->tutor_dashboard_url() );
		}
	}

	/**
	 * Logged user in
	 *
	 * @param \WP_User $userdata WP_User object.
	 *
	 * @return mixed return error message if wp_error occur otherwise return void
	 */
	private static function logged_in( \WP_User $userdata ) {
		$gate=\Qalam\Security\prepare_social_2fa($userdata); if(is_wp_error($gate))return $gate; if(true===$gate)return null;
		$is_error = apply_filters( 'authenticate', $userdata, $userdata->user_login, '' );

		if ( is_wp_error( $is_error ) ) {
			return $is_error;
		}

		wp_set_current_user( $userdata->ID, $userdata->user_login );
		wp_set_auth_cookie( $userdata->ID );
		do_action( 'wp_login', $userdata->user_login, $userdata );
	}
}
