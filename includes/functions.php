<?php

/**
 * User Avatar Functions
 *
 * @since 0.1.0
 *
 * @package Plugins/Users/Avatars/Functions
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Output the proper encoding type for the user edit form
 *
 * @since 0.1.0
 */
function wp_user_avatars_user_edit_form_tag() {
	echo 'enctype="multipart/form-data"';
}

/**
 * Save any changes to the user profile
 *
 * @param int $user_id ID of user being updated
 */
function wp_user_avatars_edit_user_profile_update( $user_id = 0 ) {

	// Bail if nonce fails
	if ( empty( $_POST['_wp_user_avatars_nonce'] ) || ! wp_verify_nonce( $_POST['_wp_user_avatars_nonce'], 'wp_user_avatars_nonce' ) ) {
		return;
	}

	// Check for upload
	if ( ! empty( $_FILES['wp-user-avatars']['name'] ) ) {

		// need to be more secure since low privelege users can upload
		if ( false !== strpos( $_FILES['wp-user-avatars']['name'], '.php' ) ) {
			add_action( 'user_profile_update_errors', 'wp_user_avatars_file_extension_error' );
			return;
		}

		// front end (theme my profile etc) support
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		// Override avatar file-size
		add_filter( 'upload_size_limit', 'wp_user_avatars_upload_size_limit' );

		// Handle upload
		$avatar = wp_handle_upload( $_FILES['wp-user-avatars'], array(
			'mimes' => array(
				'jpg|jpeg|jpe' => 'image/jpeg',
				'gif'          => 'image/gif',
				'png'          => 'image/png',
			),
			'test_form' => false,
			'unique_filename_callback' => 'wp_user_avatars_unique_filename_callback'
		) );

		remove_filter( 'upload_size_limit', 'wp_user_avatars_upload_size_limit' );

		// Failures
		if ( empty( $avatar['file'] ) ) {

			// Error feedback
			switch ( $avatar['error'] ) {
				case 'File type does not meet security guidelines. Try another.' :
					add_action( 'user_profile_update_errors', 'wp_user_avatars_file_extension_error' );
					return;
				default :
					add_action( 'user_profile_update_errors', 'wp_user_avatars_generic_error' );
					return;
			}
		}

		// Update
		wp_user_avatars_update_avatar( $user_id, $avatar['url'] );
	}

	// Rating
	if ( isset( $avatar['url'] ) || $avatar = get_user_meta( $user_id, 'wp_user_avatars', true ) ) {
		if ( empty( $_POST['wp_user_avatars_rating'] ) || ! array_key_exists( $_POST['wp_user_avatars_rating'], wp_user_avatars_get_ratings() ) ) {
			$_POST['wp_user_avatars_rating'] = key( wp_user_avatars_get_ratings() );
		}

		update_user_meta( $user_id, 'wp_user_avatars_rating', $_POST['wp_user_avatars_rating'] );
	}
}

/**
 * Return a unique filename for uploaded avatars
 *
 * @since 0.1.0
 *
 * @param  string  $dir   Path for file
 * @param  string  $name  Filename
 * @param  string  $ext   File extension (e.g. ".jpg")
 *
 * @return string Final filename
 */
function wp_user_avatars_unique_filename_callback( $dir, $name, $ext ) {

	// Get user
	$user = get_user_by( 'id', $GLOBALS['user_id'] );

	// File suffix
	$suffix = time();

	// Override names
	$_name = $base_name = sanitize_file_name( 'avatar_user_' . $user->ID . '_' . $suffix );

	// Ensure no conflicts with existing file names
	$number = 1;
	while ( file_exists( $dir . "/{$_name}{$ext}" ) ) {
		$_name = $base_name . '_' . $number;
		$number++;
	}

	// Return the unique filename
	return $_name . $ext;
}

/**
 * Override maximum allowable avatar upload file-size
 *
 * @since 0.1.0
 *
 * @param  int $bytes WordPress default byte size check
 *
 * @return int Maximum byte size
 */
function wp_user_avatars_upload_size_limit( $bytes = 2000 ) {
	return apply_filters( 'wp_user_avatars_upload_size_limit', $bytes );
}

/**
 * Return an array of avatar ratings
 *
 * @since 0.1.0
 *
 * @return array
 */
function wp_user_avatars_get_ratings() {
	return apply_filters( 'wp_user_avatars_get_ratings', array(
		'G'  => esc_html__( 'Suitable for all audiences',                             'wp-user-avatars' ),
		'PG' => esc_html__( 'Possibly offensive, usually for audiences 13 and above', 'wp-user-avatars' ),
		'R'  => esc_html__( 'Intended for adult audiences above 17',                  'wp-user-avatars' ),
		'X'  => esc_html__( 'Even more mature than above',                            'wp-user-avatars' )
	) );
}

