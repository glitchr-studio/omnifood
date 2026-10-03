<?php

namespace Omnifood\Model;

/** Where an order stands, the same for every platform; Order::$raw keeps the platform's own word. */
enum OrderStatus: string
{
    /** Sent to the restaurant, waiting for its answer */
    case NEW = 'new';
    case ACCEPTED = 'accepted';
    /** Ready for the courier or the customer */
    case READY = 'ready';
    /** Collected: on its way, or in the customer's hands */
    case PICKED_UP = 'picked_up';
    case DELIVERED = 'delivered';
    /** Refused by the restaurant before it was accepted */
    case DENIED = 'denied';
    case CANCELLED = 'cancelled';
    /** The platform's word for it is not one of these */
    case UNKNOWN = 'unknown';

    public function isFinal(): bool
    {
        return \in_array($this, [self::DELIVERED, self::DENIED, self::CANCELLED], true);
    }
}
