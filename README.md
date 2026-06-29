# Baytek Filter Archive

`Baytek Filter Archive` is a custom Gutenberg block plugin for building AJAX-powered query loops with filter controls, URL-based state, reusable post templates, and pagination.

## Features

- AJAX filtering without a full page reload
- URL query parameter syncing for filters and pagination
- Query support for posts and custom post types
- Optional keyword search
- Optional content type filter
- Optional taxonomy filters based on the selected post type(s)
- Optional event date filter for `Upcoming` and `Past` results using a meta field
- Optional sort by filter with `Featured`, `Newest`, and `Oldest`
- Optional search and clear filter buttons
- Optional results count with multiple display formats
- Optional pagination with first, previous, next, and last buttons
- Compact pagination with ellipses for larger result sets
- Layout controls for alignments, columns, gap spacing, and filter placement
- Support for synced patterns / reusable blocks (`wp_block`) as the result template

## Plugin Files

- `baytek-filter-archive.php`: WordPress plugin entry file
- `php/Plugin.php`: main plugin bootstrap and block registration
- `php/Blocks/BlockHelpers.php`: attribute, request, and query helpers
- `php/Blocks/Controls/Filters.php`: front-end filter rendering
- `php/Views/ResultsView.php`: results, results count, and pagination rendering
- `assets/js/editor.js`: block editor controls
- `assets/js/view.js`: front-end AJAX behavior
- `assets/css/style.css`: front-end styles

## Requirements

- WordPress `6.5+`
- PHP `7.4+`
- Block editor / Gutenberg enabled

## Installation

1. Place the plugin in `wp-content/plugins/baytek-filter-archive`.
2. Activate `Baytek Filter Archive` from the WordPress plugins screen.
3. Open the block editor for a page or post.
4. Insert the `Baytek Filter Archive` block.

## How To Use

1. Add the `Baytek Filter Archive` block to a page.
2. Choose the post type in the block sidebar.
3. Set the number of posts per page.
4. Choose the default query order.
5. Enable any filters you want to show on the front end.
6. Select the taxonomy filters that should be available.
7. Optionally choose a synced pattern / reusable block for each result item.
8. Publish or update the page.

## Block Settings

### Query

- `Post Type`: choose a single post type or `All`
- `Posts Per Page`: set the page size
- `Show Pagination`: show or hide pagination
- `Show Results Count`: show or hide result totals
- `Results Count Position`: display the count above or below the results
- `Results Count Format`: choose range, page total, or custom text
- `Order By`: editor-controlled default ordering
- `Order`: ascending or descending

### Filters

- `Enable Keyword Search`
- `Enable Content Type Filter`
- `Taxonomy Filters`
- `Enable Event Filter`
- `Enable Sort By Filter`
- `Sort By Position`
- `Add Search Button`
- `Add Clear Filters Button`

### Layout

- `Filters Position`: top of results or left sidebar
- `Columns`: number of result columns
- `Gap`: use native block spacing controls
- `Post Pattern`: choose a synced pattern / reusable block
- `Empty Message`: custom no-results message

## Results Count Custom Text

When `Results Count Format` is set to `Custom`, these placeholders are supported:

- `{start}`
- `{end}`
- `{total}`
- `{page}`
- `{pages}`

Examples:

- `Showing {start}-{end} of {total} results`
- `Page {page} of {pages}`

## URL Parameters

The block stores active filters in the page URL. Depending on enabled filters, parameters may include:

- `keyword`
- `content_type`
- taxonomy slugs such as `category`
- `event_status`
- `sort_by`
- `page`

## Event Filter Notes

The event date filter expects:

- a selected event post type
- a date meta key
- date values stored in a queryable format such as `Y-m-d`

If `Upcoming` is selected, results are ordered by the event date ascending.
If `Past` is selected, results are ordered by the event date descending.

## Template Notes

If a synced pattern / reusable block is selected as the result template:

- each queried post is rendered inside that block pattern
- post-aware core blocks use the current result post context

If no pattern is selected, the plugin falls back to a linked post title.

## Hooks

The plugin exposes WordPress filters and actions so theme code can change query behavior and extend the filter UI without modifying the plugin directly.

### Query Filters

- `baytek-filter-archive-build-query-args`
  Parameters: `$args`, `$attributes`, `$state`
  Use this to change the final `WP_Query` args before the query runs.

- `baytek-filter-archive-filterable-taxonomies`
  Parameters: `$taxonomies`, `$attributes`, `$state`
  Use this to adjust which taxonomies are available in the filter UI.

- `baytek-filter-archive-sort-by-options`
  Parameters: `$sort_by_options`, `$attributes`, `$state`
  Use this to modify the available sort options shown in the filter UI.

Example:

```php
add_filter( 'baytek-filter-archive-build-query-args', function( $args, $attributes, $state ) {
	$args['ignore_sticky_posts'] = true;
	return $args;
}, 10, 3 );
```

### Filter UI Actions

- `baytek-filter-archive-before-filters`
- `baytek-filter-archive-before-filters-toolbar`
- `baytek-filter-archive-after-filters-toolbar`
- `baytek-filter-archive-before-filters-form`
- `baytek-filter-archive-before-filters-fields`
- `baytek-filter-archive-after-filters-fields`
- `baytek-filter-archive-before-filter-actions`
- `baytek-filter-archive-after-filter-actions`
- `baytek-filter-archive-after-filters-form`
- `baytek-filter-archive-after-filters`

Each action receives:

- `$attributes`
- `$state`
- `$taxonomies`
- `$sort_by_options`

These hooks are useful when you want to output additional UI markup around the built-in filters.

Example:

```php
add_action( 'baytek-filter-archive-before-filter-actions', function( $attributes, $state ) {
	echo '<div class="archive-filter-help">Use the filters below to narrow your results.</div>';
}, 10, 2 );
```

### Filter UI HTML Filter

- `baytek-filter-archive-render-filters-html`
  Parameters: `$html`, `$attributes`, `$state`, `$taxonomies`, `$sort_by_options`
  Use this to alter the final rendered HTML for the filter form after output buffering.

Example:

```php
add_filter( 'baytek-filter-archive-render-filters-html', function( $html ) {
	return str_replace( 'baytek-query-filters', 'baytek-query-filters custom-filter-ui', $html );
} );
```

## Development Notes

- The block is server-rendered.
- Front-end filtering uses a custom REST endpoint: `baytek-filter-archive/v1/render`
- The plugin uses AJAX on the front end and keeps state in the URL.

## Known Structure

Current plugin structure assumes:

- `block.json` lives in the plugin root
- PHP source lives in `php/`
- front-end and editor assets live in `assets/`
