import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.scss';
import './editor.scss';

const BUSINESS_HOURS_FIELDS = [ 'listing_opening_time', 'listing_closing_time', 'listing_working_days' ];
const ADDRESS_FIELDS = [ 'listing_address', 'listing_city', 'listing_state', 'listing_zip_code', 'listing_country' ];

function useEnabledFieldsForCard() {
	const enabledFields = ( typeof window !== 'undefined' && window.cbListingAnythingData?.enabledFields ) || [];
	return {
		showCategories: true,
		showTags: true,
		showPrice: enabledFields.includes( 'listing_price' ),
		showOpenStatus: BUSINESS_HOURS_FIELDS.some( ( f ) => enabledFields.includes( f ) ),
		showAddress: ADDRESS_FIELDS.some( ( f ) => enabledFields.includes( f ) ),
		showCallButton: enabledFields.includes( 'listing_contact_phone' ),
	};
}

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const {
			showFilterSidebar,
			showFilterCategory,
			showFilterTag,
			showFilterPrice,
			showProductCount,
			showSorting,
			showCategoryTabs,
			showSubcategoryButtons,
			showEmptyCategories,
			postsPerPage,
			columns,
			orderBy,
			showCategories,
			showOpenStatus,
			showPrice,
			showTags,
			showAddress,
			showCallButton,
		} = attributes;

		const blockProps = useBlockProps();
		const cardOptions = useEnabledFieldsForCard();

		const orderByOptions = [
			{ label: __( 'Newest', 'cb-listing-anything' ), value: 'date' },
			{ label: __( 'Title A–Z', 'cb-listing-anything' ), value: 'title' },
			{ label: __( 'Price low–high', 'cb-listing-anything' ), value: 'price_asc' },
			{ label: __( 'Price high–low', 'cb-listing-anything' ), value: 'price_desc' },
		];

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Filters', 'cb-listing-anything' ) } initialOpen={ true }>
						<ToggleControl
							label={ __( 'Show filter sidebar', 'cb-listing-anything' ) }
							checked={ showFilterSidebar }
							onChange={ ( value ) => setAttributes( { showFilterSidebar: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Category filter', 'cb-listing-anything' ) }
							checked={ showFilterCategory }
							onChange={ ( value ) => setAttributes( { showFilterCategory: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Tag filter', 'cb-listing-anything' ) }
							checked={ showFilterTag }
							onChange={ ( value ) => setAttributes( { showFilterTag: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Price range filter', 'cb-listing-anything' ) }
							checked={ showFilterPrice }
							onChange={ ( value ) => setAttributes( { showFilterPrice: value } ) }
						/>
					</PanelBody>
					<PanelBody title={ __( 'Category navigation', 'cb-listing-anything' ) }>
						<ToggleControl
							label={ __( 'Show Category', 'cb-listing-anything' ) }
							checked={ showCategoryTabs }
							onChange={ ( value ) => setAttributes( { showCategoryTabs: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Subcategory', 'cb-listing-anything' ) }
							checked={ showSubcategoryButtons }
							onChange={ ( value ) => setAttributes( { showSubcategoryButtons: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show empty categories and subcategories', 'cb-listing-anything' ) }
							checked={ showEmptyCategories }
							onChange={ ( value ) => setAttributes( { showEmptyCategories: value } ) }
						/>
					</PanelBody>
					<PanelBody title={ __( 'Query / Display', 'cb-listing-anything' ) }>
						<RangeControl
							label={ __( 'Listings per page', 'cb-listing-anything' ) }
							value={ postsPerPage }
							onChange={ ( value ) => setAttributes( { postsPerPage: value } ) }
							min={ 6 }
							max={ 24 }
						/>
						<RangeControl
							label={ __( 'Columns', 'cb-listing-anything' ) }
							value={ columns }
							onChange={ ( value ) => setAttributes( { columns: value } ) }
							min={ 1 }
							max={ 4 }
						/>
						<SelectControl
							label={ __( 'Default sort', 'cb-listing-anything' ) }
							value={ orderBy }
							options={ orderByOptions }
							onChange={ ( value ) => setAttributes( { orderBy: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show product count', 'cb-listing-anything' ) }
							checked={ showProductCount }
							onChange={ ( value ) => setAttributes( { showProductCount: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show sorting options', 'cb-listing-anything' ) }
							checked={ showSorting }
							onChange={ ( value ) => setAttributes( { showSorting: value } ) }
						/>
					</PanelBody>
					<PanelBody title={ __( 'Card elements', 'cb-listing-anything' ) }>
						{ cardOptions.showCategories && (
							<ToggleControl
								label={ __( 'Show Categories', 'cb-listing-anything' ) }
								checked={ showCategories }
								onChange={ ( value ) => setAttributes( { showCategories: value } ) }
							/>
						) }
						{ cardOptions.showOpenStatus && (
							<ToggleControl
								label={ __( 'Show Open/Closed Status', 'cb-listing-anything' ) }
								checked={ showOpenStatus }
								onChange={ ( value ) => setAttributes( { showOpenStatus: value } ) }
							/>
						) }
						{ cardOptions.showPrice && (
							<ToggleControl
								label={ __( 'Show Price', 'cb-listing-anything' ) }
								checked={ showPrice }
								onChange={ ( value ) => setAttributes( { showPrice: value } ) }
							/>
						) }
						{ cardOptions.showTags && (
							<ToggleControl
								label={ __( 'Show Tags', 'cb-listing-anything' ) }
								checked={ showTags }
								onChange={ ( value ) => setAttributes( { showTags: value } ) }
							/>
						) }
						{ cardOptions.showAddress && (
							<ToggleControl
								label={ __( 'Show Address', 'cb-listing-anything' ) }
								checked={ showAddress }
								onChange={ ( value ) => setAttributes( { showAddress: value } ) }
							/>
						) }
						{ cardOptions.showCallButton && (
							<ToggleControl
								label={ __( 'Show Call Button', 'cb-listing-anything' ) }
								checked={ showCallButton }
								onChange={ ( value ) => setAttributes( { showCallButton: value } ) }
							/>
						) }
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<ServerSideRender
						block="cb-listing-anything/listings-archive"
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
