<?php
/**
 * Test doubles for wpDiscuz compatibility.
 *
 * @package WP_User_Avatars
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
/**
 * Provide the wpDiscuz singleton test double.
 *
 * @return mixed
 */
function wpDiscuz() {
	return wpua_test_call( __FUNCTION__, func_get_args() );
}
// phpcs:enable WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
