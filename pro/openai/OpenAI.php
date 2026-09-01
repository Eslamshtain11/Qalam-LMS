<?php
/**
 * Helper class for handling magic ai functionalities
 *
 * @package TutorPro\OpenAI
 * @author Themeum <support@themeum.com>
 * @link https://themeum.com
 * @since 3.0.0
 */

namespace TutorPro\OpenAI;

use TutorPro\OpenAI\Factory;
use TutorPro\OpenAI\Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The root class for making openai client
 */
final class OpenAI {
	/**
	 * Create the openai client for making request.
	 *
	 * @since 3.0.0
	 *
	 * @param string $api_key The api key for the openai.
	 * @param string $organization The organization value.
	 *
	 * @return Client
	 */
	public static function client( string $api_key, $organization = null ) {
		return self::client_with_base_uri( $api_key, 'https://api.openai.com/v1', array( 'OpenAI-Beta' => 'assistants=v2' ), $organization );
	}

	/**
	 * Create an OpenAI-compatible client for Qalam AI providers.
	 *
	 * @param string      $api_key API key.
	 * @param string      $base_uri Provider API base URL, excluding chat/completions.
	 * @param array       $headers Optional provider-specific headers.
	 * @param string|null $organization OpenAI organization, when applicable.
	 * @return Client
	 */
	public static function client_with_base_uri( string $api_key, string $base_uri, array $headers = array(), $organization = null ) {
		$factory = self::factory()
			->with_api_key( $api_key )
			->with_organization( $organization )
			->with_base_uri( $base_uri );

		foreach ( $headers as $name => $value ) {
			if ( is_string( $name ) && '' !== trim( (string) $value ) ) {
				$factory->with_http_header( $name, (string) $value );
			}
		}

		return $factory->make();
	}

	/**
	 * The application factory class for instantiating the client.
	 *
	 * @since 3.0.0
	 *
	 * @return Factory
	 */
	private static function factory() {
		return new Factory();
	}
}
