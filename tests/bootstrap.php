<?php

declare(strict_types=1);

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$GLOBALS['wpua_test'] = array();

function wpua_test_call( $name, $arguments ) {
	$GLOBALS['wpua_test']['calls'][ $name ][] = $arguments;

	if ( isset( $GLOBALS['wpua_test']['callbacks'][ $name ] ) ) {
		return $GLOBALS['wpua_test']['callbacks'][ $name ]( ...$arguments );
	}

	return $GLOBALS['wpua_test']['returns'][ $name ] ?? null;
}

class WP_User {
	public $ID;
	/**
	 * Local avatar data.
	 *
	 * @var array<string, string>
	 */
	public $wp_user_avatars;
	/**
	 * Local avatar rating.
	 *
	 * @var string
	 */
	public $wp_user_avatars_rating;
	public function __construct( $id = 0 ) { $this->ID = (int) $id; }
}

class WP_Post {
	public $post_author;
	/**
	 * Post content.
	 *
	 * @var string
	 */
	public $post_content = '';
	public function __construct( $author = 0 ) { $this->post_author = (int) $author; }
}

class WP_Comment {
	public $user_id;
	public function __construct( $user_id = 0 ) { $this->user_id = (int) $user_id; }
}

class WP_Error {
	public $errors = array();
	/**
	 * Provide the __construct test double.
	 *
	 * @param mixed $code Test input.
	 * @param mixed $message Test input.
	 *
	 * @return mixed
	 */
	public function __construct( $code = '', $message = '' ) {
		if ( $code ) {
			$this->add( $code, $message );
		}
	}
	public function add( $code, $message ) { $this->errors[ $code ][] = $message; }
	/**
	 * Provide the get_error_code test double.
	 *
	 * @return mixed
	 */
	public function get_error_code() {
		return key( $this->errors );
	}
	/**
	 * Provide the get_error_message test double.
	 *
	 * @return mixed
	 */
	public function get_error_message() {
		$code = $this->get_error_code();
		return $code ? $this->errors[ $code ][0] : '';
	}
}

function add_action( ...$arguments ) { wpua_test_call( __FUNCTION__, $arguments ); }
function add_filter( ...$arguments ) { wpua_test_call( __FUNCTION__, $arguments ); }
/**
 * Provide the add_shortcode test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return void
 */
function add_shortcode( ...$arguments ) {
	wpua_test_call( __FUNCTION__, $arguments );
}
/**
 * Provide the has_shortcode test double.
 *
 * @param string $content Content to inspect.
 * @param string $tag     Shortcode tag.
 *
 * @return bool
 */
function has_shortcode( $content, $tag ) {
	return false !== strpos( $content, '[' . $tag );
}
function remove_action( ...$arguments ) { wpua_test_call( __FUNCTION__, $arguments ); }
/**
 * Provide the remove_filter test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function remove_filter( ...$arguments ) {
	wpua_test_call( __FUNCTION__, $arguments );
}
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_dir_url() { return 'https://example.test/wp-content/plugins/wp-user-avatars/'; }
function load_plugin_textdomain( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
function apply_filters( $hook, $value, ...$arguments ) {
	$result = wpua_test_call( __FUNCTION__ . ':' . $hook, array_merge( array( $value ), $arguments ) );
	return null === $result ? $value : $result;
}
function esc_html__( $text ) { return $text; }
/**
 * Provide the esc_html test double.
 *
 * @param mixed $text Test input.
 *
 * @return string
 */
function esc_html( $text ) {
	return (string) $text;
}
/**
 * Provide the esc_attr test double.
 *
 * @param mixed $text Test input.
 *
 * @return string
 */
function esc_attr( $text ) {
	return (string) $text;
}
/**
 * Provide the esc_url test double.
 *
 * @param mixed $text Test input.
 *
 * @return string
 */
function esc_url( $text ) {
	return (string) $text;
}
/**
 * Provide the esc_html_e test double.
 *
 * @param mixed $text Test input.
 *
 * @return void
 */
function esc_html_e( $text ) {
	echo esc_html( $text );
}
/**
 * Provide the translation test double.
 *
 * @param mixed $text Test input.
 *
 * @return mixed
 */
function __( $text ) {
	return $text;
}
/**
 * Provide the checked test double.
 *
 * @param mixed $checked Compared value.
 * @param mixed $current Expected value.
 * @param bool  $display Whether to output the result.
 *
 * @return string
 */
function checked( $checked, $current = true, $display = true ) {
	// phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Match WordPress checked() coercion.
	$result = $checked == $current ? 'checked="checked"' : '';
	if ( $display ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The test double emits fixed markup.
		echo $result;
	}
	return $result;
}
/**
 * Provide the disabled test double.
 *
 * @param mixed $disabled Compared value.
 * @param mixed $current  Expected value.
 * @param bool  $display  Whether to output the result.
 *
 * @return string
 */
