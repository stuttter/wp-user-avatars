( function ( blocks, blockEditor, components, element, i18n ) {
	'use strict';

	var createElement = element.createElement;
	var Placeholder = components.Placeholder;
	var useBlockProps = blockEditor.useBlockProps;
	var __ = i18n.__;

	blocks.registerBlockType( 'wp-user-avatars/avatar-editor', {
		title: __( 'User Avatar Editor', 'wp-user-avatars' ),
		description: __( 'Let the signed-in user update their avatar.', 'wp-user-avatars' ),
		icon: 'admin-users',
		category: 'widgets',
		keywords: [
			__( 'avatar', 'wp-user-avatars' ),
			__( 'profile', 'wp-user-avatars' ),
			__( 'user', 'wp-user-avatars' )
		],
		supports: {
			html: false
		},
		edit: function () {
			return createElement(
				'div',
				useBlockProps( { className: 'wp-user-avatars-block-preview' } ),
				createElement(
					Placeholder,
					{
						icon: 'admin-users',
						label: __( 'User Avatar Editor', 'wp-user-avatars' )
					},
					createElement(
						'div',
						{ className: 'wp-user-avatars-block-preview__content' },
						createElement( 'span', {
							className: 'dashicons dashicons-admin-users',
							'aria-hidden': 'true'
						} ),
						createElement(
							'div',
							null,
							createElement( 'strong', null, __( 'Current user avatar', 'wp-user-avatars' ) ),
							createElement(
								'p',
								null,
								__( 'Signed-in visitors can upload, choose, rate, or remove their own avatar here.', 'wp-user-avatars' )
							)
						)
					),
					createElement(
						'span',
						{ className: 'components-button is-primary is-disabled' },
						__( 'Choose an avatar', 'wp-user-avatars' )
					)
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element, window.wp.i18n );
