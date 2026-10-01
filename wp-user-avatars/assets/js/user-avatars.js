/* global i10n_WPUserAvatars */
jQuery( document ).ready( function ( $ ) {

	/* Globals */
	var wp_user_avatars_modal,
		avatar_working;

	/**
	 * Invoke the media modal
	 *
	 * @param {object} event The event
	 */
	$( '#wp-user-avatars-media' ).on( 'click', function ( event ) {
		event.preventDefault();

		// Already adding
		if ( avatar_working ) {
			return;
		}

		// Open the modal
		if ( wp_user_avatars_modal ) {
			wp_user_avatars_modal.open();
			return;
		}

		// First time modal
		wp_user_avatars_modal = wp.media.frames.wp_user_avatars_modal = wp.media( {
			title:    i10n_WPUserAvatars.insertMediaTitle,
			button:   { text: i10n_WPUserAvatars.insertIntoPost },
			library:  { type: 'image' },
			multiple: false
		} );

		// Picking an avatar
		wp_user_avatars_modal.on( 'select', function () {

			// Prevent doubles
			avatar_lock( 'lock' );

			// Get the avatar URL
			var avatar_url = wp_user_avatars_modal.state().get( 'selection' ).first().toJSON().id;

			// Post the new avatar
			$.post( i10n_WPUserAvatars.ajaxUrl, {
				action:   'assign_wp_user_avatars_media',
				media_id: avatar_url,
				user_id:  i10n_WPUserAvatars.user_id,
				_wpnonce: i10n_WPUserAvatars.mediaNonce
			}, function ( data ) {

				// Update the UI
				if ( '' !== data ) {
					$( '#wp-user-avatars-photo' ).html( data );
					$( '#wp-user-avatars-remove' ).show();
					$( '#wp-user-avatars-ratings' ).removeClass( 'fancy-hidden' );
					$( '#wp-user-avatars-ratings fieldset' ).prop( 'disabled', false );
				}

				avatar_lock( 'unlock' );
			} );
		} );

		// Open the modal
		wp_user_avatars_modal.open();
	} );

	/**
	 * Upload an avatar as soon as a file is selected.
	 */
	$( '#wp-user-avatars' ).on( 'change', function () {
		var file = this.files && this.files[ 0 ],
			formData,
			originalAvatar;

		if ( ! file || avatar_working ) {
			return;
		}

		avatar_lock( 'lock' );
		avatar_feedback( '' );
		originalAvatar = $( '#wp-user-avatars-photo' ).html();

		if ( window.URL && window.URL.createObjectURL ) {
			$( '#wp-user-avatars-photo img' )
				.attr( 'src', window.URL.createObjectURL( file ) )
				.removeAttr( 'srcset' );
		}

		formData = new window.FormData();
		formData.append( 'action', 'upload_wp_user_avatars' );
		formData.append( 'user_id', i10n_WPUserAvatars.user_id );
		formData.append( '_wpnonce', i10n_WPUserAvatars.uploadNonce );
		formData.append( 'wp-user-avatars', file );
		formData.append( 'rating', $( 'input[name="wp_user_avatars_rating"]:checked' ).val() || 'G' );

		$.ajax( {
			url:         i10n_WPUserAvatars.ajaxUrl,
			type:        'POST',
			data:        formData,
			contentType: false,
			processData: false
		} ).done( function ( response ) {
			if ( response.success && response.data.avatar ) {
				$( '#wp-user-avatars-photo' ).html( response.data.avatar );
				$( '#wp-user-avatars-remove' ).show();
				$( '#wp-user-avatars-ratings' ).removeClass( 'fancy-hidden' );
				$( '#wp-user-avatars-ratings fieldset' ).prop( 'disabled', false );
				$( '#wp-user-avatars' ).val( '' );
				return;
			}

			$( '#wp-user-avatars-photo' ).html( originalAvatar );
			avatar_feedback( response.data && response.data.message ? response.data.message : i10n_WPUserAvatars.uploadError );
		} ).fail( function ( response ) {
			var message = response.responseJSON && response.responseJSON.data && response.responseJSON.data.message;

			$( '#wp-user-avatars-photo' ).html( originalAvatar );
			avatar_feedback( message || i10n_WPUserAvatars.uploadError );
		} ).always( function () {
			avatar_lock( 'unlock' );
		} );
	} );

	/**
	 * Remove avatar
	 *
	 * @param {object} event The event
	 */
	$( '#wp-user-avatars-remove' ).on( 'click', function ( event ) {
		event.preventDefault();

		// Already removing
		if ( avatar_working ) {
			return;
		}

		// Prevent doubles
		avatar_lock( 'lock' );

		// Remove the URL
		$.get( i10n_WPUserAvatars.ajaxUrl, {
			action:   'remove_wp_user_avatars',
			user_id:  i10n_WPUserAvatars.user_id,
			_wpnonce: i10n_WPUserAvatars.deleteNonce
		} ).done( function ( data ) {

			// Update the UI
			if ( '' !== data ) {
				$( '#wp-user-avatars-photo' ).html( data );
				$( '#wp-user-avatars-remove' ).hide();
				$( '#wp-user-avatars-ratings' ).addClass( 'fancy-hidden' );
				$( '#wp-user-avatars-ratings fieldset' ).prop( 'disabled', true );
			}

			avatar_lock( 'unlock' );
		} );
	} );

	/**
	 * Lock the avatar fieldset
	 *
	 * @param {boolean} lock_or_unlock
	 */
	function avatar_lock( lock_or_unlock ) {
		if ( lock_or_unlock === 'unlock' ) {
			avatar_working = false;
			$( '#wp-user-avatars-media, #wp-user-avatars-remove, #wp-user-avatars' ).prop( 'disabled', false );
		} else {
			avatar_working = true;
			$( '#wp-user-avatars-media, #wp-user-avatars-remove, #wp-user-avatars' ).prop( 'disabled', true );
		}
	}

	/**
	 * Show upload feedback without interrupting the profile form.
	 *
	 * @param {string} message Feedback message.
	 */
	function avatar_feedback( message ) {
		$( '#wp-user-avatars-feedback' ).text( message );
	}
} );
