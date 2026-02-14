document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.cb-categories-slider' ).forEach( function ( block ) {
		var track = block.querySelector( '.cb-categories-slider__track' );
		var prev  = block.querySelector( '.cb-categories-slider__arrow--prev' );
		var next  = block.querySelector( '.cb-categories-slider__arrow--next' );

		if ( ! track || ! prev || ! next ) return;

		function getScrollStep() {
			var item = track.querySelector( '.cb-categories-slider__item' );
			if ( ! item ) return track.clientWidth * 0.8;
			var style = window.getComputedStyle( track );
			var gap   = parseFloat( style.gap ) || 16;
			return item.offsetWidth + gap;
		}

		function updateArrows() {
			var maxScroll = track.scrollWidth - track.clientWidth;
			prev.disabled = track.scrollLeft <= 2;
			next.disabled = track.scrollLeft >= maxScroll - 2;
		}

		prev.addEventListener( 'click', function () {
			if ( prev.disabled ) return;
			track.scrollBy( { left: -getScrollStep(), behavior: 'smooth' } );
		} );

		next.addEventListener( 'click', function () {
			if ( next.disabled ) return;
			track.scrollBy( { left: getScrollStep(), behavior: 'smooth' } );
		} );

		track.addEventListener( 'scroll', updateArrows, { passive: true } );
		window.addEventListener( 'resize', updateArrows );
		updateArrows();
	} );
} );
