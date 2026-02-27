document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.cb-listing-single__gallery' ).forEach( function ( gallery ) {
		const track = gallery.querySelector( '.cb-listing-single__gallery-track' );
		const prevBtn = gallery.querySelector( '.cb-listing-single__gallery-arrow--prev' );
		const nextBtn = gallery.querySelector( '.cb-listing-single__gallery-arrow--next' );
		const slides = track ? Array.from( track.querySelectorAll( '.cb-listing-single__gallery-slide img' ) ) : [];

		if ( ! track ) {
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

		if ( prevBtn && nextBtn ) {
			prevBtn.addEventListener( 'click', function () {
				track.scrollBy( { left: -getSlideWidth(), behavior: 'smooth' } );
			} );

			nextBtn.addEventListener( 'click', function () {
				track.scrollBy( { left: getSlideWidth(), behavior: 'smooth' } );
			} );

			track.addEventListener( 'scroll', updateArrows, { passive: true } );
			updateArrows();
		}

		// Lightbox
		const lightbox = gallery.querySelector( '.cb-listing-single__lightbox' );
		const lightboxImg = lightbox ? lightbox.querySelector( '.cb-listing-single__lightbox-image' ) : null;
		const lightboxPrev = lightbox ? lightbox.querySelector( '.cb-listing-single__lightbox-arrow--prev' ) : null;
		const lightboxNext = lightbox ? lightbox.querySelector( '.cb-listing-single__lightbox-arrow--next' ) : null;
		const lightboxClose = lightbox ? lightbox.querySelector( '.cb-listing-single__lightbox-close' ) : null;
		const lightboxBackdrop = lightbox ? lightbox.querySelector( '.cb-listing-single__lightbox-backdrop' ) : null;

		if ( ! lightbox || ! lightboxImg || ! slides.length ) {
			return;
		}

		let currentIndex = 0;

		function getFullSrc( index ) {
			const img = slides[ index ];
			if ( ! img ) {
				return '';
			}
			return img.getAttribute( 'data-full' ) || img.src;
		}

		function openLightbox( index ) {
			currentIndex = index;
			const src = getFullSrc( currentIndex );
			const alt = slides[ currentIndex ] ? slides[ currentIndex ].alt || '' : '';

			if ( ! src ) {
				return;
			}

			lightboxImg.src = src;
			lightboxImg.alt = alt;
			lightbox.classList.add( 'is-open' );
			lightbox.setAttribute( 'aria-hidden', 'false' );
			document.documentElement.classList.add( 'cb-listing-single--lightbox-open' );
		}

		function closeLightbox() {
			lightbox.classList.remove( 'is-open' );
			lightbox.setAttribute( 'aria-hidden', 'true' );
			document.documentElement.classList.remove( 'cb-listing-single--lightbox-open' );
		}

		function showNext( step ) {
			if ( ! slides.length ) {
				return;
			}
			currentIndex = ( currentIndex + step + slides.length ) % slides.length;
			const src = getFullSrc( currentIndex );
			const alt = slides[ currentIndex ] ? slides[ currentIndex ].alt || '' : '';
			if ( ! src ) {
				return;
			}
			lightboxImg.src = src;
			lightboxImg.alt = alt;
		}

		slides.forEach( function ( img, index ) {
			img.style.cursor = 'pointer';
			img.addEventListener( 'click', function () {
				openLightbox( index );
			} );
		} );

		if ( lightboxPrev ) {
			lightboxPrev.addEventListener( 'click', function () {
				showNext( -1 );
			} );
		}

		if ( lightboxNext ) {
			lightboxNext.addEventListener( 'click', function () {
				showNext( 1 );
			} );
		}

		if ( lightboxClose ) {
			lightboxClose.addEventListener( 'click', function () {
				closeLightbox();
			} );
		}

		if ( lightboxBackdrop ) {
			lightboxBackdrop.addEventListener( 'click', function () {
				closeLightbox();
			} );
		}

		document.addEventListener( 'keydown', function ( event ) {
			if ( ! lightbox.classList.contains( 'is-open' ) ) {
				return;
			}

			if ( event.key === 'Escape' ) {
				closeLightbox();
			} else if ( event.key === 'ArrowRight' ) {
				showNext( 1 );
			} else if ( event.key === 'ArrowLeft' ) {
				showNext( -1 );
			}
		} );
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
