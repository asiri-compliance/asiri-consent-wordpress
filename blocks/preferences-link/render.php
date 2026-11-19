<?php
/**
 * Server-side render for the cookie settings link block.
 *
 * @package Asiri_Consent
 *
 * @var array $attributes Block attributes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo wp_kses(
	Asiri_Preferences::render_block( isset( $attributes ) ? $attributes : array() ),
	array(
		'button' => array(
			'type'            => true,
			'class'           => true,
			'data-asiri-open' => true,
		),
	)
);
