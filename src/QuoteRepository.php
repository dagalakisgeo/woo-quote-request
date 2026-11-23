<?php
/**
 * Database access for quote requests.
 */

namespace Dagalakisgeo\WooQuoteRequest;

/**
 * Saves quotes to the plugin's table and reads them back.
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

    /**
     * Counts the quotes, either all of them or those with one status.
     *
     * @param QuoteStatus|null $status The status to count, or null for every status.
     * @return int The number of matching quotes.
     */
    public function count( ?QuoteStatus $status ): int {
        global $wpdb;

        $where = '';
        $args  = array( Installer::table_name() );

        if ( null !== $status ) {
            $where  = 'WHERE status = %s';
            $args[] = $status->value;
        }

        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i {$where}", $args ) );
    }

    /**
     * Returns one page of quotes, newest first, either all of them or those with one status.
     *
     * @param QuoteStatus|null $status The status to list, or null for every status.
     * @param int              $limit  The most rows to return.
     * @param int              $offset How many rows to skip first.
     * @return array[] Rows keyed by column name, newest first.
     */
    public function find_page( ?QuoteStatus $status, int $limit, int $offset ): array {
        global $wpdb;

        $where = '';
        $args  = array( Installer::table_name() );

        if ( null !== $status ) {
            $where  = 'WHERE status = %s';
            $args[] = $status->value;
        }

        $args[] = $limit;
        $args[] = $offset;

        $sql  = "SELECT * FROM %i {$where} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d";
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, $args ), ARRAY_A );

        return is_array( $rows ) ? $rows : array();
    }
}
