<?php

namespace Omnifood\Model;

/** One line of an order: so many of one item, with its choices. */
final readonly class Line
{
    /** @param list<Modifier> $modifiers */
    public function __construct(
        public string $name,
        public int $quantity,
        public ?Money $unitPrice = null,
        /** The line's total, modifiers included, as the platform counts it */
        public ?Money $total = null,
        /** The restaurant's reference (Menu\Item::$ref), when the platform carries it back */
        public ?string $posRef = null,
        /** The platform's own id for the item */
        public ?string $platformRef = null,
        public array $modifiers = [],
        /** "Well done", "no onions": the customer's words for this line */
        public ?string $note = null,
    ) {
    }
}
