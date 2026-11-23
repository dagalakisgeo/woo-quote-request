<?php
/**
 * The statuses a quote request can have.
 */

namespace Dagalakisgeo\WooQuoteRequest;

/**
 * Lists the quote statuses. Each value is what the table and the REST API hold.
 */
enum QuoteStatus: string {

    case New        = 'new';
    case InProgress = 'in_progress';
    case Quoted     = 'quoted';
    case Closed     = 'closed';

    /**
     * Returns the stored value of every status, in the order the cases are written.
     *
     * @return string[] The status values.
     */
    public static function values(): array {
        return array_column( self::cases(), 'value' );
    }
}
