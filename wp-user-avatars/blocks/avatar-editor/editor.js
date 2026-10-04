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
				useBlockProps( { className: 'wp-user-avatars-avatar-editor-block wp-user-avatars-block-preview' } ),
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
					'div',
					{ className: 'wp-user-avatars-block-preview__form' },
					createElement( 'strong', { className: 'wp-user-avatars-block-preview__label' }, __( 'Upload', 'wp-user-avatars' ) ),
					createElement(
						'div',
						{ className: 'wp-user-avatars-block-preview__row' },
						createElement( 'span', {
							className: 'dashicons dashicons-admin-users wp-user-avatars-block-preview__avatar',
							'aria-hidden': 'true'
						} ),
						createElement(
							'div',
							{ className: 'wp-user-avatars-block-preview__actions' },
							createElement(
								'div',
								{ className: 'wp-user-avatars-block-preview__file' },
								createElement(
									'button',
									{ disabled: true, type: 'button' },
									__( 'Choose File', 'wp-user-avatars' )
								),
								createElement( 'span', null, __( 'No file selected', 'wp-user-avatars' ) )
							),
							createElement(
								'button',
								{ disabled: true, type: 'button' },
								__( 'Choose from Media', 'wp-user-avatars' )
							)
						)
					),
					createElement(
						'button',
						{
							className: 'wp-user-avatars-block-preview__save',
							disabled: true,
							type: 'button'
						},
						__( 'Save avatar', 'wp-user-avatars' )
					)
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.blockEditor, window.wp.element, window.wp.i18n );
