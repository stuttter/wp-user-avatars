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

		// Upload
		case 'upload_avatar' :

		// Edit
		case 'edit_avatar' :
		case 'edit_avatar_rating' :

		// Delete
		case 'remove_avatar' :
		case 'delete_avatar' :
			if ( user_can( $user_id, 'edit_user', $args[0] ) ) {
				$caps = array();
			}
			break;
	}

	return apply_filters( 'wp_user_avatars_meta_caps', $caps, $cap, $user_id, $args );
}
