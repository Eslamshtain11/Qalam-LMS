<?php
/**
 * Qalam AI provider settings.
 *
 * @package TutorPro\TutorAI
 */

namespace TutorPro\TutorAI;

use Tutor\Helpers\HttpHelper;
use TUTOR\Input;
use Tutor\Traits\JsonResponse;
use TUTOR\User;

/** Manage the shared Qalam AI provider configuration. */
class SettingsController {
	use JsonResponse;

	const CHATGPT_API_KEY     = 'chatgpt_api_key';
	const CHATGPT_ENABLE      = 'chatgpt_enable';
	const QALAM_AI_PROVIDER   = 'qalam_ai_provider';
	const QALAM_AI_MODEL      = 'qalam_ai_model';
	const QALAM_AI_BASE_URL   = 'qalam_ai_base_url';
	const QALAM_AI_MODEL_MANUAL = 'qalam_ai_model_manual';

	/** Register hooks. */
	public function __construct() {
		add_filter( 'tutor/options/extend/attr', array( $this, 'add_chatgpt_settings_option' ) );
		add_action( 'wp_ajax_tutor_pro_chatgpt_save_settings', array( $this, 'save_settings' ) );
		add_action( 'wp_ajax_qalam_ai_activate_provider', array( $this, 'activate_provider' ) );
	}

	/**
	 * Add the Qalam AI Studio block to Settings > Advanced.
	 * All user-facing copy is authored in Arabic here instead of depending on
	 * fragment translations at render time.
	 *
	 * @param array $attr Settings schema.
	 * @return array
	 */
	public function add_chatgpt_settings_option( $attr ) {
		$options          = get_option( 'tutor_option', array() );
		$options          = is_array( $options ) ? $options : array();
		$current_provider = sanitize_key( (string) ( $options[ self::QALAM_AI_PROVIDER ] ?? 'openai' ) );
		$current_base     = (string) ( $options[ self::QALAM_AI_BASE_URL ] ?? '' );
		$current_model    = sanitize_text_field( (string) ( $options[ self::QALAM_AI_MODEL ] ?? '' ) );
		$cached_models    = Helper::get_cached_provider_models( $current_provider, $current_base );

		$model_options = array();
		foreach ( $cached_models as $model ) {
			if ( ! empty( $model['id'] ) ) {
				$model_options[ $model['id'] ] = ! empty( $model['label'] ) ? $model['label'] : $model['id'];
			}
		}
		if ( $current_model && ! isset( $model_options[ $current_model ] ) ) {
			$model_options[ $current_model ] = $current_model;
		}
		if ( empty( $model_options ) ) {
			$model_options[''] = 'اضغط «تفعيل وجلب الموديلات» بعد إدخال مفتاح API.';
		}

		$chatgpt_settings = array(
			'label'      => 'استوديو الذكاء الاصطناعي',
			'slug'       => 'options',
			'block_type' => 'uniform',
			'fields'     => array(
				array(
					'key'     => self::CHATGPT_ENABLE,
					'type'    => 'toggle_switch',
					'label'   => 'تفعيل ذكاء قلم',
					'default' => 'on',
					'desc'    => 'فعّل الخدمة علشان تستخدم الذكاء الاصطناعي في إنشاء الأسئلة والنصوص وباقي أدوات قلم.',
				),
				array(
					'key'     => self::QALAM_AI_PROVIDER,
					'type'    => 'select',
					'label'   => 'مزود الذكاء الاصطناعي',
					'default' => 'openai',
					'options' => array(
						'openai'     => 'OpenAI',
						'deepseek'   => 'DeepSeek',
						'openrouter' => 'OpenRouter',
						'google'     => 'Google AI Studio',
						'custom'     => 'مزود مخصص متوافق مع OpenAI',
					),
					'desc'    => 'اختار المزود اللي هيستخدمه قلم لإنشاء الأسئلة والنصوص وتشغيل أدوات الذكاء الاصطناعي.',
				),
				array(
					'key'         => self::CHATGPT_API_KEY,
					'type'        => 'password',
					'label'       => 'مفتاح API للمزود',
					'default'     => '',
					'desc'        => 'أدخل مفتاح API الخاص بالمزود المختار. المفتاح بيتخزن على السيرفر ومش بيتبعت للطلاب أو يظهر في واجهة الموقع.',
					'placeholder' => 'أدخل مفتاح API',
				),
				array(
					'key'            => self::QALAM_AI_MODEL,
					'type'           => 'select',
					'label'          => 'نموذج الذكاء الاصطناعي',
					'default'        => '',
					'options'        => $model_options,
					'searchable'     => true,
					'select_options' => false,
					'desc'           => 'بعد إدخال المفتاح اضغط «تفعيل وجلب الموديلات». هتظهر الموديلات المتاحة في قائمة قابلة للبحث، وبعدها اختار الموديل اللي هيستخدمه قلم.',
				),
				array(
					'key'         => self::QALAM_AI_BASE_URL,
					'type'        => 'text',
					'label'       => 'رابط Base URL للمزود المخصص',
					'default'     => '',
					'desc'        => 'الخانة دي بتستخدم فقط مع «مزود مخصص متوافق مع OpenAI». اكتب رابط الـ API الأساسي، مثال: https://provider.example.com/v1',
					'placeholder' => 'https://provider.example.com/v1',
				),
				array(
					'key'         => self::QALAM_AI_MODEL_MANUAL,
					'type'        => 'text',
					'label'       => 'Model ID يدوي للمزود المخصص',
					'default'     => '',
					'desc'        => 'استخدم الخانة دي فقط لو المزود المخصص لا يوفّر قائمة موديلات عبر ‎/models‎. سيستخدم قلم هذا المعرّف بدل القائمة.',
					'placeholder' => 'provider-model-id',
				),
			),
		);

		if ( isset( $attr['advanced']['blocks'] ) && is_array( $attr['advanced']['blocks'] ) ) {
			$attr['advanced']['blocks'][] = $chatgpt_settings;
		}

		return $attr;
	}

