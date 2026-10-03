<?php

namespace Omnifood\Model;

/** Why the restaurant refuses or cancels an order; each platform maps it to its own codes. */
enum DenyReason: string
{
    case ITEM_UNAVAILABLE = 'item_unavailable';
    case CLOSED = 'closed';
    case TOO_BUSY = 'too_busy';
    case CUSTOMER_REQUEST = 'customer_request';
    case ADDRESS = 'address';
    case PRICING = 'pricing';
    case SPECIAL_INSTRUCTIONS = 'special_instructions';
    case TECHNICAL = 'technical';
    case OTHER = 'other';
}