/**
 * More efficient to call directly in theme and avoid Gravatar entirely
 *
 * @since 0.1.0
 *
 * @param  mixed   $id_or_email A user ID,  email address, or comment object
 * @param  int     $size        Size of the avatar image
 * @param  string  $default     URL to a default image to use if no avatar is available
 * @param  string  $alt         Alternate text to use in image tag. Defaults to blank
 *
 * @return string  <img> tag for the user's avatar
 */
function get_user_avatar( $id_or_email, $size = 250, $default = '', $alt = '' ) {

	// Look for avatar on this site
	$avatar = wp_user_avatars_filter_get_avatar( '', $id_or_email, $size, $default, $alt );

	// Get Gravatar fallback
	if ( empty( $avatar ) ) {
		remove_action( 'get_avatar', 'wp_user_avatars_filter_get_avatar' );
		$avatar = get_avatar( $id_or_email, $size, $default, $alt );
		add_action( 'get_avatar', 'wp_user_avatars_filter_get_avatar' );
	}

	// Return whichever avatar was found
	return $avatar;
}

/**
 * Retrieve an avatar for this site for a specific user by email or ID
 *
 * @since 0.1.0
 *
 * @param  string            $avatar Avatar return by original function
 * @param  int|string|object $id_or_email A user ID,  email address, or comment object
 * @param  int               $size Size of the avatar image
 * @param  string            $default URL to a default image to use if no avatar is available
 * @param  string            $alt Alternative text to use in image tag. Defaults to blank
 *
 * @return string <img> tag for the user's avatar
 */
function wp_user_avatars_filter_get_avatar( $avatar = '', $id_or_email = 0, $size = 250, $default = '', $alt = '' ) {

	// Do some work to figure out the user ID
	if ( is_numeric( $id_or_email ) ) {
		$user_id = (int) $id_or_email;
	} elseif ( is_string( $id_or_email ) && ( $user = get_user_by( 'email', $id_or_email ) ) ) {
		$user_id = $user->ID;
	} elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) {
		$user_id = (int) $id_or_email->user_id;
	}

	// Bail if no user ID
	if ( empty( $user_id ) ) {
		return $avatar;
	}

	// Fetch avatars from usermeta, bail if no full option
	$user_avatars = get_user_meta( $user_id, 'wp_user_avatars', true );
	if ( empty( $user_avatars['full'] ) ) {
		return $avatar;
	}

	// Get ratings
	$avatar_rating = get_user_meta( $user_id, 'wp_user_avatars_rating', true );
	$site_rating   = get_option( 'avatar_rating', 'G' );

	// Compare ratings
	if ( ! empty( $avatar_rating ) && ( 'G' !== $avatar_rating ) && ( $avatar_rating !== $site_rating ) ) {

		// Calculate rating weights
		$ratings              = array_keys( wp_user_avatars_get_ratings() );
		$site_rating_weight   = array_search( $site_rating, $ratings );
		$avatar_rating_weight = array_search( $avatar_rating, $ratings );

		// Too risky
		if ( ( false !== $avatar_rating_weight ) && ( $avatar_rating_weight > $site_rating_weight ) ) {
			return $avatar;
		}
	}

	// Handle "real" media
	if ( ! empty( $user_avatars['media_id'] ) ) {

		// Has the media been deleted?
		$avatar_full_path = get_attached_file( $user_avatars['media_id'] );
		if ( empty( $avatar_full_path ) ) {

			// Only let logged in users delete missing avatars
			if ( is_user_logged_in() ) {
				wp_user_avatars_delete_avatar( $user_id );
			}

			return $avatar;
		}
	}

	// Alternate text
	if ( empty( $alt ) ) {
		$alt = get_the_author_meta( 'display_name', $user_id );
	}

	// Generate a new size
	if ( ! array_key_exists( $size, $user_avatars ) ) {

		// Set full size
		$user_avatars[ $size ] = $user_avatars['full'];

		// Allow rescaling to be toggled, usually for performance reasons
		if ( apply_filters( 'wp_user_avatars_dynamic_resize', true ) ) :

			// Get the upload path (hard to trust this sometimes, though...)
			$upload_path = wp_upload_dir();

			// Get path for image by converting URL
			if ( ! isset( $avatar_full_path ) ) {
				$avatar_full_path = str_replace( $upload_path['baseurl'], $upload_path['basedir'], $user_avatars['full'] );
			}

			// Load image editor (for resizing)
			$editor = wp_get_image_editor( $avatar_full_path );
			if ( ! is_wp_error( $editor ) ) {

				// Attempt to resize
				$resized = $editor->resize( $size, $size, true );
				if ( ! is_wp_error( $resized ) ) {

					$dest_file = $editor->generate_filename();
					$saved     = $editor->save( $dest_file );

					if ( ! is_wp_error( $saved ) ) {
						$user_avatars[ $size ] = str_replace( $upload_path['basedir'], $upload_path['baseurl'], $dest_file );
					}
				}
			}

			// Save updated avatar sizes
			update_user_meta( $user_id, 'wp_user_avatars', $user_avatars );
		endif;
	}

	// URL corrections
	if ( 'http' !== substr( $user_avatars[ $size ], 0, 4 ) ) {
		$user_avatars[ $size ] = home_url( $user_avatars[ $size ] );
	}

	// Current?
	$author_class = is_author( $user_id )
		? ' current-author'
		: '' ;

	// Setup the markup
	$avatar = "<img alt='" . esc_attr( $alt ) . "' src='" . esc_url( $user_avatars[ $size ] ) . "' class='avatar avatar-{$size}{$author_class} photo' height='{$size}' width='{$size}' />";

	// Filter & return
	return apply_filters( 'wp_user_avatars', $avatar, $id_or_email, $size, $default, $alt );
}

