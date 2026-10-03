<?php

namespace Omnifood;

use Omnifood\Model\Hours;
use Omnifood\Model\StoreStatus;

/** The restaurant as the platform shows it: open, paused for a rush, its hours. */
interface StoreInterface extends PlatformInterface
{
    public function status(): StoreStatus;

    /** No more orders until $until (null: until resume()). */
    public function pause(?\DateTimeImmutable $until = null, ?string $reason = null): void;

    public function resume(): void;

    public function setHours(Hours $hours): void;
}
