<?php
// Child process for the tests: the secret key constant is NOT defined, so no request may be made.
define( 'ABSPATH', __DIR__ . '/' );
$sent = false;
function get_option( $k, $d = false ) { return array( 'notice_ref' => 'n', 'notice_version' => 1, 'purposes' => array( array( 'id' => 'a', 'label' => 'A' ) ), 'forms' => array( 'cf7' ) ); }
function update_option() {}
function wp_remote_post() { global $sent; $sent = true; return array(); }
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_list_pluck( $l, $f ) { return array_map( function ( $i ) use ( $f ) { return $i[ $f ]; }, $l ); }
function wp_generate_uuid4() { return 'x'; }
function sanitize_text_field( $s ) { return $s; }
class WP_Error {}
require dirname( __DIR__ ) . '/asiri-consent/includes/class-asiri-settings.php';
require dirname( __DIR__ ) . '/asiri-consent/includes/class-asiri-client.php';
require dirname( __DIR__ ) . '/asiri-consent/includes/class-asiri-forms.php';
Asiri_Forms::record( 'cf7', '1', '', array( 'asiri_consent_shown' => '1' ) );
echo $sent ? 'request-sent' : 'no-request';
