<?php

/**
 * User Avatars Capabilities
 *
 * @package Plugins/Users/Avatars/Capabilities
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Maps avatars capabilities
 *
 * @since 0.1.0
 *
 * @param  array   $caps     Capabilities for meta capability
 * @param  string  $cap      Capability name
 * @param  int     $user_id  User id
 * @param  array   $args     Arguments
 *
 * @return array   Actual capabilities for meta capability
 */
function wp_user_avatars_meta_caps( $caps = array(), $cap = '', $user_id = 0, $args = array() ) {

	// What capability is being checked?
	switch ( $cap ) {

		// Reading
		case 'upload_avatar' :
		case 'upload_avatars' :
			$caps = array( 'list_users' );
			break;

		// Editing
		case 'edit_avatar' :
		case 'edit_avatars' :
		case 'edit_others_avatars' :
		case 'edit_avatar_rating' :
		case 'edit_avatar_ratings' :
		case 'edit_others_avatar_ratings' :

		// Deleting
		case 'remove_avatar' :
		case 'delete_avatar' :
		case 'remove_avatars' :
		case 'delete_avatars' :
		case 'remove_others_avatars'  :
		case 'delete_others_avatars'  :
			$caps = array( 'list_users' );
			break;
	}

	return apply_filters( 'wp_user_avatars_meta_caps', $caps, $cap, $user_id, $args );
}
