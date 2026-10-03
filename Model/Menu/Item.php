<?php

namespace Omnifood\Model\Menu;

use Omnifood\Model\Allergen;
use Omnifood\Model\Money;

/** A dish or a drink. */
final readonly class Item
{
    /**
     * @param list<Allergen>      $allergens
     * @param list<ModifierGroup> $modifierGroups
     * @param list<string>        $labels          "vegetarian", "vegan", "spicy", "halal"...
     */
    public function __construct(
        /** The restaurant's reference: the one setAvailability() and the orders take */
        public string $ref,
        public string $name,
        public Money $price,
        public ?string $description = null,
        /** In percent: 10.0, 5.5, 20.0 */
        public ?float $vatRate = null,
        public array $allergens = [],
        /** A public URL the platform fetches */
        public ?string $photo = null,
        public array $modifierGroups = [],
        public bool $available = true,
        public array $labels = [],
    ) {
    }
}
