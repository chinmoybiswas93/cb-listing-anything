import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import TableToolbarExtras from '../shared/components/TableToolbarExtras';
import {
	CATEGORY_COLUMN_KEYS,
	TAG_COLUMN_KEYS,
	TERM_SORT_VALUES,
	defaultTermVisibleColumns,
} from '../taxonomies/termTableConfig';

const COLUMN_LABELS = {
	thumb: () => __( 'Image', 'cb-listing-anything' ),
	name: () => __( 'Name', 'cb-listing-anything' ),
	slug: () => __( 'Slug', 'cb-listing-anything' ),
	count: () => __( 'Count', 'cb-listing-anything' ),
	description: () => __( 'Description', 'cb-listing-anything' ),
};

const SORT_LABELS = {
	name: () => __( 'Name', 'cb-listing-anything' ),
	slug: () => __( 'Slug', 'cb-listing-anything' ),
	count: () => __( 'Count', 'cb-listing-anything' ),
	id: () => __( 'ID', 'cb-listing-anything' ),
	description: () => __( 'Description', 'cb-listing-anything' ),
	parent: () => __( 'Parent', 'cb-listing-anything' ),
};

/**
 * Sort + column visibility (Categories / Tags tables).
 *
 * @param {Object}   props
 * @param {'categories'|'tags'} props.variant
 * @param {string}   props.orderby
 * @param {string}   props.order
 * @param {(orderby: string, order: string) => void} props.onSortApply
 * @param {Record<string, boolean>} props.visibleColumns
 * @param {(cols: Record<string, boolean>) => void} props.onColumnsApply
 */
export default function TaxonomyTableToolbarExtras( {
	variant,
	orderby,
	order,
	onSortApply,
	visibleColumns,
	onColumnsApply,
} ) {
	const columnKeys =
		variant === 'categories' ? CATEGORY_COLUMN_KEYS : TAG_COLUMN_KEYS;

	const sortOptions = useMemo( () => {
		const allowed =
			variant === 'categories'
				? TERM_SORT_VALUES
				: TERM_SORT_VALUES.filter( ( v ) => v !== 'parent' );
		return allowed.map( ( value ) => ( {
			value,
			label: SORT_LABELS[ value ] ? SORT_LABELS[ value ]() : value,
		} ) );
	}, [ variant ] );

	return (
		<TableToolbarExtras
			sortRadioGroupName={ `cb-term-sort-field-${ variant }` }
			sortOptions={ sortOptions }
			orderby={ orderby }
			order={ order }
			onSortApply={ onSortApply }
			columnKeys={ columnKeys }
			columnLabel={ ( key ) => COLUMN_LABELS[ key ]() }
			visibleColumns={ visibleColumns }
			onColumnsApply={ onColumnsApply }
			getDefaultColumns={ () => defaultTermVisibleColumns( variant ) }
		/>
	);
}
