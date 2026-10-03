<?php

namespace Omnifood\Model\Menu;

use Omnifood\Model\Allergen;
use Omnifood\Model\Money;

/** One option of a group; it may open groups of its own (a menu's main course, and its cooking). */
final readonly class Modifier
{
    /**
     * @param list<Allergen>      $allergens
     * @param list<ModifierGroup> $modifierGroups
     */
    public function __construct(
        public string $ref,
        public string $name,
        /** On top of the item's; null or zero: free */
        public ?Money $price = null,
        public array $allergens = [],
        public bool $available = true,
        public array $modifierGroups = [],
        public ?float $vatRate = null,
    ) {
    }
}
