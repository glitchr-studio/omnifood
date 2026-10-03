<?php

namespace Omnifood;

use Omnifood\Model\DenyReason;
use Omnifood\Model\Order;

/**
 * The orders a delivery platform sends to the restaurant: read, accepted
 * (within the platform's delay: Capabilities::$acceptanceDelay), denied,
 * marked ready, cancelled. An order arrives by NotifiableInterface (a
 * webhook) or by orders() (polling); $ref is always the platform's id.
 */
interface OrdersInterface extends PlatformInterface
{
    public function order(string $ref): Order;

    /**
     * The orders placed since then, newest first, where the platform lists
     * them (NotSupportedException where it only pushes them).
     *
     * @return list<Order>
     */
    public function orders(?\DateTimeImmutable $since = null): array;

    /** Accepted; $readyAt is when the kitchen expects it ready, where the platform takes it. */
    public function accept(string $ref, ?\DateTimeImmutable $readyAt = null): void;

    public function deny(string $ref, DenyReason $reason, ?string $note = null): void;

    /** Ready for the courier, or for the customer who collects it. */
    public function ready(string $ref): void;

    /** An order already accepted, cancelled by the restaurant. */
    public function cancel(string $ref, DenyReason $reason, ?string $note = null): void;
}
