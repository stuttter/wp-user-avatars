<?php
/**
 * Front-end avatar editor.
 *
 * @since 2.1.0
 *
 * @package Plugins/Users/Avatars/Frontend
 */

// Exit if accessed directly.
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
 * Register the dynamic avatar editor block.
 *
 * @since 2.1.0
 *
 * @return void
 */
function wp_user_avatars_register_block() {
	register_block_type(
		dirname( __DIR__ ) . '/blocks/avatar-editor',
		array(
			'render_callback' => 'wp_user_avatars_render_block',
		)
	);
}

/**
 * Render the current user's avatar editor block.
 *
 * @since 2.1.0
 *
 * @param array<string, mixed> $attributes Block attributes.
 *
 * @return string Block markup.
 */
function wp_user_avatars_render_block( $attributes = array() ) {
	$editor = wp_user_avatars_get_editor();

	if ( '' === $editor ) {
		return '';
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'wp-user-avatars-avatar-editor-block',
		)
	);

	$heading     = isset( $attributes['heading'] ) && is_string( $attributes['heading'] )
		? trim( wp_strip_all_tags( $attributes['heading'], true ) )
		: '';
	$description = isset( $attributes['description'] ) && is_string( $attributes['description'] )
		? trim( wp_strip_all_tags( $attributes['description'], true ) )
		: '';
	$copy        = '';

	if ( '' !== $heading ) {
		$copy .= '<h2 class="wp-user-avatars-block-heading">' . esc_html( $heading ) . '</h2>';
	}

	if ( '' !== $description ) {
		$copy .= '<p class="wp-user-avatars-block-description">' . esc_html( $description ) . '</p>';
	}

	return '<div ' . $wrapper_attributes . '>' . $copy . $editor . '</div>';
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

	// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capabilities mapped by wp_user_avatars_meta_caps().
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
		<?php wp_user_avatars_frontend_editor_content( $user ); ?>
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
 * Render semantic front-end avatar controls for one user.
 *
 * IDs remain unique for pages containing multiple shortcode or block instances,
 * while the shared classes preserve the existing JavaScript contract.
 *
 * @since 2.1.0
 *
 * @param WP_User $user User whose avatar is being edited.
 *
 * @return void
 */
function wp_user_avatars_frontend_editor_content( WP_User $user ) {
	static $instance = 0;
	++$instance;

	$suffix      = 1 === $instance ? '' : '-' . $instance;
	$file_id     = 'wp-user-avatars' . $suffix;
	$photo_id    = 'wp-user-avatars-photo' . $suffix;
	$actions_id  = 'wp-user-avatars-actions' . $suffix;
	$media_id    = 'wp-user-avatars-media' . $suffix;
	$remove_id   = 'wp-user-avatars-remove' . $suffix;
	$ratings_id  = 'wp-user-avatars-ratings' . $suffix;
	$feedback_id = 'wp-user-avatars-feedback' . $suffix;
	$has_avatar  = ! empty( $user->wp_user_avatars );
	?>

	<div
		class="wp-user-avatars-editor"
		data-user-id="<?php echo esc_attr( (string) $user->ID ); ?>"
		data-upload-nonce="<?php echo esc_attr( wp_create_nonce( 'upload_wp_user_avatars_nonce' ) ); ?>"
		data-media-nonce="<?php echo esc_attr( wp_create_nonce( 'assign_wp_user_avatars_nonce' ) ); ?>"
		data-delete-nonce="<?php echo esc_attr( wp_create_nonce( 'remove_wp_user_avatars_nonce' ) ); ?>"
	>
		<?php // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps(). ?>
		<?php if ( current_user_can( 'edit_avatar', $user->ID ) ) : ?>
			<div class="wp-user-avatars-upload-group">
				<?php // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps(). ?>
				<?php if ( current_user_can( 'upload_avatar', $user->ID ) ) : ?>
					<label class="wp-user-avatars-field-label" for="<?php echo esc_attr( $file_id ); ?>">
						<?php esc_html_e( 'Upload', 'wp-user-avatars' ); ?>
					</label>
				<?php else : ?>
					<p class="wp-user-avatars-field-label"><?php esc_html_e( 'Avatar', 'wp-user-avatars' ); ?></p>
				<?php endif; ?>
				<div class="wp-user-avatars-upload-layout">
					<div id="<?php echo esc_attr( $photo_id ); ?>" class="wp-user-avatars-photo">
						<?php echo wp_kses_post( wp_user_avatars_get_avatar_preview( $user->ID, 250 ) ); ?>
					</div>
					<div id="<?php echo esc_attr( $actions_id ); ?>" class="wp-user-avatars-actions">
						<?php // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps(). ?>
						<?php if ( current_user_can( 'upload_avatar', $user->ID ) ) : ?>
							<input type="file" name="wp-user-avatars" id="<?php echo esc_attr( $file_id ); ?>" class="standard-text wp-user-avatars-upload" accept="image/jpeg,image/gif,image/png,image/webp" />
						<?php endif; ?>

						<div class="wp-user-avatars-action-buttons">
							<?php // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps(). ?>
							<?php if ( current_user_can( 'select_avatar', $user->ID ) ) : ?>
								<button type="button" class="button hide-if-no-js wp-user-avatars-media" id="<?php echo esc_attr( $media_id ); ?>">
									<?php esc_html_e( 'Choose from Media', 'wp-user-avatars' ); ?>
								</button>
							<?php endif; ?>

							<?php // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps(). ?>
							<?php if ( current_user_can( 'remove_avatar', $user->ID ) ) : ?>
								<button
									type="submit"
									name="wp_user_avatars_frontend_action"
									value="remove"
									class="button item-delete submitdelete deletion wp-user-avatars-remove"
									id="<?php echo esc_attr( $remove_id ); ?>"
									<?php if ( ! $has_avatar ) : ?>
										style="display:none;"
									<?php endif; ?>
								>
									<?php esc_html_e( 'Remove', 'wp-user-avatars' ); ?>
								</button>
							<?php endif; ?>
						</div>

						<?php wp_nonce_field( 'wp_user_avatars_nonce', '_wp_user_avatars_nonce', false ); ?>
						<p id="<?php echo esc_attr( $feedback_id ); ?>" class="description wp-user-avatars-feedback" aria-live="polite"></p>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php // phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps(). ?>
		<?php if ( current_user_can( 'edit_avatar_rating', $user->ID ) ) : ?>
			<?php
			if ( empty( $user->wp_user_avatars_rating ) || ! array_key_exists( $user->wp_user_avatars_rating, wp_user_avatars_get_ratings() ) ) {
				$user->wp_user_avatars_rating = 'G';
			}
			?>
			<div class="wp-user-avatars-rating-row<?php echo $has_avatar ? '' : ' fancy-hidden'; ?>">
				<div id="<?php echo esc_attr( $ratings_id ); ?>" class="wp-user-avatars-ratings<?php echo $has_avatar ? '' : ' fancy-hidden'; ?>">
					<fieldset <?php disabled( ! $has_avatar ); ?>>
						<legend><?php esc_html_e( 'Rating', 'wp-user-avatars' ); ?></legend>
						<div class="wp-user-avatars-rating-options">
							<?php wp_user_avatars_user_rating_form_field( $user ); ?>
						</div>
					</fieldset>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<?php
}

