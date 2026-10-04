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
		$this->assertSame( '5497a87bdf34c1dcf9075f4822279caf830720b6cc860323d3a19a8437a2c05f', hash( 'sha256', $script ) );
		$this->assertSame( '10325a4f3ae3e6afa44c96b167e030a4b22af8d8586b0536f02580b1ceecaf61', hash( 'sha256', $style ) );
		$this->assertSame( '6776d64ca275d70460c691144a0afd7a716c07c5d48e464664175b1ea5eac833', hash( 'sha256', $rtl_style ) );
		$this->assertStringContainsString( 'text-align: start;', $style );
		$this->assertStringContainsString( 'padding-inline-start: 0;', $style );
		$this->assertStringContainsString( '#wp-user-avatars-user-settings #wp-user-avatars-ratings fieldset', $style );
		$this->assertStringContainsString( '#wp-user-avatars-user-settings #wp-user-avatars-ratings fieldset', $rtl_style );
		$this->assertStringNotContainsString( "\n\t#wp-user-avatars-ratings fieldset {", $style );
		$this->assertStringNotContainsString( "\n\t#wp-user-avatars-ratings fieldset {", $rtl_style );
		$this->assertSame( 202610040004, wp_user_avatars_get_asset_version() );
	}

	/**
	 * Verify the block metadata and editor assets remain bound to the reviewed files.
	 *
	 * @return void
	 */
	public function test_avatar_editor_block_assets_match_reviewed_files(): void {
		$block_path = dirname( __DIR__ ) . '/wp-user-avatars/blocks/avatar-editor/';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read a local test fixture.
		$metadata_source = file_get_contents( $block_path . 'block.json' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read a local test fixture.
		$editor_script = file_get_contents( $block_path . 'editor.js' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read a local test fixture.
		$editor_style = file_get_contents( $block_path . 'editor.css' );
		$editor_asset = require $block_path . 'editor.asset.php';

		$this->assertNotFalse( $metadata_source );
		$this->assertNotFalse( $editor_script );
		$this->assertNotFalse( $editor_style );

		$metadata = json_decode( $metadata_source, true, 512, JSON_THROW_ON_ERROR );

		$this->assertSame( 'wp-user-avatars/avatar-editor', $metadata['name'] );
		$this->assertSame( 3, $metadata['apiVersion'] );
		$this->assertSame( 'file:./editor.js', $metadata['editorScript'] );
		$this->assertSame( 'file:./editor.css', $metadata['editorStyle'] );
		$this->assertStringContainsString( "registerBlockType( 'wp-user-avatars/avatar-editor'", $editor_script );
		$this->assertStringContainsString( 'wp-user-avatars-block-preview', $editor_style );
		$this->assertSame( 'e9e7c5eccfc3dcfdb8338f82bd6689c77990ac58fda5831d8e4f07e884bacafc', hash( 'sha256', $editor_script ) );
		$this->assertSame( 'f83516df37ddaf452531df2bf9a420263b9aef97f6b14452e35008a432cbc1aa', hash( 'sha256', $editor_style ) );
		$this->assertSame( '37b27dc919216c69417b8beab91200db62d9e825155412a940a14918efa92d3b', hash( 'sha256', $metadata_source ) );
		$this->assertSame( '202610040004', $editor_asset['version'] );
		$this->assertSame(
			array( 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-element', 'wp-i18n' ),
			$editor_asset['dependencies']
		);
	}
}
