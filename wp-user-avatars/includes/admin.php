<?php

/**
 * User Avatar Admin
 *
 * @since 0.1.0
 *
 * @package Plugins/Users/Avatar/Admin
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register avatar settings
 *
 * @since 0.1.0
 *
 * @return void
 */
function wp_user_avatars_register_settings() {

	// Register the settings
	register_setting( 'discussion', 'wp_user_avatars_roles',          'wp_user_avatars_sanitize_roles'          );
	register_setting( 'discussion', 'wp_user_avatars_block_gravatar', 'wp_user_avatars_sanitize_block_gravatar' );

	// Maybe hide by default
	$args = get_option( 'show_avatars' )
		? array( 'class' => 'avatar-settings' )
		: array( 'class' => 'avatar-settings hide-if-js' );

	// Capabilities
	add_settings_field( 'wp_user_avatars_roles', esc_html__( 'Allowed Roles', 'wp-user-avatars' ), 'wp_user_avatars_settings_field_roles', 'discussion', 'avatars', $args );

	// Local only (no Gravatars)
	add_settings_field( 'wp_user_avatars_block_gravatar', esc_html__( 'Block Gravatar', 'wp-user-avatars' ), 'wp_user_avatars_settings_field_gravatar', 'discussion', 'avatars', $args );
}

/**
 * Settings field for preventing requests to Gravatar
 *
 * @since 0.1.0
 *
 * @return void
 */
function wp_user_avatars_settings_field_gravatar() {

	// Get roles
	$val = (bool) get_option( 'wp_user_avatars_block_gravatar', false ); ?>

	<label>
		<input type="checkbox" name="wp_user_avatars_block_gravatar" id="wp_user_avatars_block_gravatar" value="1" <?php checked( $val ); ?> />
		<?php esc_html_e( 'Prevent avatar requests from reaching out to Gravatar.com.', 'wp-user-avatars' ); ?>
	</label>

<?php
}

/**
 * Settings field for cherry-picking which roles are allowed to upload avatars
 *
 * @since 0.1.0
 *
 * @return void
 */
function wp_user_avatars_settings_field_roles() {

	// Get roles
	$roles = get_editable_roles();
	$val   = get_option( 'wp_user_avatars_roles', array_keys( $roles ) ); ?>

	<fieldset>
		<legend class="screen-reader-text"><?php esc_html_e( 'Upload Options', 'wp-user-avatars' ); ?></legend>

		<?php foreach ( $roles as $role_id => $role ) : ?>

		<label>
			<input type="checkbox" name="wp_user_avatars_roles[]" value="<?php echo esc_attr( $role_id ); ?>" <?php checked( in_array( $role_id, $val ) ); ?> />
			<?php echo esc_html( translate_user_role( $role['name'] ) ); ?>
		</label>
		<br>

		<?php endforeach; ?>

	</fieldset>

<?php
}

/**
 * Sanitize new settings field before saving
 *
 * @since 0.1.0
 *
 * @param  array<int, string> $input Passed input values to sanitize
 *
 * @return array<int, string> Sanitized input fields
 */
function wp_user_avatars_sanitize_roles( $input ) {
	$roles = array_keys( get_editable_roles() );
	return array_intersect( $input, $roles );
}

/**
 * Sanitize new settings field before saving
 *
 * @since 0.1.0
 *
 * @param  mixed $input Passed input value to sanitize
 *
 * @return bool Sanitized input field
 */
function wp_user_avatars_sanitize_block_gravatar( $input ) {
	return (bool) $input;
}

/**
 * Add scripts to the profile editing page
 *
 * @since 0.1.0
 *
 * @return void
 */
