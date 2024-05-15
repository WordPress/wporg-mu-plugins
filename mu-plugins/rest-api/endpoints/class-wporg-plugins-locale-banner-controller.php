<?php

namespace WordPressdotorg\MU_Plugins\REST_API;

use WordPressdotorg\Plugin_Directory\Plugin_Directory;
use function WordPressdotorg\MU_Plugins\Helpers\Locale\{ get_all_locales_with_subdomain, get_all_valid_locales, get_locale_from_header, get_translated_locales };

/**
 * Plugins_Locale_Banner_Controller
 */
class Plugins_Locale_Banner_Controller extends Base_Locale_Banner_Controller {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'wporg-plugins/v1';
		$this->rest_base = 'locale-banner';
	}

	/**
	 * Validate the plugin slug.
	 */
	public function check_slug( $param ) {
		return Plugin_Directory::get_plugin_post( $param );
	}

	/**
	 * Get banner for general plugin directory
	 */
	public function get_response( $request ) {
		if ( ! defined( 'GLOTPRESS_LOCALES_PATH' ) ) {
			return;
		}

		require_once GLOTPRESS_LOCALES_PATH;

		$locale_subdomain_assoc = get_all_locales_with_subdomain();
		$current_locale = get_locale();
		$current_gp_locale = \GP_Locales::by_field( 'wp_locale', $current_locale );

		// Build a list of WordPress locales which we'll suggest to the user.
		$suggest_locales = array_values( array_intersect( get_locale_from_header(), get_all_valid_locales() ) );

		$suggestion_links = [];
		foreach ( $suggest_locales as $locale ) {
			$language = \GP_Locales::by_field( 'wp_locale', $locale )->native_name;
			$suggestion_links[ $locale ] = sprintf(
				'<a href="https://%s.wordpress.org%s">%s</a>',
				$locale_subdomain_assoc[ $locale ]->subdomain,
				esc_url( get_site()->path ),
				$language
			);
		}

		$suggest_string = '';

		unset( $suggestion_links[ $current_locale ] );

		if ( ! empty( $suggestion_links ) ) {
			$output_locale = key( $suggestion_links );
			switch_to_locale( $output_locale );
			$suggest_string = sprintf(
				// translators: %s: List of links to plugin directory in other locales.
				__( 'The plugin directory is also available in %s.', 'wporg' ),
				wp_sprintf_l( '%l', $suggestion_links )
			);
		}

		// Return more information if this is a debug request.
		if ( ! empty( $request['debug'] ) ) {
			return new \WP_REST_Response(
				array(
					'currentLocale' => $current_locale,
					'suggestions' => $suggest_locales,
					'message' => $suggest_string,
				)
			);
		}

		// The result should be a raw text response.
		add_filter( 'rest_pre_echo_response', array( $this, 'send_plain_text' ) );
		return new \WP_REST_Response( $suggest_string );
	}

	/**
	 * Get banner for single plugins.
	 */
	public function get_response_for_item( $request ) {
		// This has already been validated by `validate_callback`.
		$plugin_slug = $request['slug'];

		if ( ! defined( 'GLOTPRESS_LOCALES_PATH' ) ) {
			return;
		}

		require_once GLOTPRESS_LOCALES_PATH;

		$locale_subdomain_assoc = get_all_locales_with_subdomain();
		$current_locale = get_locale();
		$current_gp_locale = \GP_Locales::by_field( 'wp_locale', $current_locale );
		$translated_locales = get_translated_locales( 'plugin', $plugin_slug );

		// Build a list of WordPress locales which we'll suggest to the user.
		$suggest_locales = array_values( array_intersect( get_locale_from_header(), $translated_locales ) );

		$suggestion_links = [];
		foreach ( $suggest_locales as $locale ) {
			$language = \GP_Locales::by_field( 'wp_locale', $locale )->native_name;
			$suggestion_links[ $locale ] = sprintf(
				'<a href="https://%s.wordpress.org%s">%s</a>',
				$locale_subdomain_assoc[ $locale ]->subdomain,
				esc_url( get_site()->path . $plugin_slug . '/' ),
				$language
			);
		}

		$suggest_string = '';

		unset( $suggestion_links[ $current_locale ] );

		// If we're on a rosetta site, and the plugin is not translated, the message should ask for help.
		if ( 'en_US' !== $current_locale && $current_gp_locale && ! in_array( $current_locale, $translated_locales ) ) {
			$output_locale = $current_locale;
			switch_to_locale( $output_locale );

			if ( ! empty( $suggestion_links ) ) {
				$suggest_string = sprintf(
					// translators: %1$s: Locale name, %2$s: List of links to plugin in other locales.
					__( 'This plugin is not translated into %1$s yet, but it is available in %2$s.', 'wporg' ),
					$current_gp_locale->native_name,
					wp_sprintf_l( '%l', $suggestion_links )
				);
			} else {
				$suggest_string = sprintf(
					// translators: %s: Locale name.
					__( 'This plugin is not translated into %s yet.', 'wporg' ),
					$current_gp_locale->native_name
				);
			}
			$suggest_string .= ' ' . sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( 'https://translate.wordpress.org/projects/wp-plugins/' . $plugin_slug ),
				__( 'Help translate it!', 'wporg' )
			);

		} else if ( ! empty( $suggestion_links ) ) {
			$output_locale = key( $suggestion_links );
			switch_to_locale( $output_locale );
			$suggest_string = sprintf(
				// translators: %s: List of links to plugin in other locales.
				__( 'This plugin is also available in %s.', 'wporg' ),
				wp_sprintf_l( '%l', $suggestion_links )
			);
			$suggest_string .= ' ' . sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( 'https://translate.wordpress.org/projects/wp-plugins/' . $plugin_slug ),
				__( 'Help improve the translation!', 'wporg' )
			);

		} else if ( ! empty( $locales_from_header ) ) {
			$output_locale = reset( $locales_from_header );
			switch_to_locale( $output_locale );

			$suggest_string = sprintf(
				// translators: %s: Locale name.
				__( 'This plugin is not translated into %s yet.', 'wporg' ),
				\GP_Locales::by_field( 'wp_locale', $output_locale )->native_name
			);
			$suggest_string .= ' ' . sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( 'https://translate.wordpress.org/projects/wp-plugins/' . $plugin_slug ),
				__( 'Help translate it!', 'wporg' )
			);
		}

		// Return more information if this is a debug request.
		if ( ! empty( $request['debug'] ) ) {
			return new \WP_REST_Response(
				array(
					'currentLocale' => $current_locale,
					'suggestions' => $suggest_locales,
					'message' => $suggest_string,
				)
			);
		}

		// The result should be a raw text response.
		add_filter( 'rest_pre_echo_response', array( $this, 'send_plain_text' ) );
		return new \WP_REST_Response( $suggest_string );
	}
}
