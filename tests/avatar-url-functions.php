<?php
/**
 * Site URL doubles for HTTPS avatar regressions.
 *
 * @package WP_User_Avatars
 */

/**
 * Return the configured owning site's front-end URL.
 *
 * @param mixed ...$arguments Site URL arguments.
 * @return string
 */
function get_home_url( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments ) ?? '';
}

/**
 * Return the configured owning site's WordPress URL.
 *
 * @param mixed ...$arguments Site URL arguments.
 * @return string
 */
function get_site_url( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments ) ?? '';
}
