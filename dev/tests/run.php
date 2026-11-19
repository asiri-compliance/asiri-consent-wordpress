<?php
/**
 * Dependency-free tests for the Asiri Consent plugin. WordPress is replaced by small stubs.
 * Run: php tests/run.php   (from integrations/wordpress)
 */

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

define( 'ABSPATH', __DIR__ . '/' );
define( 'ASIRI_SECRET_KEY', 'sk_' . 'live_SECRETVALUE0123456789abcdef' );
$plugin_dir = dirname( __DIR__ ) . '/asiri-consent/';

// ---------------------------------------------------------------- stubs
class WP_Error {
	public $code; public $msg;
	public function __construct( $c = '', $m = '' ) { $this->code = $c; $this->msg = $m; }
	public function get_error_message() { return $this->msg; }
}
class Stub_Stop extends Exception {}

$GLOBALS['t'] = array(
	'options' => array( 'blogname' => 'My site', 'other_plugin_opt' => 'keep' ),
	'filters' => array(),
	'posts'   => array(),
	'can'     => true,
	'nonce'   => true,
	'redirect' => null,
	'transients' => array(),
	'deleted' => array(),
	'response' => array( 'response' => array( 'code' => 201 ) ),
);

function add_action( $h, $cb, $p = 10, $n = 1 ) { $GLOBALS['t']['filters'][ $h ][ $p ][] = $cb; return true; }
function add_filter( $h, $cb, $p = 10, $n = 1 ) { return add_action( $h, $cb, $p, $n ); }
function apply_filters( $h, $v, ...$args ) {
	if ( empty( $GLOBALS['t']['filters'][ $h ] ) ) { return $v; }
	ksort( $GLOBALS['t']['filters'][ $h ] );
	foreach ( $GLOBALS['t']['filters'][ $h ] as $cbs ) {
		foreach ( $cbs as $cb ) { $v = call_user_func_array( $cb, array_merge( array( $v ), $args ) ); }
	}
	return $v;
}
function do_action( $h, ...$args ) {
	if ( empty( $GLOBALS['t']['filters'][ $h ] ) ) { return; }
	ksort( $GLOBALS['t']['filters'][ $h ] );
	foreach ( $GLOBALS['t']['filters'][ $h ] as $cbs ) {
		foreach ( $cbs as $cb ) { call_user_func_array( $cb, $args ); }
	}
}
function __return_true() { return true; }
function get_option( $k, $d = false ) { return array_key_exists( $k, $GLOBALS['t']['options'] ) ? $GLOBALS['t']['options'][ $k ] : $d; }
function update_option( $k, $v, $a = null ) { $GLOBALS['t']['options'][ $k ] = $v; return true; }
function delete_option( $k ) { unset( $GLOBALS['t']['options'][ $k ] ); $GLOBALS['t']['deleted'][] = $k; return true; }
function set_transient( $k, $v, $e = 0 ) { $GLOBALS['t']['transients'][ $k ] = $v; return true; }
function get_transient( $k ) { return $GLOBALS['t']['transients'][ $k ] ?? false; }
function get_current_user_id() { return 1; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_textarea( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function __( $s, $d = '' ) { return $s; }
function esc_html__( $s, $d = '' ) { return esc_html( $s ); }
function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $s ) ) ); }
function sanitize_key( $s ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $s ) ); }
function wp_unslash( $v ) { return is_array( $v ) ? array_map( 'wp_unslash', $v ) : stripslashes( (string) $v ); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_list_pluck( $list, $f ) { return array_map( function ( $i ) use ( $f ) { return $i[ $f ]; }, $list ); }
function wp_generate_uuid4() { return 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee'; }
function checked( $c ) { if ( $c ) { echo ' checked="checked"'; } }
function wp_nonce_field( $a ) { echo '<input type="hidden" name="_wpnonce" value="n" />'; }
function submit_button() { echo '<input type="submit" />'; }
function admin_url( $p = '' ) { return 'https://site.test/wp-admin/' . $p; }
function add_query_arg( $k, $v, $u ) { return $u . ( false === strpos( $u, '?' ) ? '?' : '&' ) . $k . '=' . $v; }
function wp_safe_redirect( $u ) { $GLOBALS['t']['redirect'] = $u; throw new Stub_Stop( 'redirect' ); }
function wp_die( $m = '', $t = '', $a = array() ) { throw new Stub_Stop( 'wp_die:' . ( $a['response'] ?? 0 ) ); }
function current_user_can( $c ) { return $GLOBALS['t']['can'] && 'manage_options' === $c; }
function check_admin_referer( $a ) { if ( ! $GLOBALS['t']['nonce'] ) { throw new Stub_Stop( 'bad_nonce' ); } return 1; }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
function plugin_basename( $f ) { return 'asiri-consent/asiri-consent.php'; }
function plugin_dir_path( $f ) { return dirname( $f ) . '/'; }
function plugins_url( $p, $f ) { return 'https://site.test/wp-content/plugins/asiri-consent/' . $p; }
function wp_enqueue_script( $h, $src, $deps, $ver, $args ) { $GLOBALS['t']['enqueued'][ $h ] = $src; }
function wp_add_inline_script( $h, $code, $pos ) { $GLOBALS['t']['inline'][ $h ] = $code; }
function add_options_page() {}
function add_shortcode( $t, $cb ) { $GLOBALS['t']['shortcodes'][ $t ] = $cb; }
function shortcode_atts( $d, $a, $s = '' ) { return array_merge( $d, array_intersect_key( $a, $d ) ); }
function register_block_type( $p ) { $GLOBALS['t']['blocks'][] = $p; }
function wp_kses( $s, $allowed ) { return $s; }
function wp_remote_post( $url, $args ) {
	$GLOBALS['t']['posts'][] = array( 'url' => $url, 'args' => $args );
	return $GLOBALS['t']['response'];
}
function wp_remote_retrieve_response_code( $r ) { return is_array( $r ) ? ( $r['response']['code'] ?? 0 ) : 0; }

// ---------------------------------------------------------------- tiny harness
$GLOBALS['pass'] = 0; $GLOBALS['fail'] = 0;
function check( $name, $cond, $detail = '' ) {
	if ( $cond ) { $GLOBALS['pass']++; echo "  ok   $name\n"; }
	else { $GLOBALS['fail']++; echo "  FAIL $name $detail\n"; }
}
function throws( $fn ) { try { $fn(); } catch ( Stub_Stop $e ) { return $e->getMessage(); } return null; }
function reset_posts() { $GLOBALS['t']['posts'] = array(); }
function set_settings( $over = array() ) {
	$GLOBALS['t']['options']['asiri_consent_settings'] = array_merge(
		array(
			'publishable_key' => 'pk_test_abc123',
			'source_id'       => 'src_web1',
			'notice_ref'      => 'notice-1',
			'notice_version'  => 3,
			'purposes'        => array(
				array( 'id' => 'newsletter', 'label' => 'Send me the newsletter' ),
				array( 'id' => 'offers', 'label' => 'Send me offers' ),
			),
			'forms'           => array( 'wpforms', 'gravityforms', 'cf7', 'woocommerce' ),
		),
		$over
	);
}

// ---------------------------------------------------------------- load the plugin
$file = $plugin_dir . 'asiri-consent.php';
require $file;

echo "php " . PHP_VERSION . "\n";

// ---------------------------------------------------------------- T1 head output
echo "T1 head output\n";
set_settings();
ob_start(); Asiri_Head::output(); $head = ob_get_clean();
Asiri_Head::enqueue();
check( 'the edge script goes through the script queue', Asiri_Head::script_url() === ( $GLOBALS['t']['enqueued']['asiri-consent-edge'] ?? '' ) );
$head .= apply_filters( 'script_loader_tag', '<script src="x"></script>', 'asiri-consent-edge', Asiri_Head::script_url() );
check( 'another script is left as WordPress built it', '<script src="y"></script>' === apply_filters( 'script_loader_tag', '<script src="y"></script>', 'other', 'y' ) );
$defaults_at = strpos( $head, 'data-asiri-defaults' );
$script_at   = strpos( $head, 'src="https://edge.asiri.ng/c/v' );
check( 'defaults block present', false !== $defaults_at );
check( 'defaults block comes before the script tag', false !== $defaults_at && false !== $script_at && $defaults_at < $script_at );
check( 'defaults block is the release bytes exactly', 0 === strpos( $head, ASIRI_CONSENT_DEFAULTS_HTML ) );
preg_match( '/^<script[^>]*>(.*)<\/script>\s*$/s', ASIRI_CONSENT_DEFAULTS_HTML, $m );
check( 'defaults inline hash equals the manifest hash', 'sha256-' . base64_encode( hash( 'sha256', $m[1], true ) ) === ASIRI_CONSENT_DEFAULTS_SHA256 );
$tag = substr( $head, $script_at - 10 );
check( 'script src is pinned to the release', false !== strpos( $head, 'src="https://edge.asiri.ng/c/v' . ASIRI_CONSENT_EDGE_VERSION . '/asiri.js"' ) );
check( 'script has integrity', 1 === preg_match( '/integrity="sha384-[A-Za-z0-9+\/=]+"/', $head ) );
check( 'script has crossorigin anonymous', false !== strpos( $head, 'crossorigin="anonymous"' ) );
check( 'script has data-source and data-key', false !== strpos( $head, 'data-source="src_web1"' ) && false !== strpos( $head, 'data-key="pk_test_abc123"' ) );
check( 'script has no data-subject', false === strpos( $head, 'data-subject' ) );
check( 'head hook is priority 1', isset( $GLOBALS['t']['filters']['wp_head'][1] ) );
check( 'the queue hook runs before the theme', isset( $GLOBALS['t']['filters']['wp_enqueue_scripts'][1] ) );
set_settings( array( 'publishable_key' => '' ) );
ob_start(); Asiri_Head::output(); check( 'nothing printed when not configured', '' === ob_get_clean() );
$GLOBALS['t']['enqueued'] = array(); Asiri_Head::enqueue(); check( 'nothing queued when not configured', array() === $GLOBALS['t']['enqueued'] );
check( 'no tag when not configured', 'KEEP' === Asiri_Head::loader_tag( 'KEEP', 'asiri-consent-edge' ) );
set_settings();

// ---------------------------------------------------------------- T2 secret never leaks
echo "T2 secret key never leaks\n";
$secret = ASIRI_SECRET_KEY;
$page   = Asiri_Settings::page_html();
check( 'settings page says found in wp-config.php', false !== strpos( $page, 'Secret key: found in wp-config.php' ) );
check( 'settings page never contains the secret', false === strpos( $page, $secret ) );
check( 'settings page never contains the secret fragment', false === strpos( $page, 'SECRETVALUE' ) );
check( 'head output never contains the secret', false === strpos( $head, $secret ) );
$GLOBALS['t']['options']['asiri_consent_settings'] = Asiri_Settings::sanitise( array(
	'publishable_key' => 'pk_live_xyz', 'source_id' => 'src_1', 'notice_ref' => 'n', 'notice_version' => 1,
	'purposes' => "a | A\n", 'forms' => array( 'cf7' ),
) );
check( 'stored options never contain the secret', false === strpos( serialize( $GLOBALS['t']['options'] ), $secret ) );
Asiri_Bridge::enqueue();
check( 'bridge inline script never contains the secret', false === strpos( json_encode( $GLOBALS['t']['inline'] ?? array() ), $secret ) );
// a failed send writes an error option: still no secret
$GLOBALS['t']['response'] = array( 'response' => array( 'code' => 500 ) );
set_settings();
Asiri_Forms::record( 'cf7', '7', '', array( 'asiri_consent_shown' => '1', 'asiri_consent' => array() ) );
check( 'error option written on failure', isset( $GLOBALS['t']['options']['asiri_consent_last_error'] ) );
check( 'error option never contains the secret', false === strpos( serialize( $GLOBALS['t']['options'] ), $secret ) );
check( 'form boxes markup never contains the secret', false === strpos( Asiri_Forms::boxes_html( 'cf7' ), $secret ) );
$GLOBALS['t']['response'] = array( 'response' => array( 'code' => 201 ) );
$src_all = '';
foreach ( glob( $plugin_dir . '{*.php,includes/*.php,blocks/*/*.php,assets/*.js}', GLOB_BRACE ) as $f ) { $src_all .= file_get_contents( $f ); }
check( 'plugin source has no logging or dumping calls', 0 === preg_match( '/\b(error_log|var_dump|print_r|var_export|file_put_contents|syslog|trigger_error)\s*\(/', $src_all ) );
check( 'plugin source has no eval or remote fetch other than wp_remote_post', 0 === preg_match( '/\b(eval|file_get_contents|curl_exec|wp_remote_get|wp_remote_request|setcookie)\s*\(/', $src_all ) );
$users = array();
foreach ( glob( $plugin_dir . '{*.php,includes/*.php,blocks/*/*.php}', GLOB_BRACE ) as $f ) { if ( false !== strpos( file_get_contents( $f ), 'ASIRI_SECRET_KEY' ) ) { $users[] = basename( $f ); } }
sort( $users );
check( 'the secret constant is named only in the settings check and the client', array( 'class-asiri-client.php', 'class-asiri-settings.php' ) === $users, implode( ',', $users ) );
check( 'the constant is never echoed, escaped, stored or interpolated', 0 === preg_match( '/(echo|print|esc_\w+\(|update_option\([^;]*|sprintf\([^;]*|\.)\s*\(?\s*ASIRI_SECRET_KEY\s*[,;)]/', preg_replace( '/\'Bearer \' \. ASIRI_SECRET_KEY/', '', $src_all ) ) );

// ---------------------------------------------------------------- T3 forms
echo "T3 forms record from the server\n";
set_settings();
reset_posts();
$r = Asiri_Forms::record( 'wpforms', '12', '55', array( 'asiri_consent_shown' => '1', 'asiri_consent' => array( 'newsletter' => '1' ) ) );
$body = json_decode( $GLOBALS['t']['posts'][0]['args']['body'], true );
check( 'one request sent', 1 === count( $GLOBALS['t']['posts'] ) && true === $r['ok'] );
check( 'ticked box is granted true', array( 'purpose' => 'newsletter', 'granted' => true ) === $body['decisions'][0] );
check( 'unticked box is granted false', array( 'purpose' => 'offers', 'granted' => false ) === $body['decisions'][1] );
check( 'body carries notice, channel, ui, external id, method', 'notice-1' === $body['notice']['ref'] && 3 === $body['notice']['version'] && 'web' === $body['channel'] && 'customer' === $body['ui'] && 'wpforms:12:55' === $body['externalId'] && 'chose' === $body['method'] );
check( 'collectedAt is an ISO UTC time', 1 === preg_match( '/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ$/', $body['collectedAt'] ) );
reset_posts();
Asiri_Forms::record( 'gravityforms', '2', '9', array( 'asiri_consent_shown' => '1' ) );
$body = json_decode( $GLOBALS['t']['posts'][0]['args']['body'], true );
check( 'nothing ticked records two noes and refused-all', array( false, false ) === wp_list_pluck( $body['decisions'], 'granted' ) && 'refused-all' === $body['method'] );
reset_posts();
Asiri_Forms::record( 'woocommerce', 'checkout', '301', array( 'asiri_consent_shown' => '1', 'asiri_consent' => array( 'newsletter' => '1', 'offers' => '1', 'unknown' => '1' ) ) );
$body = json_decode( $GLOBALS['t']['posts'][0]['args']['body'], true );
check( 'all ticked is accepted-all and unknown purposes are ignored', 'accepted-all' === $body['method'] && 2 === count( $body['decisions'] ) );
reset_posts();
Asiri_Forms::record( 'cf7', '4', '', array( 'asiri_consent' => array( 'newsletter' => '1' ) ) );
check( 'a form that did not show the boxes records nothing', 0 === count( $GLOBALS['t']['posts'] ) );
set_settings( array( 'forms' => array() ) );
reset_posts();
Asiri_Forms::record( 'wpforms', '12', '56', array( 'asiri_consent_shown' => '1', 'asiri_consent' => array( 'newsletter' => '1' ) ) );
check( 'with the forms switched off nothing is sent', 0 === count( $GLOBALS['t']['posts'] ) );
check( 'with the forms switched off no box is drawn', '' === Asiri_Forms::boxes_html( 'wpforms' ) );
set_settings( array( 'forms' => array( 'cf7' ) ) );
check( 'a form plugin that is off draws no box while another is on', '' === Asiri_Forms::boxes_html( 'wpforms' ) && '' !== Asiri_Forms::boxes_html( 'cf7' ) );
$html = Asiri_Forms::boxes_html( 'cf7' );
check( 'boxes are checkboxes, one per purpose, none ticked', 2 === substr_count( $html, 'type="checkbox"' ) && false === strpos( $html, 'checked' ) );
set_settings();
reset_posts();
$GLOBALS['t']['dummy'] = 1;
$_POST = array( 'asiri_consent_shown' => '1', 'asiri_consent' => array( 'offers' => '1' ) );
Asiri_Forms::on_woocommerce( 77 );
Asiri_Forms::on_woocommerce_block( new class { public function get_id() { return 78; } } );
Asiri_Forms::on_gravity( array( 'id' => 5 ), array( 'id' => 2 ) );
Asiri_Forms::on_cf7( new class { public function id() { return 4; } } );
Asiri_Forms::on_wpforms( array(), $_POST, array( 'id' => 12 ), 90 );
check( 'the five hooks each record once', 5 === count( $GLOBALS['t']['posts'] ) );
$ext = array_map( function ( $p ) { return json_decode( $p['args']['body'], true )['externalId']; }, $GLOBALS['t']['posts'] );
check( 'external ids use the order or entry id', 'woocommerce:checkout:77' === $ext[0] && 'woocommerce:checkout:78' === $ext[1] && 'gravityforms:2:5' === $ext[2] && 'wpforms:12:90' === $ext[4] );
foreach ( array( 'wpforms_process_complete', 'gform_after_submission', 'wpcf7_before_send_mail', 'woocommerce_checkout_order_processed', 'woocommerce_store_api_checkout_order_processed' ) as $h ) {
	check( "hook $h is registered", ! empty( $GLOBALS['t']['filters'][ $h ] ) );
}
$_POST = array();
// no secret key => nothing, even with everything else on: checked by running a child process without the constant
$child = shell_exec( escapeshellarg( PHP_BINARY ) . ' -r ' . escapeshellarg( "define('ABSPATH','/');require '" . $plugin_dir . "includes/release.php';" . 'echo 1;' ) );
check( 'release file loads on its own', '1' === trim( (string) $child ) );

// ---------------------------------------------------------------- T4 settings
echo "T4 settings validation, capability and nonce\n";
foreach ( array( 'sk_live_abc', 'abc', 'pk_live_', 'pk_prod_abc', 'pk_live_a b', 'PK_live_abc' ) as $bad ) {
	check( "refuses key '$bad'", Asiri_Settings::sanitise( array( 'publishable_key' => $bad, 'notice_version' => 1 ) ) instanceof WP_Error );
}
foreach ( array( 'pk_live_abc123', 'pk_test_Z9' ) as $good ) {
	$o = Asiri_Settings::sanitise( array( 'publishable_key' => $good, 'source_id' => 'src_1', 'notice_version' => 2 ) );
	check( "accepts key '$good'", is_array( $o ) && $good === $o['publishable_key'] );
}
check( 'refuses a source id with markup', Asiri_Settings::sanitise( array( 'source_id' => '"><script>' ) ) instanceof WP_Error );
check( 'refuses a purpose without words', Asiri_Settings::sanitise( array( 'purposes' => 'news' ) ) instanceof WP_Error );
check( 'refuses a purpose id with a space', Asiri_Settings::sanitise( array( 'purposes' => 'a b | Words' ) ) instanceof WP_Error );
check( 'refuses a notice version of zero', Asiri_Settings::sanitise( array( 'notice_version' => 0 ) ) instanceof WP_Error );
$o = Asiri_Settings::sanitise( array( 'forms' => array( 'cf7', 'evil', 'cf7' ), 'purposes' => "a | <b>Hi</b>\n" ) );
check( 'unknown form names are dropped and labels stripped of markup', array( 'cf7' ) === $o['forms'] && 'Hi' === $o['purposes'][0]['label'] );
check( 'no setting exists for scroll or implied consent', 0 === preg_match( '/scroll|implied/i', json_encode( array_keys( Asiri_Settings::defaults() ) ) . $page ) );
$GLOBALS['t']['options'] = array( 'blogname' => 'x' );
$GLOBALS['t']['can'] = false;
$_POST = array( 'asiri' => array( 'publishable_key' => 'pk_live_abc' ) );
check( 'save refused without the capability (403)', 'wp_die:403' === throws( array( 'Asiri_Settings', 'handle_save' ) ) );
check( 'nothing stored after the refusal', ! isset( $GLOBALS['t']['options']['asiri_consent_settings'] ) );
check( 'settings page refused without the capability', 'wp_die:403' === throws( array( 'Asiri_Settings', 'render_page' ) ) );
$GLOBALS['t']['can'] = true; $GLOBALS['t']['nonce'] = false;
check( 'save refused with a bad nonce', 'bad_nonce' === throws( array( 'Asiri_Settings', 'handle_save' ) ) );
check( 'nothing stored after the nonce refusal', ! isset( $GLOBALS['t']['options']['asiri_consent_settings'] ) );
$GLOBALS['t']['nonce'] = true;
check( 'save with capability and nonce redirects', 'redirect' === throws( array( 'Asiri_Settings', 'handle_save' ) ) );
check( 'valid key was stored', 'pk_live_abc' === ( $GLOBALS['t']['options']['asiri_consent_settings']['publishable_key'] ?? '' ) );
$_POST = array( 'asiri' => array( 'publishable_key' => 'sk_live_nope' ) );
throws( array( 'Asiri_Settings', 'handle_save' ) );
check( 'a secret key typed into the field is refused and not stored', 'pk_live_abc' === $GLOBALS['t']['options']['asiri_consent_settings']['publishable_key'] && false === strpos( serialize( $GLOBALS['t']['options'] ), 'sk_live_nope' ) );
$_POST = array();
check( 'the form carries a nonce field', false !== strpos( Asiri_Settings::page_html(), '_wpnonce' ) );

// ---------------------------------------------------------------- T5 consent type and registration
echo "T5 consent type and registration\n";
unset( $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'], $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY_REGION'] );
check( 'no header gives optin', 'optin' === apply_filters( 'wp_get_consent_type', 'optin' ) );
$_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'] = 'US'; $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY_REGION'] = 'CA';
check( 'US-CA gives optout', 'optout' === apply_filters( 'wp_get_consent_type', 'optin' ) );
$_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY_REGION'] = 'NY';
check( 'US-NY gives optin', 'optin' === apply_filters( 'wp_get_consent_type', 'optin' ) );
$_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'] = 'NG'; $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY_REGION'] = 'CA';
check( 'a CA region outside the US gives optin', 'optin' === apply_filters( 'wp_get_consent_type', 'optin' ) );
$_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'] = 'GB'; unset( $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY_REGION'] );
check( 'GB gives optin', 'optin' === apply_filters( 'wp_get_consent_type', 'optin' ) );
$_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'] = 'US'; $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY_REGION'] = 'CA';
check( 'registration filter is on the plugin basename', true === apply_filters( 'wp_consent_api_registered_asiri-consent/asiri-consent.php', false ) );
set_settings();
$page = Asiri_Settings::page_html();
check( 'page shows Not registered in red when the WP Consent API is absent', false !== strpos( $page, 'color:#b32d2e">Not registered' ) );
check( 'page says wp_has_consent() returns true without a manager', false !== strpos( $page, 'wp_has_consent() returns true for every category' ) );
eval( 'class WP_CONSENT_API {}' ); // runtime declaration, so the class is absent until this line.
$page = Asiri_Settings::page_html();
check( 'page shows Registered once the WP Consent API is present', false !== strpos( $page, '>Registered<' ) && false === strpos( $page, 'Not registered' ) );
Asiri_Bridge::enqueue();
check( 'bridge script is enqueued with the server type', ! empty( $GLOBALS['t']['enqueued']['asiri-consent-bridge'] ) && false !== strpos( $GLOBALS['t']['inline']['asiri-consent-bridge'], '"optout"' ) );

