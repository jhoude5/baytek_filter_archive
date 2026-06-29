<?php

namespace BaytekFilterArchive;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait Filters {

	/**
	 * Render filter UI.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $state Current state.
	 * @return string
	 */
	private function render_filters( $attributes, $state ) {
		$taxonomies = $this->get_filterable_taxonomies( $attributes['postTypes'], $attributes['selectedTaxonomies'] );
		$sort_by_options = $this->get_sort_by_options();
		$form_id = 'baytek-query-form-' . $attributes['queryId'];
		$has_active_filters = $this->has_active_filters( $state );

		ob_start();
		?>
		<?php if ( $attributes['showSortByFilter'] && $attributes['showSortByOutside'] ) : ?>
			<div class="baytek-query-filters-toolbar">
				<div class="baytek-query-filter baytek-query-filter-sort baytek-query-filter-sort-external filter-taxonomy wp-block-column select-container" id="sort-by">
					<label for="sort-by-external-select"><?php esc_html_e( 'Sort By', 'baytek-filter-archive' ); ?></label>
					<div class="select-wrapper">
						<select name="sort_by_external" id="sort-by-external-select" data-baytek-query-external-sort>
							<option value=""><?php esc_html_e( 'Default', 'baytek-filter-archive' ); ?></option>
							<?php foreach ( $sort_by_options as $sort_by ) : ?>
								<option value="<?php echo esc_attr( $sort_by['value'] ); ?>" <?php selected( $state['sortBy'], $sort_by['value'] ); ?>>
									<?php echo esc_html( $sort_by['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<form class="baytek-query-filters" data-baytek-query-form id="<?php echo esc_attr( $form_id ); ?>">
			<input type="hidden" name="page" value="<?php echo esc_attr( $state['page'] ); ?>">
			<?php if ( $attributes['showSortByFilter'] && $attributes['showSortByOutside'] ) : ?>
				<input type="hidden" name="sort_by" value="<?php echo esc_attr( $state['sortBy'] ); ?>">
			<?php endif; ?>
			<?php if ( $attributes['enableSearch'] ) : ?>
				<div class="baytek-query-filter filter-taxonomy wp-block-column input-container" id="keyword">
					<label for="keyword-search"><?php esc_html_e( 'Keyword', 'baytek-filter-archive' ); ?></label>
					<div class="select-wrapper">
						<input type="search" name="keyword" id="keyword-search" value="<?php echo esc_attr( $state['keyword'] ); ?>" placeholder="<?php esc_attr_e( 'Search', 'baytek-filter-archive' ); ?>">
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $attributes['showContentTypeFilter'] && count( $attributes['postTypes'] ) > 1 ) : ?>
				<div class="baytek-query-filter filter-taxonomy wp-block-column select-container" id="content-type">
					<label for="content-type-select"><?php esc_html_e( 'Content Type', 'baytek-filter-archive' ); ?></label>
					<div class="select-wrapper">
						<select name="content_type" id="content-type-select">
							<option value=""><?php esc_html_e( 'All', 'baytek-filter-archive' ); ?></option>
							<?php foreach ( $attributes['postTypes'] as $post_type ) : ?>
								<?php $post_type_object = get_post_type_object( $post_type ); ?>
								<?php if ( ! $post_type_object ) : ?>
									<?php continue; ?>
								<?php endif; ?>
								<option value="<?php echo esc_attr( $post_type ); ?>" <?php selected( $state['contentType'], $post_type ); ?>>
									<?php echo esc_html( $post_type_object->labels->singular_name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			<?php endif; ?>

			<?php foreach ( $taxonomies as $taxonomy ) : ?>
				<div class="baytek-query-filter filter-taxonomy wp-block-column select-container" id="<?php echo esc_attr( $taxonomy->name ); ?>">
					<label for="<?php echo esc_attr( $taxonomy->name . '-select' ); ?>"><?php echo esc_html( $taxonomy->labels->singular_name ); ?></label>
					<div class="select-wrapper">
						<select name="<?php echo esc_attr( $taxonomy->name ); ?>" id="<?php echo esc_attr( $taxonomy->name . '-select' ); ?>">
							<option value=""><?php esc_html_e( 'All', 'baytek-filter-archive' ); ?></option>
							<?php foreach ( $this->get_filter_terms( $taxonomy->name, $attributes['postTypes'] ) as $term ) : ?>
								<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $state['taxonomies'][ $taxonomy->name ] ?? '', $term->slug ); ?>>
									<?php echo esc_html( $term->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			<?php endforeach; ?>

			<?php if ( $attributes['enableEventDateFilter'] && in_array( $attributes['eventPostType'], $attributes['postTypes'], true ) ) : ?>
				<div class="baytek-query-filter filter-taxonomy wp-block-column select-container" id="event-status">
					<label for="event-status-select"><?php esc_html_e( 'Event Date', 'baytek-filter-archive' ); ?></label>
					<div class="select-wrapper">
						<select name="event_status" id="event-status-select">
							<option value=""><?php esc_html_e( 'All Events', 'baytek-filter-archive' ); ?></option>
							<option value="upcoming" <?php selected( $state['eventStatus'], 'upcoming' ); ?>><?php esc_html_e( 'Upcoming', 'baytek-filter-archive' ); ?></option>
							<option value="past" <?php selected( $state['eventStatus'], 'past' ); ?>><?php esc_html_e( 'Past', 'baytek-filter-archive' ); ?></option>
						</select>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $attributes['showSortByFilter'] && ! $attributes['showSortByOutside'] ) : ?>
				<div class="baytek-query-filter baytek-query-filter-sort filter-taxonomy wp-block-column select-container" id="sort-by">
					<label for="sort-by-select"><?php esc_html_e( 'Sort By', 'baytek-filter-archive' ); ?></label>
					<div class="select-wrapper">
						<select name="sort_by" id="sort-by-select">
							<option value=""><?php esc_html_e( 'Default', 'baytek-filter-archive' ); ?></option>
							<?php foreach ( $sort_by_options as $sort_by ) : ?>
								<option value="<?php echo esc_attr( $sort_by['value'] ); ?>" <?php selected( $state['sortBy'], $sort_by['value'] ); ?>>
									<?php echo esc_html( $sort_by['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $attributes['showSearchButton'] || $attributes['showClearFiltersButton'] ) : ?>
				<div class="baytek-query-filter-actions">
					<?php if ( $attributes['showSearchButton'] ) : ?>
						<button type="submit" class="baytek-query-button baytek-query-button-search">
							<?php echo esc_html( $attributes['searchButtonText'] ?: __( 'Search', 'baytek-filter-archive' ) ); ?>
						</button>
					<?php endif; ?>
					<?php if ( $attributes['showClearFiltersButton'] ) : ?>
						<button
							type="button"
							class="baytek-query-button baytek-query-button-clear<?php echo $has_active_filters ? '' : ' is-hidden'; ?>"
							data-baytek-query-clear
							<?php echo $has_active_filters ? '' : 'hidden'; ?>
						>
							<?php echo esc_html( $attributes['clearFiltersButtonText'] ?: __( 'Clear filters', 'baytek-filter-archive' ) ); ?>
						</button>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</form>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Get sort-by options for the filter UI.
	 *
	 * @return array
	 */
	private function get_sort_by_options() {
		return [
			[
				'value' => 'featured',
				'label' => __( 'Featured', 'baytek-filter-archive' ),
			],
			[
				'value' => 'newest',
				'label' => __( 'Newest', 'baytek-filter-archive' ),
			],
			[
				'value' => 'oldest',
				'label' => __( 'Oldest', 'baytek-filter-archive' ),
			],
		];
	}

	/**
	 * Get allowed sort-by values.
	 *
	 * @return array
	 */
	private function get_sort_by_values() {
		return array_column( $this->get_sort_by_options(), 'value' );
	}

	/**
	 * Determine whether the current state has any active filters applied.
	 *
	 * @param array $state Current state.
	 * @return bool
	 */
	private function has_active_filters( $state ) {
		if ( ! empty( $state['keyword'] ) || ! empty( $state['contentType'] ) || ! empty( $state['sortBy'] ) || ! empty( $state['eventStatus'] ) ) {
			return true;
		}

		foreach ( $state['taxonomies'] as $term_slug ) {
			if ( ! empty( $term_slug ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Apply the selected sort mode to query arguments.
	 *
	 * @param array  $args Query args.
	 * @param string $sort_by Selected sort value.
	 * @return array
	 */
	private function apply_sort_by_selection( $args, $sort_by ) {
		switch ( $sort_by ) {
			case 'featured':
				$args['orderby'] = [
					'menu_order' => 'ASC',
					'date' => 'DESC',
				];
				$args['order'] = 'DESC';
				break;

			case 'oldest':
				$args['orderby'] = 'date';
				$args['order'] = 'ASC';
				break;

			case 'newest':
			default:
				$args['orderby'] = 'date';
				$args['order'] = 'DESC';
				break;
		}

		return $args;
	}

	/**
	 * Get taxonomies available to selected post types.
	 *
	 * @param array $post_types Selected post types.
	 * @param array $selected_taxonomies Selected taxonomies.
	 * @return array
	 */
	private function get_filterable_taxonomies( $post_types, $selected_taxonomies ) {
		$taxonomies = [];

		foreach ( $selected_taxonomies as $taxonomy_name ) {
			$taxonomy = get_taxonomy( $taxonomy_name );

			if ( ! $taxonomy ) {
				continue;
			}

			if ( array_intersect( (array) $taxonomy->object_type, $post_types ) ) {
				$taxonomies[] = $taxonomy;
			}
		}

		return $taxonomies;
	}

	/**
	 * Get terms that belong to the current query post types.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @param array  $post_types Selected post types.
	 * @return array
	 */
	private function get_filter_terms( $taxonomy, $post_types ) {
		return get_terms(
			[
				'taxonomy' => $taxonomy,
				'hide_empty' => true,
				'orderby' => 'name',
				'order' => 'ASC',
				'object_ids' => get_posts(
					[
						'post_type' => $post_types,
						'post_status' => 'publish',
						'posts_per_page' => -1,
						'fields' => 'ids',
					]
				),
			]
		);
	}
}
