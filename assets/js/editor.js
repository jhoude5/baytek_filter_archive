( function( wp ) {
	const { __ } = wp.i18n;
	const { useEffect, createElement: el, Fragment } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor || wp.editor;
	const {
		PanelBody,
		Placeholder,
		Spinner,
		TextControl,
		TextareaControl,
		ToggleControl,
		RangeControl,
		SelectControl,
		CheckboxControl,
	} = wp.components;
	const { useSelect } = wp.data;
	const ServerSideRender = wp.serverSideRender;

	wp.blocks.registerBlockType( 'baytek/baytek-filter-archive', {
		edit: function( props ) {
			const { attributes, setAttributes, clientId } = props;
			const blockProps = useBlockProps();

			useEffect( function() {
				if ( ! attributes.queryId ) {
					setAttributes( { queryId: clientId.replace( /-/g, '' ).slice( 0, 8 ) } );
				}
			}, [ attributes.queryId, clientId ] );

			const postTypes = useSelect( function( select ) {
				return select( 'core' ).getPostTypes( { per_page: -1 } );
			}, [] );

			const taxonomies = useSelect( function( select ) {
				return select( 'core' ).getTaxonomies();
			}, [] );

			const reusableBlocks = useSelect( function( select ) {
				return select( 'core' ).getEntityRecords( 'postType', 'wp_block', { per_page: -1, status: 'publish' } );
			}, [] );

			if ( ! postTypes || ! taxonomies ) {
				return el(
					'div',
					blockProps,
					el( Placeholder, { label: __( 'Baytek Filter Archive', 'baytek-filter-archive' ) }, el( Spinner ) )
				);
			}

			const publicPostTypes = postTypes.filter( function( type ) {
				return !! type.viewable && type.slug !== 'attachment' && type.slug !== 'page';
			} );

			const selectedPostTypeValue =
				attributes.postTypes.length === publicPostTypes.length
					? 'all'
					: ( attributes.postTypes[0] || 'all' );

			const postTypeOptions = [
				{ label: __( 'All', 'baytek-filter-archive' ), value: 'all' },
			].concat(
				publicPostTypes.map( function( type ) {
					return {
						label: type.labels.singular_name,
						value: type.slug,
					};
				} )
			);

			const availableTaxonomies = taxonomies.filter( function( taxonomy ) {
				if ( ! taxonomy.types ) {
					return false;
				}

				return taxonomy.visibility && taxonomy.visibility.public && taxonomy.types.some( function( slug ) {
					return attributes.postTypes.includes( slug );
				} );
			} );

			const templateOptions = [ { label: __( 'Default (Title Only)', 'baytek-filter-archive' ), value: 0 } ];

			if ( reusableBlocks ) {
				reusableBlocks.forEach( function( block ) {
					const blockTitle =
						( block.title && ( block.title.rendered || block.title.raw ) ) ||
						block.name ||
						block.slug ||
						'(no title)';

					templateOptions.push( { label: blockTitle, value: block.id } );
				} );
			}

			const orderByOptions = [
				{ label: __( 'Publish Date', 'baytek-filter-archive' ), value: 'date' },
				{ label: __( 'Title', 'baytek-filter-archive' ), value: 'title' },
				{ label: __( 'Modified Date', 'baytek-filter-archive' ), value: 'modified' },
				{ label: __( 'Menu Order', 'baytek-filter-archive' ), value: 'menu_order' },
				{ label: __( 'Postmeta', 'baytek-filter-archive' ), value: 'meta_value' },
				{ label: __( 'Random', 'baytek-filter-archive' ), value: 'rand' },
			];

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Query', 'baytek-filter-archive' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Post Type', 'baytek-filter-archive' ),
							value: selectedPostTypeValue,
							options: postTypeOptions,
							onChange: function( value ) {
								if ( value === 'all' ) {
									setAttributes( {
										postTypes: publicPostTypes.map( function( type ) {
											return type.slug;
										} ),
									} );
									return;
								}

								setAttributes( { postTypes: [ value ] } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Posts Per Page', 'baytek-filter-archive' ),
							value: attributes.postsPerPage,
							onChange: function( value ) {
								setAttributes( { postsPerPage: value } );
							},
							min: 1,
							max: 50,
						} ),
						el( ToggleControl, {
							label: __( 'Show pagination', 'baytek-filter-archive' ),
							checked: !! attributes.showPagination,
							onChange: function( value ) {
								setAttributes( { showPagination: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show results count', 'baytek-filter-archive' ),
							checked: !! attributes.showResultsCount,
							onChange: function( value ) {
								setAttributes( { showResultsCount: value } );
							},
						} ),
						attributes.showResultsCount && el( SelectControl, {
							label: __( 'Results Count Position', 'baytek-filter-archive' ),
							value: attributes.resultsCountPosition,
							options: [
								{ label: __( 'Above results', 'baytek-filter-archive' ), value: 'above' },
								{ label: __( 'Below results', 'baytek-filter-archive' ), value: 'below' },
							],
							onChange: function( value ) {
								setAttributes( { resultsCountPosition: value } );
							},
						} ),
						attributes.showResultsCount && el( SelectControl, {
							label: __( 'Results Count Format', 'baytek-filter-archive' ),
							value: attributes.resultsCountFormat || 'range_total',
							options: [
								{ label: __( 'Showing 1-10 of 199 results', 'baytek-filter-archive' ), value: 'range_total' },
								{ label: __( 'Page 1 of 82', 'baytek-filter-archive' ), value: 'page_total' },
								{ label: __( 'Custom', 'baytek-filter-archive' ), value: 'custom' },
							],
							onChange: function( value ) {
								setAttributes( { resultsCountFormat: value } );
							},
						} ),
						attributes.showResultsCount && attributes.resultsCountFormat === 'custom' && el( TextControl, {
							label: __( 'Results Count Custom Text', 'baytek-filter-archive' ),
							value: attributes.resultsCountCustomText || '',
							onChange: function( value ) {
								setAttributes( { resultsCountCustomText: value } );
							},
							help: __( 'Use {start}, {end}, {total}, {page}, or {pages}.', 'baytek-filter-archive' ),
						} ),
						el( SelectControl, {
							label: __( 'Order By', 'baytek-filter-archive' ),
							value: attributes.orderBy,
							options: orderByOptions,
							onChange: function( value ) {
								setAttributes( {
									orderBy: value,
									orderByMetaKey: value === 'meta_value' ? attributes.orderByMetaKey : '',
								} );
							},
						} ),
						attributes.orderBy === 'meta_value' && el( TextControl, {
							label: __( 'Meta Key', 'baytek-filter-archive' ),
							value: attributes.orderByMetaKey || '',
							onChange: function( value ) {
								setAttributes( { orderByMetaKey: value } );
							},
							help: __( 'Enter the custom field key used for ordering.', 'baytek-filter-archive' ),
						} ),
						el( SelectControl, {
							label: __( 'Order', 'baytek-filter-archive' ),
							value: attributes.order,
							options: [
								{ label: __( 'Descending', 'baytek-filter-archive' ), value: 'desc' },
								{ label: __( 'Ascending', 'baytek-filter-archive' ), value: 'asc' },
							],
							onChange: function( value ) {
								setAttributes( { order: value } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Filters', 'baytek-filter-archive' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Enable Keyword Search', 'baytek-filter-archive' ),
							checked: !! attributes.enableSearch,
							onChange: function( value ) {
								setAttributes( { enableSearch: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Enable Content Type Filter', 'baytek-filter-archive' ),
							checked: !! attributes.showContentTypeFilter,
							onChange: function( value ) {
								setAttributes( { showContentTypeFilter: value } );
							},
							help: __( 'Lets visitors filter between the selected post types.', 'baytek-filter-archive' ),
						} ),
						el( 'p', { className: 'baytek-query-editor-section-label' }, __( 'Taxonomy Filters', 'baytek-filter-archive' ) ),
						availableTaxonomies.length
							? availableTaxonomies.map( function( taxonomy ) {
								return el( CheckboxControl, {
									key: taxonomy.slug,
									label: taxonomy.labels.singular_name,
									checked: attributes.selectedTaxonomies.includes( taxonomy.slug ),
									onChange: function( checked ) {
										const next = checked
											? attributes.selectedTaxonomies.concat( taxonomy.slug )
											: attributes.selectedTaxonomies.filter( function( slug ) {
												return slug !== taxonomy.slug;
											} );

										setAttributes( { selectedTaxonomies: next } );
									},
								} );
							} )
							: el( 'p', null, __( 'Select post types to choose available taxonomies.', 'baytek-filter-archive' ) ),
						el( ToggleControl, {
							label: __( 'Enable Event Filter', 'baytek-filter-archive' ),
							checked: !! attributes.enableEventDateFilter,
							onChange: function( value ) {
								setAttributes( { enableEventDateFilter: value } );
							},
						} ),
						attributes.enableEventDateFilter && el( SelectControl, {
							label: __( 'Event Post Type', 'baytek-filter-archive' ),
							value: attributes.eventPostType,
							options: publicPostTypes.map( function( type ) {
								return { label: type.labels.singular_name, value: type.slug };
							} ),
							onChange: function( value ) {
								setAttributes( { eventPostType: value } );
							},
						} ),
						attributes.enableEventDateFilter && el( TextControl, {
							label: __( 'Event Date Meta Key', 'baytek-filter-archive' ),
							value: attributes.eventDateMetaKey,
							onChange: function( value ) {
								setAttributes( { eventDateMetaKey: value } );
							},
							help: __( 'Example: eventDate', 'baytek-filter-archive' ),
						} ),
						el( ToggleControl, {
							label: __( 'Enable Sort By Filter', 'baytek-filter-archive' ),
							checked: !! attributes.showSortByFilter,
							onChange: function( value ) {
								setAttributes( {
									showSortByFilter: value,
									showSortByOutside: value ? attributes.showSortByOutside : false,
								} );
							},
						} ),
						attributes.showSortByFilter && el( SelectControl, {
							label: __( 'Sort By Position', 'baytek-filter-archive' ),
							value: attributes.showSortByOutside ? 'outside' : 'inside',
							options: [
								{ label: __( 'Inside filters', 'baytek-filter-archive' ), value: 'inside' },
								{ label: __( 'Above filters, right aligned', 'baytek-filter-archive' ), value: 'outside' },
							],
							onChange: function( value ) {
								setAttributes( { showSortByOutside: value === 'outside' } );
							},
							help: __( 'Choose whether the sort control appears in the filter box or above it.', 'baytek-filter-archive' ),
						} ),
						attributes.enableSearch && el( ToggleControl, {
							label: __( 'Add Search Button', 'baytek-filter-archive' ),
							checked: !! attributes.showSearchButton,
							onChange: function( value ) {
								setAttributes( { showSearchButton: value } );
							},
						} ),
						attributes.enableSearch && attributes.showSearchButton && el( TextControl, {
							label: __( 'Search Button Text', 'baytek-filter-archive' ),
							value: attributes.searchButtonText,
							onChange: function( value ) {
								setAttributes( { searchButtonText: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Add Clear Filters Button', 'baytek-filter-archive' ),
							checked: !! attributes.showClearFiltersButton,
							onChange: function( value ) {
								setAttributes( { showClearFiltersButton: value } );
							},
						} ),
						attributes.showClearFiltersButton && el( TextControl, {
							label: __( 'Clear Filters Button Text', 'baytek-filter-archive' ),
							value: attributes.clearFiltersButtonText,
							onChange: function( value ) {
								setAttributes( { clearFiltersButtonText: value } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Layout', 'baytek-filter-archive' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Placement of Filters', 'baytek-filter-archive' ),
							value: attributes.filtersPosition || 'top',
							options: [
								{ label: __( 'Top of results', 'baytek-filter-archive' ), value: 'top' },
								{ label: __( 'Left sidebar', 'baytek-filter-archive' ), value: 'sidebar' },
							],
							onChange: function( value ) {
								setAttributes( { filtersPosition: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Columns (Desktop)', 'baytek-filter-archive' ),
							value: attributes.columns,
							onChange: function( value ) {
								setAttributes( { columns: value } );
							},
							min: 1,
							max: 6,
							help: __( 'Applies at desktop widths (1024px and above).', 'baytek-filter-archive' ),
						} ),
						el( RangeControl, {
							label: __( 'Columns (Tablet)', 'baytek-filter-archive' ),
							value: attributes.columnsTablet,
							onChange: function( value ) {
								setAttributes( { columnsTablet: value } );
							},
							min: 1,
							max: 6,
							help: __( 'Applies at tablet widths (768px–1023px).', 'baytek-filter-archive' ),
						} ),
						el( RangeControl, {
							label: __( 'Columns (Mobile)', 'baytek-filter-archive' ),
							value: attributes.columnsMobile,
							onChange: function( value ) {
								setAttributes( { columnsMobile: value } );
							},
							min: 1,
							max: 6,
							help: __( 'Applies at mobile widths (below 768px).', 'baytek-filter-archive' ),
						} ),
						el( SelectControl, {
							label: __( 'Post Pattern', 'baytek-filter-archive' ),
							value: attributes.templateId,
							options: templateOptions,
							onChange: function( value ) {
								setAttributes( { templateId: parseInt( value, 10 ) || 0 } );
							},
							help: __( 'Choose a synced pattern/reusable block for each result.', 'baytek-filter-archive' ),
						} ),
						el( TextareaControl, {
							label: __( 'Empty Message', 'baytek-filter-archive' ),
							value: attributes.noResultsMessage,
							onChange: function( value ) {
								setAttributes( { noResultsMessage: value } );
							},
							rows: 4,
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( Placeholder, {
						label: __( 'Baytek Filter Archive', 'baytek-filter-archive' ),
						instructions: __( 'Configure post types, filters, and layout in the editor sidebar.', 'baytek-filter-archive' ),
					},
					attributes.queryId && el( ServerSideRender, {
						block: 'baytek/baytek-filter-archive',
						attributes: attributes,
					} ) )
				)
			);
		},
		save: function() {
			return null;
		},
	} );
} )( window.wp );
