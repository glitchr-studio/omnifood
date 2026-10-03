<?php

namespace Omnifood\Model\Menu;

final readonly class Category
{
    /** @param list<Item> $items */
    public function __construct(
        public string $ref,
        public string $name,
        public array $items,
        public ?string $description = null,
    ) {
    }
}
