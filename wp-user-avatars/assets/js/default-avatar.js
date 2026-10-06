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

		$select.on( 'click', function () {
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

					$input.val( attachment.id );
					$activate.val( 1 );
					$image.attr( 'src', attachment.url );
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
			$select.trigger( 'focus' );
		} );

		$( 'input[name="avatar_default"]' ).on( 'change', function () {
			$activate.val( $( this ).val() === i10n_WPUserAvatarsDefault.customUrl ? 1 : 0 );
		} );
	} );
}( jQuery ) );
