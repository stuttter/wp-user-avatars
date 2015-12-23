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
 */
function wp_user_avatars_register_settings() {

	// Register the settings
	register_setting( 'discussion', 'wp_user_avatars_roles',          'wp_user_avatars_sanitize_roles'          );
	register_setting( 'discussion', 'wp_user_avatars_block_gravatar', 'wp_user_avatars_sanitize_block_gravatar' );

	// Capabilities
	add_settings_field( 'wp_user_avatars_roles', esc_html__( 'Allowed Roles', 'wp-user-avatars' ), 'wp_user_avatars_settings_field_roles', 'discussion', 'avatars' );

	// Local only (no Gravatars)
	add_settings_field( 'wp_user_avatars_block_gravatar', esc_html__( 'Block Gravatar', 'wp-user-avatars' ), 'wp_user_avatars_settings_field_gravatar', 'discussion', 'avatars' );
}

/**
 * Settings field for preventing requests to Gravatar
 *
 * @since 0.1.0
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
			<?php echo translate_user_role( $role['name'] ); ?>
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
 * @param  array $input Passed input values to sanitize
 *
 * @return array Sanitized input fields
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
 * @param  array $input Passed input values to sanitize
 *
 * @return array Sanitized input fields
 */
function wp_user_avatars_sanitize_block_gravatar( $input ) {
	return (bool) $input;
}

/**
 * Add scripts to the profile editing page
 *
 * @since 0.1.0
 */
function wp_user_avatars_admin_enqueue_scripts() {

	// Bail if not editing a user
	if ( ! defined( 'IS_PROFILE_PAGE' ) ) {
		return;
	}

	// Enqueue media
	wp_enqueue_media();

	// User ID
	$user_id = ! empty( $_GET['user_id'] )
		? (int) $_GET['user_id']
		: get_current_user_id();

	// URL & Version
	$url = wp_user_avatars_get_plugin_url();
	$ver = wp_user_avatars_get_asset_version();

	// Enqueue
	wp_enqueue_script( 'wp-user-avatars', $url . 'assets/js/user-avatars.js',   array( 'jquery' ), $ver, true  );
	wp_enqueue_style( 'wp-user-avatars',  $url . 'assets/css/user-avatars.css', array(),           $ver, false );

	// Localize
	wp_localize_script( 'wp-user-avatars', 'i10n_WPUserAvatars', array(
		'insertMediaTitle' => esc_html__( 'Choose an Avatar', 'wp-user-avatars' ),
		'insertIntoPost'   => esc_html__( 'Set as avatar',    'wp-user-avatars' ),
		'deleteNonce'      => wp_create_nonce( 'remove_wp_user_avatars_nonce' ),
		'mediaNonce'       => wp_create_nonce( 'assign_wp_user_avatars_nonce' ),
		'user_id'          => $user_id,
	) );
}

/**
 * Output avatar field on edit/profile screens
 *
 * @since 0.1.0
 *
 * @param object $user User object
 */
function wp_user_avatars_edit_user_profile( $user = 0 ) {

	// Bail if current user cannot edit this user's avatar and rating
	if ( ! current_user_can( 'edit_avatar', $user->ID ) && ! current_user_can( 'edit_avatar_rating', $user->ID ) ) {
		return;
	} ?>

	<div id="wp-user-avatars-user-settings">
		<h3><?php esc_html_e( 'Avatar','wp-user-avatars' ); ?></h3>

		<?php wp_user_avatars_section_content( $user ); ?>

	</div>

	<?php
}

/**
 * Output the HTML used for the metabox and settings section
 *
 * @since 0.1.0
 *
 * @param  object $user
 */
function wp_user_avatars_section_content( $user = null ) {

	// Bail if no user
	if ( empty( $user->ID ) ) {
		return;
	} ?>

	<table class="form-table">

		<?php

		// User needs caps to edit avatar
		if ( current_user_can( 'edit_avatar', $user->ID ) ) : ?>

			<tr>
				<th scope="row"><label for="wp-user-avatars"><?php esc_html_e( 'Upload', 'wp-user-avatars' ); ?></label></th>
				<td id="wp-user-avatars-photo">
					<?php
						add_filter( 'pre_option_avatar_rating', '__return_null' );
						echo get_user_avatar( $user->ID, 250 );
						remove_filter( 'pre_option_avatar_rating', '__return_null' );
					?>
				</td>
				<td id="wp-user-avatars-actions">

					<?php

					// User needs additional caps to upload avatars
					if ( current_user_can( 'upload_avatar', $user->ID ) ) : ?>

						<div>
							<input type="file" name="wp-user-avatars" id="wp-user-avatars" class="standard-text" />
						</div>

					<?php endif; ?>

					<div>

						<?php

						// Prevent errors if not enqueued successfully
						if ( did_action( 'wp_enqueue_media' ) ) : ?>

							<a href="#" class="button hide-if-no-js" id="wp-user-avatars-media">
								<?php esc_html_e( 'Choose from Media', 'wp-user-avatars' ); ?>
							</a> &nbsp;

						<?php endif; ?>

						<?php

						// User needs additional caps to remove existing avatar
						if ( current_user_can( 'remove_avatar', $user->ID ) ) : ?>

							<?php $remove_url = add_query_arg( array(
								'action'   => 'remove-wp-user-avatars',
								'user_id'  => $user->ID,
								'_wpnonce' => false,
							) ); ?>

							<a href="<?php echo esc_url( $remove_url ); ?>" class="button item-delete submitdelete deletion" id="wp-user-avatars-remove"<?php if ( empty( $user->wp_user_avatars ) ) echo ' style="display:none;"'; ?>>
								<?php esc_html_e( 'Remove', 'wp-user-avatars' ); ?>
							</a>

						<?php endif; ?>

					</div>

					<?php wp_nonce_field( 'wp_user_avatars_nonce', '_wp_user_avatars_nonce', false ); ?>

				</td>
			</tr>

		<?php endif; ?>

		<?php

		// User needs additional caps to edit ratings
		if ( current_user_can( 'edit_avatar_rating', $user->ID ) ) : ?>

			<tr>
				<th scope="row"><?php esc_html_e( 'Rating', 'wp-user-avatars' ); ?></th>
				<td id="wp-user-avatars-ratings" colspan="2" <?php if ( empty( $user->wp_user_avatars ) ) echo ' class="fancy-hidden"'; ?>>
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

<?php
}