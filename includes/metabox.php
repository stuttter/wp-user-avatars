<?php

/**
 * User Profile Avatar Metabox
 * 
 * @package Plugins/Users/Profiles/Metaboxes/Avatar
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add the default user profile metaboxes
 *
 * @since 0.1.0
 *
 * @param   string  $type
 * @param   mixed   $user
 */
function wp_user_profiles_add_avatar_meta_box( $type = '', $user = null ) {

	// Bail if no profile sections
	if ( ! function_exists( 'wp_user_profiles_sections' ) ) {
		return;
	}

	// Support for WP User Profiles 0.1.7 and higher
	if ( function_exists( 'wp_user_profiles_get_section_hooknames' ) ) {
		$types = wp_user_profiles_get_section_hooknames();

	// WP User Profiles 0.1.6 and lower
	} else {
		$types = wp_user_avatars_profile_sections();
	}

	// Bail if not user metaboxes
	if ( empty( $user ) || ! in_array( $type, $types, true ) ) {
		return;
	}

	// Register avatar metabox
	add_meta_box(
		'user-avatar',
		_x( 'Avatar', 'users user-admin edit screen', 'wp-user-avatars' ),
		'wp_user_profiles_avatar_metabox',
		$type,
		'side',
		'low'
	);
}

/**
 * Render the primary metabox for user profile screen
 *
 * @since 0.1.0
 *
 * @param WP_User $user The WP_User object to be edited.
 */
function wp_user_profiles_avatar_metabox( $user = null ) {

	// Bail if no user id or if the user has not activated their account yet
	if ( empty( $user->ID ) ) {
		return;
	}

	// First pass
	wp_user_avatars_section_content( $user );
}