/**
 * Enqueue front-end assets before page output.
 *
 * Styles load for eligible signed-in users so the documented PHP renderer also
 * remains styled when called after wp_head. Stored blocks retain their prompt
 * styling for visitors who cannot use the editor. Scripts and Media Library
 * assets remain limited to eligible users on pages whose stored content
 * contains the shortcode or block. The renderer enqueues those assets as a
 * fallback for programmatic rendering.
 *
 * @since 2.1.0
 *
 * @return void
 */
function wp_user_avatars_frontend_enqueue_assets() {
	global $post;

	$has_block = $post instanceof WP_Post
		&& ! empty( $post->post_content )
		&& has_block( 'wp-user-avatars/avatar-editor', $post );

	if ( $has_block ) {
		wp_user_avatars_enqueue_styles();
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	$user_id = get_current_user_id();
	// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capabilities mapped by wp_user_avatars_meta_caps().
	if ( ! current_user_can( 'edit_avatar', $user_id ) && ! current_user_can( 'edit_avatar_rating', $user_id ) ) {
		return;
	}

	wp_user_avatars_enqueue_styles();

	if ( ! $post instanceof WP_Post || empty( $post->post_content ) ) {
		return;
	}

	$has_shortcode = has_shortcode( $post->post_content, 'wp_user_avatars' );

	if ( ! $has_shortcode && ! $has_block ) {
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
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps().
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
	$avatar_file    = array();
	if ( isset( $_FILES['wp-user-avatars'] ) && is_array( $_FILES['wp-user-avatars'] ) ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The upload API requires the original file array and validates it against the avatar MIME allowlist.
		$avatar_file = $_FILES['wp-user-avatars'];
	}
	if ( ! empty( $avatar_file['name'] ) && is_string( $avatar_file['name'] ) ) {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps().
		if ( ! current_user_can( 'upload_avatar', $user_id ) ) {
			return new WP_Error( 'forbidden', esc_html__( 'You do not have permission to edit this avatar.', 'wp-user-avatars' ) );
		}

		$avatar = wp_user_avatars_handle_upload( $user_id, $avatar_file );
		if ( is_wp_error( $avatar ) ) {
			return $avatar;
		}

		$avatar_updated = true;
	}

	// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps().
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

	wp_safe_redirect( wp_user_avatars_get_frontend_redirect() );
	exit;
}

/**
 * Return the safe destination for a successful front-end editor submission.
 *
 * The form posts back to the page that contains it. wp_get_referer() rejects a
 * referer matching the current request, so use the raw value before validating
 * it against the site URL.
 *
 * @since 2.1.0
 *
 * @return string Redirect URL.
 */
function wp_user_avatars_get_frontend_redirect() {
	$fallback = home_url( '/' );
	$referer  = wp_get_raw_referer();

	return wp_validate_redirect( $referer ? $referer : '', $fallback );
}
