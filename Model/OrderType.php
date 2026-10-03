<?php

namespace Omnifood\Model;

enum OrderType: string
{
    /** Taken to the customer, by the platform's courier or the restaurant's own */
    case DELIVERY = 'delivery';
    /** Collected at the counter by the customer */
    case PICKUP = 'pickup';
    /** Eaten at the restaurant */
    case DINE_IN = 'dine_in';
}