function disabled( $disabled, $current = true, $display = true ) {
	// phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Match WordPress disabled() coercion.
	$result = $disabled == $current ? 'disabled="disabled"' : '';
	if ( $display ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The test double emits fixed markup.
		echo $result;
	}
	return $result;
}
function is_email( $value ) { return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function get_user_by( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
/**
 * Provide the get_userdata test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function get_userdata( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}
function get_avatar( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
function get_user_meta( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
function update_user_meta( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
function delete_user_meta( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
function get_option( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
function is_multisite() { return (bool) wpua_test_call( __FUNCTION__, array() ); }
function switch_to_blog( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
function restore_current_blog() { return wpua_test_call( __FUNCTION__, array() ); }
function get_attached_file( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
function is_user_logged_in() { return (bool) wpua_test_call( __FUNCTION__, array() ); }
function wp_upload_dir() { return wpua_test_call( __FUNCTION__, array() ); }
function wp_delete_file( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
function wp_get_image_editor( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
/**
 * Provide the wp_handle_upload test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function wp_handle_upload( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
/**
 * Provide the wp_parse_url test double.
 *
 * @param mixed $url Test input.
 * @param mixed $component Test input.
 *
 * @return mixed
 */
function wp_parse_url( $url, $component = -1 ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- This WordPress test double delegates to PHP's URL parser.
	return parse_url( $url, $component );
}
/**
 * Provide the wp_is_stream test double.
 *
 * @param mixed $path Test input.
 *
 * @return mixed
 */
function wp_is_stream( $path ) {
	return false !== strpos( $path, '://' );
}
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function get_current_blog_id() { return (int) ( wpua_test_call( __FUNCTION__, array() ) ?? 1 ); }
function wp_get_attachment_url( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
/**
 * Provide the wp_get_attachment_image_url test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function wp_get_attachment_image_url( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}
function esc_url_raw( $value ) { return (string) $value; }
/**
 * Provide the wp_kses_post test double.
 *
 * @param mixed $value Test input.
 *
 * @return mixed
 */
function wp_kses_post( $value ) {
	return $value;
}
function user_can( ...$arguments ) { return (bool) wpua_test_call( __FUNCTION__, $arguments ); }
function get_editable_roles() { return wpua_test_call( __FUNCTION__, array() ) ?? array(); }
function sanitize_file_name( $value ) { return preg_replace( '/[^A-Za-z0-9._-]/', '-', $value ); }
/**
 * Provide the sanitize_key test double.
 *
 * @param mixed $value Test input.
 *
 * @return mixed
 */
function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $value ) );
}
/**
 * Provide the sanitize_text_field test double.
 *
 * @param mixed $value Test input.
 *
 * @return mixed
 */
function sanitize_text_field( $value ) {
	return trim( (string) $value );
}
/**
 * Provide the wp_unslash test double.
 *
 * @param mixed $value Test input.
 *
 * @return mixed
 */
function wp_unslash( $value ) {
	return stripslashes( (string) $value );
}
/**
 * Provide the current_user_can test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function current_user_can( ...$arguments ) {
	return (bool) wpua_test_call( __FUNCTION__, $arguments );
}
/**
 * Provide the bbp_is_single_user_edit test double.
 *
 * @return mixed
 */
function bbp_is_single_user_edit() {
	return (bool) wpua_test_call( __FUNCTION__, array() );
}
/**
 * Provide the bbp_get_displayed_user_id test double.
 *
 * @return mixed
 */
function bbp_get_displayed_user_id() {
	return (int) wpua_test_call( __FUNCTION__, array() );
}
/**
 * Provide the wp_enqueue_media test double.
 *
 * @return mixed
 */
function wp_enqueue_media() {
	return wpua_test_call( __FUNCTION__, array() );
}
/**
 * Provide the wp_enqueue_script test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function wp_enqueue_script( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}
/**
 * Provide the wp_enqueue_style test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function wp_enqueue_style( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}
/**
 * Provide the wp_localize_script test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function wp_localize_script( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}
/**
 * Provide the wp_create_nonce test double.
 *
 * @param mixed $action Test input.
 *
 * @return mixed
 */
function wp_create_nonce( $action ) {
	return $action . '-nonce';
}
/**
 * Provide the admin_url test double.
 *
 * @param mixed $path Test input.
 *
 * @return mixed
 */
function admin_url( $path = '' ) {
	return 'https://example.test/wp-admin/' . $path;
}
/**
 * Provide the get_current_user_id test double.
 *
 * @return mixed
 */
function get_current_user_id() {
	return (int) ( wpua_test_call( __FUNCTION__, array() ) ?? 1 );
}
/**
 * Provide the wp_nonce_field test double.
 *
 * @param string $action  Nonce action.
 * @param string $name    Field name.
 * @param bool   $referer Whether to include the referer field.
 * @param bool   $display Whether to output the field.
 *
 * @return string
 */
function wp_nonce_field( $action, $name, $referer = true, $display = true ) {
	$field = '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_create_nonce( $action ) ) . '" />';
	if ( $display ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The test double builds escaped fixed markup.
		echo $field;
	}
	return $field;
}
/**
 * Provide the is_rtl test double.
 *
 * @return mixed
 */
function is_rtl() {
	return false;
}

require_once dirname( __DIR__ ) . '/wp-user-avatars.php';
require_once dirname( __DIR__ ) . '/wp-user-avatars/includes/common.php';
require_once dirname( __DIR__ ) . '/wp-user-avatars/includes/capabilities.php';
require_once dirname( __DIR__ ) . '/wp-user-avatars/includes/admin.php';
require_once dirname( __DIR__ ) . '/wp-user-avatars/includes/ajax.php';
require_once dirname( __DIR__ ) . '/wp-user-avatars/includes/frontend.php';
require_once dirname( __DIR__ ) . '/wp-user-avatars/includes/hooks.php';
