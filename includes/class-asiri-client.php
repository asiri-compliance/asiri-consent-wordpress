<?php
/**
 * The one place that talks to api.asiri.ng from the server.
 *
 * The secret key is read from the ASIRI_SECRET_KEY constant at the moment of the call, put in one header, and
 * never stored, logged or returned. The address is fixed: the key goes to https://api.asiri.ng and nowhere else,
 * and redirects are not followed.
 *
 * @package Asiri_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Asiri_Client {

	const API_BASE = 'https://api.asiri.ng';
	/** Today's route. The one place to change when the server-side named subject lands. */
	const RECEIPTS_PATH = '/public/consent/receipts';

	/**
	 * Records one consent act made through a form.
	 *
	 * @param array  $notice     array( 'ref' => string, 'version' => int ).
	 * @param array  $decisions  List of array( 'purpose' => string, 'granted' => bool ).
	 * @param string $method     accepted-all, refused-all or chose.
	 * @param string $external   The form's entry id or the order id, with a short prefix.
	 * @param string $idem_basis What makes this act the same act if sent again, for example form id and entry id.
	 * @return array array( 'ok' => bool, 'status' => int )
	 */
	public static function record_receipt( $notice, $decisions, $method, $external, $idem_basis ) {
		if ( ! Asiri_Settings::secret_key_found() ) {
			return array(
				'ok'     => false,
				'status' => 0,
			);
		}

		$body = array(
			'notice'      => array(
				'ref'     => (string) $notice['ref'],
				'version' => (int) $notice['version'],
			),
			'decisions'   => $decisions,
			'method'      => $method,
			'collectedAt' => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'channel'     => 'web',
			'ui'          => 'customer',
			'externalId'  => (string) $external,
		);

		$args = array(
			'timeout'     => 5,
			'redirection' => 0,
			'sslverify'   => true,
			'blocking'    => true,
			'headers'     => array(
				'Authorization'   => 'Bearer ' . ASIRI_SECRET_KEY,
				'Idempotency-Key' => substr( hash( 'sha256', 'asiri-consent|' . $idem_basis ), 0, 48 ),
				'Content-Type'    => 'application/json',
				'Accept'          => 'application/json',
			),
			'body'        => wp_json_encode( $body ),
		);

		$response = wp_remote_post( self::API_BASE . self::RECEIPTS_PATH, $args );
		$status   = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
		$ok       = $status >= 200 && $status < 300;

		if ( ! $ok ) {
			// Status and time only. Never the key, never the body, never the person.
			update_option(
				Asiri_Settings::ERROR_OPT,
				array(
					'status' => $status,
					'at'     => gmdate( 'Y-m-d H:i' ),
				),
				false
			);
		}
		return array(
			'ok'     => $ok,
			'status' => $status,
		);
	}
}
