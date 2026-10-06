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
	register_setting( 'discussion', 'wp_user_avatars_default_avatar', 'wp_user_avatars_sanitize_default_avatar' );

	// Maybe hide by default
	$args = get_option( 'show_avatars' )
		? array( 'class' => 'avatar-settings' )
		: array( 'class' => 'avatar-settings hide-if-js' );

	// Capabilities
	add_settings_field( 'wp_user_avatars_roles', esc_html__( 'Allowed Roles', 'wp-user-avatars' ), 'wp_user_avatars_settings_field_roles', 'discussion', 'avatars', $args );

	// Local only (no Gravatars)
	add_settings_field( 'wp_user_avatars_block_gravatar', esc_html__( 'Block Gravatar', 'wp-user-avatars' ), 'wp_user_avatars_settings_field_gravatar', 'discussion', 'avatars', $args );

	// Site default.
	add_settings_field( 'wp_user_avatars_default_avatar', esc_html__( 'Custom Default Avatar', 'wp-user-avatars' ), 'wp_user_avatars_settings_field_default_avatar', 'discussion', 'avatars', $args );
}

/**
 * Settings field for choosing a site-specific default avatar.
 *
 * @since 2.1.0
 *
 * @return void
 */
function wp_user_avatars_settings_field_default_avatar() {
	$avatar          = get_option( 'wp_user_avatars_default_avatar', array() );
	$stored_media_id = is_array( $avatar ) && ! empty( $avatar['media_id'] )
		? absint( $avatar['media_id'] )
		: 0;
	$is_valid        = $stored_media_id && wp_user_avatars_is_square_image( $stored_media_id );
	$media_id        = $is_valid ? $stored_media_id : 0;
	$preview         = $media_id
		? wp_get_attachment_url( $media_id )
		: false;
	?>

	<div id="wp-user-avatars-default-avatar-field">
		<input type="hidden" class="wp-user-avatars-default-avatar-id" name="wp_user_avatars_default_avatar[media_id]" value="<?php echo esc_attr( (string) $media_id ); ?>" />
		<input type="hidden" class="wp-user-avatars-default-avatar-activate" name="wp_user_avatars_default_avatar[activate]" value="0" />
		<p class="wp-user-avatars-default-avatar-preview"<?php echo $preview ? '' : ' hidden'; ?>>
			<img src="<?php echo esc_url( $preview ? $preview : '' ); ?>" alt="<?php echo esc_attr( esc_html__( 'Current custom default avatar', 'wp-user-avatars' ) ); ?>" width="96" height="96" />
		</p>
		<p>
			<button type="button" class="button wp-user-avatars-default-avatar-select hide-if-no-js"><?php esc_html_e( 'Choose image', 'wp-user-avatars' ); ?></button>
			<button type="button" class="button-link-delete wp-user-avatars-default-avatar-remove hide-if-no-js"<?php echo $media_id ? '' : ' hidden'; ?>><?php esc_html_e( 'Remove image', 'wp-user-avatars' ); ?></button>
		</p>
		<?php if ( $stored_media_id && ! $is_valid ) : ?>
			<p class="notice notice-warning inline">
				<?php esc_html_e( 'The previously selected image is unavailable or no longer square. Save changes to clear it, or choose another image.', 'wp-user-avatars' ); ?>
			</p>
		<?php endif; ?>
		<p class="description">
			<?php esc_html_e( 'Choose a square image to use when WordPress would otherwise show a default avatar. This does not assign an avatar to individual users. Enable Block Gravatar to serve the selected fallback without a Gravatar request.', 'wp-user-avatars' ); ?>
		</p>
	</div>

	<?php
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
 * Validate and activate a site-specific default avatar attachment.
 *
 * The last selection made in the picker or WordPress default list wins. Saving
 * an unchanged attachment preserves the active choice, while removing an active
 * custom attachment restores Mystery Person.
 *
 * @since 2.1.0
 *
 * @param mixed $input Submitted setting value.
 *
 * @phpstan-return array{media_id: int, url: string}|array{}
 *
 * @return array
 */
function wp_user_avatars_sanitize_default_avatar( $input ) {
	$previous     = get_option( 'wp_user_avatars_default_avatar', array() );
	$previous     = is_array( $previous ) ? $previous : array();
	$previous_id  = ! empty( $previous['media_id'] ) ? absint( $previous['media_id'] ) : 0;
	$previous_url = ! empty( $previous['url'] ) && is_string( $previous['url'] )
		? $previous['url']
		: '';
	$media_id     = is_array( $input ) && ! empty( $input['media_id'] ) ? absint( $input['media_id'] ) : 0;
	$activate     = is_array( $input ) && ! empty( $input['activate'] );
	$active       = wp_user_avatars_get_raw_avatar_default();
	$previous_now = $previous_id ? wp_get_attachment_url( $previous_id ) : '';
	$previous_now = is_string( $previous_now ) ? esc_url_raw( $previous_now ) : '';
	$slot_active  = $active && ( $active === $previous_url || $active === $previous_now );

	if ( 0 === $media_id ) {
		if ( $slot_active ) {
			wp_user_avatars_update_raw_avatar_default( 'mystery' );
		}

		return array();
	}

	if ( ! wp_user_avatars_is_square_image( $media_id ) ) {
		if ( $media_id === $previous_id ) {
			if ( $slot_active ) {
				wp_user_avatars_update_raw_avatar_default( 'mystery' );
			}

			return array();
		}

		if ( ! wp_attachment_is_image( $media_id ) ) {
			add_settings_error( 'wp_user_avatars_default_avatar', 'invalid-image', esc_html__( 'Choose a valid image attachment.', 'wp-user-avatars' ) );
			return $previous;
		}

		add_settings_error( 'wp_user_avatars_default_avatar', 'image-not-square', esc_html__( 'Choose a square image so the default avatar is not distorted.', 'wp-user-avatars' ) );
		return $previous;
	}

	$url = wp_get_attachment_url( $media_id );
	if ( ! is_string( $url ) || '' === $url ) {
		return $previous;
	}

	$url = esc_url_raw( $url );

	// Keep an active custom default synchronized after URL or scheme changes.
	if ( $activate || $slot_active ) {
		wp_user_avatars_update_raw_avatar_default( $url );
	}

	return array(
		'media_id' => $media_id,
		'url'      => $url,
	);
}

/**
 * Return the stored WordPress default without this plugin's display filter.
 *
 * @since 2.1.0
 *
 * @return string
 */
function wp_user_avatars_get_raw_avatar_default() {
	$priority = has_filter( 'option_avatar_default', 'wp_user_avatars_option_avatar_default' );
	if ( false !== $priority ) {
		remove_filter( 'option_avatar_default', 'wp_user_avatars_option_avatar_default', $priority );
	}

	$default = get_option( 'avatar_default', 'mystery' );

	if ( false !== $priority ) {
		add_filter( 'option_avatar_default', 'wp_user_avatars_option_avatar_default', $priority );
	}

	return is_string( $default ) ? $default : 'mystery';
}

/**
 * Update the stored WordPress default without the display filter hiding a
 * migrated custom URL from WordPress's unchanged-value check.
 *
 * @since 2.1.0
 *
 * @param string $avatar_default Default avatar value.
 *
 * @return void
 */
function wp_user_avatars_update_raw_avatar_default( $avatar_default ) {
	$priority = has_filter( 'option_avatar_default', 'wp_user_avatars_option_avatar_default' );
	if ( false !== $priority ) {
		remove_filter( 'option_avatar_default', 'wp_user_avatars_option_avatar_default', $priority );
	}

	update_option( 'avatar_default', $avatar_default );

	if ( false !== $priority ) {
		add_filter( 'option_avatar_default', 'wp_user_avatars_option_avatar_default', $priority );
	}
}

/**
 * Load the default-avatar Media Library picker on Discussion settings.
 *
 * @since 2.1.0
 *
 * @param string $hook_suffix Current admin screen suffix.
 *
 * @return void
 */
function wp_user_avatars_settings_enqueue_scripts( $hook_suffix ) {
	if ( 'options-discussion.php' !== $hook_suffix || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wp_enqueue_media();

	$url = wp_user_avatars_get_plugin_url();
	$ver = wp_user_avatars_get_asset_version();

	wp_enqueue_script( 'wp-user-avatars-default-avatar', $url . 'assets/js/default-avatar.js', array( 'jquery' ), $ver, true );
	wp_localize_script(
		'wp-user-avatars-default-avatar',
		'i10n_WPUserAvatarsDefault',
		array(
			'chooseTitle'  => esc_html__( 'Choose a Default Avatar', 'wp-user-avatars' ),
			'chooseButton' => esc_html__( 'Use as default avatar', 'wp-user-avatars' ),
			'squareImage'  => esc_html__( 'Choose a square image so the default avatar is not distorted.', 'wp-user-avatars' ),
			'customUrl'    => wp_user_avatars_get_default_avatar_url(),
		)
	);
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

	wp_user_avatars_enqueue_styles();

	// URL & Version
	$url = wp_user_avatars_get_plugin_url();
	$ver = wp_user_avatars_get_asset_version();

	// Enqueue
	wp_enqueue_script( 'wp-user-avatars', $url . 'assets/js/user-avatars.js',   array( 'jquery' ), $ver, true  );

	// Localize
	wp_localize_script( 'wp-user-avatars', 'i10n_WPUserAvatars', array(
		'insertMediaTitle' => esc_html__( 'Choose an Avatar', 'wp-user-avatars' ),
		'insertIntoPost'   => esc_html__( 'Set as avatar',    'wp-user-avatars' ),
		'deleteNonce'      => wp_create_nonce( 'remove_wp_user_avatars_nonce' ),
		'mediaNonce'       => wp_create_nonce( 'assign_wp_user_avatars_nonce' ),
		'uploadNonce'      => wp_create_nonce( 'upload_wp_user_avatars_nonce' ),
		'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
		'mediaError'       => esc_html__( 'The avatar could not be selected. Please try again.', 'wp-user-avatars' ),
		'removeError'      => esc_html__( 'The avatar could not be removed. Please try again.', 'wp-user-avatars' ),
		'uploadError'      => esc_html__( 'The avatar could not be uploaded. Please try again.', 'wp-user-avatars' ),
		'user_id'          => $user_id,
	) );
}

/**
 * Return a unique suffix for one avatar editor instance.
 *
 * The admin and front-end renderers share this counter because both can appear
 * on the same request when another plugin embeds the current-user editor.
 *
 * @since 2.1.0
 *
 * @return string Empty for the first editor, or a numbered suffix.
 */
function wp_user_avatars_get_editor_id_suffix() {
	static $instance = 0;
	++$instance;

	return 1 === $instance ? '' : '-' . $instance;
}

/**
 * Enqueue the shared avatar editor styles.
 *
 * @since 2.1.0
 *
 * @return void
 */
function wp_user_avatars_enqueue_styles() {
	$url = wp_user_avatars_get_plugin_url();
	$ver = wp_user_avatars_get_asset_version();

	wp_enqueue_style( 'wp-user-avatars',  $url . 'assets/css/user-avatars.css', array(),           $ver );
	if ( is_rtl() ) {
		wp_enqueue_style( 'wp-user-avatars-rtl', $url . 'assets/css/user-avatars-rtl.css', array( 'wp-user-avatars' ), $ver );
	}
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
 * @param WP_User|null $user User object.
 *
 * @return void
 */
function wp_user_avatars_section_content( $user = null ) {

	// Bail if no user
	if ( empty( $user->ID ) ) {
		return;
	}

	$suffix      = wp_user_avatars_get_editor_id_suffix();
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

					</div>

					<?php wp_nonce_field( 'wp_user_avatars_nonce', '_wp_user_avatars_nonce', false ); ?>
					<p id="<?php echo esc_attr( $feedback_id ); ?>" class="description wp-user-avatars-feedback" aria-live="polite"></p>

				</td>
			</tr>

		<?php endif; ?>

		<?php

		// User needs additional caps to edit ratings
		if ( current_user_can( 'edit_avatar_rating', $user->ID ) ) : ?>

			<tr class="wp-user-avatars-rating-row">
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
