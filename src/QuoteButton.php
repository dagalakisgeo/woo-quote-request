<?php
/**
 * Add to quote button for Woo Quote Request.
 */

namespace Dagalakisgeo\WooQuoteRequest;

/**
 * Prints the Add to quote button on single product pages.
 */
final class QuoteButton {

    /**
     * Prints the button when the current product has no price.
     */
    public function render(): void {
        $product = wc_get_product( get_the_ID() );

        if ( ! $product instanceof \WC_Product ) {
            return;
        }

        if ( '' !== $product->get_price() ) {
            return;
        }

        printf(
            '<div class="dgwqr-quote"><button type="button" class="button dgwqr-add-to-quote" data-product-id="%1$s">%2$s</button></div>',
            esc_attr( $product->get_id() ),
            esc_html__( 'Add to quote', 'woo-quote-request' )
        );
    }
}