	/** Activate credentials, verify them and cache the provider model catalogue. */
	public function activate_provider() {
		check_ajax_referer( 'qalam_ai_activate_provider', 'nonce' );
		if ( ! User::is_admin() || ! current_user_can( 'manage_tutor' ) ) {
			wp_send_json_error( array( 'message' => 'معندكش صلاحية لتعديل إعدادات الذكاء الاصطناعي.' ), 403 );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_key( wp_unslash( $_POST['provider'] ) ) : 'openai';
		$api_key  = isset( $_POST['api_key'] ) ? trim( (string) wp_unslash( $_POST['api_key'] ) ) : '';
		$base_url = isset( $_POST['base_url'] ) ? trim( (string) wp_unslash( $_POST['base_url'] ) ) : '';

		if ( '' === $api_key ) {
			wp_send_json_error( array( 'message' => 'أدخل مفتاح API الأول.' ), 422 );
		}

		try {
			$models = Helper::fetch_provider_models( $provider, $api_key, $base_url );
		} catch ( \Throwable $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'manual'  => 'custom' === $provider,
				),
				422
			);
		}

		if ( empty( $models ) ) {
			wp_send_json_error( array( 'message' => 'المزود رد بنجاح لكن مفيش موديلات متاحة في الحساب.' ), 422 );
		}

		$cache_base_url = 'custom' === $provider ? Helper::sanitize_custom_base_url( $base_url ) : '';
		Helper::cache_provider_models( $provider, $cache_base_url, $models );

