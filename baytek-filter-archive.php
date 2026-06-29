<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
Plugin Name: Baytek Filter Archive
Description: Dynamic query loop block with AJAX filters, URL state, pagination, and reusable block templates.
Version: 1.0.0
Author: Baytek
Author URI: http://www.baytek.ca
Text Domain: baytek-filter-archive
*/

use BaytekFilterArchive\Plugin;
require_once plugin_dir_path( __FILE__ ) . 'php/Plugin.php';

Plugin::bootstrap( __FILE__ );
