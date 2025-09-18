/**
 * Gutenberg block registration script for Simple Contact.
 *
 * @package SimpleContact
 * @since 1.0.0
 */

/* global wp */

( function ( blocks, element, i18n, editor, components ) {
	var __                = i18n.__;
	var el                = element.createElement;
	var InspectorControls = editor.InspectorControls;
	var useBlockProps     = editor.useBlockProps ? editor.useBlockProps : function () {
		return {};
	};
	var PanelBody         = components.PanelBody;
	var TextControl       = components.TextControl;

	blocks.registerBlockType(
		'simple-contact/form',
		{
			title: __( 'Simple Contact Form', 'simple-contact' ),
			description: __( 'Display a simple contact form that stores submissions and emails the site administrator.', 'simple-contact' ),
			icon: 'email',
			category: 'widgets',
			attributes: {
				successMessage: {
					type: 'string',
					default: '',
				},
				cssClass: {
					type: 'string',
					default: '',
				},
			},
			edit: function ( props ) {
				var attributes    = props.attributes;
				var setAttributes = props.setAttributes;
				var blockProps    = useBlockProps( { className: 'simple-contact-form-block-preview' } );

				return el(
					element.Fragment,
					null,
					el(
						InspectorControls,
						null,
						el(
							PanelBody,
							{ title: __( 'Form Settings', 'simple-contact' ), initialOpen: true },
							el(
								TextControl,
								{
									label: __( 'Success message', 'simple-contact' ),
									help: __( 'Optional message displayed after a successful submission.', 'simple-contact' ),
									value: attributes.successMessage,
									onChange: function ( value ) {
										setAttributes( { successMessage: value } );
									},
								},
							),
							el(
								TextControl,
								{
									label: __( 'Additional CSS classes', 'simple-contact' ),
									help: __( 'Space separated list of classes added to the form wrapper.', 'simple-contact' ),
									value: attributes.cssClass,
									onChange: function ( value ) {
										setAttributes( { cssClass: value } );
									},
								},
							)
						)
					),
					el(
						'div',
						blockProps,
						el( 'p', { className: 'simple-contact-form-block-preview__title' }, __( 'Simple Contact Form', 'simple-contact' ) ),
						el( 'p', { className: 'simple-contact-form-block-preview__description' }, __( 'The form will be rendered on the front end.', 'simple-contact' ) )
					)
				);
			},
			save: function () {
				return null;
			},
		}
	);
}( window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor || window.wp.editor, window.wp.components ) );
