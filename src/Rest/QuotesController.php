<?php
/**
 * REST controller for quote requests.
 */

namespace Dagalakisgeo\WooQuoteRequest\Rest;

use WC_Product;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Registers the quotes REST route and checks its input.
 */
final class QuotesController {

    /**
     * REST namespace: the plugin slug and the API version.
     */
    public const ROUTE_NAMESPACE = 'woo-quote-request/v1';

    /**
     * Path of the quotes route inside the namespace.
     */
    public const ROUTE_BASE = '/quotes';

    /**
     * Registers POST /quotes with WordPress.
     */
    public function register_routes(): void {
        register_rest_route(
            self::ROUTE_NAMESPACE,
            self::ROUTE_BASE,
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_item' ),
                'permission_callback' => '__return_true',
                'args'                => $this->create_item_args(),
            )
        );
    }

    /**
     * Returns the args of POST /quotes: their schema and validators.
     */
    private function create_item_args(): array {
        return array(
            'name'    => array(
                'type'              => 'string',
                'required'          => true,
                'maxLength'         => 100, 
                'format'            => 'text-field',
                'validate_callback' => array( $this, 'validate_name' ),
            ),
            'email'   => array(
                'type'              => 'string',
                'required'          => true,
                'maxLength'         => 100, 
                'format'            => 'email',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'phone'   => array(
                'type'              => 'string',
                'default'           => '',
                'maxLength'         => 40, 
                'format'            => 'text-field',
                'pattern'           => '^[0-9 +()-]*$',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'message' => array(
                'type'              => 'string',
                'default'           => '',
                'maxLength'         => 2000,
                'format'            => 'textarea-field',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'items'   => array(
                'type'              => 'array',
                'required'          => true,
                'minItems'          => 1,
                'maxItems'          => 50,
                'items'             => array(
                    'type'                 => 'object',
                    'properties'           => array(
                        'product_id' => array(
                            'type'    => 'integer',
                            'minimum' => 1,
                        ),
                        'quantity'   => array(
                            'type'    => 'integer',
                            'minimum' => 1,
                            'maximum' => 999,
                        ),
                    ),
                    'required'             => array( 'product_id', 'quantity' ),
                    'additionalProperties' => false,
                ),
                'validate_callback' => array( $this, 'validate_items' ),
            ),
        );
    }

    /**
     * Checks the name against its schema, then that it is not blank once sanitized.
     */
    public function validate_name( mixed $value, WP_REST_Request $request, string $param ): bool|WP_Error {
        $schema_check = rest_validate_request_arg( $value, $request, $param );

        if ( is_wp_error( $schema_check ) ) {
            return $schema_check;
        }

        if ( '' === sanitize_text_field( $value ) ) {
            return new WP_Error(
                'dgwqr_name_required',
                __( 'Enter your name.', 'woo-quote-request' )
            );
        }

        return true;
    }

    /**
     * Checks the items against their schema, then that each product can be quoted.
     */
    public function validate_items( mixed $value, WP_REST_Request $request, string $param ): bool|WP_Error {
        $schema_check = rest_validate_request_arg( $value, $request, $param );

        if ( is_wp_error( $schema_check ) ) {
            return $schema_check;
        }

        foreach ( $value as $item ) {
            $product_id = (int) $item['product_id'];
            $product    = wc_get_product( $product_id );

            if (
                ! $product instanceof WC_Product
                || 'publish' !== $product->get_status()
                || '' !== $product->get_price()
            ) {
                return new WP_Error(
                    'dgwqr_product_not_quotable',
                    sprintf(
                        /* translators: %d: product ID. */
                        __( 'Product %d cannot be quoted.', 'woo-quote-request' ),
                        $product_id
                    )
                );
            }
        }

        return true;
    }

    /**
     * Answers a valid quote request. A stub until the next pull request saves it.
     */
    public function create_item( WP_REST_Request $request ): WP_Error {
        return new WP_Error(
            'dgwqr_not_implemented',
            'Saving quotes arrives in the next pull request.',
            array( 'status' => 501 )
        );
    }
}
