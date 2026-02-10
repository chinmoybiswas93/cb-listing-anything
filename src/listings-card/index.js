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
			postsPerPage,
			columns,
			category,
			showExcerpt,
			showPrice,
			showLocation,
		} = attributes;

		const blockProps = useBlockProps();

		const categories = window.cbListingAnythingData?.categories || [
			{ label: __( 'All Categories', 'cb-listing-anything' ), value: 0 },
		];

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Display Settings', 'cb-listing-anything' ) }>
						<RangeControl
							label={ __( 'Number of Listings', 'cb-listing-anything' ) }
							value={ postsPerPage }
							onChange={ ( value ) => setAttributes( { postsPerPage: value } ) }
							min={ 1 }
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
							label={ __( 'Category', 'cb-listing-anything' ) }
							value={ category.toString() }
							options={ categories }
							onChange={ ( value ) => setAttributes( { category: parseInt( value, 10 ) } ) }
						/>
					</PanelBody>
					<PanelBody title={ __( 'Content Settings', 'cb-listing-anything' ) }>
						<ToggleControl
							label={ __( 'Show Excerpt', 'cb-listing-anything' ) }
							checked={ showExcerpt }
							onChange={ ( value ) => setAttributes( { showExcerpt: value } ) }
						/>
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
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<ServerSideRender
						block="cb-listing-anything/listings-card"
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
