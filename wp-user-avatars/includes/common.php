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
 *
 * @return void
 */
function wp_user_avatars_user_edit_form_tag() {
	echo 'enctype="multipart/form-data"';
}

/**
 * Save any changes to the user profile
 *
 * @param int $user_id ID of user being updated
 *
 * @return void
 */
function wp_user_avatars_edit_user_profile_update( $user_id = 0 ) {

	// Bail if nonce fails
	if ( empty( $_POST['_wp_user_avatars_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wp_user_avatars_nonce'] ) ), 'wp_user_avatars_nonce' ) ) {
		return;
	}

	$avatar_updated = false;

	// Check for upload
	if ( ! empty( $_FILES['wp-user-avatars']['name'] ) ) {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps().
		if ( ! current_user_can( 'upload_avatar', $user_id ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- The upload API requires the original file array and validates it against the avatar MIME allowlist.
		$avatar = wp_user_avatars_handle_upload( $user_id, $_FILES['wp-user-avatars'] );
		if ( is_wp_error( $avatar ) ) {
			$callback = 'invalid_file_type' === $avatar->get_error_code()
				? 'wp_user_avatars_file_extension_error'
				: 'wp_user_avatars_generic_error';

			add_action( 'user_profile_update_errors', $callback );
			return;
		}

		$avatar_updated = true;
	}

	// Rating
	// phpcs:ignore WordPress.WP.Capabilities.Unknown -- Custom avatar capability mapped by wp_user_avatars_meta_caps().
	if ( current_user_can( 'edit_avatar_rating', $user_id ) && ( $avatar_updated || get_user_meta( $user_id, 'wp_user_avatars', true ) ) ) {
		$rating = isset( $_POST['wp_user_avatars_rating'] )
			? sanitize_text_field( wp_unslash( $_POST['wp_user_avatars_rating'] ) )
			: '';

		wp_user_avatars_update_rating( $user_id, $rating );
	}
}

/**
 * Handle an avatar file upload and assign it to a user.
 *
 * @since 2.0.0
 *
 * @param int                  $user_id User ID receiving the avatar.
 * @param array<string, mixed> $file    Uploaded file data.
 *
 * @return string|WP_Error Uploaded avatar URL or an error.
 */
function wp_user_avatars_handle_upload( $user_id, $file ) {
	$file_name = isset( $file['name'] ) ? (string) $file['name'] : '';

	// Low-privilege users may upload avatars, so reject executable extensions early.
	if ( false !== stripos( $file_name, '.php' ) ) {
		return new WP_Error( 'invalid_file_type', esc_html__( 'The selected file type is not allowed.', 'wp-user-avatars' ) );
	}

	// Front-end profile integrations do not load the upload API automatically.
	if ( ! function_exists( 'wp_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	add_filter( 'upload_size_limit', 'wp_user_avatars_upload_size_limit' );
	$GLOBALS['wp_user_avatars_user_id'] = $user_id;

	try {
		$avatar = wp_handle_upload(
			$file,
			array(
				'mimes'                    => array(
					'jpg|jpeg|jpe' => 'image/jpeg',
					'gif'          => 'image/gif',
					'png'          => 'image/png',
					'webp'         => 'image/webp',
				),
				'test_form'                => false,
				'unique_filename_callback' => 'wp_user_avatars_unique_filename_callback',
			)
		);
	} finally {
		unset( $GLOBALS['wp_user_avatars_user_id'] );
		remove_filter( 'upload_size_limit', 'wp_user_avatars_upload_size_limit' );
	}

	if ( empty( $avatar['file'] ) || empty( $avatar['url'] ) ) {
		$error_code = isset( $avatar['error'] ) && 'File type does not meet security guidelines. Try another.' === $avatar['error']
			? 'invalid_file_type'
			: 'upload_error';
		$message    = isset( $avatar['error'] ) ? (string) $avatar['error'] : esc_html__( 'The avatar could not be uploaded.', 'wp-user-avatars' );

		return new WP_Error( $error_code, $message );
	}

	wp_user_avatars_update_avatar( $user_id, $avatar['url'] );

	return $avatar['url'];
}

/**
 * Validate and save an avatar rating.
 *
 * @since 2.0.0
 *
 * @param int    $user_id User ID receiving the rating.
 * @param string $rating  Requested rating.
 *
 * @return string Saved rating.
 */
function wp_user_avatars_update_rating( $user_id, $rating = '' ) {
	$ratings = wp_user_avatars_get_ratings();

	if ( empty( $rating ) || ! array_key_exists( $rating, $ratings ) ) {
		$rating = key( $ratings );
	}

	update_user_meta( $user_id, 'wp_user_avatars_rating', $rating );

	return $rating;
}

/**
 * Return a unique filename for uploaded avatars
 *
 * @since 0.1.0
 *
 * @param  string $dir   Path for file
 * @param  string $name  Filename
 * @param  string $ext   File extension (e.g. ".jpg")
 *
 * @return string Final filename
 */
function wp_user_avatars_unique_filename_callback( $dir, $name, $ext ) {

	// Get user
	$user = get_user_by( 'id', $GLOBALS['wp_user_avatars_user_id'] );

	// Override names
	$base_name = false === $user
		? sanitize_file_name( $name )
		: sanitize_file_name( 'avatar_user_' . $user->ID . '_' . time() );
	$_name     = $base_name;

	// Ensure no conflicts with existing file names
	$number = 1;
	while ( file_exists( $dir . "/{$_name}{$ext}" ) ) {
		$_name = $base_name . '_' . $number;
		++$number;
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
 * @return array<string, string>
 */
function wp_user_avatars_get_ratings() {
	return apply_filters(
		'wp_user_avatars_get_ratings',
		array(
			'G'  => esc_html__( 'Suitable for all audiences', 'wp-user-avatars' ),
			'PG' => esc_html__( 'Possibly offensive, usually for audiences 13 and above', 'wp-user-avatars' ),
			'R'  => esc_html__( 'Intended for adult audiences above 17', 'wp-user-avatars' ),
			'X'  => esc_html__( 'Even more mature than above', 'wp-user-avatars' ),
		)
	);
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
 * @return string|false
 */
function get_user_avatar( $id_or_email, $size = 250, $default = '', $alt = '' ) {
	return get_avatar( $id_or_email, $size, $default, $alt );
}

/**
 * Return an avatar for profile-editing previews even when public avatars are hidden.
 *
 * @since 2.0.0
 *
 * @param mixed $id_or_email User identifier.
 * @param int   $size        Avatar size in pixels.
 *
 * @return string Avatar markup, or an empty string on failure.
 */
function wp_user_avatars_get_avatar_preview( $id_or_email, $size = 250 ) {
	$avatar = get_avatar( $id_or_email, $size, '', '', array( 'force_display' => true ) );

	return is_string( $avatar ) ? $avatar : '';
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
		$retval = $id_or_email->ID;

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
 * @param mixed $user_id
 * @param int   $size
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
		$site_rating_weight   = array_search( $site_rating, $ratings_key );
		$avatar_rating_weight = array_search( $avatar_rating, $ratings_key );

		// Too risky
		if ( ( false !== $avatar_rating_weight ) && ( $avatar_rating_weight > $site_rating_weight ) ) {
			return null;
		}
	}

	/**
	 * Filters whether WP User Avatars may generate the requested local size.
	 *
	 * @since 0.1.0
	 * @since 1.4.1 Added the `$user_id`, `$size`, and `$user_avatars` parameters.
	 *
	 * @param bool  $dynamic_resize Whether to generate an uncached size.
	 * @param int   $user_id        Avatar owner's user ID.
	 * @param int   $size           Requested square size in pixels.
	 * @param array $user_avatars   Stored avatar data.
	 */
	$dynamic_resize = apply_filters( 'wp_user_avatars_dynamic_resize', true, $user_id, $size, $user_avatars );

	// Return early if there's no media to check and we either have an avatar of the correct size or don't dynamically resize
	if ( empty( $user_avatars['media_id'] ) && ( ! empty( $user_avatars[ $size ] ) || $dynamic_resize === false ) ) {
		$avatar_url = empty( $user_avatars[ $size ] ) ? $user_avatars['full'] : $user_avatars[ $size ];
		$site_id    = isset( $user_avatars['site_id'] ) ? (int) $user_avatars['site_id'] : null;
		return wp_user_avatars_maybe_secure_url( $avatar_url, $site_id );
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

		// Let WordPress and storage plugins resolve remotely hosted attachments.
		if ( wp_is_stream( $avatar_full_path ) ) {
			$avatar_url = wp_get_attachment_image_url( $user_avatars['media_id'], array( $size, $size ) );

			if ( empty( $avatar_url ) ) {
				$avatar_url = wp_get_attachment_url( $user_avatars['media_id'] );
			}

			if ( ! empty( $avatar_url ) ) {
				$avatar_url = wp_user_avatars_maybe_secure_url( $avatar_url );
			}

			if ( true === $switched ) {
				restore_current_blog();
			}

			return empty( $avatar_url ) ? null : $avatar_url;
		}
	}

	// Generate a new size
	if ( empty( $user_avatars[ $size ] ) ) {

		// Set full size
		$user_avatars[ $size ] = $user_avatars['full'];

		// Allow rescaling to be toggled, usually for performance reasons
		if ( $dynamic_resize ) {

			// Get the upload path (hard to trust this sometimes, though...)
			$upload_path = wp_upload_dir();

			// Get path for image by converting URL
			if ( ! isset( $avatar_full_path ) ) {
				$full_url         = wp_user_avatars_maybe_secure_url( $user_avatars['full'] );
				$upload_url       = wp_user_avatars_maybe_secure_url( $upload_path['baseurl'] );
				$avatar_full_path = str_replace( $upload_url, $upload_path['basedir'], $full_url );
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
	$avatar_url = wp_user_avatars_maybe_secure_url( $user_avatars[ $size ] );

	// Maybe switch back
	if ( true === $switched ) {
		restore_current_blog();
	}

	// Return the url
	return $avatar_url;
}

/**
 * Upgrade an HTTP avatar only when its owning site uses HTTPS on that host.
 *
 * @since 2.0.1
 *
 * @param string   $url     Stored or resolved avatar URL.
 * @param int|null $site_id Owning site, or null for the current site.
 * @return string Avatar URL without changing stored metadata.
 */
function wp_user_avatars_maybe_secure_url( $url, $site_id = null ) {
	$avatar = wp_parse_url( $url );
	if ( ! is_array( $avatar ) || empty( $avatar['host'] ) || empty( $avatar['scheme'] ) || 'http' !== strtolower( $avatar['scheme'] ) ) {
		return $url;
	}

	foreach ( array( get_home_url( $site_id ), get_site_url( $site_id ) ) as $site_url ) {
		$site = wp_parse_url( $site_url );
		if ( is_array( $site ) && ! empty( $site['host'] ) && ! empty( $site['scheme'] ) && 'https' === strtolower( $site['scheme'] ) && strtolower( $avatar['host'] ) === strtolower( $site['host'] ) && ( $avatar['port'] ?? null ) === ( $site['port'] ?? null ) ) {
			return 'https:' . substr( $url, strpos( $url, ':' ) + 1 );
		}
	}

	return $url;
}

/**
 * Filter 'get_avatar_url' and maybe return a local avatar
 *
 * @since 1.0.0
 *
 * @param string $url
 * @param mixed  $id_or_email
 * @param array  $args
 *
 * @phpstan-param array<string, mixed> $args
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
 * Restore local-avatar precedence when another provider returns early.
 *
 * Normal requests continue through get_avatar_url so existing filters retain
 * their established order. This callback only handles requests that another
 * pre_get_avatar_data provider has already short-circuited.
 *
 * @since 2.1.0
 *
 * @param array $args        Processed avatar arguments.
 * @param mixed $id_or_email Avatar identity.
 *
 * @phpstan-param array<string, mixed> $args
 * @phpstan-return array<string, mixed>
 *
 * @return array
 */
function wp_user_avatars_filter_pre_get_avatar_data( $args, $id_or_email ) {

	// Preserve the normal get_avatar_url path when no provider returned early
	if ( ! isset( $args['url'] ) ) {
		return $args;
	}

	// Resolve against an empty sentinel so matching provider and local URLs are
	// still recognized as a successfully resolved local avatar.
	$avatar_url = wp_user_avatars_filter_get_avatar_url( '', $id_or_email, $args );

	// Mark a resolved local avatar as found
	if ( ! empty( $avatar_url ) ) {
		$args['url']          = $avatar_url;
		$args['found_avatar'] = true;
	}

	return $args;
}

/**
 * Delete an avatar
 *
 * @since 0.1.0
 *
 * @param  int $user_id
 *
 * @return void
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
				wp_delete_file( $old_avatar_path );
			}
		}
	}

	// Remove metadata
	delete_user_meta( $user_id, 'wp_user_avatars' );
	delete_user_meta( $user_id, 'wp_user_avatars_rating' );
}

/**
 * Saves avatar image to a user
 *
 * @since 0.1.0
 *
 * @param int        $user_id  ID of user to assign image to
 * @param int|string $media    Local URL for avatar or ID of attachment
 *
 * @return void
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

	if ( false === $media ) {
		return;
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
 *
 * @param array $avatar_defaults Avatar defaults.
 *
 * @return array<string, string>
 *
 * @phpstan-param array<string, string> $avatar_defaults
 * @phpstan-return array<string, string>
 */
function wp_user_avatars_avatar_defaults( $avatar_defaults = array() ) {

	// Default
	$new_avatar_defaults = $avatar_defaults;
	$custom_url          = wp_user_avatars_get_default_avatar_url();

	// Maybe block Gravatars
	if ( get_option( 'wp_user_avatars_block_gravatar' ) ) {
		$new_avatar_defaults = array();

		if ( $custom_url ) {
			$new_avatar_defaults[ $custom_url ] = esc_html__( 'Custom Image', 'wp-user-avatars' );
		}

		$new_avatar_defaults += array(
			wp_user_avatars_get_mystery_url() => esc_html__( 'Mystery Person', 'wp-user-avatars' ),
			'blank'                           => esc_html__( 'Blank', 'wp-user-avatars' ),
		);
	} elseif ( $custom_url ) {
		$new_avatar_defaults[ $custom_url ] = esc_html__( 'Custom Image', 'wp-user-avatars' );
	}

	// Return avatar types, maybe without Gravatar options
	return $new_avatar_defaults;
}

/**
 * Divert Gravatar requests to use the local mystery person image.
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

	// Bail if the URL is not hosted by Gravatar
	$host = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! is_string( $host ) || ( 'gravatar.com' !== $host && '.gravatar.com' !== substr( $host, -13 ) ) ) {
		return $url;
	}

	// Preserve Blank without making a remote Gravatar request.
	$query = array();
	parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );
	if ( isset( $query['d'] ) && 'blank' === $query['d'] ) {
		return wp_user_avatars_get_plugin_url() . 'assets/images/blank.svg';
	}

	// Serve the selected site fallback directly when it is requested explicitly.
	$custom_url = wp_user_avatars_get_default_avatar_url();
	if ( $custom_url && isset( $query['d'] ) && $custom_url === $query['d'] ) {
		return $custom_url;
	}

	// Preserve an explicit URL default without contacting Gravatar.
	if ( isset( $query['d'] ) && is_string( $query['d'] ) ) {
		$scheme = wp_parse_url( $query['d'], PHP_URL_SCHEME );
		$host   = wp_parse_url( $query['d'], PHP_URL_HOST );

		if ( is_string( $scheme ) && in_array( strtolower( $scheme ), array( 'http', 'https' ), true ) && is_string( $host ) && '' !== $host ) {
			return esc_url_raw( $query['d'] );
		}
	}

	// Return the local mystery person
	return wp_user_avatars_get_mystery_url();
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
	$custom = get_option( 'wp_user_avatars_default_avatar', array() );
	if ( is_array( $custom ) && ! empty( $custom['url'] ) && is_string( $custom['url'] ) && $custom['url'] === $value ) {
		$current_url = wp_user_avatars_get_default_avatar_url();
		if ( $current_url ) {
			return $current_url;
		}

		$value = 'mystery';
	}

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
 * Return the current site's custom default avatar URL.
 *
 * @since 2.1.0
 *
 * @return string Empty when no valid attachment is configured.
 */
function wp_user_avatars_get_default_avatar_url() {
	$avatar = get_option( 'wp_user_avatars_default_avatar', array() );
	if ( ! is_array( $avatar ) || empty( $avatar['media_id'] ) ) {
		return '';
	}

	$url = wp_get_attachment_url( absint( $avatar['media_id'] ) );
	if ( ! is_string( $url ) || '' === $url ) {
		return '';
	}

	return $url;
}

/**
 * Output the rating field radio options for a given user object
 *
 * @since 0.1.0
 *
 * @param WP_User $user
 *
 * @return void
 */
function wp_user_avatars_user_rating_form_field( WP_User $user ) {

	// Output ratings
	foreach ( wp_user_avatars_get_ratings() as $key => $rating ) : ?>

		<label>
			<input type="radio" name="wp_user_avatars_rating" title="<?php echo esc_html( $rating ); ?>" value="<?php echo esc_attr( $key ); ?>" <?php checked( $user->wp_user_avatars_rating, $key ); ?> />
			<span class="wp-user-avatar-rating"><?php echo esc_html( strtoupper( $key ) ); ?></span>
			<span class="wp-user-avatar-rating-description"> &mdash; <?php echo esc_html( $rating ); ?></span>
		</label>
		<br>

		<?php
	endforeach;
}

/**
 * Return array of profile sections
 *
 * @since 0.1.0
 *
 * @return array<int, string>
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
