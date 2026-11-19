<?php
/**
 * Settings page: Settings, Asiri Consent.
 *
 * The secret key is never stored and never shown. It is read only from the ASIRI_SECRET_KEY constant in
 * wp-config.php, and this page says only whether it was found.
 *
 * @package Asiri_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Asiri_Settings {

	const OPTION     = 'asiri_consent_settings';
	const ERROR_OPT  = 'asiri_consent_last_error';
	const NONCE      = 'asiri_consent_save';
	const ACTION     = 'asiri_consent_save';
	const CAPABILITY = 'manage_options';
	const PAGE       = 'asiri-consent';

	/** The form plugins the boxes can be drawn in. */
	const FORM_KEYS = array( 'wpforms', 'gravityforms', 'cf7', 'woocommerce' );

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_save' ) );
	}

	public static function defaults() {
		return array(
			'publishable_key' => '',
			'source_id'       => '',
			'notice_ref'      => '',
			'notice_version'  => 1,
			'purposes'        => array(),
			'forms'           => array(),
		);
	}

	/** The saved settings, always complete and always of the right shape. */
	public static function get() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( self::defaults(), $saved );
	}

	/** True when the secret key constant is present. The value is never returned by any function here. */
	public static function secret_key_found() {
		return defined( 'ASIRI_SECRET_KEY' ) && is_string( ASIRI_SECRET_KEY ) && '' !== ASIRI_SECRET_KEY;
	}

	public static function is_publishable_key( $key ) {
		return is_string( $key ) && 1 === preg_match( '/^pk_(live|test)_[A-Za-z0-9]+$/', $key );
	}

	/** Is the script able to run: a good key and a source id. */
	public static function is_configured() {
		$s = self::get();
		return self::is_publishable_key( $s['publishable_key'] ) && '' !== $s['source_id'];
	}

	/**
	 * Cleans and checks what was submitted. Returns the settings to store, or a WP_Error naming the first
	 * field that cannot be used. Nothing is stored here.
	 *
	 * @param array $input Raw values, already unslashed.
	 */
	public static function sanitise( $input ) {
		$input = is_array( $input ) ? $input : array();
		$out   = self::defaults();

		$key = isset( $input['publishable_key'] ) ? trim( (string) $input['publishable_key'] ) : '';
		if ( '' !== $key && ! self::is_publishable_key( $key ) ) {
			return new WP_Error( 'asiri_key', 'The publishable key must start with pk_live_ or pk_test_. A secret key (sk_) never goes here.' );
		}
		$out['publishable_key'] = $key;

		$source = isset( $input['source_id'] ) ? trim( (string) $input['source_id'] ) : '';
		if ( '' !== $source && 1 !== preg_match( '/^[A-Za-z0-9_]{1,64}$/', $source ) ) {
			return new WP_Error( 'asiri_source', 'The source id can use letters, digits and underscores only.' );
		}
		$out['source_id'] = $source;

		$ref = isset( $input['notice_ref'] ) ? trim( (string) $input['notice_ref'] ) : '';
		if ( '' !== $ref && 1 !== preg_match( '/^[A-Za-z0-9._-]{1,64}$/', $ref ) ) {
			return new WP_Error( 'asiri_notice', 'The notice reference can use letters, digits, dots, dashes and underscores only.' );
		}
		$out['notice_ref'] = $ref;

		$version = isset( $input['notice_version'] ) ? (int) $input['notice_version'] : 1;
		if ( $version < 1 || $version > 100000 ) {
			return new WP_Error( 'asiri_notice_version', 'The notice version must be a whole number from 1 upwards.' );
		}
		$out['notice_version'] = $version;

		$purposes = self::parse_purposes( isset( $input['purposes'] ) ? (string) $input['purposes'] : '' );
		if ( is_wp_error( $purposes ) ) {
			return $purposes;
		}
		$out['purposes'] = $purposes;

		$forms = isset( $input['forms'] ) && is_array( $input['forms'] ) ? $input['forms'] : array();
		foreach ( $forms as $form ) {
			if ( in_array( $form, self::FORM_KEYS, true ) ) {
				$out['forms'][] = $form;
			}
		}
		$out['forms'] = array_values( array_unique( $out['forms'] ) );

		return $out;
	}

	/**
	 * One purpose a line: "purpose-id | Words next to the box". Returns a list of arrays with id and label.
	 */
	public static function parse_purposes( $text ) {
		$out   = array();
		$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' === $line ) {
				continue;
			}
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			$id    = $parts[0];
			$label = isset( $parts[1] ) ? sanitize_text_field( $parts[1] ) : '';
			if ( 1 !== preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $id ) || '' === $label ) {
				return new WP_Error( 'asiri_purposes', 'Each purpose needs an id (letters, digits, dashes) and the words shown beside its box, separated by a bar.' );
			}
			$out[] = array(
				'id'    => $id,
				'label' => function_exists( 'mb_substr' ) ? mb_substr( $label, 0, 200 ) : substr( $label, 0, 200 ),
			);
			if ( count( $out ) > 20 ) {
				return new WP_Error( 'asiri_purposes', 'Twenty purposes at most.' );
			}
		}
		return $out;
	}

	public static function add_page() {
		add_options_page(
			__( 'Asiri Consent', 'asiri-consent' ),
			__( 'Asiri Consent', 'asiri-consent' ),
			self::CAPABILITY,
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/** Handles the form post. Capability first, then the nonce, then the values. */
	public static function handle_save() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to change these settings.', 'asiri-consent' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( self::NONCE );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- the nonce is checked above and every value is sanitised in sanitise().
		$raw = isset( $_POST['asiri'] ) && is_array( $_POST['asiri'] ) ? wp_unslash( $_POST['asiri'] ) : array();
		$new = self::sanitise( $raw );

		$url = add_query_arg( 'page', self::PAGE, admin_url( 'options-general.php' ) );
		if ( is_wp_error( $new ) ) {
			set_transient( 'asiri_consent_notice_' . get_current_user_id(), $new->get_error_message(), 60 );
			wp_safe_redirect( add_query_arg( 'asiri_status', 'error', $url ) );
		} else {
			update_option( self::OPTION, $new, false );
			wp_safe_redirect( add_query_arg( 'asiri_status', 'saved', $url ) );
		}
		exit;
	}

	public static function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to open this page.', 'asiri-consent' ), '', array( 'response' => 403 ) );
		}
		echo self::page_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside page_html().
	}

	/** The page as a string, so it can be tested. Every dynamic value is escaped where it is written. */
	public static function page_html() {
		$s          = self::get();
		$registered = Asiri_Bridge::is_registered();
		$purposes   = '';
		foreach ( $s['purposes'] as $p ) {
			$purposes .= $p['id'] . ' | ' . $p['label'] . "\n";
		}
		$labels = array(
			'wpforms'      => 'WPForms',
			'gravityforms' => 'Gravity Forms',
			'cf7'          => 'Contact Form 7',
			'woocommerce'  => 'WooCommerce checkout',
		);

		$notice = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		$status = isset( $_GET['asiri_status'] ) ? sanitize_key( wp_unslash( $_GET['asiri_status'] ) ) : '';
		if ( 'saved' === $status ) {
			$notice = '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved.', 'asiri-consent' ) . '</p></div>';
		} elseif ( 'error' === $status ) {
			$msg    = get_transient( 'asiri_consent_notice_' . get_current_user_id() );
			$notice = '<div class="notice notice-error"><p>' . esc_html( is_string( $msg ) ? $msg : __( 'Settings were not saved.', 'asiri-consent' ) ) . '</p></div>';
		}

		ob_start();
		?>
