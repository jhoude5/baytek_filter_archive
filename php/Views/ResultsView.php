<?php

namespace BaytekFilterArchive;

use WP_Post;
use WP_Query;

if (! defined('ABSPATH')) exit; // Exit if accessed directly

trait ResultsView
{

	/**
	 * Build response payload.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $params Query params.
	 * @return array
	 */
	private function build_response_payload($attributes, $params)
	{
		$attributes = $this->normalize_attributes($attributes);
		$state = $this->normalize_state($attributes, $params);
		$query = new WP_Query($this->build_query_args($attributes, $state));

		ob_start();

		if ($query->have_posts()) {
			if ($attributes['showResultsCount'] && 'above' === $attributes['resultsCountPosition']) {
				echo $this->render_results_count($attributes, $query, $state); 
			}

			echo '<div class="baytek-query-grid wp-block-post-template">';

			foreach ($query->posts as $post) {
				$post_classes = implode(' ', get_post_class('baytek-query-item wp-block-post', $post));
				echo '<article class="' . esc_attr($post_classes) . '">';
				echo $this->render_result_template($attributes, $post); 
				echo '</article>';
			}

			echo '</div>';
			echo $this->render_results_footer($attributes, $state, $query);
		} else {
			printf(
				'<p class="baytek-query-empty">%s</p>',
				esc_html($attributes['noResultsMessage'])
			);
		}

		wp_reset_postdata();

		return [
			'html'  => (string) ob_get_clean(),
			'state' => $state,
		];
	}

	/**
	 * Render the results template.
	 *
	 * @param array   $attributes Block attributes.
	 * @param WP_Post $post Result post.
	 * @return string
	 */
	private function render_result_template($attributes, $post)
	{
		$template_post = $attributes['templateId'] ? get_post($attributes['templateId']) : null;

		if ($template_post instanceof WP_Post && 'wp_block' === $template_post->post_type) {
			$previous_context_post = $this->template_context_post;
			$previous_query = $GLOBALS['wp_query'] ?? null;
			$previous_post = $GLOBALS['post'] ?? null;
			$this->template_context_post = $post;

			add_filter('render_block_context', [$this, 'inject_template_context'], 10, 2);

			$item_query = new WP_Query(
				[
					'p' => $post->ID,
					'post_type' => $post->post_type,
					'post_status' => 'publish',
					'posts_per_page' => 1,
					'ignore_sticky_posts' => true,
				]
			);

			$GLOBALS['wp_query'] = $item_query;

			if ($item_query->have_posts()) {
				$item_query->the_post();
			} else {
				setup_postdata($post);
			}

			$html = do_blocks($template_post->post_content);
			remove_filter('render_block_context', [$this, 'inject_template_context'], 10);

			wp_reset_postdata();
			$GLOBALS['wp_query'] = $previous_query;

			if ($previous_post instanceof WP_Post) {
				$GLOBALS['post'] = $previous_post;
				setup_postdata($previous_post);
			}

			$this->template_context_post = $previous_context_post;

			return $html;
		}

		setup_postdata($post);

		return sprintf(
			'<h3 class="baytek-query-fallback-title"><a href="%1$s">%2$s</a></h3>',
			esc_url(get_permalink($post)),
			esc_html(get_the_title($post))
		);
	}

	/**
	 * Inject template context for post-aware blocks.
	 *
	 * @param array $context Block context.
	 * @return array
	 */
	public function inject_template_context($context)
	{
		if (! $this->template_context_post instanceof WP_Post) {
			return $context;
		}

		$context['postId'] = $this->template_context_post->ID;
		$context['postType'] = $this->template_context_post->post_type;

		return $context;
	}

