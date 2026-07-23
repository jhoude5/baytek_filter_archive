<?php

namespace BaytekFilterArchive;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BlockHelpers {

	/**
	 * Apply plugin filter hooks.
	 *
	 * @param string $suffix Hook name suffix.
	 * @param mixed  $value Filtered value.
	 * @param mixed  ...$args Additional filter arguments.
	 * @return mixed
	 */
	private function apply_plugin_filters( $suffix, $value, ...$args ) {
		return apply_filters( 'baytek-filter-archive-' . $suffix, $value, ...$args );
	}

	/**
	 * Fire plugin action hooks.
	 *
	 * @param string $suffix Hook name suffix.
	 * @param mixed  ...$args Additional action arguments.
	 * @return void
	 */
	private function do_plugin_actions( $suffix, ...$args ) {
		do_action( 'baytek-filter-archive-' . $suffix, ...$args );
	}

	/**
	 * Get the block gap CSS values from the editor.
	 *
	 * The block declares axial blockGap support (separate row/column
	 * controls), so the stored value is an object ( `top` = row/vertical,
	 * `left` = column/horizontal ) rather than a single string.
	 *
	 * @param array $attributes Block attributes.
	 * @return array{row: string, column: string}
	 */
	private function get_block_gap_value( $attributes ) {
		$block_gap = $attributes['style']['spacing']['blockGap'] ?? '';

		if ( is_array( $block_gap ) ) {
			return [
				'row'    => $this->resolve_spacing_value( $block_gap['top'] ?? '' ),
				'column' => $this->resolve_spacing_value( $block_gap['left'] ?? '' ),
			];
		}

		$value = $this->resolve_spacing_value( $block_gap );

		return [
			'row'    => $value,
			'column' => $value,
		];
	}

	/**
	 * Resolve a raw spacing value, converting preset references to CSS variables.
	 *
	 * @param mixed $value Raw spacing value.
	 * @return string
	 */
	private function resolve_spacing_value( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return '';
		}

