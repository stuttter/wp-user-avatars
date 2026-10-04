/* global i10n_WPUserAvatars, wp */
jQuery( document ).ready( function ( $ ) {

	$( '.wp-user-avatars-editor' ).each( function () {
		var $editor = $( this );

		/**
		 * Invoke the media modal.
		 */
		$editor.find( '.wp-user-avatars-media' ).on( 'click', function () {
			var modal = $editor.data( 'wp-user-avatars-modal' );

			if ( avatar_is_working( $editor ) ) {
				return;
			}

			if ( modal ) {
				modal.open();
				return;
			}

			modal = wp.media( {
				title:    i10n_WPUserAvatars.insertMediaTitle,
				button:   { text: i10n_WPUserAvatars.insertIntoPost },
				library:  { type: 'image' },
				multiple: false
			} );

			modal.on( 'select', function () {
				var mediaId = modal.state().get( 'selection' ).first().toJSON().id;

				avatar_lock( $editor, true );
				avatar_feedback( $editor, '' );

				$.post( i10n_WPUserAvatars.ajaxUrl, {
					action:   'assign_wp_user_avatars_media',
					media_id: mediaId,
					user_id:  $editor.data( 'user-id' ),
					_wpnonce: $editor.data( 'media-nonce' )
				} ).done( function ( data ) {
					if ( '' !== data ) {
						avatar_updated( $editor, data );
						return;
					}

					avatar_feedback( $editor, i10n_WPUserAvatars.mediaError );
				} ).fail( function () {
					avatar_feedback( $editor, i10n_WPUserAvatars.mediaError );
				} ).always( function () {
					avatar_lock( $editor, false );
				} );
			} );

			$editor.data( 'wp-user-avatars-modal', modal );
			modal.open();
		} );

		/**
		 * Upload an avatar as soon as a file is selected.
		 */
		$editor.find( '.wp-user-avatars-upload' ).on( 'change', function () {
			var $upload = $( this ),
				file = this.files && this.files[ 0 ],
				formData,
				originalAvatar,
				previewUrl = '';

			if ( ! file || avatar_is_working( $editor ) ) {
				return;
			}

			avatar_lock( $editor, true );
			avatar_feedback( $editor, '' );
			originalAvatar = $editor.find( '.wp-user-avatars-photo' ).html();

			if ( window.URL && window.URL.createObjectURL ) {
				previewUrl = window.URL.createObjectURL( file );
				$editor.find( '.wp-user-avatars-photo img' )
					.attr( 'src', previewUrl )
					.removeAttr( 'srcset' );
			}

			formData = new window.FormData();
			formData.append( 'action', 'upload_wp_user_avatars' );
			formData.append( 'user_id', $editor.data( 'user-id' ) );
			formData.append( '_wpnonce', $editor.data( 'upload-nonce' ) );
			formData.append( 'wp-user-avatars', file );
			formData.append( 'rating', $editor.find( 'input[name="wp_user_avatars_rating"]:checked' ).val() || 'G' );

			$.ajax( {
				url:         i10n_WPUserAvatars.ajaxUrl,
				type:        'POST',
				data:        formData,
				contentType: false,
				processData: false
			} ).done( function ( response ) {
				if ( response.success && response.data.avatar ) {
					avatar_updated( $editor, response.data.avatar );
					$upload.val( '' );
					return;
				}

				$editor.find( '.wp-user-avatars-photo' ).html( originalAvatar );
				avatar_feedback( $editor, response.data && response.data.message ? response.data.message : i10n_WPUserAvatars.uploadError );
			} ).fail( function ( response ) {
				var message = response.responseJSON && response.responseJSON.data && response.responseJSON.data.message;

				$editor.find( '.wp-user-avatars-photo' ).html( originalAvatar );
				avatar_feedback( $editor, message || i10n_WPUserAvatars.uploadError );
			} ).always( function () {
				if ( previewUrl ) {
					window.URL.revokeObjectURL( previewUrl );
				}

				avatar_lock( $editor, false );
			} );
		} );

		/**
		 * Remove an avatar with JavaScript while retaining the submit fallback.
		 *
		 * @param {object} event Click event.
		 */
		$editor.find( '.wp-user-avatars-remove' ).on( 'click', function ( event ) {
			event.preventDefault();

			if ( avatar_is_working( $editor ) ) {
				return;
			}

			avatar_lock( $editor, true );
			avatar_feedback( $editor, '' );

			$.get( i10n_WPUserAvatars.ajaxUrl, {
				action:   'remove_wp_user_avatars',
				user_id:  $editor.data( 'user-id' ),
				_wpnonce: $editor.data( 'delete-nonce' )
			} ).done( function ( data ) {
				if ( '' !== data ) {
					$editor.find( '.wp-user-avatars-photo' ).html( data );
					$editor.find( '.wp-user-avatars-remove' ).hide();
					$editor.find( '.wp-user-avatars-ratings' ).addClass( 'fancy-hidden' );
					$editor.find( '.wp-user-avatars-ratings fieldset' ).prop( 'disabled', true );
					return;
				}

				avatar_feedback( $editor, i10n_WPUserAvatars.removeError );
			} ).fail( function () {
				avatar_feedback( $editor, i10n_WPUserAvatars.removeError );
			} ).always( function () {
				avatar_lock( $editor, false );
			} );
		} );
	} );

	/**
	 * Return whether one editor is processing a request.
	 *
	 * @param {object} $editor Editor element.
	 * @return {boolean} Whether the editor is locked.
	 */
	function avatar_is_working( $editor ) {
		return true === $editor.data( 'wp-user-avatars-working' );
	}

	/**
	 * Lock or unlock one avatar editor.
	 *
	 * @param {object}  $editor Editor element.
	 * @param {boolean} locked  Whether controls should be locked.
	 */
	function avatar_lock( $editor, locked ) {
		$editor.data( 'wp-user-avatars-working', locked );
		$editor.find( '.wp-user-avatars-media, .wp-user-avatars-remove, .wp-user-avatars-upload' ).prop( 'disabled', locked );
	}

	/**
	 * Update one editor after selecting or uploading an avatar.
	 *
	 * @param {object} $editor Editor element.
	 * @param {string} avatar  Avatar HTML.
	 */
	function avatar_updated( $editor, avatar ) {
		$editor.find( '.wp-user-avatars-photo' ).html( avatar );
		$editor.find( '.wp-user-avatars-remove' ).show();
		$editor.find( '.wp-user-avatars-ratings' ).removeClass( 'fancy-hidden' );
		$editor.find( '.wp-user-avatars-ratings fieldset' ).prop( 'disabled', false );
	}

	/**
	 * Show feedback without interrupting the surrounding form.
	 *
	 * @param {object} $editor Editor element.
	 * @param {string} message Feedback message.
	 */
	function avatar_feedback( $editor, message ) {
		$editor.find( '.wp-user-avatars-feedback' ).text( message );
	}
} );