// ---------------------------------------------------------------- T6 client
echo "T6 client\n";
set_settings();
reset_posts();
Asiri_Client::record_receipt( array( 'ref' => 'n', 'version' => 1 ), array( array( 'purpose' => 'a', 'granted' => false ) ), 'refused-all', 'wpforms:1:2', 'wpforms|1|2' );
$p = $GLOBALS['t']['posts'][0];
check( 'url is https://api.asiri.ng', 0 === strpos( $p['url'], 'https://api.asiri.ng/' ) && 'api.asiri.ng' === parse_url( $p['url'], PHP_URL_HOST ) );
check( 'Authorization is Bearer with the constant', 'Bearer ' . ASIRI_SECRET_KEY === $p['args']['headers']['Authorization'] );
check( 'idempotency key is 16 to 64 safe characters', 1 === preg_match( '/^[A-Za-z0-9_-]{16,64}$/', $p['args']['headers']['Idempotency-Key'] ) );
$again_before = $p['args']['headers']['Idempotency-Key'];
reset_posts();
Asiri_Client::record_receipt( array( 'ref' => 'n', 'version' => 1 ), array( array( 'purpose' => 'a', 'granted' => false ) ), 'refused-all', 'wpforms:1:2', 'wpforms|1|2' );
check( 'the same act gets the same idempotency key', $again_before === $GLOBALS['t']['posts'][0]['args']['headers']['Idempotency-Key'] );
check( 'timeout is 5 seconds', 5 === $p['args']['timeout'] );
check( 'redirects are never followed', 0 === $p['args']['redirection'] );
check( 'TLS is verified', true === $p['args']['sslverify'] );
check( 'the key is not in the body or the url', false === strpos( $p['args']['body'], ASIRI_SECRET_KEY ) && false === strpos( $p['url'], ASIRI_SECRET_KEY ) );
$all_urls = array();
foreach ( glob( $plugin_dir . 'includes/*.php' ) as $f ) { if ( preg_match_all( '/wp_remote_[a-z_]+\(/', file_get_contents( $f ), $mm ) ) { $all_urls = array_merge( $all_urls, array( basename( $f ) => $mm[0] ) ); } }
check( 'the only outbound call in the plugin is in the client', array_keys( $all_urls ) === array( 'class-asiri-client.php' ) && array( 'wp_remote_post(', 'wp_remote_retrieve_response_code(' ) === array_values( array_unique( $all_urls['class-asiri-client.php'] ) ) );
check( 'one request per act, never an inline retry', 1 === count( $GLOBALS['t']['posts'] ) );
reset_posts();
$GLOBALS['t']['response'] = new WP_Error( 'http_request_failed', 'timeout' );
$r = Asiri_Client::record_receipt( array( 'ref' => 'n', 'version' => 1 ), array( array( 'purpose' => 'a', 'granted' => true ) ), 'accepted-all', 'cf7:1:x', 'cf7|1|x' );
check( 'a failure is reported once and not retried', false === $r['ok'] && 0 === $r['status'] && 1 === count( $GLOBALS['t']['posts'] ) );
$GLOBALS['t']['response'] = array( 'response' => array( 'code' => 201 ) );
// no key => no request
$child = shell_exec( escapeshellarg( PHP_BINARY ) . ' -d display_errors=1 ' . escapeshellarg( __DIR__ . '/nokey.php' ) );
check( 'with no secret key set nothing is sent', 'no-request' === trim( (string) $child ), (string) $child );

