<?php
/**
 * Custom default-avatar regression tests.
 *
 * @package WP_User_Avatars
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/default-avatar-functions.php';

/**
 * Verify the site-specific custom default-avatar behavior.
 */
final class DefaultAvatarTest extends TestCase {
	/**
	 * Reset recorded WordPress calls.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['wpua_test'] = array(
			'returns' => array(
				'wp_attachment_is_image'     => true,
				'wp_get_attachment_metadata' => array(
					'width'  => 512,
					'height' => 512,
				),
			),
		);
	}

	/**
	 * Register the custom default in Discussion settings.
	 *
	 * @return void
	 */
	public function test_discussion_settings_register_custom_default_avatar(): void {
		$GLOBALS['wpua_test']['returns']['get_option'] = true;

		wp_user_avatars_register_settings();

		$this->assertContains(
			array( 'discussion', 'wp_user_avatars_default_avatar', 'wp_user_avatars_sanitize_default_avatar' ),
			$GLOBALS['wpua_test']['calls']['register_setting']
		);
		$this->assertContains(
			array(
				'wp_user_avatars_default_avatar',
				'Custom Default Avatar',
				'wp_user_avatars_settings_field_default_avatar',
				'discussion',
				'avatars',
				array( 'class' => 'avatar-settings' ),
			),
			$GLOBALS['wpua_test']['calls']['add_settings_field']
		);
	}

	/**
	 * Validate and activate a newly selected image.
	 *
	 * @return void
	 */
	public function test_new_image_is_validated_and_activated(): void {
		$GLOBALS['wpua_test']['callbacks']['get_option']               = static function ( $key ) {
			return 'wp_user_avatars_default_avatar' === $key ? array() : 'mystery';
		};
		$GLOBALS['wpua_test']['returns']['wp_attachment_is_image']     = true;
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_metadata'] = array(
			'width'  => 512,
			'height' => 512,
		);
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url']      = 'https://example.test/uploads/default.jpg';

