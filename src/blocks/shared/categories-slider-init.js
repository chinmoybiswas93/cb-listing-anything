if ( typeof window !== 'undefined' && ! window.cbListingAnythingCategoriesSliderInit ) {
	window.cbListingAnythingCategoriesSliderInit = true;

	function cbListingAnythingInitSliders() {
		document.querySelectorAll( '.cb-categories-slider' ).forEach( function ( block ) {
			var track = block.querySelector( '.cb-categories-slider__track' );
			var prev  = block.querySelector( '.cb-categories-slider__arrow--prev' );
			var next  = block.querySelector( '.cb-categories-slider__arrow--next' );

			if ( ! track || ! prev || ! next ) {
				return;
			}

			function getScrollStep() {
				// Try to move exactly one item (plus gap); fall back to viewport width.
				var item = track.querySelector( '.cb-categories-slider__item' );
				if ( item ) {
					var style = window.getComputedStyle( track );
					var gap   = parseFloat( style.gap ) || 0;
					var step  = item.offsetWidth + gap;
					if ( step > 0 ) {
						return step;
					}
				}
				return track.clientWidth * 0.9 || 0;
			}

			function updateArrows() {
				var maxScroll = track.scrollWidth - track.clientWidth;
				// Guard against negative/NaN.
				if ( maxScroll <= 0 ) {
					prev.disabled = true;
					next.disabled = true;
					return;
				}
				prev.disabled = track.scrollLeft <= 2;
				next.disabled = track.scrollLeft >= maxScroll - 2;
			}

			prev.addEventListener( 'click', function () {
				if ( prev.disabled ) return;
				var step = getScrollStep();
				if ( step <= 0 ) return;
				track.scrollBy( { left: -step, behavior: 'smooth' } );
			} );

			next.addEventListener( 'click', function () {
				if ( next.disabled ) return;
				var step = getScrollStep();
				if ( step <= 0 ) return;
				track.scrollBy( { left: step, behavior: 'smooth' } );
			} );

			track.addEventListener( 'scroll', updateArrows, { passive: true } );
			window.addEventListener( 'resize', updateArrows );
			updateArrows();
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', cbListingAnythingInitSliders );
	} else {
		cbListingAnythingInitSliders();
	}
}

