import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import TableToolbarExtras from '../shared/components/TableToolbarExtras';
import {
	LISTING_COLUMN_KEYS,
	defaultVisibleColumns,
} from '../features/listings/listingTableConfig';

const COLUMN_LABELS = {
	num: () => __( '#', 'cb-listing-anything' ),
	thumb: () => __( 'Image', 'cb-listing-anything' ),
	title: () => __( 'Title', 'cb-listing-anything' ),
	status: () => __( 'Status', 'cb-listing-anything' ),
	categories: () => __( 'Categories', 'cb-listing-anything' ),
	author: () => __( 'Author', 'cb-listing-anything' ),
	date: () => __( 'Date', 'cb-listing-anything' ),
};

/**
 * Sort + column visibility popovers (listings list toolbar).
 *
 * @param {Object}   props
 * @param {string}   props.orderby        Applied REST orderby.
 * @param {string}   props.order          Applied order asc|desc.
 * @param {(orderby: string, order: string) => void} props.onSortApply
 * @param {boolean}  props.hasSearch      Whether keyword search is active (enables relevance).
 * @param {Record<string, boolean>} props.visibleColumns
 * @param {(cols: Record<string, boolean>) => void} props.onColumnsApply
 */
export default function ListingsToolbarExtras( {
	orderby,
	order,
	onSortApply,
	hasSearch,
	visibleColumns,
	onColumnsApply,
} ) {
	const sortOptions = useMemo( () => {
		const base = [
			{
				value: 'date',
				label: __( 'Date published', 'cb-listing-anything' ),
			},
			{
				value: 'modified',
				label: __( 'Last modified', 'cb-listing-anything' ),
			},
			{ value: 'title', label: __( 'Title', 'cb-listing-anything' ) },
			{ value: 'author', label: __( 'Author', 'cb-listing-anything' ) },
			{ value: 'id', label: __( 'ID', 'cb-listing-anything' ) },
		];
		const relevance = {
			value: 'relevance',
			label: __( 'Relevance', 'cb-listing-anything' ),
			disabled: ! hasSearch,
		};
		return hasSearch ? [ relevance, ...base ] : base;
	}, [ hasSearch ] );

	const normalizeSortBeforeApply = useMemo(
		() => ( ob, ord ) => {
			if ( ob === 'relevance' && ! hasSearch ) {
				return [ 'date', ord ];
			}
			return [ ob, ord ];
		},
		[ hasSearch ]
	);

	return (
		<TableToolbarExtras
			sortRadioGroupName="cb-listing-sort-field"
			sortOptions={ sortOptions }
			orderby={ orderby }
			order={ order }
			onSortApply={ onSortApply }
			normalizeSortBeforeApply={ normalizeSortBeforeApply }
			columnKeys={ LISTING_COLUMN_KEYS }
			columnLabel={ ( key ) => COLUMN_LABELS[ key ]() }
			visibleColumns={ visibleColumns }
			onColumnsApply={ onColumnsApply }
			getDefaultColumns={ defaultVisibleColumns }
		/>
	);
}
