<?php
/**
 * REST controller for quote requests.
 */

namespace Dagalakisgeo\WooQuoteRequest\Rest;

use Dagalakisgeo\WooQuoteRequest\Plugin;
use Dagalakisgeo\WooQuoteRequest\QuoteMailer;
use Dagalakisgeo\WooQuoteRequest\QuoteRepository;
use Dagalakisgeo\WooQuoteRequest\QuoteStatus;
use Dagalakisgeo\WooQuoteRequest\RateLimiter;
use DateTimeImmutable;
use DateTimeZone;
use WC_Product;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
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
     * Keeps the repository, the rate limiter and the mailer.
     */
    public function __construct(
        private readonly QuoteRepository $quotes,
        private readonly RateLimiter $limiter,
        private readonly QuoteMailer $mailer,
    ) {}

    /**
     * Registers POST and GET /quotes, and PATCH /quotes/{id}, with WordPress.
     */
    public function register_routes(): void {
        register_rest_route(
            self::ROUTE_NAMESPACE,
            self::ROUTE_BASE,
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'create_item' ),
                    'permission_callback' => '__return_true',
                    'args'                => $this->create_item_args(),
                ),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => array( $this, 'admin_permissions_check' ),
                    'args'                => $this->get_items_args(),
                ),
            )
        );

        register_rest_route(
            self::ROUTE_NAMESPACE,
            self::ROUTE_BASE . '/(?P<id>\d+)',
            array(
                'methods'             => 'PATCH',
                'callback'            => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'admin_permissions_check' ),
                'args'                => $this->update_item_args(),
            )
        );
    }

    /**
     * Allows only users who can manage the shop.
     */
    public function admin_permissions_check(): bool {
        return current_user_can( Plugin::CAPABILITY );
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
            'website' => array(
                'type'              => 'string',
                'default'           => '',
                'validate_callback' => array( $this, 'validate_honeypot' ),
            ),
        );
    }

    /**
     * Returns the args of GET /quotes: paging and an optional status filter.
     */
    private function get_items_args(): array {
        return array(
            'page'     => array(
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'per_page' => array(
                'type'              => 'integer',
                'default'           => 20,
                'minimum'           => 1,
                'maximum'           => 100,
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'status'   => array(
                'type'              => 'string',
                'enum'              => QuoteStatus::values(),
                'validate_callback' => 'rest_validate_request_arg',
            ),
        );
    }

    /**
     * Returns the args of PATCH /quotes/{id}: the id from the path and the new status.
     */
    private function update_item_args(): array {
        return array(
            'id'     => array(
                'type'              => 'integer',
                'minimum'           => 1,
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'status' => array(
                'type'              => 'string',
                'required'          => true,
                'enum'              => QuoteStatus::values(),
                'validate_callback' => 'rest_validate_request_arg',
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
     * Checks the honeypot field, which only bots fill in.
     */
    public function validate_honeypot( mixed $value, WP_REST_Request $request, string $param ): bool|WP_Error {
        $schema_check = rest_validate_request_arg( $value, $request, $param );

        if ( is_wp_error( $schema_check ) ) {
            return $schema_check;
        }

        if ( '' !== $value ) {
            return new WP_Error(
                'dgwqr_rejected',
                __( 'The request could not be accepted.', 'woo-quote-request' )
            );
        }

        return true;
    }

    /**
     * Saves a valid quote, emails the shop and the customer, and answers 201 with its id,
     * or 429 when the address has reached the limit.
     */
    public function create_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $ip   = $this->limiter->client_ip();
        $wait = $this->limiter->retry_after( $ip );

        if ( $wait > 0 ) {
            $response = rest_convert_error_to_response(
                new WP_Error(
                    'dgwqr_rate_limited',
                    __( 'Too many quote requests. Try again later.', 'woo-quote-request' ),
                    array( 'status' => 429 )
                )
            );
            $response->header( 'Retry-After', (string) $wait );

            return $response;
        }

        $items = array();

        foreach ( $request['items'] as $item ) {
            $product_id = (int) $item['product_id'];

            $items[] = array(
                'product_id' => $product_id,
                'name'       => wc_get_product( $product_id )->get_name(),
                'quantity'   => (int) $item['quantity'],
            );
        }

        $quote = array(
            'name'    => $request['name'],
            'email'   => $request['email'],
            'phone'   => $request['phone'],
            'message' => $request['message'],
            'items'   => $items,
        );

        $id = $this->quotes->insert( $quote );

        if ( null === $id ) {
            return new WP_Error(
                'dgwqr_db_error',
                __( 'The quote could not be saved.', 'woo-quote-request' ),
                array( 'status' => 500 )
            );
        }

        $this->limiter->record( $ip );

        $this->mailer->send_shop_notice( $id, $quote );
        $this->mailer->send_customer_receipt( $id, $quote );

        return new WP_REST_Response( array( 'id' => $id ), 201 );
    }

    /**
     * Lists one page of quotes, newest first, with the totals in two headers.
     */
    public function get_items( WP_REST_Request $request ): WP_REST_Response {
        $status   = null === $request['status'] ? null : QuoteStatus::from( $request['status'] );
        $per_page = $request['per_page'];
        $offset   = ( $request['page'] - 1 ) * $per_page;

        $total = $this->quotes->count( $status );
        $rows  = $this->quotes->find_page( $status, $per_page, $offset );

        $data = array();

        foreach ( $rows as $row ) {
            $data[] = $this->prepare_item( $row );
        }

        $response = new WP_REST_Response( $data, 200 );
        $response->header( 'X-WP-Total', (string) $total );
        $response->header( 'X-WP-TotalPages', (string) (int) ceil( $total / $per_page ) );

        return $response;
    }

    /**
     * Changes one quote's status and answers with the whole quote.
     */
    public function update_item( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $id    = $request['id'];
        $quote = $this->quotes->find( $id );

        if ( null === $quote ) {
            return new WP_Error(
                'dgwqr_quote_not_found',
                __( 'Quote not found.', 'woo-quote-request' ),
                array( 'status' => 404 )
            );
        }

        $status = QuoteStatus::from( $request['status'] );

        if ( $status->value !== $quote['status'] ) {
            if ( ! $this->quotes->update_status( $id, $status ) ) {
                return new WP_Error(
                    'dgwqr_db_error',
                    __( 'The quote could not be updated.', 'woo-quote-request' ),
                    array( 'status' => 500 )
                );
            }

            $quote = $this->quotes->find( $id );
        }

        return new WP_REST_Response( $this->prepare_item( $quote ), 200 );
    }

    /**
     * Turns a table row into the quote the API returns.
     */
    private function prepare_item( array $row ): array {
        return array(
            'id'         => (int) $row['id'],
            'status'     => $row['status'],
            'name'       => $row['customer_name'],
            'email'      => $row['customer_email'],
            'phone'      => $row['customer_phone'],
            'message'    => $row['message'],
            'items'      => json_decode( $row['items'], true ),
            'created_at' => $this->to_iso8601( $row['created_at'] ),
            'updated_at' => $this->to_iso8601( $row['updated_at'] ),
        );
    }

    /**
     * Turns a UTC date from the table into ISO 8601 with its offset.
     */
    private function to_iso8601( string $mysql_utc ): string {
        return ( new DateTimeImmutable( $mysql_utc, new DateTimeZone( 'UTC' ) ) )->format( DATE_ATOM );
    }
}