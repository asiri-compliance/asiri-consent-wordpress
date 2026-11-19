<?php
/**
 * WP Consent API bridge: registers the plugin as a consent manager, answers the consent type, and loads the
 * browser half (assets/bridge.js).
 *
 * @package Asiri_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Asiri_Bridge {

	public static function init() {
		add_filter( 'wp_consent_api_registered_' . plugin_basename( ASIRI_CONSENT_FILE ), '__return_true' );
		add_filter( 'wp_get_consent_type', array( __CLASS__, 'consent_type' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/** True when the WP Consent API itself lists this plugin as its consent manager. */
	public static function is_registered() {
		if ( ! class_exists( 'WP_CONSENT_API' ) ) {
			return false;
		}
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- the WP Consent API fixes this hook's name.
		return true === apply_filters( 'wp_consent_api_registered_' . plugin_basename( ASIRI_CONSENT_FILE ), false );
	}

	/**
	 * optin for everyone except a visitor in California, who gets optout. The country and region come from
	 * CloudFront's headers; with no such header the answer is optin, which is the stricter model.
	 *
	 * @param string $type The type decided so far.
	 */
	public static function consent_type( $type = 'optin' ) {
		unset( $type );
		return self::is_us_california() ? 'optout' : 'optin';
	}

	private static function header_value( $name ) {
		$key = 'HTTP_' . strtoupper( str_replace( '-', '_', $name ) );
		if ( empty( $_SERVER[ $key ] ) || ! is_string( $_SERVER[ $key ] ) ) {
			return '';
		}
		return strtoupper( preg_replace( '/[^A-Za-z0-9-]/', '', wp_unslash( $_SERVER[ $key ] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- reduced to letters, digits and dashes here.
	}

	public static function is_us_california() {
		return 'US' === self::header_value( 'CloudFront-Viewer-Country' )
			&& 'CA' === self::header_value( 'CloudFront-Viewer-Country-Region' );
	}

	public static function enqueue() {
		if ( ! Asiri_Settings::is_configured() ) {
			return;
		}
		wp_enqueue_script(
			'asiri-consent-bridge',
			plugins_url( 'assets/bridge.js', ASIRI_CONSENT_FILE ),
			array(),
			ASIRI_CONSENT_VERSION,
			array(
				'in_footer' => false,
				'strategy'  => 'defer',
			)
		);
		// The server's own answer, used until the script reports the visitor's region. Not user data.
		wp_add_inline_script(
			'asiri-consent-bridge',
			'window.asiriConsentBridge=' . wp_json_encode( array( 'type' => self::consent_type() ) ) . ';',
			'before'
		);
	}
}
