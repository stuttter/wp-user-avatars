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
require_once __DIR__ . '/frontend-editor-functions.php';

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
		$this->saved_post             = $_POST;
		$this->saved_files            = $_FILES;
		$this->saved_post_object      = $GLOBALS['post'] ?? null;
		$_POST                        = array();
		$_FILES                       = array();
		$user                         = new WP_User( 7 );
		$user->wp_user_avatars        = array( 'full' => 'https://example.test/avatar.jpg' );
		$user->wp_user_avatars_rating = 'G';
		$GLOBALS['wpua_test']         = array(
			'returns' => array(
				'is_user_logged_in'   => true,
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
	 * The block uses the same current-user editor inside block wrapper markup.
	 *
	 * @return void
	 */
	public function test_block_reuses_the_current_user_editor(): void {
		$output = wp_user_avatars_render_block();

		$this->assertStringContainsString( 'wp-block-wp-user-avatars-avatar-editor', $output );
		$this->assertStringContainsString( 'wp-user-avatars-avatar-editor-block', $output );
		$this->assertStringNotContainsString( 'wp-user-avatars-block-heading', $output );
		$this->assertStringNotContainsString( 'wp-user-avatars-block-description', $output );
		$this->assertStringContainsString( 'class="wp-user-avatars-editor"', $output );
		$this->assertStringContainsString( 'data-user-id="7"', $output );
	}

	/**
	 * Optional block copy is rendered only when the author supplies it.
	 *
	 * @return void
	 */
	public function test_block_renders_optional_author_copy(): void {
		$output = wp_user_avatars_render_block(
			array(
				'heading'     => 'Choose your avatar',
				'description' => 'Use a square image that looks good at small sizes.',
			)
		);

		$this->assertStringContainsString( '<h2 class="wp-user-avatars-block-heading">Choose your avatar</h2>', $output );
		$this->assertStringContainsString( '<p class="wp-user-avatars-block-description">Use a square image that looks good at small sizes.</p>', $output );
		$this->assertStringContainsString( 'class="wp-user-avatars-editor"', $output );
	}

	/**
	 * Block copy remains plain text when stored attributes contain markup or breaks.
	 *
	 * @return void
	 */
	public function test_block_normalizes_optional_author_copy_to_plain_text(): void {
		$output = wp_user_avatars_render_block(
			array(
				'heading'     => '<strong>Choose</strong> your avatar',
				'description' => "Use a square image.\nIt works best at small sizes.",
			)
		);

		$this->assertStringContainsString( '>Choose your avatar</h2>', $output );
		$this->assertStringContainsString( '>Use a square image. It works best at small sizes.</p>', $output );
		$this->assertStringNotContainsString( '<strong>', $output );
		$this->assertStringNotContainsString( "image.\nIt", $output );
	}

	/**
	 * The block keeps its wrapper around the logged-out prompt.
	 *
	 * @return void
	 */
	public function test_logged_out_block_retains_styled_wrapper(): void {
		$GLOBALS['wpua_test']['returns']['is_user_logged_in'] = false;

		$output = wp_user_avatars_render_block();

		$this->assertStringContainsString( 'wp-user-avatars-avatar-editor-block', $output );
		$this->assertStringContainsString( 'log in', strtolower( $output ) );
	}

	/**
	 * The block keeps its wrapper around the capability-denied prompt.
	 *
	 * @return void
	 */
	public function test_forbidden_block_retains_styled_wrapper(): void {
		$GLOBALS['wpua_test']['returns']['current_user_can'] = false;

		$output = wp_user_avatars_render_block();

		$this->assertStringContainsString( 'wp-user-avatars-avatar-editor-block', $output );
		$this->assertStringContainsString( 'permission', strtolower( $output ) );
	}

	/**
	 * The empty avatar state hides the complete rating row without selector support.
	 *
	 * @return void
	 */
	public function test_block_hides_empty_avatar_rating_row_in_markup(): void {
		$user                  = $GLOBALS['wpua_test']['returns']['get_userdata'];
		$user->wp_user_avatars = array();

		$output = wp_user_avatars_render_block();

		$this->assertStringContainsString( 'class="wp-user-avatars-rating-row fancy-hidden"', $output );
	}

	/**
	 * The block metadata registers the shared dynamic renderer.
	 *
	 * @return void
	 */
	public function test_avatar_editor_block_registration_uses_metadata_and_dynamic_renderer(): void {
		wp_user_avatars_register_block();

		$this->assertSame(
			dirname( __DIR__ ) . '/wp-user-avatars/blocks/avatar-editor',
			$GLOBALS['wpua_test']['calls']['register_block_type'][0][0]
		);
		$this->assertSame(
			'wp_user_avatars_render_block',
			$GLOBALS['wpua_test']['calls']['register_block_type'][0][1]['render_callback']
		);
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
	 * The page preflight keeps renderer styles available without loading scripts early.
	 *
	 * @return void
	 */
	public function test_frontend_asset_preflight_keeps_renderer_styles_available(): void {
		$post               = new WP_Post();
		$post->post_content = 'Plain page content.';
		$GLOBALS['post']    = $post;

		wp_user_avatars_frontend_enqueue_assets();
		$this->assertArrayNotHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] ?? array() );
		$this->assertArrayNotHasKey( 'wp_enqueue_media', $GLOBALS['wpua_test']['calls'] ?? array() );
		$this->assertArrayHasKey( 'wp_enqueue_style', $GLOBALS['wpua_test']['calls'] );

		$post->post_content = 'Before [wp_user_avatars] after.';
		wp_user_avatars_frontend_enqueue_assets();
		$this->assertArrayHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] );
	}

	/**
	 * Stored block content loads the interactive editor assets before page output.
	 *
	 * @return void
	 */
	public function test_frontend_asset_preflight_recognizes_the_avatar_editor_block(): void {
		$post               = new WP_Post();
		$post->post_content = '<!-- wp:wp-user-avatars/avatar-editor /-->';
		$GLOBALS['post']    = $post;
		$GLOBALS['wpua_test']['returns']['has_block'] = true;

		wp_user_avatars_frontend_enqueue_assets();

		$this->assertArrayHasKey( 'wp_enqueue_style', $GLOBALS['wpua_test']['calls'] );
		$this->assertArrayHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] );
	}

	/**
	 * Stored blocks retain card styles for logged-out visitors without editor scripts.
	 *
	 * @return void
	 */
	public function test_frontend_asset_preflight_styles_logged_out_block_prompt(): void {
		$post               = new WP_Post();
		$post->post_content = '<!-- wp:wp-user-avatars/avatar-editor /-->';
		$GLOBALS['post']    = $post;

		$GLOBALS['wpua_test']['returns']['has_block'] = true;

		$GLOBALS['wpua_test']['returns']['is_user_logged_in'] = false;

		wp_user_avatars_frontend_enqueue_assets();

		$this->assertArrayHasKey( 'wp_enqueue_style', $GLOBALS['wpua_test']['calls'] );
		$this->assertArrayNotHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] ?? array() );
		$this->assertArrayNotHasKey( 'wp_enqueue_media', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Stored blocks retain card styles for denied users without editor scripts.
	 *
	 * @return void
	 */
	public function test_frontend_asset_preflight_styles_forbidden_block_prompt(): void {
		$post               = new WP_Post();
		$post->post_content = '<!-- wp:wp-user-avatars/avatar-editor /-->';
		$GLOBALS['post']    = $post;

		$GLOBALS['wpua_test']['returns']['has_block'] = true;

		$GLOBALS['wpua_test']['returns']['current_user_can'] = false;

		wp_user_avatars_frontend_enqueue_assets();

		$this->assertArrayHasKey( 'wp_enqueue_style', $GLOBALS['wpua_test']['calls'] );
		$this->assertArrayNotHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] ?? array() );
		$this->assertArrayNotHasKey( 'wp_enqueue_media', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Logged-out visitors do not receive editor assets during the page preflight.
	 *
	 * @return void
	 */
	public function test_frontend_asset_preflight_skips_logged_out_visitors(): void {
		$post               = new WP_Post();
		$post->post_content = '[wp_user_avatars]';
		$GLOBALS['post']    = $post;
		$GLOBALS['wpua_test']['returns']['is_user_logged_in'] = false;

		wp_user_avatars_frontend_enqueue_assets();

		$this->assertArrayNotHasKey( 'wp_enqueue_style', $GLOBALS['wpua_test']['calls'] ?? array() );
		$this->assertArrayNotHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] ?? array() );
		$this->assertArrayNotHasKey( 'wp_enqueue_media', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Users without either editor capability do not receive preflight assets.
	 *
	 * @return void
	 */
	public function test_frontend_asset_preflight_skips_users_without_editor_capabilities(): void {
		$post               = new WP_Post();
		$post->post_content = '[wp_user_avatars]';
		$GLOBALS['post']    = $post;
		$GLOBALS['wpua_test']['returns']['current_user_can'] = false;

		wp_user_avatars_frontend_enqueue_assets();

		$this->assertArrayNotHasKey( 'wp_enqueue_style', $GLOBALS['wpua_test']['calls'] ?? array() );
		$this->assertArrayNotHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] ?? array() );
		$this->assertArrayNotHasKey( 'wp_enqueue_media', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * The non-JavaScript upload path always targets the signed-in user.
	 *
	 * @return void
	 */
	public function test_frontend_upload_targets_current_user(): void {
		$_POST  = array(
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
		$_POST  = array(
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
