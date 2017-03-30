<?php

/**
 * User Avatar Common Functions
 *
 * @since 0.1.0
 *
 * @package Plugins/Users/Avatars/Functions/Common
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

		// Temporary global
		$GLOBALS['wp_user_avatars_user_id'] = $user_id;

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

		// No more global
		unset( $GLOBALS['wp_user_avatars_user_id'] );

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
	$user = get_user_by( 'id', $GLOBALS['wp_user_avatars_user_id'] );

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
 * Deprecated. Now you can use `get_avatar()` directly.
 *
 * @since 0.1.0
 * @deprecated 1.0.0
 *
 * @param mixed  $id_or_email
 * @param int    $size
 * @param string $default
 * @param string $alt
 *
 * @return string
 */
function get_user_avatar( $id_or_email, $size = 250, $default = '', $alt = '' ) {
	return get_avatar( $id_or_email, $size, $default, $alt );
}

/**
 * Calculate a user ID based on whatever object was passed in
 *
 * @since 1.0.0
 *
 * @param mixed $id_or_email
 *
 * @return int
 */
function wp_user_avatars_get_user_id( $id_or_email ) {

	// Default
	$retval = 0;

	// Numeric, so use ID
	if ( is_numeric( $id_or_email ) ) {
		$retval = $id_or_email;

	// Maybe email or login
	} elseif ( is_string( $id_or_email ) ) {

		// User by
		$user_by = is_email( $id_or_email )
			? 'email'
			: 'login';

		// Get user
		$user = get_user_by( $user_by, $id_or_email );

		// User ID
		if ( ! empty( $user ) ) {
			$retval = $user->ID;
		}

	// User Object
	} elseif ( $id_or_email instanceof WP_User ) {
		$user = $id_or_email->ID;

	// Post Object
	} elseif ( $id_or_email instanceof WP_Post ) {
		$retval = $id_or_email->post_author;

	// Comment
	} elseif ( $id_or_email instanceof WP_Comment ) {
		if ( ! empty( $id_or_email->user_id ) ) {
			$retval = $id_or_email->user_id;
		}
	}

	return (int) apply_filters( 'wp_user_avatars_get_user_id', (int) $retval, $id_or_email );
}

/**
 * Look for and return the URL to a local avatar if found
 *
 * @since 1.0.0
 *
 * @param int    $user_id
 * @param int    $size
 * @param string $fallback
 *
 * @return mixed
 */
function wp_user_avatars_get_local_avatar_url( $user_id = false, $size = 250 ) {

	// Try to get user ID
	$user_id = wp_user_avatars_get_user_id( $user_id );

	// Bail if no user ID
	if ( empty( $user_id ) ) {
		return null;
	}

	// Fetch avatars from usermeta, bail if no full option
	$user_avatars = get_user_meta( $user_id, 'wp_user_avatars', true );
	if ( empty( $user_avatars['full'] ) ) {
		return null;
	}

	// Get ratings
	$avatar_rating = get_user_meta( $user_id, 'wp_user_avatars_rating', true );
	$site_rating   = get_option( 'avatar_rating', 'G' );
	$switched      = false;

	// Compare ratings
	if ( ! empty( $avatar_rating ) && ( 'G' !== $avatar_rating ) && ( $avatar_rating !== $site_rating ) ) {

		// Calculate rating weights
		$ratings              = wp_user_avatars_get_ratings();
		$ratings_key          = array_keys( $ratings );
		$site_rating_weight   = array_search( $site_rating,   $ratings_key );
		$avatar_rating_weight = array_search( $avatar_rating, $ratings_key );

		// Too risky
		if ( ( false !== $avatar_rating_weight ) && ( $avatar_rating_weight > $site_rating_weight ) ) {
			return null;
		}
	}

	// Maybe switch to blog
	if ( isset( $user_avatars['site_id'] ) && is_multisite() ) {
		$switched = true;
		switch_to_blog( $user_avatars['site_id'] );
	}

	// Handle "real" media
	if ( ! empty( $user_avatars['media_id'] ) ) {

		// Has the media been deleted?
		$avatar_full_path = get_attached_file( $user_avatars['media_id'] );

		// Maybe return null & maybe delete the avatar setting
		if ( empty( $avatar_full_path ) ) {

			// Only let logged in users delete missing avatars
			if ( is_user_logged_in() ) {
				wp_user_avatars_delete_avatar( $user_id );
			}

			// Maybe switch back
			if ( true === $switched ) {
				restore_current_blog();
			}

			return null;
		}
	}

	// Generate a new size
	if ( empty( $user_avatars[ $size ] ) ) {

		// Set full size
		$user_avatars[ $size ] = $user_avatars['full'];

		// Allow rescaling to be toggled, usually for performance reasons
		if ( apply_filters( 'wp_user_avatars_dynamic_resize', true ) ) {

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
		}
	}

	// URL corrections
	if ( 'http' !== substr( $user_avatars[ $size ], 0, 4 ) ) {
		$user_avatars[ $size ] = home_url( $user_avatars[ $size ] );
	}

	// Maybe switch back
	if ( true === $switched ) {
		restore_current_blog();
	}

	// Return the url
	return $user_avatars[ $size ];
}

