<?php

namespace Omnifood\Model;

use Omnifood\Channel;

/**
 * A table booked, the same shape for every platform. $reference is null for
 * a reservation not yet recorded (createReservation() answers it with one).
 */
final readonly class Reservation
{
    public function __construct(
        /** When the guests come: the date and the time, in the restaurant's time zone */
        public \DateTimeImmutable $date,
        public int $covers,
        public Guest $guest,
        public ReservationStatus $status = ReservationStatus::CONFIRMED,
        public ?string $reference = null,
        public ?Channel $channel = null,
        /** The guest's request ("a high chair", "a birthday") */
        public ?string $notes = null,
        public ?string $allergies = null,
        /** The table asked for or given, by its name or number */
        public ?string $table = null,
        /** Minutes the table is held */
        public ?int $duration = null,
        /** A deposit or prepayment taken, or a card imprint's guarantee */
        public ?Money $deposit = null,
        /** Where it came from inside the platform: "widget", "marketplace", "phone"... */
        public ?string $source = null,
        public ?\DateTimeImmutable $createdAt = null,
        /** The restaurant's id on the platform */
        public ?string $restaurant = null,
        public array $raw = [],
    ) {
    }

    /** The same reservation with its fields replaced: a reference given back, a new time. */
    public function with(mixed ...$changes): self
    {
        return new self(...array_replace(get_object_vars($this), $changes));
    }
}
