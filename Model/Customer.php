<?php

namespace Omnifood\Model;

/** Who ordered, as much as the platform tells (often a first name and a masked phone). */
final readonly class Customer
{
    public function __construct(
        public ?string $name = null,
        public ?string $phone = null,
        /** An extension or code to dial after the platform's masked number */
        public ?string $phoneCode = null,
        public ?string $email = null,
        /** The delivery address, one line, when the restaurant delivers itself */
        public ?string $address = null,
    ) {
    }
}
