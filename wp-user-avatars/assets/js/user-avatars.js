/* global i10n_WPUserAvatars, ajaxurl */
jQuery( document ).ready( function ( $ ) {

	/**
	 * Check if submit should move
	 */
	function checkSubmitMove() {
		var windowsize = $( window ).width();

		if ( windowsize > 1110 ) {
			$( '#your-profile p.submit' ).appendTo( $( '#wp-user-avatars-user-settings' ) );
		} else {
			$( '#wp-user-avatars-user-settings p.submit' ).appendTo( $( '#your-profile' ) );
		}
	}

	// Bind event listener
	$( window ).on( 'resize', function() {
		checkSubmitMove();
	} );

	// Fire right away
	checkSubmitMove();

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
			$.post( ajaxurl, {
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
		$.get( ajaxurl, {
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
			$( '#wp-user-avatars-media' ).prop( 'disabled', false );
		} else {
			avatar_working = true;
			$( '#wp-user-avatars-media' ).prop( 'disabled', true );
		}
	}
} );
