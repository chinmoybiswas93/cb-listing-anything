import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ButtonGroup, Button, RangeControl } from '@wordpress/components';

const DEVICES = [ 'desktop', 'tablet', 'mobile' ];

const LABELS = {
	desktop: __( 'Desktop', 'cb-listing-anything' ),
	tablet: __( 'Tablet', 'cb-listing-anything' ),
	mobile: __( 'Mobile', 'cb-listing-anything' ),
};

/**
 * Shared responsive range control with a device switcher.
 *
 * Props:
 * - label: string
 * - desktop: number
 * - tablet: number
 * - mobile: number
 * - min, max, step: numbers
 * - onChange: ( { desktop, tablet, mobile } ) => void
 */
export default function ResponsiveDeviceRangeControl( {
	label,
	desktop,
	tablet,
	mobile,
	min = 0,
	max = 10,
	step = 1,
	onChange,
} ) {
	const [ activeDevice, setActiveDevice ] = useState( 'desktop' );

	const currentValue =
		activeDevice === 'desktop'
			? desktop
			: activeDevice === 'tablet'
			? tablet
			: mobile;

	const handleChange = ( value ) => {
		const next = {
			desktop,
			tablet,
			mobile,
		};

		if ( activeDevice === 'desktop' ) {
			next.desktop = value;
		} else if ( activeDevice === 'tablet' ) {
			next.tablet = value;
		} else {
			next.mobile = value;
		}

		onChange?.( next );
	};

	return (
		<div className="cb-responsive-range-control">
			<div className="cb-responsive-range-control__header">
				<span className="cb-responsive-range-control__label">{ label }</span>
				<ButtonGroup
					aria-label={ __( 'Device', 'cb-listing-anything' ) }
					className="cb-responsive-range-control__devices"
				>
					{ DEVICES.map( ( device ) => (
						<Button
							key={ device }
							isSmall
							isPrimary={ activeDevice === device }
							isSecondary={ activeDevice !== device }
							onClick={ () => setActiveDevice( device ) }
						>
							<span aria-hidden="true">
								{ device === 'desktop' && '🖥' }
								{ device === 'tablet' && '📱' }
								{ device === 'mobile' && '📱' }
							</span>
							<span className="screen-reader-text">{ LABELS[ device ] }</span>
						</Button>
					) ) }
				</ButtonGroup>
			</div>
			<RangeControl
				label={ LABELS[ activeDevice ] }
				value={ currentValue }
				onChange={ handleChange }
				min={ min }
				max={ max }
				step={ step }
			/>
		</div>
	);
}

