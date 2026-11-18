<?php
/**
 * Database access for quote requests.
 */

namespace Dagalakisgeo\WooQuoteRequest;

/**
 * Saves quotes to the plugin's table.
 */
final class QuoteRepository {

    /**
     * Inserts one quote and returns its id, or null when the insert fails.
     *
     * @param array $quote Keys: name, email, phone, message, items (a list of product_id, name, quantity).
     * @return int|null The new quote's id, or null on failure.
     */
    public function insert( array $quote ): ?int {
        global $wpdb;

        $now = current_time( 'mysql', true );

        $result = $wpdb->insert(
            Installer::table_name(),
            array(
                'customer_name'  => $quote['name'],
                'customer_email' => $quote['email'],
                'customer_phone' => $quote['phone'],
                'message'        => $quote['message'],
                'items'          => wp_json_encode( $quote['items'], JSON_UNESCAPED_UNICODE ),
                'created_at'     => $now,
                'updated_at'     => $now,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            return null;
        }

        return (int) $wpdb->insert_id;
    }
}
