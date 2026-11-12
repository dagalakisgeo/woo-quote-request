<?php
/**
 * Bootstrap class for Woo Quote Request.
 */

namespace Dagalakisgeo\WooQuoteRequest;

/**
 * Boots the plugin and registers its WordPress hooks.
 */
final class Plugin {

    public function __construct( private readonly string $plugin_file ) {}

    /**
     * Registers the plugin’s hooks with WordPress.
     */
    public function register(): void {
        // Empty for now. From Thursday it adds the installer and button hooks.
    }
}
