<?php

/**
 * Plugin Name: WP User Avatars
 * Plugin URI:  https://wordpress.org/plugins/wp-user-avatars/
 * Author:      John James Jacoby
 * Author URI:  https://profiles.wordpress.org/johnjamesjacoby/
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Description: Avatars for your users, without Gravatar
 * Version:     0.2.0
 * Text Domain: wp-user-avatars
 * Domain Path: /assets/lang/
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Include the Event Calendar files
 *
 * @since 0.1.0
 */
function _wp_user_avatars() {

	// Get the plugin path
	$plugin_path = plugin_dir_path( __FILE__ );

	// Required files
	require_once $plugin_path . 'includes/admin.php'; // Only hooked if is_admin()
	require_once $plugin_path . 'includes/ajax.php';
	require_once $plugin_path . 'includes/capabilities.php';
	require_once $plugin_path . 'includes/functions.php';
	require_once $plugin_path . 'includes/errors.php';
	require_once $plugin_path . 'includes/metabox.php';
	require_once $plugin_path . 'includes/uninstall.php';
	require_once $plugin_path . 'includes/hooks.php';
}
add_action( 'plugins_loaded', '_wp_user_avatars' );

/**
 * Return the plugin's URL
 *
 * @since 0.1.0
 *
 * @return string
 */
function wp_user_avatars_get_plugin_url() {
	return plugin_dir_url( __FILE__ );
}

/**
 * Return the asset version
 *
 * @since 0.1.0
 *
 * @return int
 */
function wp_user_avatars_get_asset_version() {
	return 201512230001;
}

/**
 * Loads the translation file.
 *
 * @since 0.1.0
 */
function wp_user_avatars_i18n() {
	load_plugin_textdomain( 'wp-user-avatars', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
