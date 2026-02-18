import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const { placeholder, buttonText } = attributes;
		const blockProps = useBlockProps( { className: 'cb-listing-search' } );

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Settings', 'cb-listing-anything' ) }>
						<TextControl
							label={ __( 'Placeholder Text', 'cb-listing-anything' ) }
							value={ placeholder }
							onChange={ ( value ) => setAttributes( { placeholder: value } ) }
						/>
						<TextControl
							label={ __( 'Button Text', 'cb-listing-anything' ) }
							value={ buttonText }
							onChange={ ( value ) => setAttributes( { buttonText: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<div className="cb-listing-search__form">
						<div className="cb-listing-search__field cb-listing-search__field--keyword">
							<svg className="cb-listing-search__icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
							<input
								type="text"
								className="cb-listing-search__input"
								placeholder={ placeholder }
								readOnly
							/>
						</div>
						<div className="cb-listing-search__field cb-listing-search__field--category">
							<svg className="cb-listing-search__icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
							<select className="cb-listing-search__select" disabled>
								<option>{ __( 'All Categories', 'cb-listing-anything' ) }</option>
							</select>
						</div>
						<button type="button" className="cb-listing-search__button" disabled>
							{ buttonText }
						</button>
					</div>
				</div>
			</>
		);
	},
} );
