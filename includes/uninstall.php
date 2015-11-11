<?php

/**
 * User Profile Avatar Uninstall
 * 
 * @package Plugins/User/Avatars/Uninstall
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Hokey uninstall routine to remove all avatars
 *
 * @since 0.1.0
 */
function wp_user_avatars_uninstall() {

	// Get users of blog
	$users = get_users( array(
		'meta_key' => 'wp_user_avatars',
		'fields'   => 'ids'
	) );

	// Delete all avatars
	foreach ( $users as $user_id ) {
		wp_user_avatars_delete_avatar( $user_id );
	}

	// Cleanup options
	delete_option( 'wp_user_avatars' );
}
