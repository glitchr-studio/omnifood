# Menu

## The model

```php
use Omnifood\Model\Allergen;
use Omnifood\Model\Hours;
use Omnifood\Model\Menu\{Category, Item, Menu, Modifier, ModifierGroup};
use Omnifood\Model\Money;

$extras = new ModifierGroup('extras', 'Extras', [
    new Modifier('egg', 'Egg', Money::of(150, 'EUR')),
    new Modifier('nori', 'Nori', Money::of(100, 'EUR')),
], min: 0, max: 2);

$menu = new Menu('Dinner', [
    new Category('mains', 'Mains', [
        new Item('ramen', 'Shoyu ramen', Money::of(1400, 'EUR'), 'Chicken broth', vatRate: 10.0,
            allergens: [Allergen::GLUTEN, Allergen::EGGS], photo: 'https://site.example/ramen.jpg',
            modifierGroups: [$extras]),
    ]),
], 'EUR', new Hours([1 => [['12:00', '14:30'], ['19:00', '22:30']]]), ref: 'dinner');
```

Every `ref` is the restaurant's own (its till's): the platforms carry it back on the orders
(`Line::$posRef`), and `setAvailability()` takes it. An item listed in two categories, or an option
offered in two groups, is the same thing under the same reference.

`Item::$labels` carries what the model has no field for: `vegetarian`, `vegan`, `gluten_free`,
`alcohol`, and a platform's own allergen codes to be precise where Omnifood's `GLUTEN` and `NUTS` are
families (`gluten_wheat`, `CEREAL_WHEAT`...).

## Checking it

```php
$violations = $validator->validate($menu, $platform->capabilities());
foreach ($violations as $violation) {
    echo $violation->path, ': ', $violation->message, "\n";   // categories[0].items[1].name: 130 characters, at most 120.
}
```

The references (given, one thing each), the names and descriptions, the prices (in the menu's
currency, not negative), the VAT rates, the groups' bounds, how deep modifiers nest. `pushMenu()`
runs it and throws `InvalidMenuException` (its `violations`) before sending anything.

## Pushing it, and items out of stock

```php
$platform->pushMenu($menu);                                              // the platform's menu replaced
$platform->setAvailability('ramen', false, new \DateTimeImmutable('tomorrow 11:00'));
$platform->setAvailability('ramen', true);
```

| | Uber Eats | Deliveroo | Just Eat |
|---|---|---|---|
| Options | items of the menu | items of the menu | items nested in the group |
| Allergens | free strings (the EU-14 names) | Deliveroo's list | JET's list (shown in DE, NL, AT, BE, BG, DK, LU, PL, SK) |
| VAT | rate per item | rate per item (required) | category (standard, reduced, none) |
| Hours | the menu's service hours | the menu's mealtime schedule | the menu's availability |
| Out of stock until | a date (a year when none) | the next opening, or hidden | a date, or until said |
