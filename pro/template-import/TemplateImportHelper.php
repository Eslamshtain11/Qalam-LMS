<?php
/**
 * TemplateImportHelper methods
 *
 * @package TutorPro\TemplateImport
 * @author Tutor <support@themeum.com>
 * @link https://tutor.com
 * @since 4.0.0
 */

namespace TutorPro\TemplateImport;

use Tutor\Traits\JsonResponse;

/**
 * TemplateImportHelper methods
 */
class TemplateImportHelper {

	use JsonResponse;

	/**
	 * Template list endpoint.
	 *
	 * @since 4.0.0
	 *
	 * @var string
	 */
	public $template_list_endpoint;

	/**
	 * Template download endpoint.
	 *
	 * @since 4.0.0
	 *
	 * @var string
	 */
	public $template_download_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 4.0.0
	 *
	 * @return  void
	 */
	public function __construct() {
		$this->template_list_endpoint     = self::make_url( 'theme-templates' );
		$this->template_download_endpoint = self::make_url( 'theme-template-download' );
	}

	/**
	 * Get base url.
	 *
	 * @since 3.6.0
	 *
	 * @return string The base URL for the template import API.
	 */
	private static function get_base_url() {
		// Fail closed during Qalam security quarantine. A trusted Qalam endpoint
		// may be injected explicitly in a later phase.
		if ( defined( 'QALAM_TEMPLATE_IMPORT_BASE_URL' ) && QALAM_TEMPLATE_IMPORT_BASE_URL ) {
			$url = esc_url_raw( trim( (string) QALAM_TEMPLATE_IMPORT_BASE_URL ) );
			$parts = wp_parse_url( $url );
			if ( ! $url || ! is_array( $parts ) || 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) ) || empty( $parts['host'] ) || ! wp_http_validate_url( $url ) ) {
				return '';
			}
			return rtrim( $url, '/' );
		}

		return '';
	}

	/**
	 * Make url
	 *
	 * @since 4.0.0
	 *
	 * @param  string $url_path  url path.
	 *
	 * @return  string full url.
	 */
	public static function make_url( $url_path ) {
		$base_url = self::get_base_url();
		return $base_url ? $base_url . '/' . ltrim( $url_path, '/' ) : '';
	}

	/**
	 * Get Template list.
	 *
	 * @since 4.0.0
	 *
	 * @throws \Exception If there is an error fetching or decoding the templates.
	 */
	public function get_template_list() {
		if ( empty( $this->template_list_endpoint ) ) {
			return array();
		}

		try {
			$response             = wp_safe_remote_get( $this->template_list_endpoint, array( 'timeout' => 15, 'redirection' => 1, 'sslverify' => true ) );
			$response_status_code = wp_remote_retrieve_response_code( $response );
			if ( is_wp_error( $response ) ) {
				throw new \Exception( 'Failed to fetch templates: ' . $response->get_error_message() );
			}
			$template_list = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				throw new \Exception( 'Failed to decode JSON response: ' . json_last_error_msg() );
			}
			if ( 200 !== $response_status_code ) {
				throw new \Exception( 'Failed to fetch templates: ' . $template_list['response'] );
			}
			return $template_list['body_response'];
		} catch ( \Exception $e ) {
			return array();
		}
	}

	/**
	 * Get Template download url
	 *
	 * @since 4.0.0
	 *
	 * @param string $template_id The ID of the template to download.
	 *
	 * return string The download URL for the specified template.
	 */
	public function get_template_download_url( $template_id ) {
		if ( empty( $this->template_download_endpoint ) ) {
			return '';
		}

		$website_url        = get_site_url();
		$args               = array(
			'body'    => array(
				'slug'        => $template_id,
				'website_url' => $website_url,
			),
		);
		$args['timeout'] = 20;
		$args['redirection'] = 1;
		$args['sslverify'] = true;
		$response           = wp_safe_remote_post( $this->template_download_endpoint, $args );
		$response_body      = wp_remote_retrieve_body( $response );
		$data               = json_decode( $response_body, true );
		if ( is_wp_error( $response ) ) {
			self::json_response( $data['response'], null, 400 );
		}
		if ( empty( $data['body_response'] ) ) {
			self::json_response( $data['response'], null, 400 );
		}
		$template_download_url = esc_url_raw( (string) $data['body_response'] );
		$parts = wp_parse_url( $template_download_url );
		if ( ! $template_download_url || ! is_array( $parts ) || 'https' !== strtolower( (string) ( $parts['scheme'] ?? '' ) ) || ! wp_http_validate_url( $template_download_url ) ) {
			return '';
		}
		return $template_download_url;
	}
}
