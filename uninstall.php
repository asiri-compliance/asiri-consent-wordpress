<?php
/**
 * Removes the plugin's own options when it is deleted. Nothing else is touched.
 *
 * @package Asiri_Consent
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'asiri_consent_settings' );
delete_option( 'asiri_consent_last_error' );
