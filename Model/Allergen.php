<?php

namespace Omnifood\Model;

/** The fourteen allergens EU Regulation 1169/2011 (Annex II) requires a menu to declare. */
enum Allergen: string
{
    case GLUTEN = 'gluten';
    case CRUSTACEANS = 'crustaceans';
    case EGGS = 'eggs';
    case FISH = 'fish';
    case PEANUTS = 'peanuts';
    case SOYBEANS = 'soybeans';
    case MILK = 'milk';
    case NUTS = 'nuts';
    case CELERY = 'celery';
    case MUSTARD = 'mustard';
    case SESAME = 'sesame';
    case SULPHITES = 'sulphites';
    case LUPIN = 'lupin';
    case MOLLUSCS = 'molluscs';

    public function label(): string
    {
        return match ($this) {
            self::GLUTEN => 'Cereals containing gluten', self::CRUSTACEANS => 'Crustaceans', self::EGGS => 'Eggs',
            self::FISH => 'Fish', self::PEANUTS => 'Peanuts', self::SOYBEANS => 'Soybeans', self::MILK => 'Milk',
            self::NUTS => 'Nuts', self::CELERY => 'Celery', self::MUSTARD => 'Mustard', self::SESAME => 'Sesame seeds',
            self::SULPHITES => 'Sulphur dioxide and sulphites', self::LUPIN => 'Lupin', self::MOLLUSCS => 'Molluscs',
        };
    }
}
