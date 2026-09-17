<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CapabilitiesTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['wpua_test'] = array();
	}

	public function test_avatar_capability_is_granted_when_user_can_edit_target(): void {
		$GLOBALS['wpua_test']['returns']['user_can'] = true;

		$this->assertSame( array(), wp_user_avatars_meta_caps( array( 'do_not_allow' ), 'edit_avatar', 3, array( 7 ) ) );
		$this->assertSame( array( 3, 'edit_user', 7 ), $GLOBALS['wpua_test']['calls']['user_can'][0] );
	}

	public function test_avatar_capability_is_preserved_when_user_cannot_edit_target(): void {
		$GLOBALS['wpua_test']['returns']['user_can'] = false;

		$this->assertSame(
			array( 'do_not_allow' ),
			wp_user_avatars_meta_caps( array( 'do_not_allow' ), 'remove_avatar', 3, array( 7 ) )
		);
	}

	public function test_unrelated_capability_is_not_remapped(): void {
		$this->assertSame( array( 'read' ), wp_user_avatars_meta_caps( array( 'read' ), 'read', 3, array() ) );
		$this->assertArrayNotHasKey( 'user_can', $GLOBALS['wpua_test']['calls'] ?? array() );
	}
}
