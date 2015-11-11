<?php

/**
 * User Avatar Errors
 *
 * @since 0.1.0
 *
 * @package Plugins/Users/Avatars/Errors
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Adds errors based on avatar upload problems.
 *
 * @since 0.1.0
 *
 * @param WP_Error $errors Error messages for user profile screen.
 */
function wp_user_avatars_file_extension_error( WP_Error $errors ) {
	$errors->add( 'avatar_error', esc_html__( 'This image file appears to be invalid, or violates an upload rule.', 'wp-user-avatars' ) );
}

/**
 * Adds errors based on avatar upload problems.
 *
 * @since 0.1.0
 *
 * @param WP_Error $errors Error messages for user profile screen.
 */
function wp_user_avatars_generic_error( WP_Error $errors ) {
	$errors->add( 'avatar_error', esc_html__( 'Avatar upload failed.', 'wp-user-avatars' ) );
}
