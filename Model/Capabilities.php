<?php

namespace Omnifood\Model;

/**
 * What a platform takes and imposes, for a back office to show and the
 * Validator to check a menu against before it is sent. What the platform
 * does not say is null (no limit known).
 */
final readonly class Capabilities
{
    /**
     * @param list<OrderType> $orderTypes
     * @param list<string>    $currencies ISO 4217; [] any
     */
    public function __construct(
        public array $orderTypes = [],
        /** Seconds the restaurant has to accept a new order before the platform cancels it (Uber Eats: 11.5 minutes) */
        public ?int $acceptanceDelay = null,
        /** Whether accept() takes when the order will be ready */
        public bool $readyAt = false,
        /** How deep modifiers nest: 0 none, 1 an item's groups, 2 a modifier's own groups... */
        public int $modifierDepth = 1,
        /** Whether the menu carries the EU-14 allergens */
        public bool $allergens = false,
        public bool $photos = false,
        /** Whether each item carries its VAT rate */
        public bool $vatRates = false,
        public ?int $nameMaxLength = null,
        public ?int $descriptionMaxLength = null,
        /** Whether pause() takes an end */
        public bool $pauseUntil = false,
        /** Whether a reservation may carry a deposit or a card guarantee */
        public bool $deposits = false,
        /** Whether the restaurant can push its free tables (pushAvailability()) */
        public bool $availabilityPush = false,
        public array $currencies = [],
        /** Whether a modifier must be a menu item of its own on the platform (Uber Eats: every option is an item) */
        public bool $modifiersAreItems = false,
    ) {
    }

    public function takes(OrderType $type): bool
    {
        return \in_array($type, $this->orderTypes, true);
    }
}
