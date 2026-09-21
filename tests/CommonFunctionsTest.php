<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CommonFunctionsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['wpua_test'] = array();
	}

	public function test_user_id_resolves_supported_identity_types(): void {
		$GLOBALS['wpua_test']['returns']['get_user_by'] = new WP_User( 17 );

		$this->assertSame( 11, wp_user_avatars_get_user_id( 11 ) );
		$this->assertSame( 17, wp_user_avatars_get_user_id( 'person@example.test' ) );
		$this->assertSame( 23, wp_user_avatars_get_user_id( new WP_User( 23 ) ) );
		$this->assertSame( 29, wp_user_avatars_get_user_id( new WP_Post( 29 ) ) );
		$this->assertSame( 31, wp_user_avatars_get_user_id( new WP_Comment( 31 ) ) );
	}

	/**
	 * A missing user should not make the upload callback fatal.
	 */
	public function test_unique_filename_falls_back_when_user_no_longer_exists(): void {
		$GLOBALS['wp_user_avatars_user_id']             = 17;
		$GLOBALS['wpua_test']['returns']['get_user_by'] = false;

		$this->assertSame(
			'avatar.jpg',
			wp_user_avatars_unique_filename_callback( '/tmp', 'avatar', '.jpg' )
		);
	}

	public function test_local_avatar_uses_cached_size_without_resizing(): void {
		$GLOBALS['wpua_test']['callbacks']['get_user_meta'] = static function ( $user_id, $key ) {
			return 'wp_user_avatars' === $key
				? array( 'full' => 'https://example.test/full.jpg', 96 => 'https://example.test/96.jpg' )
				: 'G';
		};
		$GLOBALS['wpua_test']['returns']['get_option'] = 'G';

		$this->assertSame( 'https://example.test/96.jpg', wp_user_avatars_get_local_avatar_url( 7, 96 ) );
		$this->assertArrayNotHasKey( 'wp_get_image_editor', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	public function test_local_avatar_respects_site_rating(): void {
		$GLOBALS['wpua_test']['callbacks']['get_user_meta'] = static function ( $user_id, $key ) {
			return 'wp_user_avatars' === $key
				? array( 'full' => 'https://example.test/full.jpg' )
				: 'R';
		};
		$GLOBALS['wpua_test']['returns']['get_option'] = 'PG';

		$this->assertNull( wp_user_avatars_get_local_avatar_url( 7, 96 ) );
	}

	public function test_avatar_filter_preserves_forced_default(): void {
		$this->assertSame(
			'https://secure.gravatar.com/avatar/hash',
			wp_user_avatars_filter_get_avatar_url(
				'https://secure.gravatar.com/avatar/hash',
				7,
				array( 'force_default' => true, 'size' => 96 )
			)
		);
		$this->assertArrayNotHasKey( 'get_user_meta', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	public function test_attachment_avatar_preserves_media_and_site_identity(): void {
		$GLOBALS['wpua_test']['returns']['get_user_meta']          = array();
		$GLOBALS['wpua_test']['returns']['get_current_blog_id']    = 4;
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url'] = 'https://example.test/avatar.jpg';

		wp_user_avatars_update_avatar( 7, 42 );

		$this->assertSame(
			array( 7, 'wp_user_avatars', array( 'media_id' => 42, 'site_id' => 4, 'full' => 'https://example.test/avatar.jpg' ) ),
			$GLOBALS['wpua_test']['calls']['update_user_meta'][0]
		);
	}

	/**
	 * A deleted attachment should not leave an empty avatar record.
	 */
	public function test_missing_attachment_does_not_store_an_empty_avatar_url(): void {
		$GLOBALS['wpua_test']['returns']['get_user_meta']         = array();
		$GLOBALS['wpua_test']['returns']['get_current_blog_id']   = 4;
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url'] = false;

		wp_user_avatars_update_avatar( 7, 42 );

		$this->assertArrayNotHasKey( 'update_user_meta', $GLOBALS['wpua_test']['calls'] );
	}

	public function test_generated_avatar_is_deleted_through_wordpress_api(): void {
		$file = tempnam( sys_get_temp_dir(), 'wpua-' );
		$this->assertNotFalse( $file );

		$directory = dirname( $file );
		$filename  = basename( $file );
		$GLOBALS['wpua_test']['returns']['get_user_meta'] = array(
			'full' => 'https://example.test/uploads/' . $filename,
		);
		$GLOBALS['wpua_test']['returns']['wp_upload_dir'] = array(
			'baseurl' => 'https://example.test/uploads',
			'basedir' => $directory,
		);

		try {
			wp_user_avatars_delete_avatar( 7 );

			$this->assertSame( array( array( $file ) ), $GLOBALS['wpua_test']['calls']['wp_delete_file'] );
		} finally {
			if ( file_exists( $file ) ) {
				unlink( $file );
			}
		}
	}

	public function test_blocking_gravatar_replaces_remote_defaults(): void {
		$GLOBALS['wpua_test']['returns']['get_option'] = true;

		$defaults = wp_user_avatars_avatar_defaults( array( 'mystery' => 'Mystery Person', 'retro' => 'Retro' ) );

		$this->assertSame( array( wp_user_avatars_get_mystery_url() => 'Mystery Person', 'blank' => 'Blank' ), $defaults );
	}
}
