document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.cb-listing-single__gallery' ).forEach( function ( gallery ) {
		const track = gallery.querySelector( '.cb-listing-single__gallery-track' );
		const prevBtn = gallery.querySelector( '.cb-listing-single__gallery-arrow--prev' );
		const nextBtn = gallery.querySelector( '.cb-listing-single__gallery-arrow--next' );

		if ( ! track || ! prevBtn || ! nextBtn ) {
			return;
		}

		function getSlideWidth() {
			const slide = track.querySelector( '.cb-listing-single__gallery-slide' );
			if ( ! slide ) {
				return 0;
			}
			const style = window.getComputedStyle( track );
			const gap = parseFloat( style.gap ) || 8;
			return slide.offsetWidth + gap;
		}

		function updateArrows() {
			const maxScroll = track.scrollWidth - track.clientWidth;
			prevBtn.style.opacity = track.scrollLeft <= 1 ? '0' : '1';
			prevBtn.style.pointerEvents = track.scrollLeft <= 1 ? 'none' : 'auto';
			nextBtn.style.opacity = track.scrollLeft >= maxScroll - 1 ? '0' : '1';
			nextBtn.style.pointerEvents = track.scrollLeft >= maxScroll - 1 ? 'none' : 'auto';
		}

		prevBtn.addEventListener( 'click', function () {
			track.scrollBy( { left: -getSlideWidth(), behavior: 'smooth' } );
		} );

		nextBtn.addEventListener( 'click', function () {
			track.scrollBy( { left: getSlideWidth(), behavior: 'smooth' } );
		} );

		track.addEventListener( 'scroll', updateArrows, { passive: true } );
		updateArrows();
	} );

	document.querySelectorAll( '[data-hours-toggle]' ).forEach( function ( wrapper ) {
		const btn = wrapper.querySelector( '.cb-listing-single__hours-header' );
		const list = wrapper.querySelector( '.cb-listing-single__hours-list' );

		if ( ! btn || ! list ) {
			return;
		}

		btn.addEventListener( 'click', function () {
			const expanded = btn.getAttribute( 'aria-expanded' ) === 'true';
			btn.setAttribute( 'aria-expanded', String( ! expanded ) );
			list.hidden = expanded;
		} );
	} );
} );