	/**
	 * Render results footer.
	 *
	 * @param array    $attributes Block attributes.
	 * @param array    $state Current state.
	 * @param WP_Query $query Current query.
	 * @return string
	 */
	private function render_results_footer($attributes, $state, $query)
	{
		$show_results_count = $attributes['showResultsCount'] && 'below' === $attributes['resultsCountPosition'];
		$show_pagination = $attributes['showPagination'];

		if (! $show_results_count && ! $show_pagination) {
			return '';
		}

		ob_start();
?>
		<div class="baytek-query-results-footer">
			<?php
			if ($show_results_count) {
				echo $this->render_results_count($attributes, $query, $state);
			}

			if ($show_pagination) {
				echo $this->render_pagination($attributes, $state, $query->max_num_pages);
			}
			?>
		</div>
	<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render the results count text.
	 *
	 * @param array    $attributes Block attributes.
	 * @param WP_Query $query Current query.
	 * @param array    $state Current state.
	 * @return string
	 */
	private function render_results_count($attributes, $query, $state)
	{
		$total = (int) $query->found_posts;

		if ($total < 1) {
			return '';
		}

		$start = ((max(1, (int) $state['page']) - 1) * (int) $query->query_vars['posts_per_page']) + 1;
		$end   = min($start + count($query->posts) - 1, $total);
		$page  = max(1, (int) $state['page']);
		$pages = max(1, (int) $query->max_num_pages);

		if ('page_total' === $attributes['resultsCountFormat']) {
			// Render the count mesage example: Page 1 of 100
			$text = sprintf(
				/* 1: current page number, 2: total pages */
				__('Page %1$d of %2$d', 'baytek-filter-archive'),
				$page,
				$pages
			);
		} elseif ('custom' === $attributes['resultsCountFormat'] && '' !== $attributes['resultsCountCustomText']) {
			// Render custom count message
			$text = strtr(
				$attributes['resultsCountCustomText'],
				[
					'{start}' => (string) $start,
					'{end}' => (string) $end,
					'{total}' => (string) $total,
					'{page}' => (string) $page,
					'{pages}' => (string) $pages,
				]
			);
		} else {
			// Render count message example: Show 1 - 10 of 199 results
			$text = sprintf(
				/* 1: first result number, 2: last result number, 3: total results */
				__('Showing %1$d - %2$d of %3$d results', 'baytek-filter-archive'),
				$start,
				$end,
				$total
			);
		}

		return sprintf(
			'<p class="baytek-query-results-count has-small-font-size">%s</p>',
			esc_html($text)
		);
	}

	/**
	 * Render the pagination.
	 *
	 * @param array $attributes Block attributes.
	 * @param array $state Current state.
	 * @param int   $max_pages Max pages.
	 * @return string
	 */
	private function render_pagination($attributes, $state, $max_pages)
	{
		if ($max_pages < 2) {
			return '';
		}

		$current_page = max(1, (int) $state['page']);
		$prev_page = max(1, $current_page - 1);
		$next_page = min($max_pages, $current_page + 1);
		$page_window = $this->get_pagination_window($current_page, $max_pages);

		ob_start();
		?>
		<nav class="baytek-query-pagination pagination-wrapper" aria-label="<?php esc_attr_e('Results pagination', 'baytek-filter-archive'); ?>">
			<button
				type="button"
				class="baytek-query-page baytek-query-page-first<?php echo 1 === $current_page ? ' is-disabled' : ''; ?>"
				data-page="1"
				<?php disabled(1 === $current_page); ?>>
				<?php esc_html_e('First Page', 'baytek-filter-archive'); ?>
			</button>
			<button
				type="button"
				class="baytek-query-page baytek-query-page-prev<?php echo 1 === $current_page ? ' is-disabled' : ''; ?>"
				data-page="<?php echo esc_attr($prev_page); ?>"
				<?php disabled(1 === $current_page); ?>>
				<?php esc_html_e('Previous Page', 'baytek-filter-archive'); ?>
			</button>

			<?php if (! empty($page_window['show_leading_ellipsis'])) : ?>
				<span class="baytek-query-ellipsis" aria-hidden="true">&hellip;</span>
			<?php endif; ?>

			<?php for ($page = $page_window['start']; $page <= $page_window['end']; $page++) : ?>
				<button
					type="button"
					class="baytek-query-page<?php echo $page === $current_page ? ' is-active' : ''; ?>"
					data-page="<?php echo esc_attr($page); ?>">
					<?php echo esc_html($page); ?>
				</button>
			<?php endfor; ?>

			<?php if (! empty($page_window['show_trailing_ellipsis'])) : ?>
				<span class="baytek-query-ellipsis" aria-hidden="true">&hellip;</span>
			<?php endif; ?>

			<button
				type="button"
				class="baytek-query-page baytek-query-page-next<?php echo $max_pages === $current_page ? ' is-disabled' : ''; ?>"
				data-page="<?php echo esc_attr($next_page); ?>"
				<?php disabled($max_pages === $current_page); ?>>
				<?php esc_html_e('Next', 'baytek-filter-archive'); ?>
			</button>
			<button
				type="button"
				class="baytek-query-page baytek-query-page-last<?php echo $max_pages === $current_page ? ' is-disabled' : ''; ?>"
				data-page="<?php echo esc_attr($max_pages); ?>"
				<?php disabled($max_pages === $current_page); ?>>
				<?php esc_html_e('Last', 'baytek-filter-archive'); ?>
			</button>
		</nav>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Get the pagination window for compact page number rendering.
	 *
	 * @param int $current_page Current page.
	 * @param int $max_pages Maximum number of pages.
	 * @return array
	 */
	private function get_pagination_window($current_page, $max_pages)
	{
		if ($max_pages <= 3) {
			return [
				'start' => 1,
				'end' => $max_pages,
				'show_leading_ellipsis' => false,
				'show_trailing_ellipsis' => false,
			];
		}

		$start = max(1, $current_page - 1);
		$end = min($max_pages, $start + 2);
		$start = max(1, $end - 2);

		return [
			'start' => $start,
			'end' => $end,
			'show_leading_ellipsis' => $start > 1,
			'show_trailing_ellipsis' => $end < $max_pages,
		];
	}
}
