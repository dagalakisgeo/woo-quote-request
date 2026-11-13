<?php
/**
 * Installer class for Woo Quote Request.
 */

namespace Dagalakisgeo\WooQuoteRequest;

/**
 * Creates and updates the plugin's database table.
 */
final class Installer {

    /**
     * Version of the table structure. Raise it when the table changes.
     */
    public const SCHEMA_VERSION = '1';

    /**
     * Name of the option that stores the installed schema version.
     */
    public const VERSION_OPTION = 'dgwqr_schema_version';

    /**
     * Returns the quotes table name, with this site's prefix.
     */
    public static function table_name(): string {
        global $wpdb;

        return $wpdb->prefix . 'dgwqr_quotes';
    }

    /**
     * Creates or updates the quotes table.
     */
    public function install(): void {
        // Filled in commit 14.
    }
}
