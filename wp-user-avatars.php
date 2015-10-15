<?php

/**
 * Plugin Name: WP User Avatars
 * Plugin URI:  https://wordpress.org/plugins/wp-user-avatars/
 * Description: Avatars for your users, without Gravatar
 * Version:     0.1.1
 * Author:      John James Jacoby
 * Author URI:  http://jjj.me
 * License:     GPLv2 or later
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

	// Admin-only common files
	if ( is_admin() ) {
		require $plugin_path . 'includes/admin.php';
		require $plugin_path . 'includes/ajax.php';
	}

	// Avatar files
	require $plugin_path . 'includes/capabilities.php';
	require $plugin_path . 'includes/functions.php';
	require $plugin_path . 'includes/errors.php';
	require $plugin_path . 'includes/metabox.php';
	require $plugin_path . 'includes/uninstall.php';
	require $plugin_path . 'includes/hooks.php';
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
	return 201510150002;
}

/**
 * Loads the translation file.
 *
 * @since 0.1.0
 */
function wp_user_avatars_i18n() {
	load_plugin_textdomain( 'wp-user-avatars', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
