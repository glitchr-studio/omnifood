<?php

namespace Omnifood;

/** The platforms an order or a reservation comes from. */
enum Channel: string
{
    case UBEREATS = 'ubereats';
    case DELIVEROO = 'deliveroo';
    case JUSTEAT = 'justeat';
    case THEFORK = 'thefork';
    case ZENCHEF = 'zenchef';
    case DOORDASH = 'doordash';
    case WOLT = 'wolt';
    case GLOVO = 'glovo';
    case OPENTABLE = 'opentable';
    case SEVENROOMS = 'sevenrooms';
    /** The restaurant's own site, the phone, the walk-in: no platform. */
    case DIRECT = 'direct';

    public function label(): string
    {
        return match ($this) {
            self::UBEREATS => 'Uber Eats', self::DELIVEROO => 'Deliveroo', self::JUSTEAT => 'Just Eat', self::THEFORK => 'TheFork',
            self::ZENCHEF => 'Zenchef', self::DOORDASH => 'DoorDash', self::WOLT => 'Wolt', self::GLOVO => 'Glovo',
            self::OPENTABLE => 'OpenTable', self::SEVENROOMS => 'SevenRooms', self::DIRECT => 'Direct',
        };
    }
}
