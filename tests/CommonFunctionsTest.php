<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CommonFunctionsTest extends TestCase {
	/**
	 * Reset the recorded WordPress calls.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['wpua_test'] = array();
	}

	/**
	 * Verify user id resolves supported identity types.
	 *
	 * @return void
	 */
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
			'CommonFunctionsTest_1.php',
			wp_user_avatars_unique_filename_callback( __DIR__, 'CommonFunctionsTest', '.php' )
		);
	}

	/**
	 * Verify avatar upload is assigned and cleans up temporary state.
	 *
	 * @return void
	 */
	public function test_avatar_upload_is_assigned_and_cleans_up_temporary_state(): void {
		$GLOBALS['wpua_test']['returns']['wp_handle_upload'] = array(
			'file' => '/tmp/avatar.jpg',
			'url'  => 'https://example.test/uploads/avatar.jpg',
		);

		$this->assertSame(
			'https://example.test/uploads/avatar.jpg',
			wp_user_avatars_handle_upload( 7, array( 'name' => 'avatar.jpg' ) )
		);
		$this->assertArrayNotHasKey( 'wp_user_avatars_user_id', $GLOBALS );
		$this->assertSame(
			array( 'upload_size_limit', 'wp_user_avatars_upload_size_limit' ),
			$GLOBALS['wpua_test']['calls']['remove_filter'][0]
		);
	}

	/**
	 * Verify avatar upload rejects executable file names.
	 *
	 * @return void
	 */
	public function test_avatar_upload_rejects_executable_file_names(): void {
		$result = wp_user_avatars_handle_upload( 7, array( 'name' => 'avatar.php.jpg' ) );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_file_type', $result->get_error_code() );
		$this->assertArrayNotHasKey( 'wp_handle_upload', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Verify avatar rating falls back to the safest value.
	 *
	 * @return void
	 */
	public function test_avatar_rating_falls_back_to_the_safest_value(): void {
		$this->assertSame( 'G', wp_user_avatars_update_rating( 7, 'invalid' ) );
		$this->assertSame(
			array( 7, 'wp_user_avatars_rating', 'G' ),
			$GLOBALS['wpua_test']['calls']['update_user_meta'][0]
		);
	}

	/**
	 * An invalid profile value should not make the display callback fatal.
	 */
	public function test_profile_field_ignores_invalid_user_value(): void {
		ob_start();

		try {
			wp_user_avatars_edit_user_profile( 0 );
		} finally {
			$output = ob_get_clean();
		}

		$this->assertSame( '', $output );
		$this->assertSame( array(), $GLOBALS['wpua_test'] );
	}

	/**
	 * Verify local avatar uses cached size without resizing.
	 *
	 * @return void
	 */
	public function test_local_avatar_uses_cached_size_without_resizing(): void {
		$GLOBALS['wpua_test']['callbacks']['get_user_meta'] = static function ( $user_id, $key ) {
			return 'wp_user_avatars' === $key
				? array(
					'full' => 'https://example.test/full.jpg',
					96     => 'https://example.test/96.jpg',
				)
				: 'G';
		};
		$GLOBALS['wpua_test']['returns']['get_option']      = 'G';

		$GLOBALS['wpua_test']['returns']['apply_filters:wp_user_avatars_dynamic_resize'] = false;

		$this->assertSame( 'https://example.test/96.jpg', wp_user_avatars_get_local_avatar_url( 7, 96 ) );
		$this->assertArrayNotHasKey( 'wp_get_image_editor', $GLOBALS['wpua_test']['calls'] ?? array() );
		$this->assertArrayNotHasKey( 'update_user_meta', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Verify local avatar generation honors the caller's requested size.
	 *
	 * @return void
	 */
	public function test_local_avatar_generation_uses_the_requested_size(): void {
		$GLOBALS['wpua_test']['callbacks']['get_user_meta'] = static function ( $user_id, $key ) {
			return 'wp_user_avatars' === $key
				? array( 'full' => 'https://example.test/uploads/full.jpg' )
				: 'G';
		};
		$GLOBALS['wpua_test']['returns']['get_option']      = 'G';

		$GLOBALS['wpua_test']['callbacks']['apply_filters:wp_user_avatars_dynamic_resize'] = static function ( $resize, $user_id, $size ) {
			return $resize && 7 === $user_id && in_array( $size, array( 96, 256 ), true );
		};

		$GLOBALS['wpua_test']['returns']['wp_upload_dir'] = array(
			'baseurl' => 'https://example.test/uploads',
			'basedir' => '/tmp/uploads',
		);

		$editor = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'resize', 'generate_filename', 'save' ) )
			->getMock();
		$editor->expects( $this->once() )
			->method( 'resize' )
			->with( 256, 256, true )
			->willReturn( true );
		$editor->expects( $this->once() )
			->method( 'generate_filename' )
			->willReturn( '/tmp/uploads/full-256x256.jpg' );
		$editor->expects( $this->once() )
			->method( 'save' )
			->with( '/tmp/uploads/full-256x256.jpg' )
			->willReturn( array( 'path' => '/tmp/uploads/full-256x256.jpg' ) );

		$GLOBALS['wpua_test']['returns']['wp_get_image_editor'] = $editor;

		$this->assertSame( 'https://example.test/uploads/full-256x256.jpg', wp_user_avatars_get_local_avatar_url( 7, 256 ) );
		$this->assertSame(
			array( true, 7, 256, array( 'full' => 'https://example.test/uploads/full.jpg' ) ),
			$GLOBALS['wpua_test']['calls']['apply_filters:wp_user_avatars_dynamic_resize'][0]
		);
	}

	/**
	 * Verify a size allowlist falls back without generating or changing metadata.
	 *
	 * @return void
	 */
	public function test_local_avatar_generation_falls_back_for_a_disallowed_size(): void {
		$GLOBALS['wpua_test']['callbacks']['get_user_meta'] = static function ( $user_id, $key ) {
			return 'wp_user_avatars' === $key
				? array( 'full' => 'https://example.test/uploads/full.jpg' )
				: 'G';
		};
		$GLOBALS['wpua_test']['returns']['get_option']      = 'G';

		$GLOBALS['wpua_test']['callbacks']['apply_filters:wp_user_avatars_dynamic_resize'] = static function ( $resize, $user_id, $size ) {
			return $resize && 7 === $user_id && in_array( $size, array( 96, 256 ), true );
		};

		$this->assertSame( 'https://example.test/uploads/full.jpg', wp_user_avatars_get_local_avatar_url( 7, 512 ) );
		$this->assertArrayNotHasKey( 'wp_get_image_editor', $GLOBALS['wpua_test']['calls'] ?? array() );
		$this->assertArrayNotHasKey( 'update_user_meta', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Verify repeated requests reuse the derivative stored by the first request.
	 *
	 * @return void
	 */
	public function test_repeated_local_avatar_requests_reuse_the_generated_size(): void {
		$avatar = array( 'full' => 'https://example.test/uploads/full.jpg' );

		$GLOBALS['wpua_test']['callbacks']['get_user_meta'] = static function ( $user_id, $key ) use ( &$avatar ) {
			return 'wp_user_avatars' === $key ? $avatar : 'G';
		};

		$GLOBALS['wpua_test']['callbacks']['update_user_meta'] = static function ( $user_id, $key, $value ) use ( &$avatar ) {
			if ( 'wp_user_avatars' === $key ) {
				$avatar = $value;
			}
		};
		$GLOBALS['wpua_test']['returns']['get_option']         = 'G';

		$GLOBALS['wpua_test']['returns']['wp_upload_dir'] = array(
			'baseurl' => 'https://example.test/uploads',
			'basedir' => '/tmp/uploads',
		);

		$editor = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'resize', 'generate_filename', 'save' ) )
			->getMock();
		$editor->expects( $this->once() )
			->method( 'resize' )
			->with( 256, 256, true )
			->willReturn( true );
		$editor->expects( $this->once() )
			->method( 'generate_filename' )
			->willReturn( '/tmp/uploads/full-256x256.jpg' );
		$editor->expects( $this->once() )
			->method( 'save' )
			->willReturn( array( 'path' => '/tmp/uploads/full-256x256.jpg' ) );

		$GLOBALS['wpua_test']['returns']['wp_get_image_editor'] = $editor;

		$this->assertSame( 'https://example.test/uploads/full-256x256.jpg', wp_user_avatars_get_local_avatar_url( 7, 256 ) );
		$this->assertSame( 'https://example.test/uploads/full-256x256.jpg', wp_user_avatars_get_local_avatar_url( 7, 256 ) );
		$this->assertCount( 1, $GLOBALS['wpua_test']['calls']['wp_get_image_editor'] );
		$this->assertCount( 1, $GLOBALS['wpua_test']['calls']['update_user_meta'] );
	}

	/**
	 * Verify interleaved generation converges on one derivative URL.
	 *
	 * @return void
	 */
	public function test_interleaved_local_avatar_generation_converges_on_the_same_size(): void {
		$GLOBALS['wpua_test']['callbacks']['get_user_meta'] = static function ( $user_id, $key ) {
			return 'wp_user_avatars' === $key
				? array( 'full' => 'https://example.test/uploads/full.jpg' )
				: 'G';
		};
		$GLOBALS['wpua_test']['returns']['get_option']      = 'G';

		$GLOBALS['wpua_test']['returns']['wp_upload_dir'] = array(
			'baseurl' => 'https://example.test/uploads',
			'basedir' => '/tmp/uploads',
		);

		$nested_url   = null;
		$first_editor = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'resize', 'generate_filename', 'save' ) )
			->getMock();
		$first_editor->expects( $this->once() )
			->method( 'resize' )
			->with( 256, 256, true )
			->willReturnCallback(
				static function () use ( &$nested_url ) {
					$nested_url = wp_user_avatars_get_local_avatar_url( 7, 256 );
					return true;
				}
			);
		$first_editor->expects( $this->once() )
			->method( 'generate_filename' )
			->willReturn( '/tmp/uploads/full-256x256.jpg' );
		$first_editor->expects( $this->once() )
			->method( 'save' )
			->with( '/tmp/uploads/full-256x256.jpg' )
			->willReturn( array( 'path' => '/tmp/uploads/full-256x256.jpg' ) );

		$second_editor = $this->getMockBuilder( stdClass::class )
			->addMethods( array( 'resize', 'generate_filename', 'save' ) )
			->getMock();
		$second_editor->expects( $this->once() )
			->method( 'resize' )
			->with( 256, 256, true )
			->willReturn( true );
		$second_editor->expects( $this->once() )
			->method( 'generate_filename' )
			->willReturn( '/tmp/uploads/full-256x256.jpg' );
		$second_editor->expects( $this->once() )
			->method( 'save' )
			->with( '/tmp/uploads/full-256x256.jpg' )
			->willReturn( array( 'path' => '/tmp/uploads/full-256x256.jpg' ) );

		$editors = array( $first_editor, $second_editor );
		$GLOBALS['wpua_test']['callbacks']['wp_get_image_editor'] = static function () use ( &$editors ) {
			return array_shift( $editors );
		};

		$outer_url = wp_user_avatars_get_local_avatar_url( 7, 256 );

		$this->assertSame( 'https://example.test/uploads/full-256x256.jpg', $nested_url );
		$this->assertSame( $nested_url, $outer_url );
		$this->assertSame(
			$GLOBALS['wpua_test']['calls']['update_user_meta'][0],
			$GLOBALS['wpua_test']['calls']['update_user_meta'][1]
		);
	}

	/**
	 * Verify a local attachment follows the same disallowed-size fallback policy.
	 *
	 * @return void
	 */
	public function test_local_attachment_falls_back_for_a_disallowed_size(): void {
		$GLOBALS['wpua_test']['callbacks']['get_user_meta']   = static function ( $user_id, $key ) {
			return 'wp_user_avatars' === $key
				? array(
					'full'     => 'https://example.test/uploads/full.jpg',
					'media_id' => 42,
				)
				: 'G';
		};
		$GLOBALS['wpua_test']['returns']['get_option']        = 'G';
		$GLOBALS['wpua_test']['returns']['get_attached_file'] = '/tmp/uploads/full.jpg';

		$GLOBALS['wpua_test']['returns']['apply_filters:wp_user_avatars_dynamic_resize'] = false;

		$this->assertSame( 'https://example.test/uploads/full.jpg', wp_user_avatars_get_local_avatar_url( 7, 512 ) );
		$this->assertArrayNotHasKey( 'wp_get_image_editor', $GLOBALS['wpua_test']['calls'] ?? array() );
		$this->assertArrayNotHasKey( 'update_user_meta', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Verify local avatar respects site rating.
	 *
	 * @return void
	 */
	public function test_local_avatar_respects_site_rating(): void {
		$GLOBALS['wpua_test']['callbacks']['get_user_meta'] = static function ( $user_id, $key ) {
			return 'wp_user_avatars' === $key
				? array( 'full' => 'https://example.test/full.jpg' )
				: 'R';
		};
		$GLOBALS['wpua_test']['returns']['get_option']      = 'PG';

		$this->assertNull( wp_user_avatars_get_local_avatar_url( 7, 96 ) );
	}

	/**
	 * Verify avatar filter preserves forced default.
	 *
	 * @return void
	 */
	public function test_avatar_filter_preserves_forced_default(): void {
		$this->assertSame(
			'https://secure.gravatar.com/avatar/hash',
			wp_user_avatars_filter_get_avatar_url(
				'https://secure.gravatar.com/avatar/hash',
				7,
				array(
					'force_default' => true,
					'size'          => 96,
				)
			)
		);
		$this->assertArrayNotHasKey( 'get_user_meta', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * An early provider should not hide an explicitly assigned local avatar.
	 *
	 * @return void
	 */
	public function test_early_avatar_provider_preserves_local_avatar_precedence(): void {
		$GLOBALS['wpua_test']['callbacks']['get_user_meta'] = static function ( $user_id, $key ) {
			return 'wp_user_avatars' === $key
				? array(
					'full' => 'https://example.test/full.jpg',
					144    => 'https://example.test/144.jpg',
				)
				: 'G';
		};
		$GLOBALS['wpua_test']['returns']['get_option']      = 'G';

		$this->assertSame(
			array(
				'url'           => 'https://example.test/144.jpg',
				'size'          => 144,
				'force_default' => false,
				'found_avatar'  => true,
			),
			wp_user_avatars_filter_pre_get_avatar_data(
				array(
					'url'           => 'https://provider.example/avatar.jpg',
					'size'          => 144,
					'force_default' => false,
					'found_avatar'  => false,
				),
				7
			)
		);
	}

	/**
	 * Normal avatar resolution should continue through get_avatar_url.
	 *
	 * @return void
	 */
	public function test_pre_avatar_filter_ignores_requests_without_an_early_url(): void {
		$args = array(
			'size'          => 96,
			'force_default' => false,
		);

		$this->assertSame( $args, wp_user_avatars_filter_pre_get_avatar_data( $args, 7 ) );
		$this->assertArrayNotHasKey( 'get_user_meta', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Forced defaults should retain an earlier provider's URL.
	 *
	 * @return void
	 */
	public function test_pre_avatar_filter_preserves_forced_default(): void {
		$args = array(
			'url'           => 'https://provider.example/avatar.jpg',
			'size'          => 96,
			'force_default' => true,
		);

		$this->assertSame( $args, wp_user_avatars_filter_pre_get_avatar_data( $args, 7 ) );
		$this->assertArrayNotHasKey( 'get_user_meta', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Verify avatar preview displays when public avatars are hidden.
	 *
	 * @return void
	 */
	public function test_avatar_preview_displays_when_public_avatars_are_hidden(): void {
		$GLOBALS['wpua_test']['returns']['get_avatar'] = '<img src="avatar.jpg">';

		$this->assertSame( '<img src="avatar.jpg">', wp_user_avatars_get_avatar_preview( 7, 250 ) );
		$this->assertSame(
			array( 7, 250, '', '', array( 'force_display' => true ) ),
			$GLOBALS['wpua_test']['calls']['get_avatar'][0]
		);
	}

	/**
	 * Verify avatar preview normalizes failure to empty markup.
	 *
	 * @return void
	 */
	public function test_avatar_preview_normalizes_failure_to_empty_markup(): void {
		$GLOBALS['wpua_test']['returns']['get_avatar'] = false;

		$this->assertSame( '', wp_user_avatars_get_avatar_preview( 7, 250 ) );
	}

	/**
	 * Verify streamed attachment uses WordPress image url.
	 *
	 * @return void
	 */
	public function test_streamed_attachment_uses_wordpress_image_url(): void {
		$GLOBALS['wpua_test']['callbacks']['get_user_meta']             = static function ( $user_id, $key ) {
			return 'wp_user_avatars' === $key
				? array(
					'full'     => 'https://example.test/avatar.jpg',
					'media_id' => 42,
					'site_id'  => 4,
				)
				: 'G';
		};
		$GLOBALS['wpua_test']['returns']['get_option']                  = 'G';
		$GLOBALS['wpua_test']['returns']['is_multisite']                = true;
		$GLOBALS['wpua_test']['returns']['get_attached_file']           = 's3sfo2://bucket/avatar.jpg';
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_image_url'] = 'https://cdn.example.test/avatar-96x96.jpg';

		$GLOBALS['wpua_test']['returns']['apply_filters:wp_user_avatars_dynamic_resize'] = false;

		$this->assertSame(
			'https://cdn.example.test/avatar-96x96.jpg',
			wp_user_avatars_get_local_avatar_url( 7, 96 )
		);
		$this->assertSame(
			array( 42, array( 96, 96 ) ),
			$GLOBALS['wpua_test']['calls']['wp_get_attachment_image_url'][0]
		);
		$this->assertSame( array( array( 4 ) ), $GLOBALS['wpua_test']['calls']['switch_to_blog'] );
		$this->assertArrayHasKey( 'restore_current_blog', $GLOBALS['wpua_test']['calls'] );
		$this->assertArrayNotHasKey( 'wp_get_image_editor', $GLOBALS['wpua_test']['calls'] );
	}

	/**
	 * Verify attachment avatar preserves media and site identity.
	 *
	 * @return void
	 */
	public function test_attachment_avatar_preserves_media_and_site_identity(): void {
		$GLOBALS['wpua_test']['returns']['get_user_meta']         = array();
		$GLOBALS['wpua_test']['returns']['get_current_blog_id']   = 4;
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url'] = 'https://example.test/avatar.jpg';

		wp_user_avatars_update_avatar( 7, 42 );

		$this->assertSame(
			array(
				7,
				'wp_user_avatars',
				array(
					'media_id' => 42,
					'site_id'  => 4,
					'full'     => 'https://example.test/avatar.jpg',
				),
			),
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

	/**
	 * Verify generated avatar is deleted through WordPress api.
	 *
	 * @return void
	 */
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

	/**
	 * Attachment replacement removes plugin derivatives without deleting the original.
	 *
	 * @return void
	 */
	public function test_attachment_replacement_only_deletes_plugin_owned_derivatives(): void {
		$original   = tempnam( sys_get_temp_dir(), 'wpua-original-' );
		$derivative = tempnam( sys_get_temp_dir(), 'wpua-size-' );
		$this->assertNotFalse( $original );
		$this->assertNotFalse( $derivative );

		$directory = dirname( $original );

		$GLOBALS['wpua_test']['returns']['get_user_meta'] = array(
			'media_id' => 42,
			'site_id'  => 4,
			'full'     => 'https://example.test/uploads/' . basename( $original ),
			96         => 'https://example.test/uploads/' . basename( $derivative ),
		);
		$GLOBALS['wpua_test']['returns']['wp_upload_dir'] = array(
			'baseurl' => 'https://example.test/uploads',
			'basedir' => $directory,
		);

		try {
			wp_user_avatars_update_avatar( 7, 'https://example.test/uploads/new.jpg' );

			$this->assertSame( array( array( $derivative ) ), $GLOBALS['wpua_test']['calls']['wp_delete_file'] );
			$this->assertSame(
				array( 7, 'wp_user_avatars', array( 'full' => 'https://example.test/uploads/new.jpg' ) ),
				$GLOBALS['wpua_test']['calls']['update_user_meta'][0]
			);
		} finally {
			foreach ( array( $original, $derivative ) as $file ) {
				if ( file_exists( $file ) ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test cleanup for a temporary file.
					unlink( $file );
				}
			}
		}
	}

	/**
	 * Verify blocking gravatar replaces remote defaults.
	 *
	 * @return void
	 */
	public function test_blocking_gravatar_replaces_remote_defaults(): void {
		$GLOBALS['wpua_test']['returns']['get_option'] = true;

		$defaults = wp_user_avatars_avatar_defaults(
			array(
				'mystery' => 'Mystery Person',
				'retro'   => 'Retro',
			)
		);

		$this->assertSame(
			array(
				wp_user_avatars_get_mystery_url() => 'Mystery Person',
				'blank'                           => 'Blank',
			),
			$defaults
		);
	}

	/**
	 * Verify blocking gravatar replaces gravatar urls with local mystery person.
	 *
	 * @return void
	 */
	public function test_blocking_gravatar_replaces_gravatar_urls_with_local_mystery_person(): void {
		$GLOBALS['wpua_test']['returns']['get_option'] = true;

		$this->assertSame(
			wp_user_avatars_get_mystery_url(),
			wp_user_avatars_maybe_use_local_mystery_person( 'https://secure.gravatar.com/avatar/hash?s=32&d=mystery' )
		);
		$this->assertSame(
			wp_user_avatars_get_mystery_url(),
			wp_user_avatars_maybe_use_local_mystery_person( 'https://0.gravatar.com/avatar/hash?s=32&d=mystery' )
		);
	}

	/**
	 * Blocking Gravatar must preserve the advertised Blank default locally.
	 */
	public function test_blocking_gravatar_preserves_blank_default_locally(): void {
		$GLOBALS['wpua_test']['returns']['get_option'] = true;
		$this->assertSame(
			wp_user_avatars_get_plugin_url() . 'assets/images/blank.svg',
			wp_user_avatars_maybe_use_local_mystery_person( 'https://0.gravatar.com/avatar/hash?s=32&d=blank&f=y' )
		);
	}

	/**
	 * Verify blocking gravatar preserves non gravatar urls.
	 *
	 * @return void
	 */
	public function test_blocking_gravatar_preserves_non_gravatar_urls(): void {
		$GLOBALS['wpua_test']['returns']['get_option'] = true;
		$url = 'https://example.test/avatar.jpg';

		$this->assertSame( $url, wp_user_avatars_maybe_use_local_mystery_person( $url ) );
	}
}
