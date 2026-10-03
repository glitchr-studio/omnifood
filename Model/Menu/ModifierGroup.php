<?php

namespace Omnifood\Model\Menu;

/** A choice on an item: "Your sauce" (exactly 1), "Extras" (0 to 3). */
final readonly class ModifierGroup
{
    /** @param list<Modifier> $modifiers */
    public function __construct(
        public string $ref,
        public string $name,
        public array $modifiers,
        public int $min = 0,
        /** null: as many as there are */
        public ?int $max = null,
    ) {
    }

    public function isRequired(): bool
    {
        return $this->min > 0;
    }
}
