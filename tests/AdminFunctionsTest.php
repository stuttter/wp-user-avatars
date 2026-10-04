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
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read a local test fixture.
		$script = file_get_contents( dirname( __DIR__ ) . '/wp-user-avatars/assets/js/user-avatars.js' );

		$this->assertNotFalse( $script );
		$this->assertStringNotContainsString( '$( \'#your-profile p.submit\' )', $script );
		$this->assertStringNotContainsString( '$( \'#wp-user-avatars-user-settings p.submit\' )', $script );
	}

	/**
	 * Verify each script action stays inside the editor that triggered it.
	 *
	 * @return void
	 */
	public function test_avatar_script_scopes_actions_to_each_editor(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read a local test fixture.
		$script = file_get_contents( dirname( __DIR__ ) . '/wp-user-avatars/assets/js/user-avatars.js' );

		$this->assertNotFalse( $script );
		$this->assertStringContainsString( "$( '.wp-user-avatars-editor' ).each", $script );
		$this->assertStringContainsString( '$editor.find( \'.wp-user-avatars-upload\' )', $script );
		$this->assertStringNotContainsString( "$( '#wp-user-avatars-media' )", $script );
		$this->assertStringNotContainsString( "$( '#wp-user-avatars-remove' )", $script );
		$this->assertStringNotContainsString( "$( '#wp-user-avatars' )", $script );
	}

	/**
	 * Verify changes to the editor assets also invalidate production caches.
	 *
	 * @return void
	 */
	public function test_editor_asset_version_matches_reviewed_files(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read a local test fixture.
		$script = file_get_contents( dirname( __DIR__ ) . '/wp-user-avatars/assets/js/user-avatars.js' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read a local test fixture.
		$style = file_get_contents( dirname( __DIR__ ) . '/wp-user-avatars/assets/css/user-avatars.css' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read a local test fixture.
		$rtl_style = file_get_contents( dirname( __DIR__ ) . '/wp-user-avatars/assets/css/user-avatars-rtl.css' );

		$this->assertNotFalse( $script );
		$this->assertNotFalse( $style );
		$this->assertNotFalse( $rtl_style );
		$this->assertSame( '2e5b8b306afb22d0a15f05fd72f03932cc7493e994a9579168e9273fbde872bc', hash( 'sha256', $script ) );
		$this->assertSame( '7710b916d507e11ddb65fd7e650cb6f4e45312889039b37f57b238d9e36aef54', hash( 'sha256', $style ) );
		$this->assertSame( '6776d64ca275d70460c691144a0afd7a716c07c5d48e464664175b1ea5eac833', hash( 'sha256', $rtl_style ) );
		$this->assertStringContainsString( 'text-align: start;', $style );
		$this->assertStringContainsString( 'padding-inline-start: 0;', $style );
		$this->assertStringContainsString( '#wp-user-avatars-user-settings #wp-user-avatars-ratings fieldset', $style );
		$this->assertStringContainsString( '#wp-user-avatars-user-settings #wp-user-avatars-ratings fieldset', $rtl_style );
		$this->assertStringNotContainsString( "\n\t#wp-user-avatars-ratings fieldset {", $style );
		$this->assertStringNotContainsString( "\n\t#wp-user-avatars-ratings fieldset {", $rtl_style );
		$this->assertSame( 202610040002, wp_user_avatars_get_asset_version() );
	}
}
