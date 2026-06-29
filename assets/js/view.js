( function() {
	function initBlock( block ) {
		const form = block.querySelector( '[data-baytek-query-form]' );
		const results = block.querySelector( '[data-baytek-query-results]' );
		const externalSortField = block.querySelector( '[data-baytek-query-external-sort]' );

		if ( ! form || ! results ) {
			return;
		}

		let isLoading = false;
		let attributes = {};
		try {
			attributes = JSON.parse( block.dataset.attributes || '{}' );
		} catch ( error ) {
			attributes = {};
		}
		const usesSearchButton = !! attributes.showSearchButton;
		const clearButton = form.querySelector( '[data-baytek-query-clear]' );

		function readState() {
			const data = {};
			const formData = new FormData( form );

			formData.forEach( function( value, key ) {
				if ( value !== '' ) {
					data[ key ] = value;
				}
			} );

			if ( externalSortField && externalSortField.value !== '' ) {
				data.sort_by = externalSortField.value;
			}

			return data;
		}

		function hasActiveFilters( state ) {
			return Object.keys( state ).some( function( key ) {
				return key !== 'page';
			} );
		}

		function updateClearButtonVisibility() {
			if ( ! clearButton ) {
				return;
			}

			const active = hasActiveFilters( readState() );
			clearButton.hidden = ! active;
			clearButton.classList.toggle( 'is-hidden', ! active );
		}

		function applyStateToUrl( state ) {
			const params = new URLSearchParams( window.location.search );

			Array.from( form.elements ).forEach( function( field ) {
				if ( field && field.name ) {
					params.delete( field.name );
				}
			} );

			Object.keys( state ).forEach( function( key ) {
				params.set( key, state[ key ] );
			} );

			const next = window.location.pathname + ( params.toString() ? '?' + params.toString() : '' ) + window.location.hash;
			window.history.pushState( { baytekFilterArchive: true }, '', next );
		}

		function applyUrlToForm() {
			const params = new URLSearchParams( window.location.search );
			Array.from( form.elements ).forEach( function( field ) {
				if ( ! field.name ) {
					return;
				}

				const value = params.get( field.name );
				field.value = value || '';
			} );

			if ( externalSortField ) {
				externalSortField.value = params.get( 'sort_by' ) || '';
			}

			updateClearButtonVisibility();
		}

		function render( pushState ) {
			if ( isLoading ) {
				return;
			}

			const state = readState();

			isLoading = true;
			block.classList.add( 'is-loading' );
			updateClearButtonVisibility();

			if ( pushState ) {
				applyStateToUrl( state );
			}

			window.fetch( BaytekFilterArchiveView.restUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': BaytekFilterArchiveView.nonce,
				},
				body: JSON.stringify( {
					attributes: attributes,
					params: state,
				} ),
			} )
				.then( function( response ) {
					return response.json();
				} )
				.then( function( payload ) {
					results.innerHTML = payload.html || '';
				} )
				.finally( function() {
					isLoading = false;
					block.classList.remove( 'is-loading' );
				} );
		}

		form.addEventListener( 'change', function( event ) {
			if ( usesSearchButton ) {
				return;
			}

			if ( event.target && event.target.name !== 'keyword' ) {
				const pageField = form.querySelector( '[name="page"]' );
				if ( pageField ) {
					pageField.value = '1';
				}
				updateClearButtonVisibility();
				render( true );
			}
		} );

		if ( externalSortField ) {
			externalSortField.addEventListener( 'change', function() {
				const sortField = form.querySelector( '[name="sort_by"]' );
				const pageField = form.querySelector( '[name="page"]' );

				if ( sortField ) {
					sortField.value = externalSortField.value;
				}

				if ( pageField ) {
					pageField.value = '1';
				}

				updateClearButtonVisibility();

				if ( ! usesSearchButton ) {
					render( true );
				}
			} );
		}

		form.addEventListener( 'submit', function( event ) {
			event.preventDefault();
			const pageField = form.querySelector( '[name="page"]' );
			if ( pageField ) {
				pageField.value = '1';
			}
			render( true );
		} );

		const searchField = form.querySelector( '[name="keyword"]' );
		if ( searchField && ! usesSearchButton ) {
			let timeoutId;
			searchField.addEventListener( 'input', function() {
				window.clearTimeout( timeoutId );
				timeoutId = window.setTimeout( function() {
					const pageField = form.querySelector( '[name="page"]' );
					if ( pageField ) {
						pageField.value = '1';
					}
					updateClearButtonVisibility();
					render( true );
				}, 250 );
			} );
		}

		if ( clearButton ) {
			clearButton.addEventListener( 'click', function( event ) {
				event.preventDefault();

				Array.from( form.elements ).forEach( function( field ) {
					if ( ! field.name ) {
						return;
					}

					if ( field.name === 'page' ) {
						field.value = '1';
						return;
					}

					if ( field.type === 'checkbox' || field.type === 'radio' ) {
						field.checked = false;
						return;
					}

					field.value = '';
				} );

				if ( externalSortField ) {
					externalSortField.value = '';
				}

				updateClearButtonVisibility();
				render( true );
			} );
		}

		block.addEventListener( 'click', function( event ) {
			const button = event.target.closest( '.baytek-query-page' );
			if ( ! button ) {
				return;
			}

			event.preventDefault();

			let pageField = form.querySelector( '[name="page"]' );
			if ( ! pageField ) {
				pageField = document.createElement( 'input' );
				pageField.type = 'hidden';
				pageField.name = 'page';
				form.appendChild( pageField );
			}

			pageField.value = button.dataset.page || '1';
			render( true );
		} );

		window.addEventListener( 'popstate', function() {
			applyUrlToForm();
			render( false );
		} );

		applyUrlToForm();
		updateClearButtonVisibility();
	}

	document.addEventListener( 'DOMContentLoaded', function() {
		document.querySelectorAll( '[data-baytek-query]' ).forEach( initBlock );
	} );
} )();