// ---------------------------------------------------------------- shortcode and block
echo "Shortcode and block\n";
set_settings();
$sc = Asiri_Preferences::shortcode( array() );
check( 'shortcode prints a button with data-asiri-open', false !== strpos( $sc, 'data-asiri-open' ) && false !== strpos( $sc, '<button type="button"' ) );
check( 'shortcode label is escaped', false === strpos( Asiri_Preferences::shortcode( array( 'label' => '<img src=x>' ) ), '<img' ) );
Asiri_Preferences::register();
check( 'shortcode and block are registered', isset( $GLOBALS['t']['shortcodes']['asiri_preferences'] ) && 1 === count( $GLOBALS['t']['blocks'] ) );
$bj = json_decode( file_get_contents( $plugin_dir . 'blocks/preferences-link/block.json' ), true );
check( 'block.json names the block, server rendered', 'Asiri: cookie settings link' === $bj['title'] && 'file:./render.php' === $bj['render'] );
set_settings( array( 'publishable_key' => '' ) );
check( 'with no key set the link prints nothing', '' === Asiri_Preferences::shortcode( array() ) );
set_settings();

// ---------------------------------------------------------------- T7 uninstall
echo "T7 uninstall\n";
$GLOBALS['t']['options'] = array( 'blogname' => 'My site', 'other_plugin_opt' => 'keep', 'asiri_consent_settings' => array( 'x' => 1 ), 'asiri_consent_last_error' => array( 'status' => 500 ) );
$GLOBALS['t']['deleted'] = array();
define( 'WP_UNINSTALL_PLUGIN', 'asiri-consent/asiri-consent.php' );
require $plugin_dir . 'uninstall.php';
check( 'only the plugin options were deleted', array( 'asiri_consent_settings', 'asiri_consent_last_error' ) === $GLOBALS['t']['deleted'] );
check( 'other options are untouched', 'My site' === $GLOBALS['t']['options']['blogname'] && 'keep' === $GLOBALS['t']['options']['other_plugin_opt'] );
check( 'uninstall does nothing outside an uninstall', 0 === preg_match( '/DROP|wpdb|delete_user|delete_post/i', file_get_contents( $plugin_dir . 'uninstall.php' ) ) );

// ---------------------------------------------------------------- summary
echo "\n" . $GLOBALS['pass'] . ' passed, ' . $GLOBALS['fail'] . " failed\n";
exit( $GLOBALS['fail'] > 0 ? 1 : 0 );
