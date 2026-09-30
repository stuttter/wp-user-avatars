<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CapabilitiesTest extends TestCase {
	/**
	 * Reset the recorded WordPress calls.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['wpua_test'] = array();
	}

	/**
	 * Missing and malformed targets must fail closed without WordPress calls.
	 *
	 * @return void
	 */
	public function test_avatar_capabilities_reject_invalid_targets(): void {
		foreach ( array( 'select_avatar', 'upload_avatar', 'edit_avatar', 'edit_avatar_rating', 'remove_avatar', 'delete_avatar' ) as $cap ) {
			foreach ( array( array(), array( null ), array( 0 ), array( -1 ), array( '' ), array( 'invalid' ), array( true ), array( 1.5 ), array( array() ), array( new stdClass() ) ) as $args ) {
				$this->assertSame( array( 'do_not_allow' ), wp_user_avatars_meta_caps( array( $cap ), $cap, 3, $args ) );
			}
		}
		$this->assertArrayNotHasKey( 'user_can', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Valid numeric IDs keep the existing self and other-user permission check.
	 *
	 * @return void
	 */
	public function test_avatar_capabilities_preserve_target_checks(): void {
		$GLOBALS['wpua_test']['callbacks']['user_can'] = static function ( $actor, $cap, $target = null ) {
			return 'upload_files' === $cap || $actor === $target;
		};
		foreach ( array( 'select_avatar', 'upload_avatar', 'edit_avatar', 'edit_avatar_rating', 'remove_avatar', 'delete_avatar' ) as $cap ) {
			$this->assertSame( array(), wp_user_avatars_meta_caps( array( $cap ), $cap, 3, array( '3' ) ) );
			$this->assertSame( array( $cap ), wp_user_avatars_meta_caps( array( $cap ), $cap, 3, array( 7 ) ) );
		}
	}

	/**
	 * Verify avatar capability is granted when user can edit target.
	 *
	 * @return void
	 */
	public function test_avatar_capability_is_granted_when_user_can_edit_target(): void {
		$GLOBALS['wpua_test']['returns']['user_can'] = true;

		$this->assertSame( array(), wp_user_avatars_meta_caps( array( 'do_not_allow' ), 'edit_avatar', 3, array( 7 ) ) );
		$this->assertSame( array( 3, 'edit_user', 7 ), $GLOBALS['wpua_test']['calls']['user_can'][0] );
	}

	/**
	 * Verify avatar capability is preserved when user cannot edit target.
	 *
	 * @return void
	 */
	public function test_avatar_capability_is_preserved_when_user_cannot_edit_target(): void {
		$GLOBALS['wpua_test']['returns']['user_can'] = false;

		$this->assertSame(
			array( 'do_not_allow' ),
			wp_user_avatars_meta_caps( array( 'do_not_allow' ), 'remove_avatar', 3, array( 7 ) )
		);
	}

	/**
	 * Verify media selection requires editing and media capabilities.
	 *
	 * @return void
	 */
	public function test_media_selection_requires_editing_and_media_capabilities(): void {
		$GLOBALS['wpua_test']['callbacks']['user_can'] = static function ( $user_id, $capability ) {
			return in_array( $capability, array( 'edit_user', 'upload_files' ), true );
		};

		$this->assertSame( array(), wp_user_avatars_meta_caps( array( 'do_not_allow' ), 'select_avatar', 3, array( 7 ) ) );
		$this->assertSame(
			array( array( 3, 'edit_user', 7 ), array( 3, 'upload_files' ) ),
			$GLOBALS['wpua_test']['calls']['user_can']
		);
	}

	/**
	 * Verify media selection is denied without media capability.
	 *
	 * @return void
	 */
	public function test_media_selection_is_denied_without_media_capability(): void {
		$GLOBALS['wpua_test']['callbacks']['user_can'] = static function ( $user_id, $capability ) {
			return 'edit_user' === $capability;
		};

		$this->assertSame(
			array( 'do_not_allow' ),
			wp_user_avatars_meta_caps( array( 'do_not_allow' ), 'select_avatar', 3, array( 7 ) )
		);
	}

	/**
	 * Verify unrelated capability is not remapped.
	 *
	 * @return void
	 */
	public function test_unrelated_capability_is_not_remapped(): void {
		$this->assertSame( array( 'read' ), wp_user_avatars_meta_caps( array( 'read' ), 'read', 3, array() ) );
		$this->assertArrayNotHasKey( 'user_can', $GLOBALS['wpua_test']['calls'] ?? array() );
	}
}
