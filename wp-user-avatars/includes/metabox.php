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

	// Register avatar metabox
	add_meta_box(
		'user-avatar',
		_x( 'Avatar', 'users user-admin edit screen', 'wp-user-avatars' ),
		'wp_user_profiles_avatar_metabox',
		$type,
		'side',
		'low',
		$user
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
