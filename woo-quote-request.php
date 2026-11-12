<?php
/**
 * Plugin Name: Woo Quote Request
 * Plugin URI: https://github.com/dagalakisgeo/woo-quote-request
 * Description: Lets visitors request a quote for WooCommerce products sold without a public price.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Giorgos Dagalakis
 * Author URI: https://github.com/dagalakisgeo
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-quote-request
 * Update URI: https://github.com/dagalakisgeo/woo-quote-request
 */

namespace Dagalakisgeo\WooQuoteRequest;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
    add_action(
        'admin_notices',
        function () {
            if ( ! current_user_can( 'activate_plugins' ) ) {
                return;
            }

            echo '<div class="notice notice-error"><p>'
                . esc_html__( 'Woo Quote Request needs its Composer files. Run composer install in the plugin folder.', 'woo-quote-request' )
                . '</p></div>';
        }
    );
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

add_action(
    'plugins_loaded',
    function () {
        $plugin = new Plugin( __FILE__ );
        $plugin->register();
    }
);
