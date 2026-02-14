document.addEventListener( 'DOMContentLoaded', function () {
	document.querySelectorAll( '.cb-listing-search' ).forEach( function ( block ) {
		var input     = block.querySelector( '.cb-listing-search__input' );
		var select    = block.querySelector( '.cb-listing-search__select' );
		var button    = block.querySelector( '.cb-listing-search__button' );
		var results   = block.querySelector( '.cb-listing-search__results' );
		var restUrl   = block.dataset.restUrl;
		var archiveUrl = block.dataset.archiveUrl;
		var timer     = null;

		if ( ! input || ! select || ! button || ! results || ! restUrl ) {
			return;
		}

		function fetchResults() {
			var keyword  = input.value.trim();
			var category = select.value;

			if ( keyword.length < 2 && ! category ) {
				results.hidden = true;
				results.innerHTML = '';
				return;
			}

			var url = restUrl + '/search?keyword=' + encodeURIComponent( keyword );
			if ( category ) {
				url += '&category=' + encodeURIComponent( category );
			}

			results.innerHTML = '<div class="cb-listing-search__loading"><span class="cb-listing-search__spinner"></span></div>';
			results.hidden = false;

			fetch( url )
				.then( function ( response ) { return response.json(); } )
				.then( function ( data ) {
					if ( input.value.trim().length < 2 && ! select.value ) {
						results.hidden = true;
						results.innerHTML = '';
						return;
					}
					renderResults( data );
				} )
				.catch( function () {
					results.innerHTML = '<div class="cb-listing-search__empty">Something went wrong.</div>';
				} );
		}

		function decodeHtmlEntities( text ) {
			if ( ! text ) return '';
			var div = document.createElement( 'div' );
			div.innerHTML = text;
			return div.textContent || div.innerText || '';
		}

		function renderResults( data ) {
			if ( ! data || data.length === 0 ) {
				results.innerHTML = '<div class="cb-listing-search__empty">No listings found.</div>';
				results.hidden = false;
				return;
			}

			var html = '';
			data.forEach( function ( item ) {
				var title = escapeHtml( decodeHtmlEntities( item.title ) );
				var metaParts = [];
				if ( item.category ) metaParts.push( decodeHtmlEntities( item.category ) );
				if ( item.location ) metaParts.push( decodeHtmlEntities( item.location ) );
				if ( item.price ) metaParts.push( decodeHtmlEntities( item.price ) );
				var meta = metaParts.length ? escapeHtml( metaParts.join( ' · ' ) ) : '';

				html += '<a href="' + escapeAttr( item.url ) + '" class="cb-listing-search__result-item">';
				if ( item.thumbnail ) {
					html += '<img class="cb-listing-search__result-thumb" src="' + escapeAttr( item.thumbnail ) + '" alt="" />';
				} else {
					html += '<span class="cb-listing-search__result-thumb cb-listing-search__result-thumb--empty"></span>';
				}
				html += '<div class="cb-listing-search__result-info">';
				html += '<span class="cb-listing-search__result-title">' + title + '</span>';
				if ( meta ) html += '<span class="cb-listing-search__result-meta">' + meta + '</span>';
				html += '</div></a>';
			} );

			results.innerHTML = html;
			results.hidden = false;
		}

		function escapeHtml( text ) {
			if ( ! text ) return '';
			var el = document.createElement( 'span' );
			el.textContent = text;
			return el.innerHTML;
		}

		function escapeAttr( text ) {
			if ( ! text ) return '';
			var el = document.createElement( 'span' );
			el.textContent = text;
			return el.innerHTML.replace( /"/g, '&quot;' );
		}

		function goToArchive() {
			var keyword  = input.value.trim();
			var category = select.value;
			var url      = archiveUrl || '/';
			var params   = [];
			if ( keyword ) params.push( 's=' + encodeURIComponent( keyword ) );
			if ( category ) params.push( 'listing_category=' + encodeURIComponent( category ) );
			if ( params.length ) url += ( url.indexOf( '?' ) !== -1 ? '&' : '?' ) + params.join( '&' );
			window.location.href = url;
		}

		input.addEventListener( 'input', function () {
			clearTimeout( timer );
			timer = setTimeout( fetchResults, 300 );
		} );

		select.addEventListener( 'change', function () {
			if ( input.value.trim().length >= 2 || select.value ) fetchResults();
		} );

		button.addEventListener( 'click', goToArchive );

		input.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) {
				e.preventDefault();
				goToArchive();
			}
		} );

		document.addEventListener( 'click', function ( e ) {
			if ( ! block.contains( e.target ) && ! results.contains( e.target ) ) {
				results.hidden = true;
			}
		} );

		input.addEventListener( 'focus', function () {
			if ( results.innerHTML && input.value.trim().length >= 2 ) results.hidden = false;
		} );
	} );
} );
