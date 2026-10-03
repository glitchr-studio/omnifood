<?php

namespace Omnifood;

use Omnifood\Model\Reservation;
use Omnifood\Model\ReservationStatus;
use Omnifood\Model\Slot;

/**
 * The table reservations a booking platform holds for the restaurant.
 * The methods that write are named after the reservation - createReservation(),
 * updateReservation(), cancelReservation() - so that one platform may take
 * both orders and reservations: OrdersInterface::cancel() is an order's.
 */
interface ReservationsInterface extends PlatformInterface
{
    /**
     * The reservations from $from to $to (their date), oldest first.
     *
     * @return list<Reservation>
     */
    public function reservations(\DateTimeImmutable $from, \DateTimeImmutable $to): array;

    public function reservation(string $ref): Reservation;

    /** A reservation taken by the restaurant (the phone, the site), recorded on the platform: answered with its reference there. */
    public function createReservation(Reservation $reservation): Reservation;

    /** $reservation->reference says which; its date, covers, guest and notes replace the platform's. */
    public function updateReservation(Reservation $reservation): Reservation;

    public function cancelReservation(string $ref, ?string $reason = null): void;

    /** Arrived, seated, left, no-show...: the service as it goes. */
    public function setStatus(string $ref, ReservationStatus $status): void;

    /**
     * The tables free, slot by slot, for the platform to sell.
     *
     * @param list<Slot> $slots
     */
    public function pushAvailability(array $slots): void;
}
