import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, ColorPalette } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit( { attributes, setAttributes, context } ) {
		const {
			textAlign,
			textColor,
			hoverColor,
			currentColor,
			fontSize,
			fontWeight,
			spacing,
		} = attributes;

		const blockProps = useBlockProps( {
			style: {
				textAlign: textAlign || 'left',
			},
		} );

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Breadcrumb Settings', 'cb-listing-anything' ) } initialOpen={ true }>
						<SelectControl
							label={ __( 'Text Alignment', 'cb-listing-anything' ) }
							value={ textAlign || 'left' }
							options={ [
								{ label: __( 'Left', 'cb-listing-anything' ), value: 'left' },
								{ label: __( 'Center', 'cb-listing-anything' ), value: 'center' },
								{ label: __( 'Right', 'cb-listing-anything' ), value: 'right' },
							] }
							onChange={ ( value ) => setAttributes( { textAlign: value } ) }
						/>
					</PanelBody>
					<PanelBody title={ __( 'Typography', 'cb-listing-anything' ) }>
						<SelectControl
							label={ __( 'Font Size', 'cb-listing-anything' ) }
							value={ fontSize || '' }
							options={ [
								{ label: __( 'Default', 'cb-listing-anything' ), value: '' },
								{ label: __( 'Small', 'cb-listing-anything' ), value: '0.75rem' },
								{ label: __( 'Normal', 'cb-listing-anything' ), value: '0.82rem' },
								{ label: __( 'Medium', 'cb-listing-anything' ), value: '0.9rem' },
								{ label: __( 'Large', 'cb-listing-anything' ), value: '1rem' },
							] }
							onChange={ ( value ) => setAttributes( { fontSize: value } ) }
						/>
						<SelectControl
							label={ __( 'Font Weight', 'cb-listing-anything' ) }
							value={ fontWeight || '' }
							options={ [
								{ label: __( 'Default', 'cb-listing-anything' ), value: '' },
								{ label: __( 'Normal', 'cb-listing-anything' ), value: '400' },
								{ label: __( 'Medium', 'cb-listing-anything' ), value: '500' },
								{ label: __( 'Semi Bold', 'cb-listing-anything' ), value: '600' },
								{ label: __( 'Bold', 'cb-listing-anything' ), value: '700' },
							] }
							onChange={ ( value ) => setAttributes( { fontWeight: value } ) }
						/>
					</PanelBody>
					<PanelBody title={ __( 'Colors', 'cb-listing-anything' ) }>
						<div className="components-base-control" style={ { marginBottom: '16px' } }>
							<label className="components-base-control__label">
								{ __( 'Text Color', 'cb-listing-anything' ) }
							</label>
							<div style={ { display: 'flex', gap: '8px', alignItems: 'center' } }>
								<input
									type="color"
									value={ textColor || '#666' }
									onChange={ ( e ) => setAttributes( { textColor: e.target.value } ) }
									style={ { width: '50px', height: '32px', borderRadius: '4px', border: '1px solid #ccc', cursor: 'pointer' } }
								/>
								<input
									type="text"
									value={ textColor || '#666' }
									onChange={ ( e ) => setAttributes( { textColor: e.target.value } ) }
									placeholder="#666"
									style={ { flex: 1, padding: '4px 8px', borderRadius: '4px', border: '1px solid #ccc' } }
								/>
							</div>
						</div>
						<div className="components-base-control" style={ { marginBottom: '16px' } }>
							<label className="components-base-control__label">
								{ __( 'Hover Color', 'cb-listing-anything' ) }
							</label>
							<div style={ { display: 'flex', gap: '8px', alignItems: 'center' } }>
								<input
									type="color"
									value={ hoverColor || '#0073aa' }
									onChange={ ( e ) => setAttributes( { hoverColor: e.target.value } ) }
									style={ { width: '50px', height: '32px', borderRadius: '4px', border: '1px solid #ccc', cursor: 'pointer' } }
								/>
								<input
									type="text"
									value={ hoverColor || '#0073aa' }
									onChange={ ( e ) => setAttributes( { hoverColor: e.target.value } ) }
									placeholder="#0073aa"
									style={ { flex: 1, padding: '4px 8px', borderRadius: '4px', border: '1px solid #ccc' } }
								/>
							</div>
						</div>
						<div className="components-base-control">
							<label className="components-base-control__label">
								{ __( 'Current Page Color', 'cb-listing-anything' ) }
							</label>
							<div style={ { display: 'flex', gap: '8px', alignItems: 'center' } }>
								<input
									type="color"
									value={ currentColor || '#333' }
									onChange={ ( e ) => setAttributes( { currentColor: e.target.value } ) }
									style={ { width: '50px', height: '32px', borderRadius: '4px', border: '1px solid #ccc', cursor: 'pointer' } }
								/>
								<input
									type="text"
									value={ currentColor || '#333' }
									onChange={ ( e ) => setAttributes( { currentColor: e.target.value } ) }
									placeholder="#333"
									style={ { flex: 1, padding: '4px 8px', borderRadius: '4px', border: '1px solid #ccc' } }
								/>
							</div>
						</div>
					</PanelBody>
					<PanelBody title={ __( 'Spacing', 'cb-listing-anything' ) }>
						<RangeControl
							label={ __( 'Bottom Margin', 'cb-listing-anything' ) }
							value={ spacing?.margin?.bottom ? parseFloat( spacing.margin.bottom ) : 1 }
							onChange={ ( value ) => setAttributes( {
								spacing: {
									...spacing,
									margin: {
										...spacing?.margin,
										bottom: value + 'rem',
									},
								},
							} ) }
							min={ 0 }
							max={ 5 }
							step={ 0.1 }
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<ServerSideRender
						block="cb-listing-anything/listing-breadcrumb"
						attributes={ attributes }
						context={ context }
					/>
				</div>
			</>
		);
	},
	save() {
		return null;
	},
} );
