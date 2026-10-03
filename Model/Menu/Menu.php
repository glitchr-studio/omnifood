<?php

namespace Omnifood\Model\Menu;

use Omnifood\Model\Hours;

/**
 * A menu as a site holds it, the same for every platform: categories of
 * items, each item with its choices (modifier groups), its price, its VAT
 * rate, its allergens. Each platform's pushMenu() turns it into its own
 * shape; every reference ($ref) is the restaurant's own (a POS's), the one
 * the platform carries back on the orders.
 */
final readonly class Menu
{
    /** @param list<Category> $categories */
    public function __construct(
        public string $name,
        public array $categories,
        public string $currency = 'EUR',
        /** When it is served; null: whenever the store is open */
        public ?Hours $hours = null,
        public ?string $ref = null,
        public ?string $description = null,
    ) {
    }

    /** @return list<Item> every item, in the order of the categories */
    public function items(): array
    {
        return array_merge([], ...array_map(static fn (Category $c) => $c->items, $this->categories));
    }
}
