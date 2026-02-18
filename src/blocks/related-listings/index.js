import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
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
			showCategories,
			showOpenStatus,
			showPrice,
			showTags,
			showAddress,
			showCallButton,
		} = attributes;

		const blockProps = useBlockProps();

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Layout', 'cb-listing-anything' ) }>
						<RangeControl
							label={ __( 'Number of Listings', 'cb-listing-anything' ) }
							value={ postsPerPage }
							onChange={ ( value ) => setAttributes( { postsPerPage: value } ) }
							min={ 1 }
							max={ 12 }
						/>
						<RangeControl
							label={ __( 'Columns', 'cb-listing-anything' ) }
							value={ columns }
							onChange={ ( value ) => setAttributes( { columns: value } ) }
							min={ 1 }
							max={ 4 }
						/>
					</PanelBody>
					<PanelBody title={ __( 'Card Elements', 'cb-listing-anything' ) }>
						<ToggleControl
							label={ __( 'Show Categories', 'cb-listing-anything' ) }
							checked={ showCategories }
							onChange={ ( value ) => setAttributes( { showCategories: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show Open Status', 'cb-listing-anything' ) }
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
						block="cb-listing-anything/related-listings"
						attributes={ attributes }
					/>
				</div>
			</>
		);
	},
} );
