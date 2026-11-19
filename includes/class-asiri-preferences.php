<?php
/**
 * The cookie settings link: the block "Asiri: cookie settings link" and the shortcode [asiri_preferences].
 *
 * Both print a button the Asiri script opens the choices panel from (data-asiri-open). When the script is not
 * configured they print nothing, so no button is shown that would do nothing.
 *
 * @package Asiri_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Asiri_Preferences {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		add_shortcode( 'asiri_preferences', array( __CLASS__, 'shortcode' ) );
		register_block_type( ASIRI_CONSENT_DIR . 'blocks/preferences-link' );
	}

	public static function link_html( $label ) {
		if ( ! Asiri_Settings::is_configured() ) {
			return '';
		}
		$label = '' === trim( (string) $label ) ? __( 'Cookie settings', 'asiri-consent' ) : (string) $label;
		return '<button type="button" class="asiri-preferences-link" data-asiri-open>' . esc_html( $label ) . '</button>';
	}

	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'label' => '' ), is_array( $atts ) ? $atts : array(), 'asiri_preferences' );
		return self::link_html( sanitize_text_field( $atts['label'] ) );
	}

	/** Server-rendered block output. */
	public static function render_block( $attributes ) {
		$label = is_array( $attributes ) && isset( $attributes['label'] ) ? sanitize_text_field( $attributes['label'] ) : '';
		return self::link_html( $label );
	}
}
