<?php

/**
 * Front-end avatar editor.
 *
 * @since 2.1.0
 *
 * @package Plugins/Users/Avatars/Frontend
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Render the current user's avatar editor shortcode.
 *
 * @since 2.1.0
 *
 * @return string Editor markup.
 */
function wp_user_avatars_shortcode() {
	return wp_user_avatars_get_editor();
}

/**
 * Return a reusable avatar editor for the current user.
 *
 * @since 2.1.0
 *
 * @return string Editor markup.
 */
function wp_user_avatars_get_editor() {
	if ( ! is_user_logged_in() ) {
		return '<p class="wp-user-avatars-login-required">' . esc_html__( 'You must log in to edit your avatar.', 'wp-user-avatars' ) . '</p>';
	}

	$user = get_userdata( get_current_user_id() );

	if ( ! $user instanceof WP_User || empty( $user->ID ) ) {
		return '';
	}

	if ( ! current_user_can( 'edit_avatar', $user->ID ) && ! current_user_can( 'edit_avatar_rating', $user->ID ) ) {
		return '<p class="wp-user-avatars-forbidden">' . esc_html__( 'You do not have permission to edit this avatar.', 'wp-user-avatars' ) . '</p>';
	}

	wp_user_avatars_enqueue_assets( $user->ID );

	$error = $GLOBALS['wp_user_avatars_frontend_error'] ?? null;
	unset( $GLOBALS['wp_user_avatars_frontend_error'] );

	ob_start();
	?>
	<form class="wp-user-avatars-frontend-form" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field( 'wp_user_avatars_frontend_nonce', '_wp_user_avatars_frontend_nonce', false ); ?>
		<?php if ( is_wp_error( $error ) ) : ?>
			<p class="wp-user-avatars-notice wp-user-avatars-notice-error" role="alert">
				<?php echo esc_html( $error->get_error_message() ); ?>
			</p>
		<?php endif; ?>
		<?php wp_user_avatars_section_content( $user, array( 'frontend' => true ) ); ?>
		<p class="wp-user-avatars-submit">
			<button type="submit" name="wp_user_avatars_frontend_action" value="update" class="button button-primary">
				<?php esc_html_e( 'Save avatar', 'wp-user-avatars' ); ?>
			</button>
		</p>
	</form>
	<?php
	return (string) ob_get_clean();
}

/**
 * Enqueue front-end assets before page output when the page contains the shortcode.
 *
 * The shortcode callback also enqueues as a fallback for programmatic rendering.
 *
 * @since 2.1.0
 *
 * @return void
 */
function wp_user_avatars_frontend_enqueue_assets() {
	global $post;

	if ( ! $post instanceof WP_Post || empty( $post->post_content ) || ! has_shortcode( $post->post_content, 'wp_user_avatars' ) || ! is_user_logged_in() ) {
		return;
	}

	$user_id = get_current_user_id();
	if ( ! current_user_can( 'edit_avatar', $user_id ) && ! current_user_can( 'edit_avatar_rating', $user_id ) ) {
		return;
	}

	wp_user_avatars_enqueue_assets( $user_id );
}

/**
 * Process the non-JavaScript front-end editor form.
 *
 * @since 2.1.0
 *
 * @return bool|WP_Error True on success, false when no form was submitted, or an error.
 */
function wp_user_avatars_process_frontend_form() {
	if ( empty( $_POST['wp_user_avatars_frontend_action'] ) || ! is_string( $_POST['wp_user_avatars_frontend_action'] ) ) {
		return false;
	}

	if ( ! is_user_logged_in() ) {
		return new WP_Error( 'logged_out', esc_html__( 'You must log in to edit your avatar.', 'wp-user-avatars' ) );
	}

	if ( empty( $_POST['_wp_user_avatars_frontend_nonce'] ) || ! is_string( $_POST['_wp_user_avatars_frontend_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wp_user_avatars_frontend_nonce'] ) ), 'wp_user_avatars_frontend_nonce' ) ) {
		return new WP_Error( 'invalid_nonce', esc_html__( 'The avatar request could not be verified.', 'wp-user-avatars' ) );
	}

	$user_id = get_current_user_id();
	$action  = sanitize_key( wp_unslash( $_POST['wp_user_avatars_frontend_action'] ) );

	if ( 'remove' === $action ) {
		if ( ! current_user_can( 'remove_avatar', $user_id ) ) {
			return new WP_Error( 'forbidden', esc_html__( 'You do not have permission to edit this avatar.', 'wp-user-avatars' ) );
		}

		wp_user_avatars_delete_avatar( $user_id );
		return true;
	}

	if ( 'update' !== $action ) {
		return new WP_Error( 'invalid_action', esc_html__( 'The avatar request was not recognized.', 'wp-user-avatars' ) );
	}

	$avatar_updated = false;
	$avatar_file    = isset( $_FILES['wp-user-avatars'] ) && is_array( $_FILES['wp-user-avatars'] )
		? $_FILES['wp-user-avatars']
		: array();
	if ( ! empty( $avatar_file['name'] ) && is_string( $avatar_file['name'] ) ) {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps().
		if ( ! current_user_can( 'upload_avatar', $user_id ) ) {
			return new WP_Error( 'forbidden', esc_html__( 'You do not have permission to edit this avatar.', 'wp-user-avatars' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The upload API requires the original file array and validates it against the avatar MIME allowlist.
		$avatar = wp_user_avatars_handle_upload( $user_id, $avatar_file );
		if ( is_wp_error( $avatar ) ) {
			return $avatar;
		}

		$avatar_updated = true;
	}

	if ( current_user_can( 'edit_avatar_rating', $user_id ) && ( $avatar_updated || get_user_meta( $user_id, 'wp_user_avatars', true ) ) ) {
		$rating = isset( $_POST['wp_user_avatars_rating'] ) && is_string( $_POST['wp_user_avatars_rating'] )
			? sanitize_text_field( wp_unslash( $_POST['wp_user_avatars_rating'] ) )
			: '';
		wp_user_avatars_update_rating( $user_id, $rating );
	}

	return true;
}

/**
 * Process front-end submissions before template output begins.
 *
 * @since 2.1.0
 *
 * @return void
 */
function wp_user_avatars_frontend_form_handler() {
	$result = wp_user_avatars_process_frontend_form();
	if ( false === $result ) {
		return;
	}

	if ( is_wp_error( $result ) ) {
		$GLOBALS['wp_user_avatars_frontend_error'] = $result;
		return;
	}

	$redirect = wp_get_referer();
	if ( empty( $redirect ) ) {
		$redirect = home_url( '/' );
	}

	wp_safe_redirect( $redirect );
	exit;
}
