<?php
/**
 * Emails sent when a quote is saved.
 */

namespace Dagalakisgeo\WooQuoteRequest;

/**
 * Sends the shop's notice and the customer's receipt, in plain text.
 */
final class QuoteMailer {

    /**
     * Emails the shop a new quote with all its details.
     *
     * @param int   $id    The quote's id.
     * @param array $quote Keys: name, email, phone, message, items.
     * @return bool Whether wp_mail() accepted the email.
     */
    public function send_shop_notice( int $id, array $quote ): bool {
        /**
         * Filters the address that receives new quote requests.
         *
         * @param string $email The site's administration email address.
         */
        $to = (string) apply_filters( 'dgwqr_shop_email', get_option( 'admin_email' ) );

        $subject = sprintf(
            /* translators: 1: site name, 2: quote ID. */
            __( '[%1$s] New quote request #%2$d', 'woo-quote-request' ),
            $this->site_name(),
            $id
        );

        $lines = array(
            sprintf(
                /* translators: %s: customer's name. */
                __( 'Name: %s', 'woo-quote-request' ),
                $quote['name']
            ),
            sprintf(
                /* translators: %s: customer's email address. */
                __( 'Email: %s', 'woo-quote-request' ),
                $quote['email']
            ),
        );

        if ( '' !== $quote['phone'] ) {
            $lines[] = sprintf(
                /* translators: %s: customer's phone number. */
                __( 'Phone: %s', 'woo-quote-request' ),
                $quote['phone']
            );
        }

        if ( '' !== $quote['message'] ) {
            $lines[] = '';
            $lines[] = __( 'Message:', 'woo-quote-request' );
            $lines[] = $quote['message'];
        }

        $lines[] = '';
        $lines[] = __( 'Products:', 'woo-quote-request' );
        $lines[] = $this->items_text( $quote['items'] );

        $sent = wp_mail( $to, $subject, implode( "\n", $lines ), array( 'Reply-To: ' . $quote['email'] ) );

        if ( ! $sent ) {
            error_log( sprintf( 'woo-quote-request: shop email for quote %d failed', $id ) );
        }

        return $sent;
    }

    /**
     * Emails the customer a receipt that lists the products and nothing they typed.
     *
     * @param int   $id    The quote's id.
     * @param array $quote Keys: name, email, phone, message, items.
     * @return bool Whether wp_mail() accepted the email.
     */
    public function send_customer_receipt( int $id, array $quote ): bool {
        $subject = sprintf(
            /* translators: %s: site name. */
            __( '[%s] We received your quote request', 'woo-quote-request' ),
            $this->site_name()
        );

        $lines = array(
            sprintf(
                /* translators: %d: quote ID. */
                __( 'Thank you. We received your quote request #%d for these products:', 'woo-quote-request' ),
                $id
            ),
            '',
            $this->items_text( $quote['items'] ),
            '',
            __( 'We will reply to this email address.', 'woo-quote-request' ),
        );

        $sent = wp_mail( $quote['email'], $subject, implode( "\n", $lines ) );

        if ( ! $sent ) {
            error_log( sprintf( 'woo-quote-request: customer receipt for quote %d failed', $id ) );
        }

        return $sent;
    }

    /**
     * Returns one line per product: the quantity, a multiplication sign and the name.
     *
     * @param array $items A list of product_id, name, quantity.
     * @return string The lines, joined with line breaks.
     */
    private function items_text( array $items ): string {
        $lines = array();

        foreach ( $items as $item ) {
            $lines[] = sprintf( '%1$d × %2$s', $item['quantity'], $item['name'] );
        }

        return implode( "\n", $lines );
    }

    /**
     * Returns the site's name as plain text, with HTML entities such as &amp; turned back.
     */
    private function site_name(): string {
        return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
    }
}
