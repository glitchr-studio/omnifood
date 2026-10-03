<?php

namespace Omnifood\Model;

use Omnifood\Channel;

/**
 * An order as a platform sent it, the same shape for every platform: what,
 * for whom, how it leaves, how much, when. Amounts are Money (minor units);
 * what the platform does not tell is null. $raw keeps the platform's answer.
 *
 * @param list<Line> $lines
 */
final readonly class Order
{
    /** @param list<Line> $lines */
    public function __construct(
        public Channel $channel,
        /** The platform's id: what every call on the order takes */
        public string $reference,
        /** The short number the courier and the customer say ("A1B2C", "#42") */
        public ?string $displayId,
        public OrderType $type,
        public OrderStatus $status,
        public array $lines = [],
        public ?Customer $customer = null,
        /** What the customer paid */
        public ?Money $total = null,
        /** The items, before fees and discounts */
        public ?Money $subtotal = null,
        /** Discounts and promotions, as a positive amount */
        public ?Money $discount = null,
        /** Delivery, service, bag fees */
        public ?Money $fees = null,
        public ?Money $tax = null,
        public ?\DateTimeImmutable $placedAt = null,
        /** When it should be ready (collected by the courier or the customer) */
        public ?\DateTimeImmutable $pickupAt = null,
        /** When it should reach the customer, for a delivery */
        public ?\DateTimeImmutable $deliverAt = null,
        public ?Courier $courier = null,
        /** The customer's note for the whole order (allergies included where the platform has no field of its own) */
        public ?string $note = null,
        /** The store (restaurant) id on the platform */
        public ?string $store = null,
        /** Cutlery asked for, when the platform says */
        public ?bool $cutlery = null,
        public array $raw = [],
    ) {
    }

    /** How many items, modifiers not counted. */
    public function count(): int
    {
        return array_sum(array_map(static fn (Line $l) => $l->quantity, $this->lines));
    }
}
