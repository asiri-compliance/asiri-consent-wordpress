<?php
/**
 * Head output: the Consent Mode defaults block, then the pinned script tag.
 *
 * Nothing user-specific is written here. The only site value is the publishable key, which is public by design.
 *
 * @package Asiri_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prints the Consent Mode defaults, then loads the pinned edge script through the WordPress script queue.
 */
class Asiri_Head {

	const EDGE_BASE = 'https://edge.asiri.ng/c/';
	const HANDLE    = 'asiri-consent-edge';

	/** Hooks the defaults block (priority 1, before any Google tag) and the script queue. */
	public static function init() {
		// Priority 1: the defaults must be set before any Google tag the theme or another plugin prints.
		add_action( 'wp_head', array( __CLASS__, 'output' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 1 );
		add_filter( 'script_loader_tag', array( __CLASS__, 'loader_tag' ), 10, 3 );
	}

	/** The pinned script's address. */
	public static function script_url() {
		return self::EDGE_BASE . 'v' . ASIRI_CONSENT_EDGE_VERSION . '/asiri.js';
	}

	/** Queues the pinned script in the head. The version argument is null so the address stays exactly as pinned. */
	public static function enqueue() {
		if ( ! Asiri_Settings::is_configured() ) {
			return;
		}
		wp_enqueue_script( self::HANDLE, self::script_url(), array(), null, array( 'in_footer' => false ) ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- the address is pinned by version and checked by its integrity hash.
	}

	/**
	 * Writes the queued script's tag with its integrity hash, anonymous cross-origin mode and the two public values.
	 *
	 * @param string $tag    The tag WordPress built.
	 * @param string $handle The script's handle.
	 */
	public static function loader_tag( $tag, $handle ) {
		if ( self::HANDLE !== $handle || ! Asiri_Settings::is_configured() ) {
			return $tag;
		}
		$s = Asiri_Settings::get();

		// This is the queued script's own tag, rewritten to add its integrity hash.
		// phpcs:disable WordPress.WP.EnqueuedResources
		return sprintf(
			'<script src="%s" integrity="%s" crossorigin="anonymous" data-source="%s" data-key="%s"></script>' . "\n",
			esc_url( self::script_url() ),
			esc_attr( ASIRI_CONSENT_EDGE_INTEGRITY ),
			esc_attr( $s['source_id'] ),
			esc_attr( $s['publishable_key'] )
		);
		// phpcs:enable WordPress.WP.EnqueuedResources
	}

	/** The defaults block: fixed bytes from the release, so the hash published for a Content-Security-Policy holds. */
	public static function html() {
		return Asiri_Settings::is_configured() ? ASIRI_CONSENT_DEFAULTS_HTML : '';
	}

	/** Prints the defaults block in the head. */
	public static function output() {
		echo self::html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed release bytes.
	}
}
