<?php
/**
 * Stored avatar URL scheme regressions.
 *
 * @package WP_User_Avatars
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/avatar-url-functions.php';

/**
 * Verify local URL upgrades do not alter remote storage or metadata.
 */
final class AvatarUrlSchemeTest extends TestCase {
	/**
	 * Reset the WordPress calls and configure an HTTPS site.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['wpua_test']                            = array();
		$GLOBALS['wpua_test']['returns']['get_home_url'] = 'https://example.test';
		$GLOBALS['wpua_test']['returns']['get_site_url'] = 'https://example.test/wordpress';
		$GLOBALS['wpua_test']['returns']['get_option']   = 'G';
	}

	/**
	 * Cached direct uploads retain the original record but return an HTTPS URL.
	 *
	 * @return void
	 */
	public function test_cached_upload_upgrades_after_https_migration(): void {
		$this->set_avatar(
			array(
				'full' => 'http://example.test/full.jpg',
				96     => 'http://example.test/avatar-96.jpg',
			)
		);
		$this->assertSame( 'https://example.test/avatar-96.jpg', wp_user_avatars_get_local_avatar_url( 7, 96 ) );
		$this->assertArrayNotHasKey( 'update_user_meta', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Full-size fallbacks also upgrade when dynamic resizing is disabled.
	 *
	 * @return void
	 */
	public function test_full_size_fallback_upgrades_without_resizing(): void {
		$this->set_avatar( array( 'full' => 'http://example.test/full.jpg' ) );
		$GLOBALS['wpua_test']['returns']['apply_filters:wp_user_avatars_dynamic_resize'] = false;
		$this->assertSame( 'https://example.test/full.jpg', wp_user_avatars_get_local_avatar_url( 7, 96 ) );
	}

	/**
	 * External hosts, different ports, HTTPS URLs and HTTP sites stay unchanged.
	 *
	 * @return void
	 */
	public function test_external_urls_and_http_sites_are_preserved(): void {
		foreach ( array( 'http://cdn.example.test/avatar.jpg', 'http://example.test:8080/avatar.jpg', 'https://example.test/avatar.jpg', 'http://example.test.evil.test/avatar.jpg' ) as $url ) {
			$this->set_avatar(
				array(
					'full' => $url,
					96     => $url,
				)
			);
			$this->assertSame( $url, wp_user_avatars_get_local_avatar_url( 7, 96 ) );
		}
		$GLOBALS['wpua_test']['returns']['get_home_url'] = 'http://example.test';
		$GLOBALS['wpua_test']['returns']['get_site_url'] = 'http://example.test/wordpress';
		$this->set_avatar(
			array(
				'full' => 'http://example.test/avatar.jpg',
				96     => 'http://example.test/avatar.jpg',
			)
		);
		$this->assertSame( 'http://example.test/avatar.jpg', wp_user_avatars_get_local_avatar_url( 7, 96 ) );
	}

	/**
	 * Streamed media uses the owning site's context and preserves CDN URLs.
	 *
	 * @return void
	 */
	public function test_streamed_media_upgrades_local_urls_and_restores_context(): void {
		$this->set_avatar(
			array(
				'full'     => 'http://example.test/full.jpg',
				'media_id' => 42,
				'site_id'  => 4,
			)
		);
		$GLOBALS['wpua_test']['returns']['is_multisite']      = true;
		$GLOBALS['wpua_test']['returns']['get_attached_file'] = 's3://bucket/avatar.jpg';
		foreach ( array(
			'http://example.test/avatar.jpg'     => 'https://example.test/avatar.jpg',
			'http://cdn.example.test/avatar.jpg' => 'http://cdn.example.test/avatar.jpg',
		) as $url => $expected ) {
			$GLOBALS['wpua_test']['returns']['wp_get_attachment_image_url'] = $url;
			$this->assertSame( $expected, wp_user_avatars_get_local_avatar_url( 7, 96 ) );
		}
		$this->assertCount( 2, $GLOBALS['wpua_test']['calls']['switch_to_blog'] );
		$this->assertCount( 2, $GLOBALS['wpua_test']['calls']['restore_current_blog'] );
	}

	/**
	 * Local media with an existing size passes through the same correction.
	 *
	 * @return void
	 */
	public function test_local_media_cached_size_upgrades(): void {
		$this->set_avatar(
			array(
				'full'     => 'http://example.test/full.jpg',
				96         => 'http://example.test/avatar-96.jpg',
				'media_id' => 42,
			)
		);
		$GLOBALS['wpua_test']['returns']['get_attached_file'] = '/tmp/avatar.jpg';
		$this->assertSame( 'https://example.test/avatar-96.jpg', wp_user_avatars_get_local_avatar_url( 7, 96 ) );
	}

	/**
	 * URL-to-file conversion continues to find the original after migration.
	 *
	 * @return void
	 */
	public function test_new_size_uses_the_local_file_after_https_migration(): void {
		$this->set_avatar( array( 'full' => 'http://example.test/uploads/full.jpg' ) );
		$GLOBALS['wpua_test']['returns']['wp_upload_dir']       = array(
			'baseurl' => 'https://example.test/uploads',
			'basedir' => '/tmp/uploads',
		);
		$GLOBALS['wpua_test']['returns']['wp_get_image_editor'] = new WP_Error( 'test' );
		$this->assertSame( 'https://example.test/uploads/full.jpg', wp_user_avatars_get_local_avatar_url( 7, 96 ) );
		$this->assertSame( array( '/tmp/uploads/full.jpg' ), $GLOBALS['wpua_test']['calls']['wp_get_image_editor'][0] );
	}

	/**
	 * Origin matching preserves ports, queries, fragments, and mapped domains.
	 *
	 * @return void
	 */
	public function test_url_origin_matching_is_precise(): void {
		$this->assertSame( 'https://EXAMPLE.test/avatar.jpg?v=1#image', wp_user_avatars_maybe_secure_url( 'http://EXAMPLE.test/avatar.jpg?v=1#image' ) );
		$this->assertSame( 'http://old-domain.test/avatar.jpg', wp_user_avatars_maybe_secure_url( 'http://old-domain.test/avatar.jpg' ) );
		$GLOBALS['wpua_test']['returns']['get_home_url'] = 'https://example.test:8443';
		$GLOBALS['wpua_test']['returns']['get_site_url'] = 'https://example.test:8443/wordpress';
		$this->assertSame( 'http://example.test:8080/avatar.jpg', wp_user_avatars_maybe_secure_url( 'http://example.test:8080/avatar.jpg' ) );
		$this->assertSame( 'https://example.test:8443/avatar.jpg', wp_user_avatars_maybe_secure_url( 'http://example.test:8443/avatar.jpg' ) );
	}

	/**
	 * Supply the stored avatar without changing its metadata.
	 *
	 * @param array $avatar Stored avatar data.
	 * @return void
	 */
	private function set_avatar( $avatar ): void {
		$GLOBALS['wpua_test']['callbacks']['get_user_meta'] = static function ( $user, $key ) use ( $avatar ) {
			return 'wp_user_avatars' === $key ? $avatar : 'G';
		};
	}
}
