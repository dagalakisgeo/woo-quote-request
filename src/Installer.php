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
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( $this->schema() );

        update_option( self::VERSION_OPTION, self::SCHEMA_VERSION );
    }

    /**
     * Returns the CREATE TABLE statement for the quotes table.
     */
    private function schema(): string {
        global $wpdb;

        $table           = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            status varchar(20) NOT NULL DEFAULT 'new',
            customer_name varchar(100) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_phone varchar(40) NOT NULL DEFAULT '',
            message text NOT NULL,
            items longtext NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";
    }
}