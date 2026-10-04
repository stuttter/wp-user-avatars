<?php
/**
 * Front-end avatar editor regression tests.
 *
 * @package WP_User_Avatars
 */

// phpcs:disable WordPress.Security.NonceVerification.Missing -- Tests populate request globals and explicitly stub nonce verification.

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/profile-rating-functions.php';

/**
 * Verify the shortcode and non-JavaScript form preserve permission boundaries.
 */
final class FrontendEditorTest extends TestCase {
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
	 * Saved post global.
	 *
	 * @var mixed
	 */
	private $saved_post_object;

	/**
	 * Reset request and WordPress test state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->saved_post        = $_POST;
		$this->saved_files       = $_FILES;
		$this->saved_post_object = $GLOBALS['post'] ?? null;
		$_POST                   = array();
		$_FILES                  = array();
		$user                    = new WP_User( 7 );
		$user->wp_user_avatars = array( 'full' => 'https://example.test/avatar.jpg' );
		$user->wp_user_avatars_rating = 'G';
		$GLOBALS['wpua_test'] = array(
			'returns' => array(
				'is_user_logged_in'  => true,
				'get_current_user_id' => 7,
				'get_userdata'        => $user,
				'current_user_can'    => true,
				'wp_verify_nonce'     => true,
				'get_avatar'          => '<img src="https://example.test/avatar.jpg" alt="" />',
				'get_user_meta'       => array( 'full' => 'https://example.test/avatar.jpg' ),
				'wp_handle_upload'    => array(
					'file' => '/tmp/avatar.jpg',
					'url'  => 'https://example.test/avatar.jpg',
				),
				'wp_upload_dir'       => array(
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
		$_POST           = $this->saved_post;
		$_FILES          = $this->saved_files;
		$GLOBALS['post'] = $this->saved_post_object;
	}

	/**
	 * Logged-out visitors receive a stable prompt without loading editor assets.
	 *
	 * @return void
	 */
	public function test_logged_out_shortcode_does_not_enqueue_editor_assets(): void {
		$GLOBALS['wpua_test']['returns']['is_user_logged_in'] = false;

		$this->assertStringContainsString( 'log in', strtolower( wp_user_avatars_shortcode() ) );
		$this->assertArrayNotHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] ?? array() );
		$this->assertArrayNotHasKey( 'wp_enqueue_style', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Multiple instances use unique controls while retaining one shared contract.
	 *
	 * @return void
	 */
	public function test_shortcode_renders_multiple_scoped_editor_instances(): void {
		$first  = wp_user_avatars_shortcode();
		$second = wp_user_avatars_shortcode();

		$this->assertStringContainsString( 'class="wp-user-avatars-editor"', $first );
		$this->assertStringContainsString( 'enctype="multipart/form-data"', $first );
		$this->assertStringContainsString( 'name="wp_user_avatars_frontend_action"', $first );
		$this->assertStringContainsString( 'id="wp-user-avatars"', $first );
		$this->assertStringContainsString( 'id="wp-user-avatars-2"', $second );
		$this->assertStringContainsString( 'type="button"', $first );
		$this->assertStringContainsString( 'data-user-id="7"', $first );
		$this->assertStringContainsString( 'data-upload-nonce=', $first );
		$this->assertArrayHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] );
		$this->assertArrayHasKey( 'wp_enqueue_style', $GLOBALS['wpua_test']['calls'] );
	}

	/**
	 * Subscribers may upload their own avatar without receiving Media Library access.
	 *
	 * @return void
	 */
	public function test_shortcode_keeps_media_library_hidden_without_selection_capability(): void {
		$GLOBALS['wpua_test']['callbacks']['current_user_can'] = static function ( $capability ) {
			return 'select_avatar' !== $capability;
		};

		$output = wp_user_avatars_shortcode();

		$this->assertStringContainsString( 'class="standard-text wp-user-avatars-upload"', $output );
		$this->assertStringNotContainsString( 'wp-user-avatars-media', $output );
		$this->assertArrayNotHasKey( 'wp_enqueue_media', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * The normal page preflight enqueues assets only when the shortcode is present.
	 *
	 * @return void
	 */
	public function test_frontend_asset_preflight_requires_shortcode(): void {
		$post               = new WP_Post();
		$post->post_content = 'Plain page content.';
		$GLOBALS['post']     = $post;

		wp_user_avatars_frontend_enqueue_assets();
		$this->assertArrayNotHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] ?? array() );

		$post->post_content = 'Before [wp_user_avatars] after.';
		wp_user_avatars_frontend_enqueue_assets();
		$this->assertArrayHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] );
	}

	/**
	 * The non-JavaScript upload path always targets the signed-in user.
	 *
	 * @return void
	 */
	public function test_frontend_upload_targets_current_user(): void {
		$_POST = array(
			'wp_user_avatars_frontend_action' => 'update',
			'_wp_user_avatars_frontend_nonce' => 'valid',
			'wp_user_avatars_rating'          => 'PG',
			'user_id'                         => 99,
		);
		$_FILES = array( 'wp-user-avatars' => array( 'name' => 'avatar.jpg' ) );

		$this->assertTrue( wp_user_avatars_process_frontend_form() );
		$this->assertContains(
			array( 7, 'wp_user_avatars_rating', 'PG' ),
			$GLOBALS['wpua_test']['calls']['update_user_meta']
		);
	}

	/**
	 * Removal remains subject to the existing per-user capability check.
	 *
	 * @return void
	 */
	public function test_frontend_removal_fails_without_permission(): void {
		$_POST = array(
			'wp_user_avatars_frontend_action' => 'remove',
			'_wp_user_avatars_frontend_nonce' => 'valid',
		);
		$GLOBALS['wpua_test']['returns']['current_user_can'] = false;

		$result = wp_user_avatars_process_frontend_form();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'forbidden', $result->get_error_code() );
		$this->assertArrayNotHasKey( 'delete_user_meta', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Authorized removal targets the signed-in user.
	 *
	 * @return void
	 */
	public function test_frontend_removal_targets_current_user(): void {
		$_POST = array(
			'wp_user_avatars_frontend_action' => 'remove',
			'_wp_user_avatars_frontend_nonce' => 'valid',
			'user_id'                         => 99,
		);

		$this->assertTrue( wp_user_avatars_process_frontend_form() );
		$this->assertContains(
			array( 7, 'wp_user_avatars' ),
			$GLOBALS['wpua_test']['calls']['delete_user_meta']
		);
		$this->assertContains(
			array( 7, 'wp_user_avatars_rating' ),
			$GLOBALS['wpua_test']['calls']['delete_user_meta']
		);
	}

	/**
	 * Failed uploads remain visible when the shortcode renders the response.
	 *
	 * @return void
	 */
	public function test_frontend_upload_error_is_rendered_without_javascript(): void {
		$_POST = array(
			'wp_user_avatars_frontend_action' => 'update',
			'_wp_user_avatars_frontend_nonce' => 'valid',
		);
		$_FILES = array( 'wp-user-avatars' => array( 'name' => 'avatar.jpg' ) );
		$GLOBALS['wpua_test']['returns']['wp_handle_upload'] = array( 'error' => 'Upload failed safely.' );

		$result = wp_user_avatars_process_frontend_form();
		$this->assertInstanceOf( WP_Error::class, $result );
		$GLOBALS['wp_user_avatars_frontend_error'] = $result;

		$output = wp_user_avatars_shortcode();

		$this->assertStringContainsString( 'role="alert"', $output );
		$this->assertStringContainsString( 'Upload failed safely.', $output );
		unset( $GLOBALS['wp_user_avatars_frontend_error'] );
	}

	/**
	 * Invalid nonces cannot update avatar metadata.
	 *
	 * @return void
	 */
	public function test_frontend_upload_rejects_invalid_nonce(): void {
		$_POST = array(
			'wp_user_avatars_frontend_action' => 'update',
			'_wp_user_avatars_frontend_nonce' => 'invalid',
		);
		$GLOBALS['wpua_test']['returns']['wp_verify_nonce'] = false;

		$result = wp_user_avatars_process_frontend_form();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_nonce', $result->get_error_code() );
		$this->assertArrayNotHasKey( 'update_user_meta', $GLOBALS['wpua_test']['calls'] ?? array() );
	}
}
