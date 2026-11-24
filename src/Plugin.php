<?php
/**
 * Bootstrap class for Woo Quote Request.
 */

namespace Dagalakisgeo\WooQuoteRequest;

/**
 * Boots the plugin and registers its WordPress hooks.
 */
final class Plugin {

    /**
     * The capability a user needs to read and change quotes.
     */
    public const CAPABILITY = 'manage_woocommerce';

    public function __construct( private readonly string $plugin_file ) {}
    
    /**
     * Registers the plugin's hooks with WordPress.
     */

    public function register(): void {
        add_action( 'init', array( new Installer(), 'maybe_install' ) );
        add_action( 'woocommerce_single_product_summary', array( new QuoteButton(), 'render' ), 31 );
        $controller = new Rest\QuotesController( new QuoteRepository(), new RateLimiter(), new QuoteMailer() );
        add_action( 'rest_api_init', array( $controller, 'register_routes' ) );
    }
}
