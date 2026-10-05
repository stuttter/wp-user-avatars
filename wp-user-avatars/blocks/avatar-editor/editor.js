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
			align: [ 'wide' ],
			border: {
				color: true,
				radius: true,
				width: true
			},
			color: {
				background: true,
				text: true
			},
			html: false,
			spacing: {
				margin: true,
				padding: true
			},
			typography: {
				fontSize: true,
				lineHeight: true
			}
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
			var ratings = [
				[ 'G', __( 'Suitable for all audiences', 'wp-user-avatars' ) ],
				[ 'PG', __( 'Possibly offensive, usually for audiences 13 and above', 'wp-user-avatars' ) ],
				[ 'R', __( 'Intended for adult audiences above 17', 'wp-user-avatars' ) ],
				[ 'X', __( 'Even more mature than above', 'wp-user-avatars' ) ]
			];

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
						'fieldset',
						{
							className: 'wp-user-avatars-block-preview__ratings',
							disabled: true
						},
						createElement( 'legend', { className: 'wp-user-avatars-block-preview__label' }, __( 'Rating', 'wp-user-avatars' ) ),
						createElement(
							'div',
							{ className: 'wp-user-avatars-block-preview__rating-options' },
							ratings.map( function ( rating, index ) {
								return createElement(
									'label',
									{ key: rating[ 0 ] },
									createElement( 'input', {
										checked: 0 === index,
										disabled: true,
										name: 'wp-user-avatars-rating-preview',
										readOnly: true,
										type: 'radio'
									} ),
									createElement( 'span', null, rating[ 0 ] + ': ' + rating[ 1 ] )
								);
							} )
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
