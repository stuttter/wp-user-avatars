<?php

/**
 * Plugin Name: WP User Avatars
 * Plugin URI:  https://wordpress.org/plugins/wp-user-avatars/
 * Author:      John James Jacoby
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: Allow registered users to upload & select their own avatars
 * Version:     1.4.0
 * Text Domain: wp-user-avatars
 * Domain Path: /wp-user-avatars/assets/languages/
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Include the plugin files
 *
 * @since 0.1.0
 */
function _wp_user_avatars() {

	// Get the plugin path
	$plugin_path = plugin_dir_path( __FILE__ ) . 'wp-user-avatars/';

	// Required files
	require_once $plugin_path . 'includes/admin.php';
	require_once $plugin_path . 'includes/ajax.php';
	require_once $plugin_path . 'includes/capabilities.php';
	require_once $plugin_path . 'includes/common.php';
	require_once $plugin_path . 'includes/errors.php';
	require_once $plugin_path . 'includes/metabox.php';
	require_once $plugin_path . 'includes/uninstall.php';
	require_once $plugin_path . 'includes/hooks.php';

	// Load translations
	load_plugin_textdomain( 'wp-user-avatars', false, $plugin_path . 'assets/languages/' );
}
add_action( 'plugins_loaded', '_wp_user_avatars' );

/**
 * Return the plugin URL
 *
 * @since 0.1.0
 *
 * @return string
 */
function wp_user_avatars_get_plugin_url() {
	return plugin_dir_url( __FILE__ ) . 'wp-user-avatars/';
}

/**
 * Return the asset version
 *
 * @since 0.1.0
 *
 * @return int
 */
function wp_user_avatars_get_asset_version() {
	return 201804180002;
}
