<?php

namespace Omnifood\Model;

/** Who comes to collect a delivery, when the platform says. */
final readonly class Courier
{
    public function __construct(
        public ?string $name = null,
        public ?string $phone = null,
        /** The platform's word: "ARRIVED_AT_PICKUP", "rider_assigned"... */
        public ?string $status = null,
        public ?\DateTimeImmutable $arrivesAt = null,
        public ?string $vehicle = null,
    ) {
    }
}
