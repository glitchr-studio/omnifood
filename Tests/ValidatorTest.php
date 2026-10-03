<?php

namespace Omnifood\Tests;

use Omnifood\Model\Allergen;
use Omnifood\Model\Capabilities;
use Omnifood\Model\Menu\Category;
use Omnifood\Model\Menu\Item;
use Omnifood\Model\Menu\Menu;
use Omnifood\Model\Menu\Modifier;
use Omnifood\Model\Menu\ModifierGroup;
use Omnifood\Model\Money;
use Omnifood\Model\Violation;
use Omnifood\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    /** @return array<string, string> path => message */
    private static function violations(Menu $menu, Capabilities $capabilities = new Capabilities()): array
    {
        $violations = [];
        foreach ((new Validator())->validate($menu, $capabilities) as $violation) {
            self::assertInstanceOf(Violation::class, $violation);
            $violations[$violation->path] = $violation->message;
        }

        return $violations;
    }

    private static function eur(int $cents): Money
    {
        return Money::of($cents, 'EUR');
    }

    private static function sauces(): ModifierGroup
    {
        return new ModifierGroup('sauce', 'Your sauce', [new Modifier('soy', 'Soy'), new Modifier('ponzu', 'Ponzu', self::eur(50))], 1, 1);
    }

    private static function menu(Item ...$items): Menu
    {
        return new Menu('Dinner', [new Category('mains', 'Mains', $items ?: [
            new Item('ramen', 'Shoyu ramen', self::eur(1400), 'Chicken broth, egg', 10.0, [Allergen::GLUTEN, Allergen::EGGS, Allergen::SOYBEANS], modifierGroups: [self::sauces()]),
            new Item('gyoza', 'Gyoza', self::eur(650), vatRate: 10.0, modifierGroups: [self::sauces()]),
        ])]);
    }

    public function testAMenuWithinTheCapabilitiesHasNoViolation(): void
    {
        self::assertSame([], self::violations(self::menu(), new Capabilities(modifierDepth: 1, nameMaxLength: 40, currencies: ['EUR'])));
    }

    public function testEveryReferenceIsGivenAndNamesOneThing(): void
    {
        $menu = new Menu('Dinner', [
            new Category('mains', 'Mains', [new Item('ramen', 'Shoyu ramen', self::eur(1400)), new Item('', 'Udon', self::eur(1200))]),
            new Category('mains', 'Specials', [new Item('ramen', 'Shoyu ramen', self::eur(1400)), new Item('ramen', 'Miso ramen', self::eur(1500))]),
        ]);

        self::assertSame([
            'categories[0].items[1].ref' => 'A reference is required.',
            'categories[1].ref' => '"mains" is already categories[0].',
            'categories[1].items[1].ref' => '"ramen" is already categories[0].items[0], with another name or price.',
        ], self::violations($menu), 'an item in two categories is one item; two items under one reference are not');
    }

    public function testAModifierOfferedInTwoGroupsIsOneModifier(): void
    {
        $extras = new ModifierGroup('extras', 'Extras', [new Modifier('egg', 'Egg', self::eur(150))], 0, 2);
        $toppings = new ModifierGroup('toppings', 'Toppings', [new Modifier('egg', 'Egg', self::eur(150)), new Modifier('nori', 'Nori', self::eur(100))]);
        $menu = self::menu(new Item('ramen', 'Ramen', self::eur(1400), modifierGroups: [$extras, $toppings]));

        self::assertSame([], self::violations($menu));

        $other = new ModifierGroup('extras', 'Extras', [new Modifier('egg', 'Egg', self::eur(200))]);
        self::assertSame(
            ['categories[0].items[1].modifierGroups[0].ref' => '"extras" names two different groups.', 'categories[0].items[1].modifierGroups[0].modifiers[0].ref' => '"egg" is already categories[0].items[0].modifierGroups[0].modifiers[0], with another name or price.'],
            self::violations(self::menu(new Item('ramen', 'Ramen', self::eur(1400), modifierGroups: [$extras]), new Item('udon', 'Udon', self::eur(1200), modifierGroups: [$other]))),
        );
    }

    public function testWhereModifiersAreItemsTheyShareTheItemsReferences(): void
    {
        $menu = self::menu(new Item('egg', 'Egg', self::eur(150)), new Item('ramen', 'Ramen', self::eur(1400), modifierGroups: [new ModifierGroup('extras', 'Extras', [new Modifier('egg', 'Boiled egg', self::eur(150))])]));

        self::assertSame([], self::violations($menu), 'apart, an item and a modifier may share a reference');
        self::assertSame(
            ['categories[0].items[1].modifierGroups[0].modifiers[0].ref' => '"egg" is already categories[0].items[0], with another name or price.'],
            self::violations($menu, new Capabilities(modifiersAreItems: true)),
        );
    }

    public function testTheGroupsBounds(): void
    {
        $item = static fn (int $min, ?int $max) => new Item('ramen', 'Ramen', self::eur(1400), modifierGroups: [new ModifierGroup('sauce', 'Sauce', [new Modifier('soy', 'Soy'), new Modifier('ponzu', 'Ponzu')], $min, $max)]);

        self::assertSame(['categories[0].items[0].modifierGroups[0]' => 'At least 2 but at most 1.'], self::violations(self::menu($item(2, 1))));
        self::assertSame(['categories[0].items[0].modifierGroups[0]' => 'At least 3 to choose among 2.'], self::violations(self::menu($item(3, null))));
        self::assertSame(['categories[0].items[0].modifierGroups[0]' => 'Bounds 0 to 0: at least 0, and at most 1 or more.'], self::violations(self::menu($item(0, 0))));
        self::assertSame([], self::violations(self::menu($item(0, null))));
        self::assertSame(
            ['categories[0].items[0].modifierGroups[0].modifiers' => 'No modifier to choose from.'],
            self::violations(self::menu(new Item('ramen', 'Ramen', self::eur(1400), modifierGroups: [new ModifierGroup('sauce', 'Sauce', [])]))),
        );
    }

    public function testHowDeepModifiersNest(): void
    {
        $cooking = new ModifierGroup('cooking', 'Cooking', [new Modifier('rare', 'Rare'), new Modifier('medium', 'Medium')], 1, 1);
        $set = new Item('set', 'Set menu', self::eur(3200), modifierGroups: [new ModifierGroup('main', 'Main course', [new Modifier('beef', 'Wagyu', modifierGroups: [$cooking])], 1, 1)]);

        self::assertSame([], self::violations(self::menu($set), new Capabilities(modifierDepth: 2)));
        self::assertSame(
            ['categories[0].items[0].modifierGroups[0].modifiers[0].modifierGroups[0]' => 'Modifiers nested 2 deep, at most 1.'],
            self::violations(self::menu($set), new Capabilities(modifierDepth: 1)),
        );
        self::assertSame(
            ['categories[0].items[0].modifierGroups' => 'Modifiers are not accepted.'],
            self::violations(self::menu($set), new Capabilities(modifierDepth: 0)),
        );
    }

    public function testNamesDescriptionsPricesAndRates(): void
    {
        $menu = self::menu(new Item('ramen', str_repeat('é', 11), Money::of(-100, 'EUR'), str_repeat('x', 21), 120.0), new Item('sake', ' ', Money::of(900, 'JPY')));

        self::assertSame([
            'categories[0].items[0].name' => '11 characters, at most 10.',
            'categories[0].items[0].description' => '21 characters, at most 20.',
            'categories[0].items[0].price' => '-1.00 EUR: a price is not negative.',
            'categories[0].items[0].vatRate' => '120 %: a rate is between 0 and 100.',
            'categories[0].items[1].name' => 'A name is required.',
            'categories[0].items[1].price' => 'In JPY, the menu is in EUR.',
        ], self::violations($menu, new Capabilities(nameMaxLength: 10, descriptionMaxLength: 20)));
    }

    public function testTheMenuItself(): void
    {
        self::assertSame(
            ['name' => 'A menu has a name.', 'currency' => 'CHF is not accepted (accepted: EUR, GBP).', 'categories' => 'No category.'],
            self::violations(new Menu('', [], 'CHF'), new Capabilities(currencies: ['EUR', 'GBP'])),
        );
        self::assertSame(['categories[0].items' => '"Empty" has no item.'], self::violations(new Menu('M', [new Category('e', 'Empty', [])])));
    }

    public function testEveryItemOfTheMenuInOrder(): void
    {
        $menu = new Menu('M', [new Category('a', 'A', [new Item('1', 'One', self::eur(1))]), new Category('b', 'B', [new Item('2', 'Two', self::eur(2)), new Item('3', 'Three', self::eur(3))])]);

        self::assertSame(['1', '2', '3'], array_map(static fn (Item $i) => $i->ref, $menu->items()));
    }
}
