<?php
/**
 * Removes the plugin's data when Woo Quote Request is deleted.
 *
 * WordPress runs this file on its own, without the main plugin file,
 * so the two names below repeat those in src/Installer.php.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit; // Exit if not called by WordPress during an uninstall.
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}dgwqr_quotes" );

delete_option( 'dgwqr_schema_version' );
