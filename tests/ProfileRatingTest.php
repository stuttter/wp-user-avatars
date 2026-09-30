<?php
/**
 * Profile and AJAX avatar rating regressions.
 *
 * @package WP_User_Avatars
 */

// phpcs:disable WordPress.Security.NonceVerification.Missing -- Tests populate request globals and explicitly stub nonce verification.

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/profile-rating-functions.php';

/**
 * Verify request handlers preserve ratings and permission boundaries.
 */
final class ProfileRatingTest extends TestCase {
	/**
	 * Saved request data.
	 *
	 * @var array
	 */
	private $saved_post;
	/**
	 * Saved uploaded files.
	 *
	 * @var array
	 */
	private $saved_files;

	/**
	 * Set up successful upload and nonce responses.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->saved_post     = $_POST;
		$this->saved_files    = $_FILES;
		$_POST                = array();
		$_FILES               = array();
		$GLOBALS['wpua_test'] = array(
			'returns' => array(
				'wp_verify_nonce'  => true,
				'current_user_can' => true,
				'get_user_meta'    => array( 'full' => 'https://example.test/avatar.jpg' ),
				'wp_handle_upload' => array(
					'file' => '/tmp/avatar.jpg',
					'url'  => 'https://example.test/avatar.jpg',
				),
				'get_avatar'       => '<img src="https://example.test/avatar.jpg" />',
				'wp_upload_dir'    => array(
					'baseurl' => 'https://example.test',
					'basedir' => '/tmp',
				),
			),
		);
	}

	/**
	 * Restore request state.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$_POST  = $this->saved_post;
		$_FILES = $this->saved_files;
	}

	/**
	 * Preserve every supported rating through a normal profile save.
	 *
	 * @return void
	 */
	public function test_profile_save_preserves_valid_rating_keys(): void {
		foreach ( array( 'G', 'PG', 'R', 'X' ) as $rating ) {
			$_POST = array(
				'_wp_user_avatars_nonce' => 'valid',
				'wp_user_avatars_rating' => $rating,
			);
			wp_user_avatars_edit_user_profile_update( 7 );
			$this->assertSame( array( 7, 'wp_user_avatars_rating', $rating ), end( $GLOBALS['wpua_test']['calls']['update_user_meta'] ) );
		}
	}

	/**
	 * Preserve every supported rating through an instant upload.
	 *
	 * @return void
	 */
	public function test_ajax_upload_preserves_valid_rating_keys(): void {
		foreach ( array( 'G', 'PG', 'R', 'X' ) as $rating ) {
			$this->upload( $rating );
			$this->assertSame( array( 7, 'wp_user_avatars_rating', $rating ), end( $GLOBALS['wpua_test']['calls']['update_user_meta'] ) );
		}
	}

	/**
	 * Upload permission does not grant permission to edit the rating.
	 *
	 * @return void
	 */
	public function test_ajax_upload_does_not_write_rating_without_permission(): void {
		$GLOBALS['wpua_test']['callbacks']['current_user_can'] = static function ( $cap ) {
			return 'edit_avatar_rating' !== $cap;
		};
		$this->upload( 'X' );
		foreach ( $GLOBALS['wpua_test']['calls']['update_user_meta'] as $call ) {
			$this->assertNotSame( 'wp_user_avatars_rating', $call[1] );
		}
		$this->assertArrayHasKey( 'wp_send_json_success', $GLOBALS['wpua_test']['calls'] );
	}

	/**
	 * Exercise a complete successful upload request.
	 *
	 * @param string $rating Requested rating.
	 * @return void
	 */
	private function upload( $rating ): void {
		$_POST  = array(
			'user_id'  => 7,
			'_wpnonce' => 'valid',
			'rating'   => $rating,
		);
		$_FILES = array( 'wp-user-avatars' => array( 'name' => 'avatar.jpg' ) );
		try {
			wp_user_avatars_ajax_upload();
			$this->fail( 'The upload must return an AJAX response.' );
		} catch ( RuntimeException $error ) {
			$this->assertSame( 'AJAX response complete', $error->getMessage() );
		}
	}
}
