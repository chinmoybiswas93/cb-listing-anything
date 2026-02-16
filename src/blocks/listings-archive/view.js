document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.cb-listings-archive' ).forEach( function ( block ) {
		var filtersForm    = block.querySelector( '.cb-listings-archive__filters-form' );
		var sortForm       = block.querySelector( '.cb-listings-archive__sort-form' );
		var gridContainer  = block.querySelector( '.cb-listings-archive__grid' );
		var paginationNav  = block.querySelector( '.cb-listings-archive__pagination' );
		var countSpan      = block.querySelector( '.cb-listings-archive__count' );
		var emptyMessage   = block.querySelector( '.cb-listings-archive__empty' );
		var mainContainer  = block.querySelector( '.cb-listings-archive__main' );
		var priceInputs    = block.querySelectorAll( '.cb-listings-archive__price-input' );
		var checkboxes     = block.querySelectorAll( '.cb-listings-archive__filter-label input[type="checkbox"]' );
		var sortSelect      = block.querySelector( '.cb-listings-archive__sort-select' );
		var clearLink       = block.querySelector( '.cb-listings-archive__clear-filters' );
		var filtersToggle   = block.querySelector( '.cb-listings-archive__filters-toggle' );
		var filtersAside    = block.querySelector( '.cb-listings-archive__filters' );
		var filtersForm     = block.querySelector( '.cb-listings-archive__filters-form' );
		var isLoading       = false;
		var debounceTimer   = null;

		if ( ! filtersForm || ! mainContainer ) {
			return;
		}

		// Get current URL without query params
		var baseUrl = window.location.protocol + '//' + window.location.host + window.location.pathname;
		
		// Check if we're on a taxonomy archive page by checking the URL structure
		// Archive URLs typically have the taxonomy slug in the path
		var isTaxArchive = baseUrl.match( /\/(cb-listing-category|cb-listing-tag)\// );
		var currentTaxTermId = null;
		if ( isTaxArchive ) {
			// Try to get current term ID from the page (might be in a data attribute or URL)
			// For now, we'll preserve it from the initial page load via URL params
			var urlParams = new URLSearchParams( window.location.search );
			// If there's a single tag/category in URL on initial load, preserve it
			var initialTags = urlParams.getAll( 'listing_tag[]' );
			if ( initialTags.length === 1 ) {
				currentTaxTermId = initialTags[0];
			}
		}

		// Build query params from form
		function getQueryParams() {
			var params = new URLSearchParams();
			
			// Get checked tags
			var checkedTags = [];
			block.querySelectorAll( 'input[name="listing_tag[]"]:checked' ).forEach( function ( cb ) {
				checkedTags.push( cb.value );
			} );
			
			// If on taxonomy archive and no tags checked, preserve current term
			if ( isTaxArchive && checkedTags.length === 0 && currentTaxTermId ) {
				checkedTags.push( currentTaxTermId );
			}
			
			checkedTags.forEach( function ( tag ) {
				params.append( 'listing_tag[]', tag );
			} );

			// Get price range
			var priceMin = block.querySelector( 'input[name="price_min"]' );
			var priceMax = block.querySelector( 'input[name="price_max"]' );
			if ( priceMin && priceMin.value ) {
				params.set( 'price_min', priceMin.value );
			}
			if ( priceMax && priceMax.value ) {
				params.set( 'price_max', priceMax.value );
			}

			// Get sort order
			if ( sortSelect && sortSelect.value ) {
				params.set( 'orderby', sortSelect.value );
			}

			return params;
		}

		// Fetch and update content
		function updateContent( params, updateUrl ) {
			if ( isLoading ) return;
			isLoading = true;

			// Add loading state
			if ( gridContainer ) {
				gridContainer.style.opacity = '0.5';
				gridContainer.style.pointerEvents = 'none';
			}

			var url = baseUrl;
			if ( params.toString() ) {
				url += '?' + params.toString();
			}

			fetch( url, {
				method: 'GET',
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
				},
			} )
				.then( function ( response ) {
					return response.text();
				} )
				.then( function ( html ) {
					var parser = new DOMParser();
					var doc     = parser.parseFromString( html, 'text/html' );
					var newBlock = doc.querySelector( '.cb-listings-archive' );
					
					if ( ! newBlock ) {
						console.error( 'Could not find archive block in response' );
						isLoading = false;
						if ( gridContainer ) {
							gridContainer.style.opacity = '';
							gridContainer.style.pointerEvents = '';
						}
						return;
					}

					// Update grid
					var newGrid = newBlock.querySelector( '.cb-listings-archive__grid' );
					if ( newGrid && gridContainer ) {
						gridContainer.innerHTML = newGrid.innerHTML;
					} else if ( ! newGrid && gridContainer ) {
						gridContainer.innerHTML = '';
					}

					// Update empty message
					var newEmpty = newBlock.querySelector( '.cb-listings-archive__empty' );
					if ( newEmpty ) {
						if ( emptyMessage ) {
							emptyMessage.textContent = newEmpty.textContent;
							emptyMessage.style.display = '';
						} else if ( mainContainer ) {
							var emptyEl = document.createElement( 'p' );
							emptyEl.className = 'cb-listings-archive__empty';
							emptyEl.textContent = newEmpty.textContent;
							if ( gridContainer ) {
								mainContainer.insertBefore( emptyEl, gridContainer );
							} else {
								mainContainer.appendChild( emptyEl );
							}
						}
					} else if ( emptyMessage ) {
						emptyMessage.style.display = 'none';
					}

					// Update count
					var newCount = newBlock.querySelector( '.cb-listings-archive__count' );
					if ( newCount && countSpan ) {
						countSpan.textContent = newCount.textContent;
					}

					// Update pagination
					var newPagination = newBlock.querySelector( '.cb-listings-archive__pagination' );
					if ( newPagination && paginationNav ) {
						paginationNav.innerHTML = newPagination.innerHTML;
						// No need to re-attach listeners - event delegation handles it
					} else if ( ! newPagination && paginationNav ) {
						paginationNav.innerHTML = '';
					}

					// Preserve filter toggle state after AJAX update
					if ( filtersToggle && filtersAside ) {
						var wasExpanded = filtersToggle.getAttribute( 'aria-expanded' ) === 'true';
						// State is preserved via data attribute, no need to update
					}

					// Update URL without reload FIRST (before syncing states)
					if ( updateUrl !== false ) {
						var newUrl = baseUrl;
						if ( params.toString() ) {
							newUrl += '?' + params.toString();
						}
						window.history.pushState( { archive: true }, '', newUrl );
					}

					// Update filter checkboxes state (sync with params that were used, not old URL)
					var tagParams = params.getAll( 'listing_tag[]' );
					checkboxes.forEach( function ( cb ) {
						var name = cb.name;
						if ( name === 'listing_tag[]' ) {
							var tagValue = cb.value;
							cb.checked = tagParams.includes( tagValue );
							// Update currentTaxTermId if this is the only tag
							if ( cb.checked && tagParams.length === 1 ) {
								currentTaxTermId = tagValue;
							}
						}
					} );

					// Update price inputs
					if ( priceInputs.length >= 1 ) {
						var urlPriceMin = params.get( 'price_min' );
						if ( urlPriceMin !== null ) {
							priceInputs[0].value = urlPriceMin;
						} else {
							priceInputs[0].value = '';
						}
					}
					if ( priceInputs.length >= 2 ) {
						var urlPriceMax = params.get( 'price_max' );
						if ( urlPriceMax !== null ) {
							priceInputs[1].value = urlPriceMax;
						} else {
							priceInputs[1].value = '';
						}
					}

					// Update sort select
					if ( sortSelect ) {
						var urlOrderby = params.get( 'orderby' );
						if ( urlOrderby ) {
							sortSelect.value = urlOrderby;
						} else {
							sortSelect.value = 'date';
						}
					}

					// Update clear link visibility
					var hasFilters = params.has( 'listing_tag[]' ) || params.has( 'price_min' ) || params.has( 'price_max' );
					if ( clearLink ) {
						if ( hasFilters ) {
							clearLink.style.display = '';
						} else {
							clearLink.style.display = 'none';
						}
					}

					isLoading = false;
					if ( gridContainer ) {
						gridContainer.style.opacity = '';
						gridContainer.style.pointerEvents = '';
					}
				} )
				.catch( function ( error ) {
					console.error( 'Error updating archive:', error );
					isLoading = false;
					if ( gridContainer ) {
						gridContainer.style.opacity = '';
						gridContainer.style.pointerEvents = '';
					}
				} );
		}

		// Handle pagination clicks using event delegation
		function attachPaginationListeners() {
			if ( ! paginationNav ) return;
			
			// Use event delegation on the pagination nav container
			paginationNav.addEventListener( 'click', function ( e ) {
				var link = e.target.closest( 'a' );
				if ( ! link || ! link.href ) return;
				
				e.preventDefault();
				var href = link.getAttribute( 'href' );
				if ( ! href ) return;

				// Extract paged parameter from URL
				var paged = null;
				try {
					// Handle relative URLs
					var url;
					if ( href.startsWith( 'http' ) ) {
						url = new URL( href );
					} else {
						url = new URL( href, window.location.origin );
					}
					paged = url.searchParams.get( 'paged' );
					if ( ! paged ) {
						// Try to extract from pathname (pretty permalinks)
						var match = url.pathname.match( /\/page\/(\d+)/ );
						if ( match ) {
							paged = match[1];
						}
					}
				} catch ( err ) {
					// Fallback: try regex on href string
					var match = href.match( /[?&]paged=(\d+)/ );
					if ( match ) {
						paged = match[1];
					} else {
						match = href.match( /\/page\/(\d+)/ );
						if ( match ) {
							paged = match[1];
						}
					}
				}

				if ( ! paged ) {
					console.warn( 'Could not extract page number from:', href );
					return;
				}

				var params = getQueryParams();
				var pageNum = parseInt( paged, 10 );
				if ( pageNum > 1 ) {
					params.set( 'paged', paged );
				} else {
					// Page 1 - remove paged param
					params.delete( 'paged' );
				}
				updateContent( params, true );
			} );
		}

		// Handle filter changes
		function handleFilterChange() {
			clearTimeout( debounceTimer );
			debounceTimer = setTimeout( function () {
				var params = getQueryParams();
				params.delete( 'paged' ); // Reset to page 1 on filter change
				updateContent( params, true );
			}, 300 );
		}

		// Handle checkbox changes - ensure state persists
		checkboxes.forEach( function ( checkbox ) {
			checkbox.addEventListener( 'change', function () {
				// Immediately update URL to reflect checkbox state
				var params = getQueryParams();
				params.delete( 'paged' );
				var newUrl = baseUrl;
				if ( params.toString() ) {
					newUrl += '?' + params.toString();
				}
				window.history.replaceState( { archive: true }, '', newUrl );
				// Then trigger the filter change handler
				handleFilterChange();
			} );
		} );

		// Handle price input changes
		priceInputs.forEach( function ( input ) {
			input.addEventListener( 'input', handleFilterChange );
		} );

		// Handle sort change
		if ( sortSelect ) {
			sortSelect.addEventListener( 'change', function () {
				var params = getQueryParams();
				params.delete( 'paged' ); // Reset to page 1 on sort change
				updateContent( params, true );
			} );
		}

		// Handle clear filters link
		if ( clearLink ) {
			clearLink.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				// Uncheck all checkboxes
				checkboxes.forEach( function ( cb ) {
					cb.checked = false;
				} );
				// Clear price inputs
				priceInputs.forEach( function ( input ) {
					input.value = '';
				} );
				// Reset sort
				if ( sortSelect ) {
					sortSelect.value = 'date';
				}
				// Update content
				var params = new URLSearchParams();
				updateContent( params, true );
			} );
		}

		// Prevent form submission
		if ( filtersForm ) {
			filtersForm.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
			} );
		}

		if ( sortForm ) {
			sortForm.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
			} );
		}

		// Handle mobile filter toggle (accordion)
		if ( filtersToggle && filtersForm ) {
			filtersToggle.addEventListener( 'click', function () {
				var isExpanded = filtersToggle.getAttribute( 'aria-expanded' ) === 'true';
				filtersToggle.setAttribute( 'aria-expanded', ! isExpanded );
				if ( filtersAside ) {
					filtersAside.setAttribute( 'data-expanded', ! isExpanded );
				}
			} );
		}

		// Initial pagination listeners (using event delegation, only need to attach once)
		if ( paginationNav ) {
			attachPaginationListeners();
		}

		// Handle browser back/forward
		window.addEventListener( 'popstate', function () {
			var params = new URLSearchParams( window.location.search );
			updateContent( params, false );
		} );

		// Sync filter states with URL on initial load
		var urlParams = new URLSearchParams( window.location.search );
		checkboxes.forEach( function ( cb ) {
			if ( cb.name === 'listing_tag[]' ) {
				var tagValue = cb.value;
				cb.checked = urlParams.getAll( 'listing_tag[]' ).includes( tagValue );
			}
		} );
		if ( priceInputs.length >= 1 ) {
			var urlPriceMin = urlParams.get( 'price_min' );
			if ( urlPriceMin !== null ) {
				priceInputs[0].value = urlPriceMin;
			}
		}
		if ( priceInputs.length >= 2 ) {
			var urlPriceMax = urlParams.get( 'price_max' );
			if ( urlPriceMax !== null ) {
				priceInputs[1].value = urlPriceMax;
			}
		}
		if ( sortSelect ) {
			var urlOrderby = urlParams.get( 'orderby' );
			if ( urlOrderby ) {
				sortSelect.value = urlOrderby;
			}
		}
	} );
} );
