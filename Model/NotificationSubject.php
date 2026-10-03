<?php

namespace Omnifood\Model;

/** What a webhook is about. */
enum NotificationSubject: string
{
    case ORDER = 'order';
    /** The courier of an order: assigned, arrived, gone */
    case COURIER = 'courier';
    case RESERVATION = 'reservation';
    case STORE = 'store';
    case MENU = 'menu';
    case OTHER = 'other';
}