		if ( 0 === strpos( $value, 'var:preset|spacing|' ) ) {
			$preset_slug = substr( $value, strlen( 'var:preset|spacing|' ) );

			return '' !== $preset_slug ? 'var(--wp--preset--spacing--' . sanitize_key( $preset_slug ) . ')' : '';
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Normalize attributes.
	 *
	 * @param array $attributes Raw attributes.
	 * @return array
	 */
	private function normalize_attributes( $attributes ) {
		$defaults = [
			'queryId' => wp_generate_password( 8, false, false ),
			'postTypes' => [ 'post' ],
			'enableSearch' => true,
			'showContentTypeFilter' => false,
			'showSortByFilter' => false,
			'showSortByOutside' => false,
			'showSearchButton' => false,
			'searchButtonText' => __( 'Search', 'baytek-filter-archive' ),
			'showClearFiltersButton' => false,
			'clearFiltersButtonText' => __( 'Clear filters', 'baytek-filter-archive' ),
			'selectedTaxonomies' => [],
			'eventPostType' => 'event',
			'eventDateMetaKey' => 'eventDate',
			'enableEventDateFilter' => false,
			'postsPerPage' => 12,
			'showPagination' => true,
			'showResultsCount' => false,
			'resultsCountPosition' => 'above',
			'resultsCountFormat' => 'range_total',
			'resultsCountCustomText' => '',
			'noResultsMessage' => __( 'No results found.', 'baytek-filter-archive' ),
			'orderBy' => 'date',
			'orderByMetaKey' => '',
			'order' => 'desc',
			'columns' => 3,
			'columnsTablet' => 2,
			'columnsMobile' => 1,
			'filtersPosition' => 'top',
			'templateId' => 0,
		];

		$attributes = wp_parse_args( $attributes, $defaults );
		// Set the attributes based on the block editor settings selected
		$attributes['queryId']                = sanitize_key( $attributes['queryId'] );
		$attributes['postTypes']              = array_values( array_filter( array_map( 'sanitize_key', (array) $attributes['postTypes'] ) ) );
		$attributes['selectedTaxonomies']     = array_values( array_filter( array_map( 'sanitize_key', (array) $attributes['selectedTaxonomies'] ) ) );
		$attributes['eventPostType']          = sanitize_key( $attributes['eventPostType'] );
		$attributes['eventDateMetaKey']       = sanitize_text_field( (string) $attributes['eventDateMetaKey'] );
		$attributes['enableSearch']           = (bool) $attributes['enableSearch'];
		$attributes['showContentTypeFilter']  = (bool) $attributes['showContentTypeFilter'];
		$attributes['showSortByFilter']       = (bool) $attributes['showSortByFilter'];
		$attributes['showSortByOutside']      = $attributes['showSortByFilter'] ? (bool) $attributes['showSortByOutside'] : false;
		$attributes['showSearchButton']       = (bool) $attributes['showSearchButton'];
		$attributes['searchButtonText']       = sanitize_text_field( (string) $attributes['searchButtonText'] );
		$attributes['showClearFiltersButton'] = (bool) $attributes['showClearFiltersButton'];
		$attributes['clearFiltersButtonText'] = sanitize_text_field( (string) $attributes['clearFiltersButtonText'] );
		$attributes['enableEventDateFilter']  = (bool) $attributes['enableEventDateFilter'];
		$attributes['postsPerPage']           = max( 1, min( 50, (int) $attributes['postsPerPage'] ) );
		$attributes['showPagination']         = (bool) $attributes['showPagination'];
		$attributes['showResultsCount']       = (bool) $attributes['showResultsCount'];
		$attributes['resultsCountPosition']   = in_array( $attributes['resultsCountPosition'], [ 'above', 'below' ], true ) ? $attributes['resultsCountPosition'] : 'above';
		$attributes['resultsCountFormat']     = in_array( $attributes['resultsCountFormat'], [ 'range_total', 'page_total', 'custom' ], true ) ? $attributes['resultsCountFormat'] : 'range_total';
		$attributes['resultsCountCustomText'] = sanitize_text_field( (string) $attributes['resultsCountCustomText'] );
		$attributes['columns']                = max( 1, min( 6, (int) $attributes['columns'] ) );
		$attributes['columnsTablet']          = max( 1, min( 6, (int) $attributes['columnsTablet'] ) );
		$attributes['columnsMobile']          = max( 1, min( 6, (int) $attributes['columnsMobile'] ) );
		$attributes['filtersPosition']        = in_array( $attributes['filtersPosition'], [ 'top', 'sidebar' ], true ) ? $attributes['filtersPosition'] : 'top';
		$attributes['templateId']             = (int) $attributes['templateId'];
		$attributes['orderBy']                = in_array( $attributes['orderBy'], [ 'date', 'title', 'modified', 'menu_order', 'rand', 'meta_value' ], true ) ? $attributes['orderBy'] : 'date';
		$attributes['orderByMetaKey']         = sanitize_text_field( (string) $attributes['orderByMetaKey'] );
		$attributes['order']                  = 'asc' === strtolower( (string) $attributes['order'] ) ? 'ASC' : 'DESC';

		if ( empty( $attributes['postTypes'] ) ) {
			$attributes['postTypes'] = [ 'post' ];
		}

		return $attributes;
	}

	/**
	 * Get current request params from URL.
	 *
	 * @param array $attributes Block attributes.
	 * @return array
	 */
	private function get_request_params( $attributes ) {
		$params = [];

		foreach ( $_GET as $key => $value ) {
			$params[ $key ] = is_array( $value ) ? wp_unslash( $value ) : sanitize_text_field( wp_unslash( $value ) );
		}

		return $params;
	}

	/**
	 * Normalize state values.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $params Request params.
	 * @return array
	 */
	private function normalize_state( $attributes, $params ) {
		$state = [
			'keyword' => '',
			'contentType' => '',
			'sortBy' => '',
			'page' => 1,
			'eventStatus' => '',
			'taxonomies' => [],
		];

		// Keyword search filder
		if ( $attributes['enableSearch'] && ! empty( $params['keyword'] ) ) {
			$state['keyword'] = sanitize_text_field( $params['keyword'] );
		}

		// Filter by post type
		if ( $attributes['showContentTypeFilter'] && ! empty( $params['content_type'] ) ) {
			$content_type = sanitize_key( $params['content_type'] );

			if ( in_array( $content_type, $attributes['postTypes'], true ) ) {
				$state['contentType'] = $content_type;
			}
		}

		// Filter with sort by
		if ( $attributes['showSortByFilter'] && ! empty( $params['sort_by'] ) ) {
			$sort_by = sanitize_key( $params['sort_by'] );

			if ( in_array( $sort_by, $this->get_sort_by_values(), true ) ) {
				$state['sortBy'] = $sort_by;
			}
		}

		// Add page number for pagination
		if ( ! empty( $params['page'] ) ) {
			$state['page'] = max( 1, (int) $params['page'] );
		}

		// Filter by event date meta key
		if ( $attributes['enableEventDateFilter'] && ! empty( $params['event_status'] ) ) {
			$state['eventStatus'] = in_array( $params['event_status'], [ 'upcoming', 'past' ], true ) ? $params['event_status'] : '';
		}

		foreach ( $attributes['selectedTaxonomies'] as $taxonomy ) {
			if ( empty( $params[ $taxonomy ] ) ) {
				continue;
			}

			$state['taxonomies'][ $taxonomy ] = sanitize_title( $params[ $taxonomy ] );
		}

		return $state;
	}

	/**
	 * Build query args for the baytek query loop
	 *
	 * @param array $attributes Block attributes.
	 * @param array $state Current state.
	 * @return array
	 */
	private function build_query_args( $attributes, $state ) {
		$args = [
			'post_type' => $attributes['postTypes'],
			'post_status' => 'publish',
			'posts_per_page' => $attributes['postsPerPage'],
			'paged' => $state['page'],
			'order' => $attributes['order'],
			'orderby' => 'meta_value' === $attributes['orderBy'] && empty( $attributes['orderByMetaKey'] )
				? 'date'
				: $attributes['orderBy'],
		];

		if ( 'meta_value' === $attributes['orderBy'] && ! empty( $attributes['orderByMetaKey'] ) ) {
			$args['meta_key'] = $attributes['orderByMetaKey'];
		}

		// Filter by keyword search
		if ( ! empty( $state['keyword'] ) ) {
			$args['s'] = $state['keyword'];
		}

		// If Content type filter is requested then qeury on post type
		if ( ! empty( $state['contentType'] ) ) {
			$args['post_type'] = $state['contentType'];
		}

		// If Sort by filter is requested then query the sorting
		if ( ! empty( $state['sortBy'] ) ) {
			$args = $this->apply_sort_by_selection( $args, $state['sortBy'] );
		}

		// Filter by taxonomies selected
		if ( ! empty( $state['taxonomies'] ) ) {
			$args['tax_query'] = [
				'relation' => 'AND',
			];

			foreach ( $state['taxonomies'] as $taxonomy => $slug ) {
				$args['tax_query'][] = [
					'taxonomy' => $taxonomy,
					'field' => 'slug',
					'terms' => [ $slug ],
				];
			}
		}

		// If the event-date filter is requested then query upcoming and past events based on event meta keys
		if ( $attributes['enableEventDateFilter'] && in_array( $attributes['eventPostType'], $attributes['postTypes'], true ) && ! empty( $state['eventStatus'] ) ) {
			$today = current_time( 'Y-m-d' );
			$args['meta_key'] = $attributes['eventDateMetaKey'];

			if ( 'upcoming' === $state['eventStatus'] ) {
				$args['meta_query'] = [
					[
						'key' => $attributes['eventDateMetaKey'],
						'value' => $today,
						'compare' => '>=',
						'type' => 'DATE',
					],
				];
				$args['orderby'] = 'meta_value';
				$args['order'] = 'ASC';
			}

			if ( 'past' === $state['eventStatus'] ) {
				$args['meta_query'] = [
					[
						'key' => $attributes['eventDateMetaKey'],
						'value' => $today,
						'compare' => '<',
						'type' => 'DATE',
					],
				];
				$args['orderby'] = 'meta_value';
				$args['order'] = 'DESC';
			}
		}

		return $this->apply_plugin_filters( 'build-query-args', $args, $attributes, $state );
	}
}
