<?php

/**
 * User Avatar Hooks
 *
 * @since 0.1.0
 *
 * @package User/Avatar/Hooks
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Register Settings
add_action( 'admin_init', 'wp_user_avatars_register_settings' );

// Caps
add_filter( 'map_meta_cap', 'wp_user_avatars_meta_caps', 10, 4 );

// Scripts
add_action( 'admin_enqueue_scripts', 'wp_user_avatars_admin_enqueue_scripts' );

// User profile
add_action( 'show_user_profile',        'wp_user_avatars_edit_user_profile'        );
add_action( 'edit_user_profile',        'wp_user_avatars_edit_user_profile'        );
add_action( 'user_edit_form_tag',       'wp_user_avatars_user_edit_form_tag'       );
add_action( 'personal_options_update',  'wp_user_avatars_edit_user_profile_update' );
add_action( 'edit_user_profile_update', 'wp_user_avatars_edit_user_profile_update' );

// Error output
add_action( 'user_profile_update_errors', 'wp_user_avatars_profile_update_errors', 10, 3 );

// Avatar defaults
add_filter( 'avatar_defaults', 'wp_user_avatars_avatar_defaults' );

// Filter avatars
add_filter( 'get_avatar', 'wp_user_avatars_filter_get_avatar', 10, 4 );

// Ajax
add_action( 'wp_ajax_assign_wp_user_avatars_media', 'wp_user_avatars_ajax_assign_media'     );
add_action( 'wp_ajax_remove_wp_user_avatars',       'wp_user_avatars_action_remove_avatars' );
add_action( 'admin_action_remove-wp-user-avatars',  'wp_user_avatars_action_remove_avatars' );
