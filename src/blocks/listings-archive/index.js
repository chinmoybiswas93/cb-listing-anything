import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const {
			showFilterCategory,
			showFilterTag,
			showFilterPrice,
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
					</PanelBody>
					<PanelBody title={ __( 'Card elements', 'cb-listing-anything' ) }>
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
