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

		$field.find( '.wp-user-avatars-default-avatar-select' ).on( 'click', function () {
			if ( ! frame ) {
				frame = wp.media( {
					title: i10n_WPUserAvatarsDefault.chooseTitle,
					button: { text: i10n_WPUserAvatarsDefault.chooseButton },
					library: { type: 'image' },
					multiple: false
				} );

				frame.on( 'select', function () {
					var attachment = frame.state().get( 'selection' ).first().toJSON();

					if ( attachment.width && attachment.height && attachment.width !== attachment.height ) {
						window.alert( i10n_WPUserAvatarsDefault.squareImage );
						return;
					}

					var previewUrl = attachment.sizes && attachment.sizes.thumbnail
						? attachment.sizes.thumbnail.url
						: attachment.url;

					$input.val( attachment.id );
					$activate.val( 1 );
					$image.attr( 'src', previewUrl );
					$preview.prop( 'hidden', false );
					$remove.prop( 'hidden', false );
				} );
			}

			frame.open();
		} );

		$remove.on( 'click', function () {
			$input.val( 0 );
			$activate.val( 0 );
			$image.attr( 'src', '' );
			$preview.prop( 'hidden', true );
			$remove.prop( 'hidden', true );
		} );

		$( 'input[name="avatar_default"]' ).on( 'change', function () {
			$activate.val( 0 );
		} );
	} );
}( jQuery ) );