function wp_user_avatars_admin_enqueue_scripts() {

	// Bail if not editing a user
	$is_bbpress_edit = function_exists( 'bbp_is_single_user_edit' ) && bbp_is_single_user_edit();
	if ( ! defined( 'IS_PROFILE_PAGE' ) && ! $is_bbpress_edit ) {
		return;
	}

	// User ID
	if ( $is_bbpress_edit && function_exists( 'bbp_get_displayed_user_id' ) ) {
		$user_id = (int) bbp_get_displayed_user_id();
	} else {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only screen context; no state is changed from this value.
		$user_id = ! empty( $_GET['user_id'] )
			? (int) $_GET['user_id']
			: get_current_user_id();
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	wp_user_avatars_enqueue_assets( $user_id );
}

/**
 * Enqueue the avatar editor assets for one user.
 *
 * @since 2.1.0
 *
 * @param int $user_id User whose avatar is being edited.
 *
 * @return void
 */
function wp_user_avatars_enqueue_assets( $user_id ) {
	// Only users with Media Library access should load or browse it.
	// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps().
	if ( current_user_can( 'select_avatar', $user_id ) ) {
		wp_enqueue_media();
	}

	// URL & Version
	$url = wp_user_avatars_get_plugin_url();
	$ver = wp_user_avatars_get_asset_version();

	// Enqueue
	wp_enqueue_script( 'wp-user-avatars', $url . 'assets/js/user-avatars.js',   array( 'jquery' ), $ver, true  );
	wp_enqueue_style( 'wp-user-avatars',  $url . 'assets/css/user-avatars.css', array(),           $ver );
	if ( is_rtl() ) {
		wp_enqueue_style( 'wp-user-avatars-rtl', $url . 'assets/css/user-avatars-rtl.css', array( 'wp-user-avatars' ), $ver );
	}

	// Localize
	wp_localize_script( 'wp-user-avatars', 'i10n_WPUserAvatars', array(
		'insertMediaTitle' => esc_html__( 'Choose an Avatar', 'wp-user-avatars' ),
		'insertIntoPost'   => esc_html__( 'Set as avatar',    'wp-user-avatars' ),
		'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
		'mediaError'       => esc_html__( 'The avatar could not be selected. Please try again.', 'wp-user-avatars' ),
		'removeError'      => esc_html__( 'The avatar could not be removed. Please try again.', 'wp-user-avatars' ),
		'uploadError'      => esc_html__( 'The avatar could not be uploaded. Please try again.', 'wp-user-avatars' ),
	) );
}

/**
 * Output avatar field on edit/profile screens
 *
 * @since 0.1.0
 *
 * @param WP_User|int $user User object
 *
 * @return void
 */
function wp_user_avatars_edit_user_profile( $user = 0 ) {

	if ( ! $user instanceof WP_User ) {
		return;
	}

	// Bail if current user cannot edit this user's avatar and rating
	if ( ! current_user_can( 'edit_avatar', $user->ID ) && ! current_user_can( 'edit_avatar_rating', $user->ID ) ) {
		return;
	} ?>

	<div id="wp-user-avatars-user-settings">
		<h2><?php esc_html_e( 'Avatar','wp-user-avatars' ); ?></h2>

		<?php wp_user_avatars_section_content( $user ); ?>

	</div>

	<?php
}

/**
 * Output the HTML used for the metabox and settings section
 *
 * @since 0.1.0
 *
 * @param WP_User|null        $user User object.
 * @param array<string, bool> $args Rendering options.
 *
 * @return void
 */
function wp_user_avatars_section_content( $user = null, $args = array() ) {

	// Bail if no user
	if ( empty( $user->ID ) ) {
		return;
	}

	static $instance = 0;
	++$instance;

	$is_frontend = ! empty( $args['frontend'] );
	$suffix      = 1 === $instance ? '' : '-' . $instance;
	$file_id     = 'wp-user-avatars' . $suffix;
	$photo_id    = 'wp-user-avatars-photo' . $suffix;
	$actions_id  = 'wp-user-avatars-actions' . $suffix;
	$media_id    = 'wp-user-avatars-media' . $suffix;
	$remove_id   = 'wp-user-avatars-remove' . $suffix;
	$ratings_id  = 'wp-user-avatars-ratings' . $suffix;
	$feedback_id = 'wp-user-avatars-feedback' . $suffix;
	?>

	<div
		class="wp-user-avatars-editor"
		data-user-id="<?php echo esc_attr( (string) $user->ID ); ?>"
		data-upload-nonce="<?php echo esc_attr( wp_create_nonce( 'upload_wp_user_avatars_nonce' ) ); ?>"
		data-media-nonce="<?php echo esc_attr( wp_create_nonce( 'assign_wp_user_avatars_nonce' ) ); ?>"
		data-delete-nonce="<?php echo esc_attr( wp_create_nonce( 'remove_wp_user_avatars_nonce' ) ); ?>"
	>

	<table class="form-table">

		<?php

		// User needs caps to edit avatar
		if ( current_user_can( 'edit_avatar', $user->ID ) ) : ?>

			<tr>
				<th scope="row"><label for="<?php echo esc_attr( $file_id ); ?>"><?php esc_html_e( 'Upload', 'wp-user-avatars' ); ?></label></th>
				<td id="<?php echo esc_attr( $photo_id ); ?>" class="wp-user-avatars-photo"><?php
					echo wp_kses_post( wp_user_avatars_get_avatar_preview( $user->ID, 250 ) );
				?></td>
				<td id="<?php echo esc_attr( $actions_id ); ?>" class="wp-user-avatars-actions"><?php

				// User needs additional caps to upload avatars
				if ( current_user_can( 'upload_avatar', $user->ID ) ) : ?>

						<div>
							<input type="file" name="wp-user-avatars" id="<?php echo esc_attr( $file_id ); ?>" class="standard-text wp-user-avatars-upload" accept="image/jpeg,image/gif,image/png,image/webp" />
						</div>

					<?php endif; ?>

					<div>

						<?php

						// Only expose the Media Library to users who can browse it
						// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps().
						if ( current_user_can( 'select_avatar', $user->ID ) ) : ?>

							<button type="button" class="button hide-if-no-js wp-user-avatars-media" id="<?php echo esc_attr( $media_id ); ?>">
								<?php esc_html_e( 'Choose from Media', 'wp-user-avatars' ); ?>
							</button> &nbsp;

						<?php endif; ?>

						<?php

						// User needs additional caps to remove existing avatar
						if ( current_user_can( 'remove_avatar', $user->ID ) ) : ?>

							<?php if ( $is_frontend ) : ?>

								<button type="submit" name="wp_user_avatars_frontend_action" value="remove" class="button item-delete submitdelete deletion wp-user-avatars-remove" id="<?php echo esc_attr( $remove_id ); ?>"
									<?php if ( empty( $user->wp_user_avatars ) ) : ?>
										style="display:none;"
									<?php endif; ?>
								>
									<?php esc_html_e( 'Remove', 'wp-user-avatars' ); ?>
								</button>

							<?php else : ?>

								<?php $remove_url = add_query_arg( array(
									'action'   => 'remove-wp-user-avatars',
									'user_id'  => $user->ID,
									'_wpnonce' => false,
								) ); ?>

								<a href="<?php echo esc_url( $remove_url ); ?>" class="button item-delete submitdelete deletion wp-user-avatars-remove" id="<?php echo esc_attr( $remove_id ); ?>"
									<?php if ( empty( $user->wp_user_avatars ) ) : ?>
										style="display:none;"
									<?php endif; ?>
								>
									<?php esc_html_e( 'Remove', 'wp-user-avatars' ); ?>
								</a>

							<?php endif; ?>

						<?php endif; ?>

					</div>

					<?php wp_nonce_field( 'wp_user_avatars_nonce', '_wp_user_avatars_nonce', false ); ?>
					<p id="<?php echo esc_attr( $feedback_id ); ?>" class="description wp-user-avatars-feedback" aria-live="polite"></p>

				</td>
			</tr>

		<?php endif; ?>

		<?php

		// User needs additional caps to edit ratings
		if ( current_user_can( 'edit_avatar_rating', $user->ID ) ) : ?>

			<tr>
				<th scope="row"><?php esc_html_e( 'Rating', 'wp-user-avatars' ); ?></th>
			<td
				id="<?php echo esc_attr( $ratings_id ); ?>"
				colspan="2"
				class="wp-user-avatars-ratings<?php echo empty( $user->wp_user_avatars ) ? ' fancy-hidden' : ''; ?>"
			>
					<fieldset <?php disabled( empty( $user->wp_user_avatars ) ); ?>>
						<legend class="screen-reader-text"><span><?php esc_html_e( 'Rating', 'wp-user-avatars' ); ?></span></legend>
						<?php

						// User rating
						if ( empty( $user->wp_user_avatars_rating ) || ! array_key_exists( $user->wp_user_avatars_rating, wp_user_avatars_get_ratings() ) ) {
							$user->wp_user_avatars_rating = 'G';
						}

						// Output the rating form field
						wp_user_avatars_user_rating_form_field( $user ); ?>

					</fieldset>
				</td>
			</tr>

		<?php endif; ?>

	</table>
	</div>

<?php
}

/**
 * Maybe remove legacy actions and rely on WP User Profiles instead.
 *
 * @since 1.1.0
 *
 * @return void
 */
function wp_user_profiles_unhook_legacy_fields() {
	if ( class_exists( 'WP_User_Profile_Section' ) ) {
		remove_action( 'show_user_profile', 'wp_user_avatars_edit_user_profile' );
		remove_action( 'edit_user_profile', 'wp_user_avatars_edit_user_profile' );
	}
}
