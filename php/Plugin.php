<?php

namespace BaytekFilterArchive;

use WP_Block;
use WP_Post;
use WP_REST_Request;

require_once __DIR__ . '/Blocks/BlockHelpers.php';
require_once __DIR__ . '/Blocks/Controls/Filters.php';
require_once __DIR__ . '/Views/ResultsView.php';

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Main plugin class.
 */
class Plugin {
	use BlockHelpers;
	use Filters;
	use ResultsView;

	/**
	 * Plugin instance.
	 *
	 * @var static|null
	 */
	protected static $instance = null;

	/**
	 * Plugin paths.
	 *
	 * @var array
	 */
	protected $paths = [];

	/**
	 * Plugin URLs.
	 *
	 * @var array
	 */
	protected $urls = [];

	/**
	 * Rendering template post.
	 *
	 * @var WP_Post|null
	 */
	private $template_context_post = null;

	/**
	 * Prevent direct constructor access.
	 */
	protected function __construct() {}

	/**
	 * Prevent cloning.
	 */
	protected function __clone() {}

	/**
	 * Bootstrap plugin from the main plugin file.
	 *
	 * @param string $plugin_path Main plugin file path.
	 * @return void
	 */
	public static function bootstrap( $plugin_path ) {
		$plugin = static::getInstance();
		$plugin->setPaths( $plugin_path );
		$plugin->run();
	}

	/**
	 * Get singleton instance.
	 *
	 * @return static
	 */
	public static function getInstance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Set plugin paths and URLs.
	 *
	 * @param string $plugin_path Main plugin file path.
	 * @return void
	 */
	public function setPaths( $plugin_path ) {
		$this->paths['plugin-file'] = $plugin_path;
		$this->paths['plugin']      = WP_PLUGIN_DIR . '/' . basename( dirname( $plugin_path ) );
		$this->paths['assets']      = trailingslashit( $this->paths['plugin'] ) . 'assets';
		$this->paths['block-json']  = trailingslashit( $this->paths['plugin'] ) . 'block.json';

		$this->urls['plugin'] = plugins_url() . '/' . basename( dirname( $plugin_path ) );
		$this->urls['assets'] = trailingslashit( $this->urls['plugin'] ) . 'assets';
	}

	/**
	 * Get the main plugin file path.
	 *
	 * @return string
	 */
	public function getPluginFile() {
		return $this->paths['plugin-file'];
	}

	/**
	 * Get plugin directory path.
	 *
	 * @return string
	 */
	public function getPluginDir() {
		return $this->paths['plugin'];
	}

	/**
	 * Get assets directory path.
	 *
	 * @return string
	 */
	public function getAssetsDir() {
		return $this->paths['assets'];
	}

	/**
	 * Get block.json path.
	 *
	 * @return string
	 */
	public function getBlockJsonPath() {
		return $this->paths['block-json'];
	}

	/**
	 * Get plugin URL.
	 *
	 * @return string
	 */
	public function getPluginUrl() {
		return $this->urls['plugin'];
	}

	/**
	 * Get assets URL.
	 *
	 * @return string
	 */
	public function getAssetsUrl() {
		return $this->urls['assets'];
	}

	/**
	 * Run the plugin's default tasks.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'init', [ $this, 'register_assets' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Register scripts, styles, and block.
	 *
	 * @return void
	 */
	public function register_assets() {
		$assets_url = $this->getAssetsUrl();
		$assets_dir = $this->getAssetsDir();

		wp_register_script(
			'baytek-filter-archive-editor',
			$assets_url . '/js/editor.js',
			[
				'wp-api-fetch',
				'wp-block-editor',
				'wp-blocks',
				'wp-components',
				'wp-compose',
				'wp-data',
				'wp-editor',
				'wp-element',
				'wp-i18n',
				'wp-server-side-render',
			],
			filemtime( $assets_dir . '/js/editor.js' ),
			true
		);

		wp_register_script(
			'baytek-filter-archive-view',
			$assets_url . '/js/view.js',
			[],
			filemtime( $assets_dir . '/js/view.js' ),
			true
		);

		wp_localize_script(
			'baytek-filter-archive-view',
			'BaytekFilterArchiveView',
			[
				'restUrl' => esc_url_raw( rest_url( 'baytek-filter-archive/v1/render' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			]
		);

		wp_register_style(
			'baytek-filter-archive-style',
			$assets_url . '/css/style.css',
			[],
			filemtime( $assets_dir . '/css/style.css' )
		);

		register_block_type(
			$this->getBlockJsonPath(),
			[
				'render_callback' => [ $this, 'render_block' ],
			]
		);
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'baytek-filter-archive/v1',
			'/render',
			[
				'methods'             => 'POST',
				'permission_callback' => '__return_true',
				'callback'            => [ $this, 'rest_render' ],
			]
		);
	}

	/**
	 * Render block endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function rest_render( WP_REST_Request $request ) {
		$attributes = $request->get_param( 'attributes' );
		$params     = $request->get_param( 'params' );

		if ( ! is_array( $attributes ) ) {
			$attributes = [];
		}

		if ( ! is_array( $params ) ) {
			$params = [];
		}

		return rest_ensure_response( $this->build_response_payload( $attributes, $params ) );
	}

	/**
	 * Render block on the front end.
	 *
	 * @param array    $attributes Block attributes.
	 * @param string   $content Inner content.
	 * @param WP_Block $block Block instance.
	 * @return string
	 */
	public function render_block( $attributes, $content, $block ) {
		$attributes = $this->normalize_attributes( $attributes );
		$params     = $this->get_request_params( $attributes );
		$payload    = $this->build_response_payload( $attributes, $params );
		$style      = '';
		$block_gap  = $this->get_block_gap_value( $attributes );

		if ( $block_gap['row'] ) {
			$style .= '--baytek-query-gap-row:' . $block_gap['row'] . ';';
		}

		if ( $block_gap['column'] ) {
			$style .= '--baytek-query-gap-column:' . $block_gap['column'] . ';';
		}

		$style .= '--baytek-query-columns-tablet:' . (int) $attributes['columnsTablet'] . ';';
		$style .= '--baytek-query-columns-mobile:' . (int) $attributes['columnsMobile'] . ';';

		$wrapper = get_block_wrapper_attributes(
			[
				'class' => sprintf(
					'baytek-query-block baytek-query-columns-%1$d baytek-query-filters-position-%2$s',
					(int) $attributes['columns'],
					$attributes['filtersPosition']
				),
				'style' => $style,
			]
		);

		ob_start();
		?>
		<div
			<?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			data-baytek-query
			data-instance="<?php echo esc_attr( $attributes['queryId'] ); ?>"
			data-attributes="<?php echo esc_attr( wp_json_encode( $attributes ) ); ?>"
		>
			<div class="baytek-query-layout">
				<div class="baytek-query-filters-area">
					<?php echo $this->render_filters( $attributes, $payload['state'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="baytek-query-results" data-baytek-query-results>
					<?php echo $payload['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}
