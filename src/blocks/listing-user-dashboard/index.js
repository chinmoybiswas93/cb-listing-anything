import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.scss';
import './editor.scss';

registerBlockType( metadata.name, {
	edit( { attributes } ) {
		const blockProps = useBlockProps();

		return (
			<div { ...blockProps }>
				<ServerSideRender block="cb-listing-anything/listing-user-dashboard" attributes={ attributes } />
			</div>
		);
	},
	save() {
		return null;
	},
} );

