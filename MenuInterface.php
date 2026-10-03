<?php

namespace Omnifood;

use Omnifood\Model\Menu\Menu;

/**
 * The menu the platform shows: pushed whole (the platform replaces its
 * own), and an item (or a modifier) out of stock for a while.
 */
interface MenuInterface extends PlatformInterface
{
    /** A menu that breaks capabilities() is refused before anything is sent (InvalidMenuException). */
    public function pushMenu(Menu $menu): void;

    /** $itemRef is the item's POS reference (Item::$ref); $until null: until said otherwise. */
    public function setAvailability(string $itemRef, bool $available, ?\DateTimeImmutable $until = null): void;
}
