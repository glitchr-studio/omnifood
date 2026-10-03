<?php

namespace Omnifood\Tests;

use Omnifood\Channel;
use Omnifood\Config;
use Omnifood\Exception\NotSupportedException;
use Omnifood\Model\Capabilities;
use Omnifood\Model\DenyReason;
use Omnifood\Model\Guest;
use Omnifood\Model\Menu\Menu;
use Omnifood\Model\Money;
use Omnifood\Model\Order;
use Omnifood\Model\OrderStatus;
use Omnifood\Model\OrderType;
use Omnifood\Model\Reservation;
use Omnifood\Model\ReservationStatus;
use Omnifood\MenuInterface;
use Omnifood\OrdersInterface;
use Omnifood\PlatformFactory;
use Omnifood\PlatformInterface;
use Omnifood\ReservationsInterface;

/** A delivery platform that accepts everything, for the tests: "stub" needs a key. */
final class StubFactory extends PlatformFactory
{
    protected function populate(Config $config): void
    {
        $config->defaults([
            'omnifood.factory_name' => 'stub',
            'omnifood.required_options' => ['key'],
            'store_id' => 'stub',
        ]);
    }

    protected function build(Config $config): PlatformInterface
    {
        return new StubKitchen((string) $config['key'], (string) $config['store_id']);
    }
}

/** A booking platform, read only, that needs nothing. */
final class StubBookFactory extends PlatformFactory
{
    protected function populate(Config $config): void
    {
        $config->defaults(['omnifood.factory_name' => 'book', 'restaurant_id' => 'book']);
    }

    protected function build(Config $config): PlatformInterface
    {
        return new StubBook((string) $config['restaurant_id']);
    }
}

final class StubKitchen implements OrdersInterface, MenuInterface
{
    /** @var array<string, string> ref => what was done */
    public array $done = [];

    public ?Menu $menu = null;

    public function __construct(public readonly string $key, public readonly string $storeId)
    {
    }

    public function getName(): string
    {
        return 'stub';
    }

    public function getChannel(): Channel
    {
        return Channel::DIRECT;
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities([OrderType::DELIVERY], 600, true);
    }

    public function order(string $ref): Order
    {
        return new Order(Channel::DIRECT, $ref, '#'.$ref, OrderType::DELIVERY, OrderStatus::NEW, total: Money::of(1250, 'EUR'), store: $this->storeId);
    }

    public function orders(?\DateTimeImmutable $since = null): array
    {
        return [$this->order('1')];
    }

    public function accept(string $ref, ?\DateTimeImmutable $readyAt = null): void
    {
        $this->done[$ref] = 'accepted';
    }

    public function deny(string $ref, DenyReason $reason, ?string $note = null): void
    {
        $this->done[$ref] = 'denied: '.$reason->value;
    }

    public function ready(string $ref): void
    {
        $this->done[$ref] = 'ready';
    }

    public function cancel(string $ref, DenyReason $reason, ?string $note = null): void
    {
        $this->done[$ref] = 'cancelled: '.$reason->value;
    }

    public function pushMenu(Menu $menu): void
    {
        $this->menu = $menu;
    }

    public function setAvailability(string $itemRef, bool $available, ?\DateTimeImmutable $until = null): void
    {
        $this->done[$itemRef] = $available ? 'available' : 'unavailable';
    }
}

final class StubBook implements ReservationsInterface
{
    public function __construct(public readonly string $restaurantId)
    {
    }

    public function getName(): string
    {
        return 'book';
    }

    public function getChannel(): Channel
    {
        return Channel::DIRECT;
    }

    public function capabilities(): Capabilities
    {
        return new Capabilities();
    }

    public function reservations(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return [$this->reservation('r1')];
    }

    public function reservation(string $ref): Reservation
    {
        return new Reservation(new \DateTimeImmutable('2026-10-10 20:00'), 2, new Guest('Aiko', 'Tanaka'), ReservationStatus::CONFIRMED, $ref, restaurant: $this->restaurantId);
    }

    public function createReservation(Reservation $reservation): Reservation
    {
        throw NotSupportedException::operation('book', 'create reservations');
    }

    public function updateReservation(Reservation $reservation): Reservation
    {
        throw NotSupportedException::operation('book', 'update reservations');
    }

    public function cancelReservation(string $ref, ?string $reason = null): void
    {
        throw NotSupportedException::operation('book', 'cancel reservations');
    }

    public function setStatus(string $ref, ReservationStatus $status): void
    {
        throw NotSupportedException::operation('book', 'set a reservation\'s status');
    }

    public function pushAvailability(array $slots): void
    {
        throw NotSupportedException::operation('book', 'take availability');
    }
}
