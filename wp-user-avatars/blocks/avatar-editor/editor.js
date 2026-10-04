( function ( blocks, blockEditor, element, i18n ) {
	'use strict';

	var createElement = element.createElement;
	var PlainText = blockEditor.PlainText;
	var useBlockProps = blockEditor.useBlockProps;
	var __ = i18n.__;
	var normalizeText = function ( value ) {
		return value.replace( /\s+/g, ' ' ).replace( /^\s+/, '' );
	};

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
		attributes: {
			heading: {
				type: 'string',
				default: ''
			},
			description: {
				type: 'string',
				default: ''
			}
		},
		edit: function ( props ) {
			return createElement(
				'div',
				useBlockProps( { className: 'wp-user-avatars-block-preview' } ),
				createElement(
					'div',
					{ className: 'components-placeholder wp-user-avatars-block-preview__editor' },
					createElement(
						'div',
						{ className: 'components-placeholder__fieldset' },
						createElement( 'span', {
							className: 'dashicons dashicons-admin-users',
							'aria-hidden': 'true'
						} ),
						createElement(
							'div',
							{ className: 'wp-user-avatars-block-preview__copy' },
							createElement(
								PlainText,
								{
									className: 'wp-user-avatars-block-preview__heading',
									'aria-label': __( 'Avatar editor heading', 'wp-user-avatars' ),
									placeholder: __( 'Add an optional heading…', 'wp-user-avatars' ),
									value: props.attributes.heading,
									onChange: function ( heading ) {
										props.setAttributes( { heading: normalizeText( heading ) } );
									}
								}
							),
							createElement(
								PlainText,
								{
									className: 'wp-user-avatars-block-preview__description',
									'aria-label': __( 'Avatar editor description', 'wp-user-avatars' ),
									placeholder: __( 'Add an optional description…', 'wp-user-avatars' ),
									value: props.attributes.description,
									onChange: function ( description ) {
										props.setAttributes( { description: normalizeText( description ) } );
									}
								}
							)
						),
						createElement(
							'button',
							{
								className: 'components-button is-primary is-disabled',
								disabled: true,
								type: 'button'
							},
							__( 'Choose an avatar', 'wp-user-avatars' )
						)
					)
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element, window.wp.i18n );
