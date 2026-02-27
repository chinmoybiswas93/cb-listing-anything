import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import ResponsiveDeviceRangeControl from '../shared/ResponsiveDeviceRangeControl';
import metadata from './block.json';
import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const {
			buttonPosition,
			buttonOutsideOffset,
			itemsToShow,
			itemsToShowDesktop,
			itemsToShowTablet,
			itemsToShowMobile,
			arrowBackgroundColor,
			arrowIconColor,
			arrowBorderRadius,
			arrowPadding,
			useCurrentQuery,
			postsPerPage,
			category,
			showCategories,
			showOpenStatus,
			showPrice,
			showTags,
			showAddress,
			showCallButton,
		} = attributes;

		const blockProps = useBlockProps();

		const categories = window.cbListingAnythingData?.categories || [
			{ label: __( 'All Categories', 'cb-listing-anything' ), value: 0 },
		];

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Slider', 'cb-listing-anything' ) }>
						<SelectControl
							label={ __( 'Button position', 'cb-listing-anything' ) }
							value={ buttonPosition }
							onChange={ ( value ) => setAttributes( { buttonPosition: value } ) }
							options={ [
								{ value: 'outside', label: __( 'Outside', 'cb-listing-anything' ) },
								{ value: 'inside', label: __( 'Inside', 'cb-listing-anything' ) },
							] }
						/>
						{ buttonPosition === 'outside' && (
							<RangeControl
								label={ __( 'Button offset from edge (px)', 'cb-listing-anything' ) }
								help={ __( 'Positive = inward, negative = outward from container.', 'cb-listing-anything' ) }
								value={ buttonOutsideOffset ?? 0 }
								onChange={ ( value ) => setAttributes( { buttonOutsideOffset: value } ) }
								min={ -80 }
								max={ 80 }
								step={ 1 }
							/>
						) }
						<ResponsiveDeviceRangeControl
							label={ __( 'Items to show', 'cb-listing-anything' ) }
							desktop={ itemsToShowDesktop || itemsToShow || 4 }
							tablet={ itemsToShowTablet || 2 }
							mobile={ itemsToShowMobile || 1 }
							min={ 1 }
							max={ 8 }
							step={ 1 }
							onChange={ ( next ) =>
								setAttributes( {
									itemsToShowDesktop: next.desktop,
									itemsToShowTablet: next.tablet,
									itemsToShowMobile: next.mobile,
									// Keep legacy itemsToShow roughly in sync for compatibility.
									itemsToShow: next.desktop,
								} )
							}
						/>
					</PanelBody>

					<PanelColorSettings
						title={ __( 'Arrow Colors', 'cb-listing-anything' ) }
						initialOpen={ false }
						colorSettings={ [
							{
								value: arrowBackgroundColor,
								onChange: ( value ) => setAttributes( { arrowBackgroundColor: value } ),
								label: __( 'Background', 'cb-listing-anything' ),
							},
							{
								value: arrowIconColor,
								onChange: ( value ) => setAttributes( { arrowIconColor: value } ),
								label: __( 'Icon', 'cb-listing-anything' ),
							},
						] }
					/>

					<PanelBody title={ __( 'Arrow Styles', 'cb-listing-anything' ) } initialOpen={ false }>
						<RangeControl
							label={ __( 'Border radius (%)', 'cb-listing-anything' ) }
							value={ arrowBorderRadius ?? 50 }
							onChange={ ( value ) => setAttributes( { arrowBorderRadius: value } ) }
							min={ 0 }
							max={ 50 }
						/>
						<RangeControl
							label={ __( 'Inner padding (px)', 'cb-listing-anything' ) }
							value={ arrowPadding ?? 0 }
							onChange={ ( value ) => setAttributes( { arrowPadding: value } ) }
							min={ 0 }
							max={ 12 }
						/>
					</PanelBody>

					<PanelBody title={ __( 'Query Settings', 'cb-listing-anything' ) }>
						<ToggleControl
							label={ __( 'Use Current Query', 'cb-listing-anything' ) }
							help={
								useCurrentQuery
									? __( 'Shows listings based on the current page/template query.', 'cb-listing-anything' )
									: __( 'Uses a custom query with the settings below.', 'cb-listing-anything' )
							}
							checked={ useCurrentQuery }
							onChange={ ( value ) => setAttributes( { useCurrentQuery: value } ) }
						/>
						<RangeControl
							label={ __( 'Number of Listings', 'cb-listing-anything' ) }
							value={ postsPerPage }
							onChange={ ( value ) => setAttributes( { postsPerPage: value } ) }
							min={ 1 }
							max={ 24 }
						/>
						{ ! useCurrentQuery && (
							<SelectControl
								label={ __( 'Category', 'cb-listing-anything' ) }
								value={ category.toString() }
								options={ categories }
								onChange={ ( value ) => setAttributes( { category: parseInt( value, 10 ) } ) }
							/>
						) }
					</PanelBody>

					<PanelBody title={ __( 'Card Elements', 'cb-listing-anything' ) }>
						<ToggleControl
							label={ __( 'Show Categories', 'cb-listing-anything' ) }
							checked={ showCategories }
							onChange={ ( value ) => setAttributes( { showCategories: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Open/Closed Status', 'cb-listing-anything' ) }
							checked={ showOpenStatus }
							onChange={ ( value ) => setAttributes( { showOpenStatus: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Price', 'cb-listing-anything' ) }
							checked={ showPrice }
							onChange={ ( value ) => setAttributes( { showPrice: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Tags', 'cb-listing-anything' ) }
							checked={ showTags }
							onChange={ ( value ) => setAttributes( { showTags: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Address', 'cb-listing-anything' ) }
							checked={ showAddress }
							onChange={ ( value ) => setAttributes( { showAddress: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Call Button', 'cb-listing-anything' ) }
							checked={ showCallButton }
							onChange={ ( value ) => setAttributes( { showCallButton: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<ServerSideRender
						block="cb-listing-anything/listing-cards-slider"
						attributes={ attributes }
					/>
				</div>
			</>
		);
	},
	save() {
		return null;
	},
} );

