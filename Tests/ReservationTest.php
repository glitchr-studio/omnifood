<?php

namespace Omnifood\Tests;

use Omnifood\Channel;
use Omnifood\Model\Guest;
use Omnifood\Model\Line;
use Omnifood\Model\Money;
use Omnifood\Model\Order;
use Omnifood\Model\OrderStatus;
use Omnifood\Model\OrderType;
use Omnifood\Model\Reservation;
use Omnifood\Model\ReservationStatus;
use PHPUnit\Framework\TestCase;

final class ReservationTest extends TestCase
{
    public function testAReservationWithItsFieldsReplaced(): void
    {
        $reservation = new Reservation(new \DateTimeImmutable('2026-10-10 20:00'), 4, new Guest('Aiko', 'Tanaka', phone: '+33612345678'), ReservationStatus::REQUESTED, notes: 'A high chair');
        $recorded = $reservation->with(reference: 'abc', status: ReservationStatus::CONFIRMED, channel: Channel::THEFORK);

        self::assertNull($reservation->reference, 'the first one unchanged');
        self::assertSame('abc', $recorded->reference);
        self::assertSame(ReservationStatus::CONFIRMED, $recorded->status);
        self::assertSame(Channel::THEFORK, $recorded->channel);
        self::assertSame('A high chair', $recorded->notes);
        self::assertSame('Aiko Tanaka', $recorded->guest->name());
        self::assertFalse($recorded->status->isFinal());
        self::assertTrue(ReservationStatus::NO_SHOW->isFinal());
    }

    public function testAnOrderCountsItsItems(): void
    {
        $order = new Order(Channel::UBEREATS, 'u-1', 'A1B2', OrderType::DELIVERY, OrderStatus::NEW, [
            new Line('Gyoza', 2, Money::of(650, 'EUR')),
            new Line('Ramen', 1, Money::of(1400, 'EUR')),
        ]);

        self::assertSame(3, $order->count());
        self::assertFalse($order->status->isFinal());
        self::assertSame('Uber Eats', $order->channel->label());
    }
}
