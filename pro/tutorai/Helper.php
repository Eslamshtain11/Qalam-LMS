<?php
/**
 * Helper class for handling magic ai functionalities
 *
 * @package TutorPro\TutorAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\TutorAI;

use Parsedown;
use RuntimeException;
use TutorPro\OpenAI\OpenAI;
use TutorPro\OpenAI\Client;
use Tutor\Traits\JsonResponse;
use TutorPro\OpenAI\Constants\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper class for openai related functionalities.
 *
 * @since 3.0.0
 */
final class Helper {
	use JsonResponse;

	/**
	 * Tutor OpenAI Client instance
	 *
	 * @since 3.0.0
	 *
	 * @var Client | null
	 */
	private static $client = array();

	/**
	 * Get the instance of the OpenAI\Client
	 *
	 * @since 3.0.0
	 *
	 * @return Client
	 *
	 * @throws RuntimeException If openai api key is not found.
	 */
	public static function get_openai_client( string $capability = 'chat' ) {
		$config = self::get_ai_provider_config();

		if ( 'image' === $capability && 'openai' !== $config['provider'] ) {
			throw new RuntimeException( 'إنشاء الصور بالذكاء الاصطناعي يتطلب مزود OpenAI حاليًا. يمكنك استخدام أي مزود مدعوم لإنشاء النصوص والأسئلة.' );
		}

		$cache_key = $config['provider'] . '|' . $config['base_url'] . '|' . substr( hash( 'sha256', $config['api_key'] ), 0, 12 );
		if ( ! isset( self::$client[ $cache_key ] ) ) {
			if ( empty( $config['api_key'] ) ) {
				throw new RuntimeException( 'مفتاح API لمزود الذكاء الاصطناعي غير موجود. أضف المفتاح من إعدادات قلم.' );
			}

			$headers = array();
			if ( 'openrouter' === $config['provider'] ) {
				$headers['HTTP-Referer']      = home_url( '/' );
				$headers['X-OpenRouter-Title'] = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ?: 'Qalam LMS';
			}

			self::$client[ $cache_key ] = OpenAI::client_with_base_uri( $config['api_key'], $config['base_url'], $headers );
		}

		return self::$client[ $cache_key ];
	}

	/**
	 * Resolve active Qalam AI provider configuration. Secrets are never localized to JavaScript.
	 *
	 * @return array{provider:string,api_key:string,base_url:string,model:string}
	 */
	public static function get_ai_provider_config(): array {
		$options  = get_option( 'tutor_option', array() );
		$options  = is_array( $options ) ? $options : array();
		$provider = sanitize_key( (string) ( $options['qalam_ai_provider'] ?? 'openai' ) );

		$presets = self::get_ai_provider_presets();
		if ( ! isset( $presets[ $provider ] ) ) {
			$provider = 'openai';
		}

		$base_url = (string) ( $presets[ $provider ]['base_url'] ?? '' );
		if ( 'custom' === $provider ) {
			$base_url = self::sanitize_custom_base_url( (string) ( $options['qalam_ai_base_url'] ?? '' ) );
		}

		$model = sanitize_text_field( (string) ( $options['qalam_ai_model'] ?? '' ) );
		if ( 'custom' === $provider && ! empty( $options['qalam_ai_model_manual'] ) ) {
			$model = sanitize_text_field( (string) $options['qalam_ai_model_manual'] );
		}

		// Google model catalogues can return resource names such as
		// `models/gemini-2.5-flash`, while both the OpenAI-compatible endpoint
		// and our UI expect the bare model id. Normalize legacy/current values.
		if ( 'google' === $provider ) {
			$model = preg_replace( '#^models/#i', '', trim( $model ) );
		}
		if ( '' === $model ) {
			$model = (string) ( $presets[ $provider ]['model'] ?? Models::GPT_4O );
		}
		if ( '' === $model ) {
			throw new RuntimeException( 'أدخل Model ID للمزود المخصص.' );
		}

		return array(
			'provider' => $provider,
			'api_key'  => (string) ( $options['chatgpt_api_key'] ?? '' ),
			'base_url' => $base_url,
			'model'    => $model,
		);
	}

