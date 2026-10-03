<?php

namespace Omnifood\Model;

/** A choice on an order's line (a sauce, a size, no onions), itself with its choices where the platform nests them. */
final readonly class Modifier
{
    /** @param list<Modifier> $modifiers */
    public function __construct(
        public string $name,
        public int $quantity = 1,
        /** The price of one, on top of the item's */
        public ?Money $price = null,
        /** The restaurant's reference for it (Menu\Modifier::$ref), when the platform carries it back */
        public ?string $posRef = null,
        /** The modifier group it was chosen in, by name */
        public ?string $group = null,
        public array $modifiers = [],
    ) {
    }
}