		$this->assertSame(
			array(
				'media_id' => 42,
				'url'      => 'https://example.test/uploads/default.jpg',
			),
			wp_user_avatars_sanitize_default_avatar(
				array(
					'media_id' => '42',
					'activate' => '1',
				)
			)
		);
		$this->assertSame(
			array( 'avatar_default', 'https://example.test/uploads/default.jpg' ),
			$GLOBALS['wpua_test']['calls']['update_option'][0]
		);
	}

	/**
	 * An unchanged image must not override a later core default choice.
	 *
	 * @return void
	 */
	public function test_unchanged_image_does_not_override_another_selected_default(): void {
		$GLOBALS['wpua_test']['returns']['get_option']                 = array(
			'media_id' => 42,
			'url'      => 'https://example.test/uploads/default.jpg',
		);
		$GLOBALS['wpua_test']['returns']['wp_attachment_is_image']     = true;
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_metadata'] = array(
			'width'  => 512,
			'height' => 512,
		);
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url']      = 'https://example.test/uploads/default.jpg';

		$this->assertSame(
			array(
				'media_id' => 42,
				'url'      => 'https://example.test/uploads/default.jpg',
			),
			wp_user_avatars_sanitize_default_avatar( array( 'media_id' => 42 ) )
		);
		$this->assertArrayNotHasKey( 'update_option', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Reject a non-image attachment without erasing the previous setting.
	 *
	 * @return void
	 */
	public function test_invalid_image_preserves_the_previous_setting(): void {
		$previous = array(
			'media_id' => 42,
			'url'      => 'https://example.test/uploads/default.jpg',
		);

		$GLOBALS['wpua_test']['returns']['get_option']             = $previous;
		$GLOBALS['wpua_test']['returns']['wp_attachment_is_image'] = false;

		$this->assertSame( $previous, wp_user_avatars_sanitize_default_avatar( array( 'media_id' => 99 ) ) );
		$this->assertArrayNotHasKey( 'update_option', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Reject a rectangular image before it can be activated.
	 *
	 * @return void
	 */
	public function test_rectangular_image_is_rejected(): void {
		$previous = array(
			'media_id' => 42,
			'url'      => 'https://example.test/uploads/default.jpg',
		);

		$GLOBALS['wpua_test']['callbacks']['get_option']               = static function ( $key ) use ( $previous ) {
			return 'wp_user_avatars_default_avatar' === $key ? $previous : 'blank';
		};
		$GLOBALS['wpua_test']['returns']['wp_attachment_is_image']     = true;
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_metadata'] = array(
			'width'  => 800,
			'height' => 600,
		);

		$this->assertSame(
			$previous,
			wp_user_avatars_sanitize_default_avatar(
				array(
					'media_id' => 99,
					'activate' => 1,
				)
			)
		);
		$this->assertSame(
			'image-not-square',
			$GLOBALS['wpua_test']['calls']['add_settings_error'][0][1]
		);
		$this->assertArrayNotHasKey( 'update_option', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Removing the active custom image restores Mystery Person.
	 *
	 * @return void
	 */
	public function test_removing_the_active_image_restores_mystery_person(): void {
		$previous = array(
			'media_id' => 42,
			'url'      => 'https://example.test/uploads/default.jpg',
		);

		$GLOBALS['wpua_test']['callbacks']['get_option'] = static function ( $key ) use ( $previous ) {
			return 'wp_user_avatars_default_avatar' === $key
				? $previous
				: 'https://example.test/uploads/default.jpg';
		};

		$this->assertSame( array(), wp_user_avatars_sanitize_default_avatar( array( 'media_id' => 0 ) ) );
		$this->assertSame(
			array( 'avatar_default', 'mystery' ),
			$GLOBALS['wpua_test']['calls']['update_option'][0]
		);
	}

	/**
	 * Removing an inactive custom image preserves Blank.
	 *
	 * @return void
	 */
	public function test_removing_an_inactive_image_preserves_blank(): void {
		$previous = array(
			'media_id' => 42,
			'url'      => 'https://example.test/uploads/default.jpg',
		);

		$GLOBALS['wpua_test']['callbacks']['get_option'] = static function ( $key ) use ( $previous ) {
			return 'wp_user_avatars_default_avatar' === $key ? $previous : 'blank';
		};

		$this->assertSame( array(), wp_user_avatars_sanitize_default_avatar( array( 'media_id' => 0 ) ) );
		$this->assertArrayNotHasKey( 'update_option', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * A core default chosen after the picker wins the interaction order.
	 *
	 * @return void
	 */
	public function test_new_image_does_not_override_a_later_blank_selection(): void {
		$GLOBALS['wpua_test']['callbacks']['get_option']               = static function ( $key ) {
			return 'wp_user_avatars_default_avatar' === $key ? array() : 'blank';
		};
		$GLOBALS['wpua_test']['returns']['wp_attachment_is_image']     = true;
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_metadata'] = array(
			'width'  => 512,
			'height' => 512,
		);
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url']      = 'https://example.test/uploads/default.jpg';

		$this->assertSame(
			array(
				'media_id' => 42,
				'url'      => 'https://example.test/uploads/default.jpg',
			),
			wp_user_avatars_sanitize_default_avatar(
				array(
					'media_id' => 42,
					'activate' => 0,
				)
			)
		);
		$this->assertArrayNotHasKey( 'update_option', $GLOBALS['wpua_test']['calls'] ?? array() );
	}

	/**
	 * Re-selecting an existing image after a core default activates the image.
	 *
	 * @return void
	 */
	public function test_picker_selection_after_blank_activates_existing_image(): void {
		$previous = array(
			'media_id' => 42,
			'url'      => 'https://example.test/uploads/default.jpg',
		);

		$GLOBALS['wpua_test']['callbacks']['get_option']               = static function ( $key ) use ( $previous ) {
			return 'wp_user_avatars_default_avatar' === $key ? $previous : 'blank';
		};
		$GLOBALS['wpua_test']['returns']['wp_attachment_is_image']     = true;
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_metadata'] = array(
			'width'  => 512,
			'height' => 512,
		);
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url']      = 'https://example.test/uploads/default.jpg';

		wp_user_avatars_sanitize_default_avatar(
			array(
				'media_id' => 42,
				'activate' => 1,
			)
		);

		$this->assertSame(
			array( 'avatar_default', 'https://example.test/uploads/default.jpg' ),
			$GLOBALS['wpua_test']['calls']['update_option'][0]
		);
	}

	/**
	 * Selecting a new image and then the existing Custom Image radio activates
	 * the new attachment instead of stranding the old URL.
	 *
	 * @return void
	 */
	public function test_custom_image_radio_activates_the_new_picker_selection(): void {
		$previous = array(
			'media_id' => 42,
			'url'      => 'https://example.test/uploads/old.jpg',
		);

		$GLOBALS['wpua_test']['callbacks']['get_option']          = static function ( $key ) use ( $previous ) {
			return 'wp_user_avatars_default_avatar' === $key
				? $previous
				: 'https://example.test/uploads/old.jpg';
		};
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url'] = 'https://example.test/uploads/new.jpg';

		$this->assertSame(
			array(
				'media_id' => 99,
				'url'      => 'https://example.test/uploads/new.jpg',
			),
			wp_user_avatars_sanitize_default_avatar(
				array(
					'media_id' => 99,
					'activate' => 0,
				)
			)
		);
		$this->assertSame(
			array( 'avatar_default', 'https://example.test/uploads/new.jpg' ),
			$GLOBALS['wpua_test']['calls']['update_option'][0]
		);
	}

	/**
	 * Removing an active image recognizes its current URL after a migration.
	 *
	 * @return void
	 */
	public function test_removing_active_image_after_url_change_restores_mystery_person(): void {
		$previous = array(
			'media_id' => 42,
			'url'      => 'http://old.example.test/uploads/default.jpg',
		);

		$GLOBALS['wpua_test']['callbacks']['get_option']          = static function ( $key ) use ( $previous ) {
			return 'wp_user_avatars_default_avatar' === $key
				? $previous
				: 'https://new.example.test/uploads/default.jpg';
		};
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url'] = 'https://new.example.test/uploads/default.jpg';

		$this->assertSame( array(), wp_user_avatars_sanitize_default_avatar( array( 'media_id' => 0 ) ) );
		$this->assertSame(
			array( 'avatar_default', 'mystery' ),
			$GLOBALS['wpua_test']['calls']['update_option'][0]
		);
	}

	/**
	 * Raw default reads preserve the registered display-filter priority.
	 *
	 * @return void
	 */
	public function test_raw_default_read_restores_the_original_filter_priority(): void {
		$GLOBALS['wpua_test']['returns']['has_filter'] = 23;
		$GLOBALS['wpua_test']['returns']['get_option'] = 'retro';

		$this->assertSame( 'retro', wp_user_avatars_get_raw_avatar_default() );
		$this->assertSame(
			array( 'option_avatar_default', 'wp_user_avatars_option_avatar_default', 23 ),
			$GLOBALS['wpua_test']['calls']['remove_filter'][0]
		);
		$this->assertSame(
			array( 'option_avatar_default', 'wp_user_avatars_option_avatar_default', 23 ),
			$GLOBALS['wpua_test']['calls']['add_filter'][0]
		);
	}

	/**
	 * Raw default updates do not add a filter that was not registered.
	 *
	 * @return void
	 */
	public function test_raw_default_update_does_not_restore_an_absent_filter(): void {
		$GLOBALS['wpua_test']['returns']['has_filter'] = false;

		wp_user_avatars_update_raw_avatar_default( 'blank' );

		$this->assertSame(
			array( 'avatar_default', 'blank' ),
			$GLOBALS['wpua_test']['calls']['update_option'][0]
		);
		$this->assertArrayNotHasKey( 'remove_filter', $GLOBALS['wpua_test']['calls'] );
		$this->assertArrayNotHasKey( 'add_filter', $GLOBALS['wpua_test']['calls'] );
	}

	/**
	 * Add the custom image without removing WordPress defaults.
	 *
	 * @return void
	 */
	public function test_custom_image_is_added_without_removing_wordpress_defaults(): void {
		$GLOBALS['wpua_test']['callbacks']['get_option']          = static function ( $key ) {
			return 'wp_user_avatars_default_avatar' === $key
				? array(
					'media_id' => 42,
					'url'      => 'https://example.test/uploads/default.jpg',
				)
				: false;
		};
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url'] = 'https://example.test/uploads/default.jpg';

		$this->assertSame(
			array(
				'mystery'                                  => 'Mystery Person',
				'https://example.test/uploads/default.jpg' => 'Custom Image',
			),
			wp_user_avatars_avatar_defaults( array( 'mystery' => 'Mystery Person' ) )
		);
	}

	/**
	 * Gravatar blocking keeps Custom Image, Mystery Person, and Blank choices.
	 *
	 * @return void
	 */
	public function test_blocking_gravatar_keeps_custom_mystery_and_blank_choices(): void {
		$GLOBALS['wpua_test']['callbacks']['get_option']          = static function ( $key ) {
			return 'wp_user_avatars_default_avatar' === $key
				? array(
					'media_id' => 42,
					'url'      => 'https://example.test/uploads/default.jpg',
				)
				: true;
		};
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url'] = 'https://example.test/uploads/default.jpg';

		$this->assertSame(
			array(
				'https://example.test/uploads/default.jpg' => 'Custom Image',
				wp_user_avatars_get_mystery_url()          => 'Mystery Person',
				'blank'                                    => 'Blank',
			),
			wp_user_avatars_avatar_defaults( array( 'retro' => 'Retro' ) )
		);
	}

	/**
	 * A selected custom fallback bypasses Gravatar when remote requests are blocked.
	 *
	 * @return void
	 */
	public function test_blocked_gravatar_uses_the_selected_custom_fallback_locally(): void {
		$GLOBALS['wpua_test']['callbacks']['get_option']          = static function ( $key ) {
			if ( 'wp_user_avatars_default_avatar' === $key ) {
				return array(
					'media_id' => 42,
					'url'      => 'https://example.test/uploads/default.jpg',
				);
			}

			return 'avatar_default' === $key
				? 'https://example.test/uploads/default.jpg'
				: true;
		};
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url'] = 'https://example.test/uploads/default.jpg';

		$this->assertSame(
			'https://example.test/uploads/default.jpg',
			wp_user_avatars_maybe_use_local_mystery_person(
				'https://secure.gravatar.com/avatar/hash?s=96&d=https%3A%2F%2Fexample.test%2Fuploads%2Fdefault.jpg'
			)
		);
		$this->assertSame(
			wp_user_avatars_get_mystery_url(),
			wp_user_avatars_maybe_use_local_mystery_person( 'https://secure.gravatar.com/avatar/hash?s=96&d=retro' )
		);
		$this->assertSame(
			'https://example.test/uploads/user-avatar.jpg',
			wp_user_avatars_maybe_use_local_mystery_person( 'https://example.test/uploads/user-avatar.jpg' )
		);
	}

	/**
	 * Explicit defaults bypass Gravatar even when the custom image is inactive.
	 *
	 * @return void
	 */
	public function test_blocked_gravatar_preserves_explicit_url_defaults(): void {
		$GLOBALS['wpua_test']['callbacks']['get_option']          = static function ( $key ) {
			if ( 'wp_user_avatars_default_avatar' === $key ) {
				return array(
					'media_id' => 42,
					'url'      => 'https://example.test/uploads/default.jpg',
				);
			}

			return 'avatar_default' === $key ? 'blank' : true;
		};
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url'] = 'https://example.test/uploads/default.jpg';

		$this->assertSame(
			'https://example.test/uploads/default.jpg',
			wp_user_avatars_maybe_use_local_mystery_person(
				'https://secure.gravatar.com/avatar/hash?f=y&d=https%3A%2F%2Fexample.test%2Fuploads%2Fdefault.jpg'
			)
		);
		$this->assertSame(
			'https://theme.example.test/fallback.png',
			wp_user_avatars_maybe_use_local_mystery_person(
				'https://secure.gravatar.com/avatar/hash?f=y&d=https%3A%2F%2Ftheme.example.test%2Ffallback.png'
			)
		);
		$this->assertSame(
			wp_user_avatars_get_mystery_url(),
			wp_user_avatars_maybe_use_local_mystery_person( 'https://secure.gravatar.com/avatar/hash?d=retro' )
		);
		$this->assertSame(
			wp_user_avatars_get_mystery_url(),
			wp_user_avatars_maybe_use_local_mystery_person(
				'https://secure.gravatar.com/avatar/hash?d=https%3A%2F%2Fcdn.gravatar.com%2Ffallback.png'
			)
		);
		$this->assertSame(
			wp_user_avatars_get_mystery_url(),
			wp_user_avatars_maybe_use_local_mystery_person(
				'https://SECURE.GRAVATAR.COM/avatar/hash?d=https%3A%2F%2FCDN.GRAVATAR.COM%2Ffallback.png'
			)
		);
	}

	/**
	 * A stored attachment that is no longer square is not served as a fallback.
	 *
	 * @return void
	 */
	public function test_runtime_rejects_a_stored_attachment_that_is_no_longer_square(): void {
		$custom = array(
			'media_id' => 42,
			'url'      => 'https://example.test/uploads/default.jpg',
		);
		$GLOBALS['wpua_test']['callbacks']['get_option']               = static function ( $key ) use ( $custom ) {
			return 'wp_user_avatars_default_avatar' === $key ? $custom : false;
		};
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_metadata'] = array(
			'width'  => 800,
			'height' => 600,
		);
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url']      = 'https://example.test/uploads/default.jpg';

		$this->assertSame( '', wp_user_avatars_get_default_avatar_url() );
		$this->assertSame(
			'mystery',
			wp_user_avatars_option_avatar_default( 'https://example.test/uploads/default.jpg' )
		);
	}

	/**
	 * Resolve the selected default through the attachment after a URL change.
	 *
	 * @return void
	 */
	public function test_selected_default_follows_the_current_attachment_url(): void {
		$GLOBALS['wpua_test']['returns']['get_option']            = array(
			'media_id' => 42,
			'url'      => 'http://old.example.test/uploads/default.jpg',
		);
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url'] = 'https://new.example.test/uploads/default.jpg';

		$this->assertSame(
			'https://new.example.test/uploads/default.jpg',
			wp_user_avatars_option_avatar_default( 'http://old.example.test/uploads/default.jpg' )
		);
	}

	/**
	 * Saving after a URL migration synchronizes the active stored values.
	 *
	 * @return void
	 */
	public function test_active_default_is_synchronized_after_an_attachment_url_change(): void {
		$previous = array(
			'media_id' => 42,
			'url'      => 'http://old.example.test/uploads/default.jpg',
		);

		$GLOBALS['wpua_test']['callbacks']['get_option']               = static function ( $key ) use ( $previous ) {
			return 'wp_user_avatars_default_avatar' === $key
				? $previous
				: 'http://old.example.test/uploads/default.jpg';
		};
		$GLOBALS['wpua_test']['returns']['wp_attachment_is_image']     = true;
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_metadata'] = array(
			'width'  => 512,
			'height' => 512,
		);
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url']      = 'https://new.example.test/uploads/default.jpg';

		$this->assertSame(
			array(
				'media_id' => 42,
				'url'      => 'https://new.example.test/uploads/default.jpg',
			),
			wp_user_avatars_sanitize_default_avatar(
				array(
					'media_id' => 42,
					'activate' => 0,
				)
			)
		);
		$this->assertSame(
			array( 'avatar_default', 'https://new.example.test/uploads/default.jpg' ),
			$GLOBALS['wpua_test']['calls']['update_option'][0]
		);
	}

	/**
	 * A deleted selected attachment falls back to Mystery Person.
	 *
	 * @return void
	 */
	public function test_deleted_selected_default_falls_back_to_mystery_person(): void {
		$custom = array(
			'media_id' => 42,
			'url'      => 'https://example.test/uploads/default.jpg',
		);
		$GLOBALS['wpua_test']['callbacks']['get_option']          = static function ( $key ) use ( $custom ) {
			return 'wp_user_avatars_default_avatar' === $key ? $custom : false;
		};
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url'] = false;

		$this->assertSame(
			'mystery',
			wp_user_avatars_option_avatar_default( 'https://example.test/uploads/default.jpg' )
		);

		$GLOBALS['wpua_test']['callbacks']['get_option'] = static function ( $key ) use ( $custom ) {
			return 'wp_user_avatars_default_avatar' === $key ? $custom : true;
		};

		$this->assertSame(
			wp_user_avatars_get_mystery_url(),
			wp_user_avatars_option_avatar_default( 'https://example.test/uploads/default.jpg' )
		);
	}

	/**
	 * Saving Discussion settings clears a deleted active attachment quietly.
	 *
	 * @return void
	 */
	public function test_saving_a_deleted_stored_attachment_clears_it_without_an_error(): void {
		$previous = array(
			'media_id' => 42,
			'url'      => 'https://example.test/uploads/default.jpg',
		);

		$GLOBALS['wpua_test']['callbacks']['get_option']           = static function ( $key ) use ( $previous ) {
			return 'wp_user_avatars_default_avatar' === $key
				? $previous
				: 'https://example.test/uploads/default.jpg';
		};
		$GLOBALS['wpua_test']['returns']['wp_attachment_is_image'] = false;
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url']  = false;

		$this->assertSame( array(), wp_user_avatars_sanitize_default_avatar( array( 'media_id' => 42 ) ) );
		$this->assertSame(
			array( 'avatar_default', 'mystery' ),
			$GLOBALS['wpua_test']['calls']['update_option'][0]
		);
		$this->assertArrayNotHasKey( 'add_settings_error', $GLOBALS['wpua_test']['calls'] );
	}

	/**
	 * Load Media Library assets only on the permitted Discussion screen.
	 *
	 * @return void
	 */
	public function test_discussion_screen_loads_the_media_picker_only_for_settings_managers(): void {
		$GLOBALS['wpua_test']['returns']['current_user_can'] = true;

		wp_user_avatars_settings_enqueue_scripts( 'options-discussion.php' );

		$this->assertArrayHasKey( 'wp_enqueue_media', $GLOBALS['wpua_test']['calls'] );
		$this->assertSame(
			'wp-user-avatars-default-avatar',
			$GLOBALS['wpua_test']['calls']['wp_enqueue_script'][0][0]
		);
		$this->assertSame(
			'i10n_WPUserAvatarsDefault',
			$GLOBALS['wpua_test']['calls']['wp_localize_script'][0][1]
		);

		$GLOBALS['wpua_test'] = array();
		wp_user_avatars_settings_enqueue_scripts( 'profile.php' );
		$this->assertSame( array(), $GLOBALS['wpua_test'] );

		$GLOBALS['wpua_test']['returns']['current_user_can'] = false;
		wp_user_avatars_settings_enqueue_scripts( 'options-discussion.php' );
		$this->assertArrayNotHasKey( 'wp_enqueue_media', $GLOBALS['wpua_test']['calls'] );
		$this->assertArrayNotHasKey( 'wp_enqueue_script', $GLOBALS['wpua_test']['calls'] );
	}

	/**
	 * Render the stored attachment and preview in the settings field.
	 *
	 * @return void
	 */
	public function test_settings_field_preserves_attachment_identity_and_preview(): void {
		$GLOBALS['wpua_test']['returns']['get_option']            = array(
			'media_id' => 42,
			'url'      => 'https://example.test/uploads/default.jpg',
		);
		$GLOBALS['wpua_test']['returns']['wp_get_attachment_url'] = 'https://example.test/uploads/default.jpg';

		ob_start();
		wp_user_avatars_settings_field_default_avatar();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'name="wp_user_avatars_default_avatar[media_id]"', $output );
		$this->assertStringContainsString( 'name="wp_user_avatars_default_avatar[activate]"', $output );
		$this->assertStringContainsString( 'value="42"', $output );
		$this->assertStringContainsString( 'src="https://example.test/uploads/default.jpg"', $output );
		$this->assertStringContainsString( 'alt="Current custom default avatar"', $output );
		$this->assertStringContainsString( 'Choose image', $output );
		$this->assertStringContainsString( 'Remove image', $output );
		$this->assertStringContainsString( 'does not assign an avatar to individual users', $output );
		$this->assertStringContainsString( 'Choose a square image', $output );
	}

	/**
	 * A stale attachment is replaced by an actionable warning and clear value.
	 *
	 * @return void
	 */
	public function test_settings_field_marks_a_stale_attachment_for_removal(): void {
		$GLOBALS['wpua_test']['returns']['get_option']             = array(
			'media_id' => 42,
			'url'      => 'https://example.test/uploads/default.jpg',
		);
		$GLOBALS['wpua_test']['returns']['wp_attachment_is_image'] = false;

		ob_start();
		wp_user_avatars_settings_field_default_avatar();
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'name="wp_user_avatars_default_avatar[media_id]" value="0"', $output );
		$this->assertStringContainsString( 'unavailable or no longer square', $output );
		$this->assertStringContainsString( 'wp-user-avatars-default-avatar-remove hide-if-no-js" hidden', $output );
	}

	/**
	 * Keep the Media Library picker scoped to its settings field.
	 *
	 * @return void
	 */
	public function test_default_avatar_script_uses_one_scoped_media_frame(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read a local test fixture.
		$script = file_get_contents( dirname( __DIR__ ) . '/wp-user-avatars/assets/js/default-avatar.js' );

		$this->assertNotFalse( $script );
		$this->assertStringContainsString( "$( '#wp-user-avatars-default-avatar-field' )", $script );
		$this->assertStringContainsString( "library: { type: 'image' }", $script );
		$this->assertStringContainsString( 'multiple: false', $script );
		$this->assertStringContainsString( "frame.on( 'select'", $script );
		$this->assertStringContainsString( '$input.val( attachment.id )', $script );
		$this->assertStringContainsString( '$activate.val( 1 )', $script );
		$this->assertStringContainsString( '$input.val( 0 )', $script );
		$this->assertStringContainsString( 'attachment.width !== attachment.height', $script );
		$this->assertStringContainsString( '$image.attr( \'src\', attachment.url )', $script );
		$this->assertStringContainsString( '$select.trigger( \'focus\' )', $script );
		$this->assertStringContainsString( 'i10n_WPUserAvatarsDefault.customUrl', $script );
		$this->assertStringContainsString( 'input[name="avatar_default"]', $script );
	}
}
