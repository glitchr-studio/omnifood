<?php

namespace Omnifood\Model;

/** A time a table can be booked at, and for how many. */
final readonly class Slot
{
    public function __construct(
        public \DateTimeImmutable $start,
        /** The covers still bookable at that time; 0 is full */
        public int $covers,
        public ?\DateTimeImmutable $end = null,
        /** The room or terrace it is in, when the platform sells them apart */
        public ?string $area = null,
    ) {
    }
}