		$options = get_option( 'tutor_option', array() );
		$options = is_array( $options ) ? $options : array();
		$options[ self::CHATGPT_API_KEY ]     = $api_key;
		$options[ self::CHATGPT_ENABLE ]      = 'on';
		$options[ self::QALAM_AI_PROVIDER ]   = $provider;
		$options[ self::QALAM_AI_BASE_URL ]   = 'custom' === $provider ? Helper::sanitize_custom_base_url( $base_url ) : '';
		$model_ids = wp_list_pluck( $models, 'id' );
		if ( empty( $options[ self::QALAM_AI_MODEL ] ) || ! in_array( $options[ self::QALAM_AI_MODEL ], $model_ids, true ) ) {
			$presets = Helper::get_ai_provider_presets();
			$recommended = (string) ( $presets[ $provider ]['model'] ?? '' );
			$options[ self::QALAM_AI_MODEL ] = $recommended && in_array( $recommended, $model_ids, true ) ? $recommended : $models[0]['id'];
		}
		update_option( 'tutor_option', $options, false );

		wp_send_json_success(
			array(
				'message'  => 'تم تفعيل المزود وجلب ' . count( $models ) . ' موديل بنجاح.',
				'models'   => $models,
				'selected' => $options[ self::QALAM_AI_MODEL ],
			)
		);
	}

	/** Legacy modal save endpoint retained for compatibility. */
	public function save_settings() {
		tutor_utils()->check_nonce();
		if ( ! User::is_admin() ) {
			$this->json_response( tutor_utils()->error_message() );
		}

		$chatgpt_enable = Input::post( 'chatgpt_enable', true, Input::TYPE_BOOL );
		$api_key        = trim( (string) Input::post( 'chatgpt_api_key', '' ) );
		$provider       = sanitize_key( (string) Input::post( self::QALAM_AI_PROVIDER, 'openai' ) );
		$model          = sanitize_text_field( (string) Input::post( self::QALAM_AI_MODEL, '' ) );
		$manual_model   = sanitize_text_field( (string) Input::post( self::QALAM_AI_MODEL_MANUAL, '' ) );
		$base_url       = trim( (string) Input::post( self::QALAM_AI_BASE_URL, '' ) );

		$presets = Helper::get_ai_provider_presets();
		if ( ! isset( $presets[ $provider ] ) ) {
			$this->json_response( 'مزود الذكاء الاصطناعي غير صالح.', null, HttpHelper::STATUS_BAD_REQUEST );
		}
		if ( $chatgpt_enable && empty( $api_key ) ) {
			$this->json_response( 'مفتاح API مطلوب.', null, HttpHelper::STATUS_BAD_REQUEST );
		}
		if ( 'custom' === $provider ) {
			try {
				$base_url = Helper::sanitize_custom_base_url( $base_url );
			} catch ( \RuntimeException $error ) {
				$this->json_response( $error->getMessage(), null, HttpHelper::STATUS_BAD_REQUEST );
			}
			if ( '' !== $manual_model ) {
				$model = $manual_model;
			}
		} else {
			$base_url = '';
		}

		if ( '' !== $model && ! preg_match( '/^[A-Za-z0-9._:~\/\-]+$/', $model ) ) {
			$this->json_response( 'معرّف الموديل غير صالح.', null, HttpHelper::STATUS_BAD_REQUEST );
		}

		$options = get_option( 'tutor_option', array() );
		$options = is_array( $options ) ? $options : array();
		$options[ self::CHATGPT_API_KEY ]       = $api_key;
		$options[ self::CHATGPT_ENABLE ]        = $chatgpt_enable ? 'on' : 'off';
		$options[ self::QALAM_AI_PROVIDER ]     = $provider;
		$options[ self::QALAM_AI_MODEL ]        = $model;
		$options[ self::QALAM_AI_MODEL_MANUAL ] = $manual_model;
		$options[ self::QALAM_AI_BASE_URL ]     = $base_url;
		update_option( 'tutor_option', $options, false );

		$this->json_response( 'تم حفظ إعدادات مزود الذكاء الاصطناعي بنجاح.' );
	}
}
