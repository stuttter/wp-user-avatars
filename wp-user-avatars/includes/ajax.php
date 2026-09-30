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
 *
 * @return void
 */
function wp_user_avatars_action_remove_avatars() {

	// Bail if not our request
	if ( empty( $_GET['user_id'] ) || empty( $_GET['_wpnonce'] ) ) {
		return;
	}

	// Bail if nonce verification fails
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'remove_wp_user_avatars_nonce' ) ) {
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
		echo wp_kses_post( wp_user_avatars_get_avatar_preview( $user_id, 90 ) );
		die();
	}
}

/**
 * AJAX callback for setting media ID as user avatar
 *
 * @since 0.1.0
 *
 * @return void
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
	if ( ! current_user_can( 'select_avatar', $user_id ) ) {
		die();
	}

	// Match Media Library visibility and prevent assigning an unreadable attachment.
	if ( ! current_user_can( 'edit_post', $media_id ) ) {
		die();
	}

	// Bail if nonce verification fails
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'assign_wp_user_avatars_nonce' ) ) {
		die();
	}

	// ensure the media is real is an image
	if ( wp_attachment_is_image( $media_id ) ) {
		wp_user_avatars_update_avatar( $user_id, $media_id );
	}

	// Output the new avatar
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		echo wp_kses_post( wp_user_avatars_get_avatar_preview( $user_id, 90 ) );
		die();
	}
}

/**
 * AJAX callback for uploading and assigning an avatar.
 *
 * @since 2.0.0
 *
 * @return void
 */
function wp_user_avatars_ajax_upload() {
	if ( empty( $_POST['user_id'] ) || empty( $_POST['_wpnonce'] ) || empty( $_FILES['wp-user-avatars'] ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'The upload request was incomplete.', 'wp-user-avatars' ) ), 400 );
	}

	$user_id = (int) $_POST['user_id'];

	if ( ! current_user_can( 'upload_avatar', $user_id ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to edit this user.', 'wp-user-avatars' ) ), 403 );
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'upload_wp_user_avatars_nonce' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'The upload request could not be verified.', 'wp-user-avatars' ) ), 403 );
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The upload API requires the original file array and validates it against the avatar MIME allowlist.
	$avatar = wp_user_avatars_handle_upload( $user_id, $_FILES['wp-user-avatars'] );
	if ( is_wp_error( $avatar ) ) {
		wp_send_json_error( array( 'message' => $avatar->get_error_message() ), 400 );
	}

	$rating = isset( $_POST['rating'] ) ? sanitize_key( wp_unslash( $_POST['rating'] ) ) : '';
	wp_user_avatars_update_rating( $user_id, $rating );

	wp_send_json_success( array(
		'avatar' => wp_user_avatars_get_avatar_preview( $user_id, 90 ),
	) );
}
