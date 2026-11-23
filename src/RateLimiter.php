<?php
/**
 * Per-IP limit on new quote requests.
 */

namespace Dagalakisgeo\WooQuoteRequest;

/**
 * Counts saved quotes per visitor address in a transient, within a fixed window.
 */
final class RateLimiter {

    /**
     * The most quotes one address may save within a window.
     */
    public const LIMIT = 5;

    /**
     * The length of a window, in seconds.
     */
    public const WINDOW = HOUR_IN_SECONDS;

    /**
     * Returns the visitor's address, as the web server saw it.
     *
     * Reads only REMOTE_ADDR. Any client can send headers such as X-Forwarded-For,
     * so the plugin does not trust them. A site behind a known proxy can supply
     * the real address with the dgwqr_client_ip filter.
     *
     * @return string A valid IP address, or an empty string.
     */
    public function client_ip(): string {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';

        if ( false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
            $ip = '';
        }

        /**
         * Filters the visitor's address that the rate limit counts.
         *
         * @param string $ip The address from REMOTE_ADDR, or an empty string.
         */
        return (string) apply_filters( 'dgwqr_client_ip', $ip );
    }

    /**
     * Returns how many seconds the address must wait, or 0 when it may save a quote.
     *
     * @param string $ip The visitor's address.
     * @return int Seconds until the window ends, or 0.
     */
    public function retry_after( string $ip ): int {
        $entry = get_transient( $this->key( $ip ) );

        if ( ! is_array( $entry ) || $entry['count'] < self::LIMIT ) {
            return 0;
        }

        return max( 1, $entry['reset_at'] - time() );
    }

    /**
     * Counts one saved quote for the address.
     *
     * @param string $ip The visitor's address.
     */
    public function record( string $ip ): void {
        $key   = $this->key( $ip );
        $entry = get_transient( $key );

        if ( ! is_array( $entry ) ) {
            $entry = array(
                'count'    => 0,
                'reset_at' => time() + self::WINDOW,
            );
        }

        ++$entry['count'];

        set_transient( $key, $entry, max( 1, $entry['reset_at'] - time() ) );
    }

    /**
     * Builds the transient name for an address, without storing the address itself.
     *
     * @param string $ip The visitor's address.
     * @return string The transient name.
     */
    private function key( string $ip ): string {
        return 'dgwqr_rl_' . wp_hash( $ip );
    }
}
