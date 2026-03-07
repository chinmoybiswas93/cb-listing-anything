import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const {
			template,
			inquiryButtonLink,
			showGallery,
			showCategories,
			showTags,
			showContent,
			showCallButton,
			showAddress,
			showContact,
			showWebsite,
			showSocials,
			showHours,
			showPrice,
		} = attributes;

		const blockProps = useBlockProps();
		const isListingDetails = template === 'listing-details';
		const isProductDetails = template === 'product-details';

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Template', 'cb-listing-anything' ) } initialOpen={ true }>
						<SelectControl
							label={ __( 'Layout', 'cb-listing-anything' ) }
							value={ template }
							options={ [
								{ label: __( 'Listing Details', 'cb-listing-anything' ), value: 'listing-details' },
								{ label: __( 'Product Details', 'cb-listing-anything' ), value: 'product-details' },
							] }
							onChange={ ( value ) => setAttributes( { template: value } ) }
						/>
						{ isProductDetails && (
							<TextControl
								label={ __( 'Inquiry button link', 'cb-listing-anything' ) }
								value={ inquiryButtonLink ?? '' }
								onChange={ ( value ) => setAttributes( { inquiryButtonLink: value || '' } ) }
								help={ __( 'URL for the Product Inquiry button (e.g. contact page or mailto:). Leave empty to use the listing contact email.', 'cb-listing-anything' ) }
							/>
						) }
					</PanelBody>
					{ isListingDetails && (
						<>
							<PanelBody title={ __( 'Left Column', 'cb-listing-anything' ) }>
								<ToggleControl
									label={ __( 'Show Gallery', 'cb-listing-anything' ) }
									checked={ showGallery }
									onChange={ ( value ) => setAttributes( { showGallery: value } ) }
								/>
								<ToggleControl
									label={ __( 'Show Categories', 'cb-listing-anything' ) }
									checked={ showCategories }
									onChange={ ( value ) => setAttributes( { showCategories: value } ) }
								/>
								<ToggleControl
									label={ __( 'Show Tags', 'cb-listing-anything' ) }
									checked={ showTags }
									onChange={ ( value ) => setAttributes( { showTags: value } ) }
								/>
								<ToggleControl
									label={ __( 'Show Content', 'cb-listing-anything' ) }
									checked={ showContent }
									onChange={ ( value ) => setAttributes( { showContent: value } ) }
								/>
							</PanelBody>
							<PanelBody title={ __( 'Sidebar', 'cb-listing-anything' ) }>
								<ToggleControl
									label={ __( 'Show Call Button', 'cb-listing-anything' ) }
									checked={ showCallButton }
									onChange={ ( value ) => setAttributes( { showCallButton: value } ) }
								/>
								<ToggleControl
									label={ __( 'Show Address', 'cb-listing-anything' ) }
									checked={ showAddress }
									onChange={ ( value ) => setAttributes( { showAddress: value } ) }
								/>
								<ToggleControl
									label={ __( 'Show Contact', 'cb-listing-anything' ) }
									checked={ showContact }
									onChange={ ( value ) => setAttributes( { showContact: value } ) }
								/>
								<ToggleControl
									label={ __( 'Show Website', 'cb-listing-anything' ) }
									checked={ showWebsite }
									onChange={ ( value ) => setAttributes( { showWebsite: value } ) }
								/>
								<ToggleControl
									label={ __( 'Show Socials', 'cb-listing-anything' ) }
									checked={ showSocials }
									onChange={ ( value ) => setAttributes( { showSocials: value } ) }
								/>
								<ToggleControl
									label={ __( 'Show Business Hours', 'cb-listing-anything' ) }
									checked={ showHours }
									onChange={ ( value ) => setAttributes( { showHours: value } ) }
								/>
								<ToggleControl
									label={ __( 'Show Price', 'cb-listing-anything' ) }
									checked={ showPrice }
									onChange={ ( value ) => setAttributes( { showPrice: value } ) }
								/>
							</PanelBody>
						</>
					) }
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
