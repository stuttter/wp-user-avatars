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
	public function __construct( $id = 0 ) { $this->ID = (int) $id; }
}

class WP_Post {
	public $post_author;
	public function __construct( $author = 0 ) { $this->post_author = (int) $author; }
}

class WP_Comment {
	public $user_id;
	public function __construct( $user_id = 0 ) { $this->user_id = (int) $user_id; }
}

class WP_Error {
	public $errors = array();
	public function add( $code, $message ) { $this->errors[ $code ][] = $message; }
}

function add_action( ...$arguments ) { wpua_test_call( __FUNCTION__, $arguments ); }
function add_filter( ...$arguments ) { wpua_test_call( __FUNCTION__, $arguments ); }
function remove_action( ...$arguments ) { wpua_test_call( __FUNCTION__, $arguments ); }
function plugin_dir_path( $file ) { return dirname( $file ) . '/'; }
function plugin_dir_url() { return 'https://example.test/wp-content/plugins/wp-user-avatars/'; }
function load_plugin_textdomain( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
function apply_filters( $hook, $value, ...$arguments ) {
	$result = wpua_test_call( __FUNCTION__ . ':' . $hook, array_merge( array( $value ), $arguments ) );
	return null === $result ? $value : $result;
}
function esc_html__( $text ) { return $text; }
function is_email( $value ) { return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function get_user_by( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
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
function wp_get_image_editor( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function home_url( $path = '' ) { return 'https://example.test' . $path; }
function get_current_blog_id() { return (int) ( wpua_test_call( __FUNCTION__, array() ) ?? 1 ); }
function wp_get_attachment_url( ...$arguments ) { return wpua_test_call( __FUNCTION__, $arguments ); }
function esc_url_raw( $value ) { return (string) $value; }
function user_can( ...$arguments ) { return (bool) wpua_test_call( __FUNCTION__, $arguments ); }
function get_editable_roles() { return wpua_test_call( __FUNCTION__, array() ) ?? array(); }
function sanitize_file_name( $value ) { return preg_replace( '/[^A-Za-z0-9._-]/', '-', $value ); }

require_once dirname( __DIR__ ) . '/wp-user-avatars.php';
require_once dirname( __DIR__ ) . '/wp-user-avatars/includes/common.php';
require_once dirname( __DIR__ ) . '/wp-user-avatars/includes/capabilities.php';
require_once dirname( __DIR__ ) . '/wp-user-avatars/includes/admin.php';
require_once dirname( __DIR__ ) . '/wp-user-avatars/includes/hooks.php';
