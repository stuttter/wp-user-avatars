<?php
/**
 * Request and response doubles for avatar rating tests.
 *
 * @package WP_User_Avatars
 */

/**
 * Verify request nonces through the recorded test response.
 *
 * @param mixed ...$arguments Nonce verification arguments.
 * @return mixed
 */
function wp_verify_nonce( ...$arguments ) {
	return wpua_test_call( __FUNCTION__, $arguments );
}

/**
 * Stop an AJAX response without terminating the test process.
 *
 * @param mixed $data Response data.
 * @return void
 * @throws RuntimeException When the AJAX response is complete.
 */
function wp_send_json_success( $data ) {
	wpua_test_call( __FUNCTION__, array( $data ) );
	throw new RuntimeException( 'AJAX response complete' );
}