/**
 * Delete an avatar
 *
 * @since 0.1.0
 *
 * @param  int $user_id
 *
 * @return type
 */
function wp_user_avatars_delete_avatar( $user_id = 0 ) {

	// Bail if no avatars to delete
	$old_avatars = (array) get_user_meta( $user_id, 'wp_user_avatars', true );
	if ( empty( $old_avatars ) ) {
		return;
	}

	// Don't erase media library files
	if ( array_key_exists( 'media_id', $old_avatars ) ) {
		unset( $old_avatars['media_id'], $old_avatars['full'] );
	}

	// Are there files to delete?
	if ( ! empty( $old_avatars ) ) {
		$upload_path = wp_upload_dir();

		// Loop through avatars
		foreach ( $old_avatars as $old_avatar ) {

			// Use the upload directory
			$old_avatar_path = str_replace( $upload_path['baseurl'], $upload_path['basedir'], $old_avatar );

			// Maybe delete the file
			if ( file_exists( $old_avatar_path ) ) {
				unlink( $old_avatar_path );
			}
		}
	}

	// Remove metadata
	delete_user_meta( $user_id, 'wp_user_avatars'        );
	delete_user_meta( $user_id, 'wp_user_avatars_rating' );
}

/**
 * Saves avatar image to a user
 *
 * @since 0.1.0
 *
 * @param int        $user_id  ID of user to assign image to
 * @param int|string $media    Local URL for avatar or ID of attachment
 */
function wp_user_avatars_update_avatar( $user_id, $media ) {

	// Delete old avatar
	wp_user_avatars_delete_avatar( $user_id );

	// Setup empty meta array
	$meta_value = array();

	// Set the attachment URL
	if ( is_int( $media ) ) {
		$meta_value['media_id'] = $media;
		$media                  = wp_get_attachment_url( $media );
	}

	// Set full value to media URL
	$meta_value['full'] = esc_url_raw( $media );

	// Update user metadata
	update_user_meta( $user_id, 'wp_user_avatars', $meta_value );
}

/**
 * Remove user-avatars filter for the avatar list in options-discussion.php.
 *
 * @since 0.1.0
 */
function wp_user_avatars_avatar_defaults( $avatar_defaults = array() ) {
	remove_action( 'get_avatar', 'wp_user_avatars_filter_get_avatar' );
	return $avatar_defaults;
}

/**
 * Output the rating field radio options for a given user object
 *
 * @since 0.1.0
 *
 * @param WP_User $user
 */
function wp_user_avatars_user_rating_form_field( WP_User $user ) {

	// Start an output buffer
	ob_start();

	// Output ratings
	foreach ( wp_user_avatars_get_ratings() as $key => $rating ) : ?>

		<label>
			<input type="radio" name="wp_user_avatars_rating" title="<?php echo esc_html( $rating ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $user->wp_user_avatars_rating, $key ); ?> />
			<span class="wp-user-avatar-rating"><?php echo esc_html( strtoupper( $key ) ); ?></span>
			<span class="wp-user-avatar-rating-description"> &mdash; <?php echo esc_html( $rating ); ?></span>
		</label>
		<br>

	<?php endforeach;

	// Output the buffer
	echo ob_get_clean();
}

/**
 * Return array of profile sections
 *
 * @since 0.1.0
 *
 * @return string
 */
function wp_user_avatars_profile_sections() {

	// Bail if no user profile sections
	if ( ! function_exists( 'wp_user_profiles_sections' ) ) {
		return array( 'profile.php', 'user-edit.php' );
	}

	// Get sections
	$sections = wp_list_pluck( wp_user_profiles_sections(), 'slug' );
	$in_array = array( 'toplevel_page_profile' );
	foreach ( $sections as $section ) {
		$in_array[] = 'users_page_' . $section;
	}

	return $in_array;
}