	/** Provider presets using official OpenAI-compatible endpoints. */
	public static function get_ai_provider_presets(): array {
		return array(
			'openai' => array( 'label' => 'OpenAI', 'base_url' => 'https://api.openai.com/v1', 'model' => Models::GPT_4O ),
			'deepseek' => array( 'label' => 'DeepSeek', 'base_url' => 'https://api.deepseek.com', 'model' => 'deepseek-v4-flash' ),
			'openrouter' => array( 'label' => 'OpenRouter', 'base_url' => 'https://openrouter.ai/api/v1', 'model' => 'openai/gpt-4o' ),
			'google' => array( 'label' => 'Google AI Studio', 'base_url' => 'https://generativelanguage.googleapis.com/v1beta/openai', 'model' => 'gemini-3.5-flash' ),
			'custom' => array( 'label' => 'مزود مخصص', 'base_url' => '', 'model' => '' ),
		);
	}


	/** Models cache option. */
	const QALAM_MODELS_CACHE_OPTION = 'qalam_ai_models_cache';

	/**
	 * Return a stable cache key without persisting the API key.
	 */
	private static function provider_models_cache_key( string $provider, string $base_url = '' ): string {
		return sanitize_key( $provider ) . ':' . substr( hash( 'sha256', untrailingslashit( trim( $base_url ) ) ), 0, 16 );
	}

	/**
	 * Read cached models for the provider currently shown in settings.
	 *
	 * @return array<int,array{id:string,label:string}>
	 */
	public static function get_cached_provider_models( string $provider, string $base_url = '' ): array {
		$cache = get_option( self::QALAM_MODELS_CACHE_OPTION, array() );
		$cache = is_array( $cache ) ? $cache : array();
		$key   = self::provider_models_cache_key( $provider, $base_url );
		$row   = isset( $cache[ $key ] ) && is_array( $cache[ $key ] ) ? $cache[ $key ] : array();
		$items = isset( $row['models'] ) && is_array( $row['models'] ) ? $row['models'] : array();
		$items = array_values( array_filter( $items, static fn( $item ) => is_array( $item ) && ! empty( $item['id'] ) ) );
		if ( 'google' === sanitize_key( $provider ) ) {
			foreach ( $items as &$item ) {
				$item['id'] = (string) preg_replace( '#^models/#i', '', trim( (string) $item['id'] ) );
				if ( isset( $item['label'] ) ) {
					$item['label'] = (string) preg_replace( '#^models/#i', '', trim( (string) $item['label'] ) );
				}
			}
			unset( $item );
		}
		return $items;
	}

	/** Persist only model metadata, never the API key. */
	public static function cache_provider_models( string $provider, string $base_url, array $models ): void {
		$cache = get_option( self::QALAM_MODELS_CACHE_OPTION, array() );
		$cache = is_array( $cache ) ? $cache : array();
		$key   = self::provider_models_cache_key( $provider, $base_url );
		$cache[ $key ] = array(
			'fetched_at' => gmdate( 'c' ),
			'models'     => array_values( $models ),
		);
		// Avoid unbounded stale catalogues if many custom endpoints are tried.
		if ( count( $cache ) > 20 ) {
			$cache = array_slice( $cache, -20, null, true );
		}
		update_option( self::QALAM_MODELS_CACHE_OPTION, $cache, false );
	}

