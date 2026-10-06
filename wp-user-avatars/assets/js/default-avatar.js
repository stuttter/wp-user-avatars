( function ( $ ) {
	'use strict';

	$( function () {
		var frame;
		var $field = $( '#wp-user-avatars-default-avatar-field' );

		if ( ! $field.length || 'undefined' === typeof wp || ! wp.media ) {
			return;
		}

		var $input = $field.find( '.wp-user-avatars-default-avatar-id' );
		var $activate = $field.find( '.wp-user-avatars-default-avatar-activate' );
		var $preview = $field.find( '.wp-user-avatars-default-avatar-preview' );
		var $image = $preview.find( 'img' );
		var $remove = $field.find( '.wp-user-avatars-default-avatar-remove' );
		var $select = $field.find( '.wp-user-avatars-default-avatar-select' );
		var $avatarDefaults = $( 'input[name="avatar_default"]' );
		var $customDefault = $avatarDefaults.filter( function () {
			return $( this ).val() === i10n_WPUserAvatarsDefault.customUrl;
		} );
		var $customLabel = $customDefault.closest( 'label' );
		var errorId = 'wp-user-avatars-default-avatar-error';
		var lastInvalidId = null;
		var failedAttachmentIds = {};

		function getSelectedAttachment() {
			var selection = frame.state().get( 'selection' );

			return selection.length ? selection.first().toJSON() : null;
		}

		function isInvalidImage( attachment ) {
			var width = attachment ? parseInt( attachment.width, 10 ) : 0;
			var height = attachment ? parseInt( attachment.height, 10 ) : 0;

			return attachment && ( ! width || ! height || width !== height );
		}

		function isPendingImage( attachment ) {
			return attachment && ! failedAttachmentIds[ attachment.id ] && ( attachment.uploading || ! attachment.type );
		}

		function setElementVisible( $element, visible ) {
			$element.prop( 'hidden', ! visible ).toggleClass( 'hidden', ! visible );
		}

		function syncFrameSelection() {
			var selection = frame.state().get( 'selection' );
			var mediaId = parseInt( $input.val(), 10 ) || 0;
			var selected = selection.first();

			if ( ( selected ? selected.id : 0 ) === mediaId ) {
				return;
			}

			selection.reset();
			if ( mediaId ) {
				var attachment = wp.media.attachment( mediaId );

				selection.add( attachment );
				attachment.fetch().done( updateSelectionValidation ).fail( function () {
					failedAttachmentIds[ attachment.id ] = true;
					updateSelectionValidation();
				} );
			}
		}

		function syncCustomDefault( attachment ) {
			if ( ! $customDefault.length ) {
				return;
			}

			i10n_WPUserAvatarsDefault.customUrl = attachment.url;
			$customDefault.val( attachment.url ).prop( {
				checked: true,
				disabled: false
			} );
			$customLabel.find( 'img' ).attr( 'src', attachment.url ).removeAttr( 'srcset' );
			setElementVisible( $customLabel, true );
		}

		function disableCustomDefault() {
			if ( ! $customDefault.length ) {
				return;
			}

			if ( $customDefault.is( ':checked' ) ) {
				$avatarDefaults.filter( function () {
					return $( this ).val() === i10n_WPUserAvatarsDefault.mysteryValue;
				} ).prop( 'checked', true );
			}

			$customDefault.prop( {
				checked: false,
				disabled: true
			} );
			setElementVisible( $customLabel, false );
		}

		function updateSelectionValidation() {
			var attachment = getSelectedAttachment();
			var pending = isPendingImage( attachment );
			var invalid = ! pending && isInvalidImage( attachment );
			var $button = frame.$el.find( '.media-button-select' );
			var $error = frame.$el.find( '.wp-user-avatars-default-avatar-error' );

			if ( invalid ) {
				if ( ! $error.length || lastInvalidId !== attachment.id ) {
					$error.remove();
					$error = $( '<div>', {
						'id': errorId,
						'class': 'notice notice-error inline wp-user-avatars-default-avatar-error',
						'role': 'alert',
						'aria-live': 'assertive'
					} ).append( $( '<p>' ) );
					frame.$el.find( '.media-frame-toolbar .media-toolbar-secondary' ).append( $error );
				}

				lastInvalidId = attachment.id;
				$error.find( 'p' ).text( i10n_WPUserAvatarsDefault.squareImage );
				setElementVisible( $error, true );
				$button.attr( 'aria-describedby', errorId );
			} else {
				lastInvalidId = null;
				setElementVisible( $error, false );
				$button.removeAttr( 'aria-describedby' );
			}

			$button.prop( 'disabled', ! attachment || pending || invalid );
		}

		$select.on( 'click', function () {
			if ( ! frame ) {
				frame = wp.media( {
					title: i10n_WPUserAvatarsDefault.chooseTitle,
					button: { text: i10n_WPUserAvatarsDefault.chooseButton },
					library: { type: 'image' },
					multiple: false
				} );

				frame.on( 'open', function () {
					var selection = frame.state().get( 'selection' );

					selection.off( 'add remove reset change', updateSelectionValidation );
					selection.on( 'add remove reset change', updateSelectionValidation );
					syncFrameSelection();
					updateSelectionValidation();
				} );
				frame.on( 'content:activate toolbar:render:select', updateSelectionValidation );

				frame.el.addEventListener( 'click', function ( event ) {
					if ( ! $( event.target ).closest( '.media-button-select' ).length ) {
						return;
					}

					var attachment = getSelectedAttachment();
					if ( attachment && ! isPendingImage( attachment ) && ! isInvalidImage( attachment ) ) {
						return;
					}

					event.preventDefault();
					event.stopPropagation();
					event.stopImmediatePropagation();
					updateSelectionValidation();
				}, true );

				frame.on( 'select', function () {
					var attachment = getSelectedAttachment();

					if ( ! attachment || isPendingImage( attachment ) || isInvalidImage( attachment ) ) {
						frame.open();
						return;
					}

					$input.val( attachment.id );
					$activate.val( 1 );
					$image.attr( 'src', attachment.url );
					setElementVisible( $preview, true );
					setElementVisible( $remove, true );
					syncCustomDefault( attachment );
				} );
			}

			frame.open();
		} );

		$remove.on( 'click', function () {
			$input.val( 0 );
			$activate.val( 0 );
			$image.attr( 'src', '' );
			setElementVisible( $preview, false );
			setElementVisible( $remove, false );
			disableCustomDefault();
			$select.trigger( 'focus' );
		} );

		$avatarDefaults.on( 'change', function () {
			$activate.val( $( this ).val() === i10n_WPUserAvatarsDefault.customUrl ? 1 : 0 );
		} );
	} );
}( jQuery ) );
