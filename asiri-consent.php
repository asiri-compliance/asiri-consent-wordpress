<?php
/**
 * Plugin Name:       Asiri Consent
 * Plugin URI:        https://asiri.ng/cookie-consent
 * Description:       Adds the Asiri consent banner to your site and tells the WP Consent API what the visitor chose, so other plugins can listen.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  wp-consent-api
 * Author:            Asiri Compliance Ltd
 * Author URI:        https://asiri.ng
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       asiri-consent
 *
 * @package Asiri_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ASIRI_CONSENT_VERSION', '0.1.0' );
define( 'ASIRI_CONSENT_FILE', __FILE__ );
define( 'ASIRI_CONSENT_DIR', plugin_dir_path( __FILE__ ) );

require_once ASIRI_CONSENT_DIR . 'includes/release.php';
require_once ASIRI_CONSENT_DIR . 'includes/class-asiri-settings.php';
require_once ASIRI_CONSENT_DIR . 'includes/class-asiri-client.php';
require_once ASIRI_CONSENT_DIR . 'includes/class-asiri-head.php';
require_once ASIRI_CONSENT_DIR . 'includes/class-asiri-bridge.php';
require_once ASIRI_CONSENT_DIR . 'includes/class-asiri-forms.php';
require_once ASIRI_CONSENT_DIR . 'includes/class-asiri-preferences.php';

Asiri_Settings::init();
Asiri_Head::init();
Asiri_Bridge::init();
Asiri_Forms::init();
Asiri_Preferences::init();
