<?php

namespace Omnifood\Model;

/** Who the table is booked for. */
final readonly class Guest
{
    public function __construct(
        public ?string $firstName = null,
        public ?string $lastName = null,
        public ?string $email = null,
        public ?string $phone = null,
        /** "fr", "en_GB"... */
        public ?string $locale = null,
        /** The platform's id for the guest */
        public ?string $id = null,
    ) {
    }

    public function name(): string
    {
        return trim(($this->firstName ?? '').' '.($this->lastName ?? ''));
    }
}
