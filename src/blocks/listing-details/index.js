import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const {
			showPrice,
			showLocation,
			showAddress,
			showContact,
			showWebsite,
		} = attributes;

		const blockProps = useBlockProps();

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Visibility', 'cb-listing-anything' ) }>
						<ToggleControl
							label={ __( 'Show Price', 'cb-listing-anything' ) }
							checked={ showPrice }
							onChange={ ( value ) => setAttributes( { showPrice: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Location', 'cb-listing-anything' ) }
							checked={ showLocation }
							onChange={ ( value ) => setAttributes( { showLocation: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Address', 'cb-listing-anything' ) }
							checked={ showAddress }
							onChange={ ( value ) => setAttributes( { showAddress: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Contact Info', 'cb-listing-anything' ) }
							checked={ showContact }
							onChange={ ( value ) => setAttributes( { showContact: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Website', 'cb-listing-anything' ) }
							checked={ showWebsite }
							onChange={ ( value ) => setAttributes( { showWebsite: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<ServerSideRender
						block="cb-listing-anything/listing-details"
						attributes={ attributes }
					/>
				</div>
			</>
		);
	},
} );
