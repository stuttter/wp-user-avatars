<?php
/**
 * Avatar editor asset regression tests.
 *
 * @package WP_User_Avatars
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Verify assets and Media Library access on bbPress profile screens.
 */
final class AdminFunctionsTest extends TestCase {
	/**
	 * Reset the recorded WordPress calls.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['wpua_test']                                       = array();
		$GLOBALS['wpua_test']['returns']['bbp_is_single_user_edit'] = true;
		$GLOBALS['wpua_test']['returns']['bbp_get_displayed_user_id'] = 7;
	}

	/**
	 * Verify bbpress profile loads avatar assets and media for authorized users.
	 *
	 * @return void
	 */
	public function test_bbpress_profile_loads_avatar_assets_and_media_for_authorized_users(): void {
		$GLOBALS['wpua_test']['returns']['current_user_can'] = true;

		wp_user_avatars_admin_enqueue_scripts();

		$this->assertArrayHasKey( 'wp_enqueue_media', $GLOBALS['wpua_test']['calls'] );
		$this->assertArrayHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] );
		$this->assertArrayHasKey( 'wp_enqueue_style', $GLOBALS['wpua_test']['calls'] );
		$this->assertSame(
			'https://example.test/wp-admin/admin-ajax.php',
			$GLOBALS['wpua_test']['calls']['wp_localize_script'][0][2]['ajaxUrl']
		);
	}

	/**
	 * Verify bbpress profile does not load media without selection capability.
	 *
	 * @return void
	 */
	public function test_bbpress_profile_does_not_load_media_without_selection_capability(): void {
		$GLOBALS['wpua_test']['returns']['current_user_can'] = false;

		wp_user_avatars_admin_enqueue_scripts();

		$this->assertArrayNotHasKey( 'wp_enqueue_media', $GLOBALS['wpua_test']['calls'] );
		$this->assertArrayHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] );
	}

	/**
	 * Verify the avatar script leaves the profile form's save action in place.
	 *
	 * @return void
	 */
	public function test_avatar_script_does_not_move_the_profile_save_action(): void {
		$script = file_get_contents( dirname( __DIR__ ) . '/wp-user-avatars/assets/js/user-avatars.js' );

		$this->assertNotFalse( $script );
		$this->assertStringNotContainsString( '$( \'#your-profile p.submit\' )', $script );
		$this->assertStringNotContainsString( '$( \'#wp-user-avatars-user-settings p.submit\' )', $script );
	}
}
