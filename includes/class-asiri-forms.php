<?php
/**
 * Consent boxes in forms, and the server-side record of what was chosen.
 *
 * A box is drawn only when the form's plugin is switched on in settings, the secret key is set, a notice is named
 * and there is at least one purpose. Every box starts unticked. When the form is submitted the answer is recorded
 * from the server: ticked is a yes, unticked is a no. With the forms switched off nothing is drawn and nothing is
 * recorded.
 *
 * @package Asiri_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Asiri_Forms {

	const FIELD  = 'asiri_consent';
	const MARKER = 'asiri_consent_shown';

	public static function init() {
		// Drawing the boxes.
		add_action( 'wpforms_display_submit_before', array( __CLASS__, 'draw_wpforms' ) );
		add_filter( 'gform_submit_button', array( __CLASS__, 'draw_gravity' ), 10, 1 );
		add_filter( 'wpcf7_form_elements', array( __CLASS__, 'draw_cf7' ), 10, 1 );
		add_action( 'woocommerce_after_order_notes', array( __CLASS__, 'draw_woocommerce' ) );

		// Recording the answer.
		add_action( 'wpforms_process_complete', array( __CLASS__, 'on_wpforms' ), 10, 4 );
		add_action( 'gform_after_submission', array( __CLASS__, 'on_gravity' ), 10, 2 );
		add_action( 'wpcf7_before_send_mail', array( __CLASS__, 'on_cf7' ), 10, 3 );
		add_action( 'woocommerce_checkout_order_processed', array( __CLASS__, 'on_woocommerce' ), 10, 3 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'on_woocommerce_block' ), 10, 1 );
	}

	/** Is this form plugin switched on, and is everything the answer needs in place. */
	public static function active( $form_key ) {
		$s = Asiri_Settings::get();
		return in_array( $form_key, $s['forms'], true )
			&& Asiri_Settings::secret_key_found()
			&& '' !== $s['notice_ref']
			&& ! empty( $s['purposes'] );
	}

	/** The unticked boxes, one per purpose. Empty when the form plugin is off. */
	public static function boxes_html( $form_key ) {
		if ( ! self::active( $form_key ) ) {
			return '';
		}
		$s     = Asiri_Settings::get();
		$html  = '<fieldset class="asiri-consent-boxes">';
		$html .= '<legend>' . esc_html__( 'Your choices', 'asiri-consent' ) . '</legend>';
		$html .= '<input type="hidden" name="' . esc_attr( self::MARKER ) . '" value="1" />';
		foreach ( $s['purposes'] as $p ) {
			$html .= '<p><label><input type="checkbox" name="' . esc_attr( self::FIELD . '[' . $p['id'] . ']' ) . '" value="1" /> '
				. esc_html( $p['label'] ) . '</label></p>';
		}
		return $html . '</fieldset>';
	}

	public static function draw_wpforms() {
		echo self::boxes_html( 'wpforms' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in boxes_html().
	}

	public static function draw_gravity( $button ) {
		return self::boxes_html( 'gravityforms' ) . $button;
	}

	public static function draw_cf7( $elements ) {
		return $elements . self::boxes_html( 'cf7' );
	}

	public static function draw_woocommerce() {
		echo self::boxes_html( 'woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in boxes_html().
	}

	/**
	 * Turns posted values into decisions, one per configured purpose. A purpose that was not ticked is a no.
	 * Returns null when this submission did not show the boxes.
	 *
	 * @param array $posted The submitted values.
	 */
	public static function decisions_from( $posted ) {
		if ( ! is_array( $posted ) || empty( $posted[ self::MARKER ] ) ) {
			return null;
		}
		$ticked = isset( $posted[ self::FIELD ] ) && is_array( $posted[ self::FIELD ] ) ? $posted[ self::FIELD ] : array();
		$s      = Asiri_Settings::get();
		$out    = array();
		foreach ( $s['purposes'] as $p ) {
			$out[] = array(
				'purpose' => $p['id'],
				'granted' => isset( $ticked[ $p['id'] ] ) && '1' === (string) $ticked[ $p['id'] ],
			);
		}
		return $out;
	}

	/**
	 * Records one form submission. Returns the client's answer, or null when nothing was sent.
	 *
	 * @param string $form_key  One of Asiri_Settings::FORM_KEYS.
	 * @param string $form_ref  The form's id.
	 * @param string $entry_id  The entry or order id; a fresh id when the plugin keeps none.
	 * @param array  $posted    The submitted values.
	 */
	public static function record( $form_key, $form_ref, $entry_id, $posted ) {
		if ( ! self::active( $form_key ) ) {
			return null;
		}
		$decisions = self::decisions_from( $posted );
		if ( null === $decisions || empty( $decisions ) ) {
			return null;
		}
		$granted = count( array_filter( wp_list_pluck( $decisions, 'granted' ) ) );
		if ( 0 === $granted ) {
			$method = 'refused-all';
		} elseif ( count( $decisions ) === $granted ) {
			$method = 'accepted-all';
		} else {
			$method = 'chose';
		}
		if ( '' === (string) $entry_id || '0' === (string) $entry_id ) {
			$entry_id = wp_generate_uuid4();
		}
		$s = Asiri_Settings::get();
		return Asiri_Client::record_receipt(
			array(
				'ref'     => $s['notice_ref'],
				'version' => $s['notice_version'],
			),
			$decisions,
			$method,
			$form_key . ':' . $form_ref . ':' . $entry_id,
			$form_key . '|' . $form_ref . '|' . $entry_id
		);
	}

	/** The submitted values. Nonces and validation belong to each form plugin, which has already run. */
	private static function posted() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the form plugin checks its own nonce before these hooks run.
		return wp_unslash( $_POST );
	}

	public static function on_wpforms( $fields, $entry, $form_data, $entry_id ) {
		unset( $fields );
		$posted  = is_array( $entry ) && isset( $entry[ self::MARKER ] ) ? $entry : self::posted();
		$form_id = is_array( $form_data ) && isset( $form_data['id'] ) ? $form_data['id'] : '';
		self::record( 'wpforms', $form_id, $entry_id, $posted );
	}

	public static function on_gravity( $entry, $form ) {
		$entry_id = is_array( $entry ) && isset( $entry['id'] ) ? $entry['id'] : '';
		$form_id  = is_array( $form ) && isset( $form['id'] ) ? $form['id'] : '';
		self::record( 'gravityforms', $form_id, $entry_id, self::posted() );
	}

	public static function on_cf7( $contact_form, &$abort = false, $submission = null ) {
		unset( $abort, $submission );
		$form_id = is_object( $contact_form ) && method_exists( $contact_form, 'id' ) ? $contact_form->id() : '';
		// Contact Form 7 keeps no entry id, so each submission gets its own.
		self::record( 'cf7', $form_id, '', self::posted() );
	}

	public static function on_woocommerce( $order_id, $posted_data = array(), $order = null ) {
		unset( $posted_data, $order );
		self::record( 'woocommerce', 'checkout', $order_id, self::posted() );
	}

	/**
	 * Block checkout. The server half is here; the boxes themselves need a checkout block, which this release does
	 * not ship, so a block-checkout order posts no consent fields and nothing is recorded for it.
	 *
	 * @param object $order The order.
	 */
	public static function on_woocommerce_block( $order ) {
		$order_id = is_object( $order ) && method_exists( $order, 'get_id' ) ? $order->get_id() : '';
		self::record( 'woocommerce', 'checkout', $order_id, self::posted() );
	}
}
