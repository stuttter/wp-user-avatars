<?php

/**
 * Exercise the public avatar metadata and filtering contract in WordPress.
 *
 * This file is loaded by the centrally maintained integration runner after the
 * production plugin build has been activated.
 *
 * @package WP_User_AvatarsTests
 */

defined( 'ABSPATH' ) || exit;

$assert = static function ( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};

$user_id = 0;

try {
	$assert( is_multisite(), 'WP User Avatars must use the multisite integration profile.' );
	$assert( function_exists( 'wp_user_avatars_update_avatar' ), 'The production plugin did not load.' );

	$created_user_id = wp_insert_user(
		array(
			'user_login' => 'portfolio-avatar-' . strtolower( wp_generate_password( 12, false, false ) ),
			'user_pass'  => wp_generate_password( 24, true, true ),
			'user_email' => 'avatar-' . wp_generate_uuid4() . '@example.test',
			'role'       => 'subscriber',
		)
	);
	$assert( ! is_wp_error( $created_user_id ), 'WordPress could not create the smoke-test user.' );
	$user_id = (int) $created_user_id;

	$avatar_url = 'https://example.test/uploads/portfolio-avatar.jpg';
	wp_user_avatars_update_avatar( $user_id, $avatar_url );
	$assert(
		array( 'full' => $avatar_url ) === get_user_meta( $user_id, 'wp_user_avatars', true ),
		'WordPress could not persist the avatar metadata.'
	);

	$disable_resize = static function () { return false; };
	add_filter( 'wp_user_avatars_dynamic_resize', $disable_resize );
	try {
		$assert( $avatar_url === get_avatar_url( $user_id, array( 'size' => 96 ) ), 'The local avatar did not filter get_avatar_url().' );
	} finally {
		remove_filter( 'wp_user_avatars_dynamic_resize', $disable_resize );
	}

	wp_user_avatars_delete_avatar( $user_id );
	$assert( '' === get_user_meta( $user_id, 'wp_user_avatars', true ), 'Avatar deletion left user metadata behind.' );
} finally {
	if ( $user_id ) {
		wpmu_delete_user( $user_id );
	}
}
