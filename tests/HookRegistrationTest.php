<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HookRegistrationTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['wpua_test'] = array();
	}

	public function test_profile_integration_hooks_are_registered(): void {
		require dirname( __DIR__ ) . '/wp-user-avatars/includes/hooks.php';

		$this->assertContains(
			array( 'wp_user_profiles_add_meta_boxes', 'wp_user_profiles_add_avatar_meta_box', 10, 2 ),
			$GLOBALS['wpua_test']['calls']['add_action']
		);
		$this->assertContains(
			array( 'wp_user_profiles_do_admin_head', 'wp_user_avatars_admin_enqueue_scripts' ),
			$GLOBALS['wpua_test']['calls']['add_action']
		);
		$this->assertContains(
			array( 'wp_enqueue_scripts', 'wp_user_avatars_admin_enqueue_scripts' ),
			$GLOBALS['wpua_test']['calls']['add_action']
		);
		$this->assertContains(
			array( 'wp_enqueue_scripts', 'wp_user_avatars_frontend_enqueue_assets', 20 ),
			$GLOBALS['wpua_test']['calls']['add_action']
		);
		$this->assertContains(
			array( 'wp_user_avatars', 'wp_user_avatars_shortcode' ),
			$GLOBALS['wpua_test']['calls']['add_shortcode']
		);
		$this->assertContains(
			array( 'init', 'wp_user_avatars_register_block' ),
			$GLOBALS['wpua_test']['calls']['add_action']
		);
		$this->assertContains(
			array( 'template_redirect', 'wp_user_avatars_frontend_form_handler' ),
			$GLOBALS['wpua_test']['calls']['add_action']
		);
		$this->assertContains(
			array( 'wp_ajax_upload_wp_user_avatars', 'wp_user_avatars_ajax_upload' ),
			$GLOBALS['wpua_test']['calls']['add_action']
		);
	}

	/**
	 * Verify the early avatar provider compatibility hook is registered.
	 *
	 * @return void
	 */
	public function test_early_avatar_provider_compatibility_hook_is_registered(): void {
		require dirname( __DIR__ ) . '/wp-user-avatars/includes/hooks.php';

		$this->assertContains(
			array( 'pre_get_avatar_data', 'wp_user_avatars_filter_pre_get_avatar_data', 99, 2 ),
			$GLOBALS['wpua_test']['calls']['add_filter']
		);
	}

	public function test_native_profile_fields_remain_registered(): void {
		require dirname( __DIR__ ) . '/wp-user-avatars/includes/hooks.php';

		$this->assertContains(
			array( 'show_user_profile', 'wp_user_avatars_edit_user_profile' ),
			$GLOBALS['wpua_test']['calls']['add_action']
		);
		$this->assertContains(
			array( 'edit_user_profile', 'wp_user_avatars_edit_user_profile' ),
			$GLOBALS['wpua_test']['calls']['add_action']
		);
	}
}
