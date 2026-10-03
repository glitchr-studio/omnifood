<?php

namespace Omnifood\Model;

/** Where a reservation stands, the same for every platform; Reservation::$raw keeps the platform's own word. */
enum ReservationStatus: string
{
    /** Asked for, waiting for the restaurant (or a deposit) */
    case REQUESTED = 'requested';
    case CONFIRMED = 'confirmed';
    /** At the door, not seated yet */
    case ARRIVED = 'arrived';
    case SEATED = 'seated';
    /** Paid and gone */
    case FINISHED = 'finished';
    case NO_SHOW = 'no_show';
    case CANCELLED = 'cancelled';
    /** Refused by the restaurant */
    case REFUSED = 'refused';
    case UNKNOWN = 'unknown';

    public function isFinal(): bool
    {
        return \in_array($this, [self::FINISHED, self::NO_SHOW, self::CANCELLED, self::REFUSED], true);
    }
}
