<?php

namespace Omnifood\Model;

/** The restaurant as the platform shows it now. */
final readonly class StoreStatus
{
    public function __construct(
        public StoreState $state,
        /** When a pause ends, when the platform says */
        public ?\DateTimeImmutable $until = null,
        public ?string $reason = null,
        public array $raw = [],
    ) {
    }

    public function isOpen(): bool
    {
        return StoreState::OPEN === $this->state;
    }
}
