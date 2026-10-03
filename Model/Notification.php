<?php

namespace Omnifood\Model;

use Omnifood\Channel;

/**
 * What a platform's webhook said, checked: one event about one order, one
 * reservation or the store. $event is the platform's own name for it;
 * $reference the order's (or reservation's) id there, for order() or
 * reservation() to read it whole when the webhook carries only the id;
 * $order or $reservation when it carries it all.
 */
final readonly class Notification
{
    public function __construct(
        public Channel $channel,
        /** The platform's event type: "orders.notification", "order.new"... */
        public string $event,
        public NotificationSubject $subject,
        public ?string $reference = null,
        /** The platform's id for the event, to handle it once: platforms retry */
        public ?string $id = null,
        public ?Order $order = null,
        public ?Reservation $reservation = null,
        /** The status it announces, when it announces one */
        public OrderStatus|ReservationStatus|null $status = null,
        public ?\DateTimeImmutable $occurredAt = null,
        /** The store (restaurant) id it concerns */
        public ?string $store = null,
        public array $raw = [],
    ) {
    }
}