/**
 * Filter 'get_avatar_url' and maybe return a local avatar
 *
 * @since 1.0.0
 *
 * @param string $url
 * @param mixed $id_or_email
 * @param array $args
 *
 * @return string
 */
function wp_user_avatars_filter_get_avatar_url( $url, $id_or_email, $args ) {

	// Bail if forcing default
	if ( ! empty( $args['force_default'] ) ) {
		return $url;
	}

	// Bail if explicitly an md5'd Gravatar url
	// https://github.com/stuttter/wp-user-avatars/issues/11
	if ( is_string( $id_or_email ) && strpos( $id_or_email, '@md5.gravatar.com' ) ) {
		return $url;
	}

	// Look for local avatar
	$avatar = wp_user_avatars_get_local_avatar_url( $id_or_email, $args['size'] );

	// Override URL if avatar is found
	if ( ! empty( $avatar ) ) {
		$url = $avatar;
	}

	// Return maybe-local URL
	return $url;
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
		$meta_value['site_id']  = get_current_blog_id();
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

	// Default
	$new_avatar_defaults = $avatar_defaults;

	// Maybe block Gravatars
	if ( get_option( 'wp_user_avatars_block_gravatar' ) ) {
		$new_avatar_defaults = array(
			wp_user_avatars_get_mystery_url() => esc_html__( 'Mystery Person', 'wp-user-avatars' ),
			'blank'                           => esc_html__( 'Blank',          'wp-user-avatars' )
		);
	}

	// Return avatar types, maybe without Gravatar options
	return $new_avatar_defaults;
}

/**
 * Maybe divert Gravatar requests to use the local mystery person image.
 *
 * @since 1.1.0
 *
 * @param string $url
 *
 * @return string
 */
function wp_user_avatars_maybe_use_local_mystery_person( $url = '' ) {

	// Bail if not blocking gravatar requests
	if ( ! get_option( 'wp_user_avatars_block_gravatar' ) ) {
		return $url;
	}

	// Local mystery
	$mystery = wp_user_avatars_get_mystery_url();

	// Bail if not already requesting the local mystery person
	if ( false === strpos( $url, urlencode( $mystery ) ) ) {
		return $url;
	}

	// Return the local mystery person
	return $mystery;
}

/**
 * Maybe change the 'mystery' avatar_default setting to be the local mystery person.
 *
 * @since 1.1.0
 *
 * @param string $value
 *
 * @return string
 */
function wp_user_avatars_update_option_avatar_default( $value = null ) {

	// Bail if not defaulting to mystery
	if ( wp_user_avatars_get_mystery_url() !== $value ) {
		return $value;
	}

	// Bail if not blocking gravatar requests
	if ( ! get_option( 'wp_user_avatars_block_gravatar' ) ) {
		return $value;
	}

	// Return the local mystery person
	return 'mystery';
}

/**
 * Maybe change the 'mystery' avatar_default setting to be the local mystery person.
 *
 * @since 1.1.0
 *
 * @param string $value
 *
 * @return string
 */
function wp_user_avatars_option_avatar_default( $value = null ) {

	// Bail if not defaulting to mystery
	if ( 'mystery' !== $value ) {
		return $value;
	}

	// Bail if not blocking gravatar requests
	if ( ! get_option( 'wp_user_avatars_block_gravatar' ) ) {
		return $value;
	}

	// Return the local mystery person
	return wp_user_avatars_get_mystery_url();
}

/**
 * Return URL to local mystery person image
 *
 * @since 1.1.0
 *
 * @return string
 */
function wp_user_avatars_get_mystery_url() {
	$mystery = wp_user_avatars_get_plugin_url() . 'assets/images/mystery.jpg';
	return apply_filters( 'wp_user_avatars_get_mystery_url', $mystery );
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
