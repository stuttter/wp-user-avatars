<?php

/**
 * User Avatar Ajax
 *
 * @since 0.1.0
 *
 * @package Plugins/Users/Avatar/Ajax
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Runs when a user clicks the Remove button for the avatar
 *
 * @since 0.1.0
 */
function wp_user_avatars_action_remove_avatars() {

	// Bail if not our request
	if ( empty( $_GET['user_id'] ) || empty( $_GET['_wpnonce'] ) ) {
		return;
	}

	// Bail if nonce verification fails
	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'remove_wp_user_avatars_nonce' ) ) {
		return;
	}

	// Cast values
	$user_id = (int) $_GET['user_id'];

	// Bail if user cannot be edited
	if ( ! current_user_can( 'edit_avatar', $user_id ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this user.', 'wp-user-avatars' ) );
	}

	// Delete the avatar
	wp_user_avatars_delete_avatar( $user_id );

	// Output the default avatar
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		echo get_user_avatar( $user_id, 90 );
		die();
	}
}

/**
 * AJAX callback for setting media ID as user avatar
 *
 * @since 0.1.0
 */
function wp_user_avatars_ajax_assign_media() {

	// check required information and permissions
	if ( empty( $_POST['user_id'] ) || empty( $_POST['media_id'] ) || empty( $_POST['_wpnonce'] ) ) {
		die();
	}

	// Cast values
	$media_id = (int) $_POST['media_id'];
	$user_id  = (int) $_POST['user_id'];

	// Bail if current user cannot proceed
	if ( ! current_user_can( 'edit_avatar', $user_id ) ) {
		die();
	}

	// Bail if nonce verification fails
	if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'assign_wp_user_avatars_nonce' ) ) {
		die();
	}

	// ensure the media is real is an image
	if ( wp_attachment_is_image( $media_id ) ) {
		wp_user_avatars_update_avatar( $user_id, $media_id );
	}

	// Output the new avatar
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		echo get_user_avatar( $user_id, 90 );
		die();
	}
}