	/**
	 * Verify provider credentials and fetch its available model catalogue.
	 * OpenAI, DeepSeek, OpenRouter and Google AI Studio all expose a /models
	 * endpoint compatible with this normalized response shape. Custom providers
	 * may opt out; the settings UI then falls back to a manual Model ID.
	 *
	 * @return array<int,array{id:string,label:string}>
	 */
	public static function fetch_provider_models( string $provider, string $api_key, string $custom_base_url = '' ): array {
		$provider = sanitize_key( $provider );
		$presets  = self::get_ai_provider_presets();
		if ( ! isset( $presets[ $provider ] ) ) {
			throw new RuntimeException( 'مزود الذكاء الاصطناعي غير معروف.' );
		}
		if ( '' === trim( $api_key ) ) {
			throw new RuntimeException( 'أدخل مفتاح API الأول.' );
		}

		$base_url = (string) $presets[ $provider ]['base_url'];
		if ( 'custom' === $provider ) {
			$base_url = self::sanitize_custom_base_url( $custom_base_url );
		}
		$endpoint = untrailingslashit( $base_url ) . '/models';

		$headers = array(
			'Authorization' => 'Bearer ' . trim( $api_key ),
			'Accept'        => 'application/json',
		);
		if ( 'openrouter' === $provider ) {
			$headers['HTTP-Referer']       = home_url( '/' );
			$headers['X-OpenRouter-Title'] = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ?: 'Qalam LMS';
		}

		$response = wp_safe_remote_get(
			$endpoint,
			array(
				'timeout'     => 20,
				'redirection' => 2,
				'sslverify'   => true,
				'headers'     => $headers,
			)
		);
		if ( is_wp_error( $response ) ) {
			throw new RuntimeException( 'تعذر الاتصال بالمزود: ' . $response->get_error_message() );
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 ) {
			$message = '';
			if ( is_array( $body ) ) {
				$message = (string) ( $body['error']['message'] ?? $body['message'] ?? '' );
			}
			if ( '' === $message ) {
				$message = 'HTTP ' . $status;
			}
			throw new RuntimeException( 'المزود رفض بيانات الربط أو تعذر جلب الموديلات: ' . sanitize_text_field( $message ) );
		}

		$rows = is_array( $body ) && isset( $body['data'] ) && is_array( $body['data'] ) ? $body['data'] : array();
		$models = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) || empty( $row['id'] ) ) {
				continue;
			}
			$id    = sanitize_text_field( (string) $row['id'] );
			if ( 'google' === $provider ) {
				$id = (string) preg_replace( '#^models/#i', '', trim( $id ) );
			}
			$label = sanitize_text_field( (string) ( $row['name'] ?? $row['display_name'] ?? $id ) );
			if ( 'google' === $provider ) {
				$label = (string) preg_replace( '#^models/#i', '', trim( $label ) );
			}
			if ( '' === $id ) {
				continue;
			}
			$models[ $id ] = array( 'id' => $id, 'label' => $label ?: $id );
		}
		ksort( $models, SORT_NATURAL | SORT_FLAG_CASE );
		return array_values( $models );
	}

	/**
	 * Validate an administrator supplied OpenAI-compatible Base URL.
	 * Public HTTPS endpoints are allowed by default. A filter can explicitly allow otherwise.
	 */
	public static function sanitize_custom_base_url( string $url ): string {
		$url = untrailingslashit( esc_url_raw( trim( $url ) ) );
		if ( '' === $url ) {
			throw new RuntimeException( 'أدخل Base URL للمزود المخصص.' );
		}

		$parts = wp_parse_url( $url );
		$allow_private = (bool) apply_filters( 'qalam_ai_allow_private_base_url', false, $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || ( ! $allow_private && 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) ) ) ) {
			throw new RuntimeException( 'Base URL غير صالح. استخدم رابط HTTPS عامًا لمزود OpenAI-compatible.' );
		}

		if ( ! $allow_private && ! wp_http_validate_url( $url ) ) {
			throw new RuntimeException( 'Base URL مرفوض لأسباب أمنية. استخدم endpoint عامًا وآمنًا.' );
		}

		return $url;
	}

	/** Active model id for chat/text/quiz generation. */
	public static function get_ai_model(): string {
		$config = self::get_ai_provider_config();
		return $config['model'];
	}

	/**
	 * Convert markdown text to html
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content that will be converted to html.
	 *
	 * @return string
	 */
	public static function markdown_to_html( string $content ) {
		$markdown = new Parsedown();
		$markdown->setSafeMode( true );

		return $markdown->text( $content );
	}

	/**
	 * Create the openai chat input options.
	 *
	 * @since 3.0.0
	 *
	 * @param array $messages The chat messages.
	 * @param array $options Optional options for overwriting the model, temperature etc.
	 *
	 * @return array
	 */
	public static function create_openai_chat_input( array $messages, array $options = array() ) {
		$default_options = array(
			'model'       => self::get_ai_model(),
			'temperature' => 0.7,
		);

		$options             = array_merge( $default_options, $options );
		$options['messages'] = $messages;

		return $options;
	}

	/**
	 * Check if a content is a valid JSON string or not.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The string content to check.
	 *
	 * @return boolean
	 */
	public static function is_valid_json( string $content ) {
		json_decode( $content );
		return json_last_error() === JSON_ERROR_NONE;
	}

	/**
	 * Sanitize the json content by removing the markdown code block.
	 *
	 * @since 3.0.0
	 *
	 * @param string $content The content that will be sanitized.
	 *
	 * @return string
	 */
	public static function sanitize_json( string $content ) {
		$content = ltrim( $content, '```json' );
		$content = rtrim( $content, '```' );

		return $content;
	}

	/**
	 * Check if the openai response has any error or not.
	 * If there any error then send the error response, otherwise continue.
	 *
	 * @since   3.0.0
	 *
	 * @param array $response The openai response.
	 *
	 * @return mixed
	 */
	public static function check_openai_response( array $response ) {
		$status_code = $response['status_code'] ?? 200;

		if ( $status_code >= 400 ) {
			$error_message = $response['error_message'] ?? '';
			wp_send_json(
				array(
					'status_code' => $status_code,
					'message'     => $error_message,
					'data'        => null,
				),
				$status_code
			);
		}

		return $response['data'];
	}
}
