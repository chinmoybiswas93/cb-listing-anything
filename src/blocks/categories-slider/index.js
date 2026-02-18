import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes } ) {
		const { itemsToShow, height, showName, showCount, buttonPosition, buttonOutsideOffset, selectedCategoryIds = [] } = attributes;
		const blockProps = useBlockProps();

		const { categories, isLoadingCategories } = useSelect( ( select ) => {
			const data = select( 'core' ).getEntityRecords( 'taxonomy', 'cb_listing_category', { per_page: -1 } );
			return {
				categories: Array.isArray( data ) ? data : [],
				isLoadingCategories: data === undefined,
			};
		}, [] );

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
						<RangeControl
							label={ __( 'Items to show', 'cb-listing-anything' ) }
							value={ itemsToShow }
							onChange={ ( value ) => setAttributes( { itemsToShow: value } ) }
							min={ 2 }
							max={ 8 }
						/>
						<RangeControl
							label={ __( 'Height (px)', 'cb-listing-anything' ) }
							value={ height }
							onChange={ ( value ) => setAttributes( { height: value } ) }
							min={ 160 }
							max={ 500 }
							step={ 10 }
						/>
					</PanelBody>
					<PanelBody title={ __( 'Categories', 'cb-listing-anything' ) } initialOpen={ false }>
						<p style={ { marginBottom: '8px', fontSize: '12px', color: '#757575' } }>
							{ __( 'Select categories to show. Leave all unchecked to show all categories.', 'cb-listing-anything' ) }
						</p>
						{ isLoadingCategories && (
							<p style={ { fontSize: '12px', color: '#757575' } }>{ __( 'Loading…', 'cb-listing-anything' ) }</p>
						) }
						{ ! isLoadingCategories && categories.length === 0 && (
							<p style={ { fontSize: '12px', color: '#757575' } }>{ __( 'No categories found.', 'cb-listing-anything' ) }</p>
						) }
						{ ! isLoadingCategories && categories.length > 0 && categories.map( ( cat ) => (
							<ToggleControl
								key={ cat.id }
								label={ cat.name }
								checked={ selectedCategoryIds.includes( cat.id ) }
								onChange={ ( checked ) => {
									const next = checked
										? [ ...selectedCategoryIds, cat.id ]
										: selectedCategoryIds.filter( ( id ) => id !== cat.id );
									setAttributes( { selectedCategoryIds: next } );
								} }
							/>
						) ) }
					</PanelBody>
					<PanelBody title={ __( 'Content', 'cb-listing-anything' ) }>
						<ToggleControl
							label={ __( 'Show category name', 'cb-listing-anything' ) }
							checked={ showName }
							onChange={ ( value ) => setAttributes( { showName: value } ) }
						/>
						<ToggleControl
							label={ __( 'Show number of listings', 'cb-listing-anything' ) }
							checked={ showCount }
							onChange={ ( value ) => setAttributes( { showCount: value } ) }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<ServerSideRender
						block="cb-listing-anything/categories-slider"
						attributes={ attributes }
					/>
				</div>
			</>
		);
	},
} );
