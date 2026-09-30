<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AdminFunctionsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['wpua_test'] = array();
		$GLOBALS['wpua_test']['returns']['bbp_is_single_user_edit']    = true;
		$GLOBALS['wpua_test']['returns']['bbp_get_displayed_user_id'] = 7;
	}

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

	public function test_bbpress_profile_does_not_load_media_without_selection_capability(): void {
		$GLOBALS['wpua_test']['returns']['current_user_can'] = false;

		wp_user_avatars_admin_enqueue_scripts();

		$this->assertArrayNotHasKey( 'wp_enqueue_media', $GLOBALS['wpua_test']['calls'] );
		$this->assertArrayHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] );
	}
}