<div class="wrap">
	<h1><?php echo esc_html__( 'Asiri Consent', 'asiri-consent' ); ?></h1>
		<?php echo $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from escaped parts. ?>

	<h2><?php echo esc_html__( 'Status', 'asiri-consent' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php echo esc_html__( 'WP Consent API bridge', 'asiri-consent' ); ?></th>
			<td>
				<?php if ( $registered ) : ?>
					<strong style="color:#1a7f37"><?php echo esc_html__( 'Registered', 'asiri-consent' ); ?></strong>
				<?php else : ?>
					<strong style="color:#b32d2e"><?php echo esc_html__( 'Not registered', 'asiri-consent' ); ?></strong>
				<?php endif; ?>
				<p class="description"><?php echo esc_html__( 'Without a consent manager registered, wp_has_consent() returns true for every category.', 'asiri-consent' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php echo esc_html__( 'Secret key', 'asiri-consent' ); ?></th>
			<td>
				<?php if ( self::secret_key_found() ) : ?>
					<?php echo esc_html__( 'Secret key: found in wp-config.php', 'asiri-consent' ); ?>
				<?php else : ?>
					<?php echo esc_html__( 'Secret key: not set', 'asiri-consent' ); ?>
				<?php endif; ?>
				<p class="description"><?php echo esc_html__( 'The secret key is read only from the ASIRI_SECRET_KEY constant in wp-config.php. It is never saved in the database or shown here. Form answers are recorded only when it is set.', 'asiri-consent' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php echo esc_html__( 'Script release', 'asiri-consent' ); ?></th>
			<td><code><?php echo esc_html( 'v' . ASIRI_CONSENT_EDGE_VERSION ); ?></code></td>
		</tr>
	</table>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
		<?php wp_nonce_field( self::NONCE ); ?>

		<h2><?php echo esc_html__( 'Your source', 'asiri-consent' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="asiri-pk"><?php echo esc_html__( 'Publishable key', 'asiri-consent' ); ?></label></th>
				<td>
					<input id="asiri-pk" class="regular-text code" type="text" name="asiri[publishable_key]" value="<?php echo esc_attr( $s['publishable_key'] ); ?>" autocomplete="off" spellcheck="false" />
					<p class="description"><?php echo esc_html__( 'Starts with pk_live_ or pk_test_. It is safe to show on your site.', 'asiri-consent' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="asiri-source"><?php echo esc_html__( 'Source id', 'asiri-consent' ); ?></label></th>
				<td><input id="asiri-source" class="regular-text code" type="text" name="asiri[source_id]" value="<?php echo esc_attr( $s['source_id'] ); ?>" autocomplete="off" spellcheck="false" /></td>
			</tr>
		</table>

		<h2><?php echo esc_html__( 'Form answers', 'asiri-consent' ); ?></h2>
		<p><?php echo esc_html__( 'Choose where to draw consent boxes. Every box starts unticked, and an unticked box is recorded as a no. A box is shown only when the secret key, the notice and at least one purpose are set.', 'asiri-consent' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php echo esc_html__( 'Show boxes in', 'asiri-consent' ); ?></th>
				<td>
					<?php foreach ( $labels as $key => $label ) : ?>
						<label style="display:block">
							<input type="checkbox" name="asiri[forms][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $s['forms'], true ) ); ?> />
							<?php echo esc_html( $label ); ?>
						</label>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="asiri-notice"><?php echo esc_html__( 'Notice reference', 'asiri-consent' ); ?></label></th>
				<td><input id="asiri-notice" class="regular-text code" type="text" name="asiri[notice_ref]" value="<?php echo esc_attr( $s['notice_ref'] ); ?>" autocomplete="off" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="asiri-notice-version"><?php echo esc_html__( 'Notice version', 'asiri-consent' ); ?></label></th>
				<td><input id="asiri-notice-version" class="small-text" type="number" min="1" name="asiri[notice_version]" value="<?php echo esc_attr( (string) $s['notice_version'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="asiri-purposes"><?php echo esc_html__( 'Purposes', 'asiri-consent' ); ?></label></th>
				<td>
					<textarea id="asiri-purposes" class="large-text code" rows="5" name="asiri[purposes]" spellcheck="false"><?php echo esc_textarea( $purposes ); ?></textarea>
					<p class="description"><?php echo esc_html__( 'One a line: the purpose id, a bar, then the words shown beside the box. For example: newsletter | Send me the newsletter', 'asiri-consent' ); ?></p>
				</td>
			</tr>
		</table>
		<?php submit_button(); ?>
	</form>
		<?php self::render_last_error(); ?>
</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_last_error() {
		$e = get_option( self::ERROR_OPT, array() );
		if ( ! is_array( $e ) || empty( $e['status'] ) ) {
			return;
		}
		echo '<p class="description">' . esc_html(
			sprintf(
				/* translators: 1: HTTP status or 0, 2: UTC time. */
				__( 'The last form answer that could not be sent got status %1$d at %2$s UTC.', 'asiri-consent' ),
				(int) $e['status'],
				isset( $e['at'] ) ? (string) $e['at'] : ''
			)
		) . '</p>';
	}
}
