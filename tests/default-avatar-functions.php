<?php
/**
 * Test doubles for custom default-avatar settings.
 *
 * @package WP_User_Avatars
 */

declare(strict_types=1);

/**
 * Provide the register_setting test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function register_setting( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}

/**
 * Provide the add_settings_field test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function add_settings_field( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}

/**
 * Provide the has_filter test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function has_filter( ...$arguments ) {
	$result = wpua_test_call( __FUNCTION__, $arguments );
	return null === $result ? 10 : $result;
}

/**
 * Provide the absint test double.
 *
 * @param mixed $value Test input.
 *
 * @return int
 */
function absint( $value ) {
	return abs( (int) $value );
}

/**
 * Provide the wp_attachment_is_image test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return bool
 */
function wp_attachment_is_image( ...$arguments ) {
	return (bool) wpua_test_call( __FUNCTION__, $arguments );
}

/**
 * Provide the wp_get_attachment_metadata test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function wp_get_attachment_metadata( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}

/**
 * Provide the add_settings_error test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function add_settings_error( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}

/**
 * Provide the update_option test double.
 *
 * @param mixed ...$arguments Test input.
 *
 * @return mixed
 */
function update_option( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}
