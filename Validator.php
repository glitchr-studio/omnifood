<?php

namespace Omnifood;

use Omnifood\Model\Capabilities;
use Omnifood\Model\Menu\Item;
use Omnifood\Model\Menu\Menu;
use Omnifood\Model\Menu\ModifierGroup;
use Omnifood\Model\Money;
use Omnifood\Model\Violation;

/**
 * A menu against a platform's capabilities, before anything is sent: the
 * references (present, one each), the names and descriptions' lengths, the
 * prices (in the menu's currency, not negative), the VAT rates, the
 * modifier groups' bounds and how deep they nest. Readable violations, by
 * path, for a back office to show.
 *
 * What a platform ignores is not a violation (a photo where it takes none):
 * only what it would refuse.
 */
final class Validator
{
    /** @return list<Violation> */
    public function validate(Menu $menu, Capabilities $c): array
    {
        $v = [];
        if ('' === trim($menu->name)) {
            $v[] = new Violation('name', 'A menu has a name.');
        }
        if ($c->currencies && !\in_array(strtoupper($menu->currency), $c->currencies, true)) {
            $v[] = new Violation('currency', \sprintf('%s is not accepted (accepted: %s).', $menu->currency, implode(', ', $c->currencies)));
        }
        if (!$menu->categories) {
            $v[] = new Violation('categories', 'No category.');
        }
        /** @var array<string, string> $refs ref => first path */
        $refs = [];
        $groups = [];
        foreach ($menu->categories as $i => $category) {
            $path = "categories[$i]";
            $this->ref($v, $refs, $category->ref, $path, 'category:');
            $this->name($v, $category->name, "$path.name", $c);
            if (!$category->items) {
                $v[] = new Violation("$path.items", \sprintf('"%s" has no item.', $category->name));
            }
            foreach ($category->items as $j => $item) {
                $this->item($v, $refs, $groups, $item, "$path.items[$j]", $menu->currency, $c);
            }
        }

        return $v;
    }

    /**
     * @param list<Violation>                   $v
     * @param array<string, string>             $refs
     * @param array<string, ModifierGroup>      $groups the groups met, by ref: one ref is one group, shared or not
     */
    private function item(array &$v, array &$refs, array &$groups, Item $item, string $path, string $currency, Capabilities $c): void
    {
        $this->ref($v, $refs, $item->ref, $path, 'item:', $item);
        $this->name($v, $item->name, "$path.name", $c);
        $this->description($v, $item->description, "$path.description", $c);
        $this->price($v, $item->price, "$path.price", $currency);
        $this->vat($v, $item->vatRate, "$path.vatRate");
        if ($item->modifierGroups && 0 === $c->modifierDepth) {
            $v[] = new Violation("$path.modifierGroups", 'Modifiers are not accepted.');

            return;
        }
        foreach ($item->modifierGroups as $k => $group) {
            $this->group($v, $refs, $groups, $group, "$path.modifierGroups[$k]", $currency, $c, 1);
        }
    }

    /**
     * @param list<Violation>              $v
     * @param array<string, string>        $refs
     * @param array<string, ModifierGroup> $groups
     */
    private function group(array &$v, array &$refs, array &$groups, ModifierGroup $group, string $path, string $currency, Capabilities $c, int $depth): void
    {
        if ($depth > $c->modifierDepth) {
            $v[] = new Violation($path, \sprintf('Modifiers nested %d deep, at most %d.', $depth, $c->modifierDepth));

            return;
        }
        if ('' === trim($group->ref)) {
            $v[] = new Violation("$path.ref", 'A reference is required.');
        } elseif (isset($groups[$group->ref]) && $groups[$group->ref] != $group) {
            $v[] = new Violation("$path.ref", \sprintf('"%s" names two different groups.', $group->ref));
        } else {
            $groups[$group->ref] = $group;
        }
        $this->name($v, $group->name, "$path.name", $c);
        $count = \count($group->modifiers);
        if (!$count) {
            $v[] = new Violation("$path.modifiers", 'No modifier to choose from.');
        }
        if ($group->min < 0 || (null !== $group->max && $group->max < 1)) {
            $v[] = new Violation($path, \sprintf('Bounds %d to %s: at least 0, and at most 1 or more.', $group->min, $group->max ?? 'any'));
        } elseif (null !== $group->max && $group->min > $group->max) {
            $v[] = new Violation($path, \sprintf('At least %d but at most %d.', $group->min, $group->max));
        } elseif ($count && $group->min > $count) {
            $v[] = new Violation($path, \sprintf('At least %d to choose among %d.', $group->min, $count));
        }
        foreach ($group->modifiers as $m => $modifier) {
            $mpath = "$path.modifiers[$m]";
            $this->ref($v, $refs, $modifier->ref, $mpath, $c->modifiersAreItems ? 'item:' : 'modifier:', $modifier);
            $this->name($v, $modifier->name, "$mpath.name", $c);
            if (null !== $modifier->price) {
                $this->price($v, $modifier->price, "$mpath.price", $currency);
            }
            $this->vat($v, $modifier->vatRate, "$mpath.vatRate");
            foreach ($modifier->modifierGroups as $k => $sub) {
                $this->group($v, $refs, $groups, $sub, "$mpath.modifierGroups[$k]", $currency, $c, $depth + 1);
            }
        }
    }

    /**
     * One reference, one thing. A category's is its own; an item may be
     * listed in two categories, a modifier offered in two groups, as long
     * as the name and price are the same: then it is the same one.
     *
     * @param list<Violation>       $v
     * @param array<string, string> $refs space+ref => what it was first, and where
     */
    private function ref(array &$v, array &$refs, string $ref, string $path, string $space, ?object $thing = null): void
    {
        if ('' === trim($ref)) {
            $v[] = new Violation("$path.ref", 'A reference is required.');

            return;
        }
        $key = $space.$ref;
        $what = null === $thing ? '' : serialize([$thing->name, $thing->price]);
        if (!isset($refs[$key])) {
            $refs[$key] = $path;
            $refs["$key#"] = $what;
        } elseif (null === $thing || $refs["$key#"] !== $what) {
            $v[] = new Violation("$path.ref", \sprintf('"%s" is already %s%s.', $ref, $refs[$key], null === $thing ? '' : ', with another name or price'));
        }
    }

    /** @param list<Violation> $v */
    private function name(array &$v, string $name, string $path, Capabilities $c): void
    {
        if ('' === trim($name)) {
            $v[] = new Violation($path, 'A name is required.');
        } elseif (null !== $c->nameMaxLength && mb_strlen($name) > $c->nameMaxLength) {
            $v[] = new Violation($path, \sprintf('%d characters, at most %d.', mb_strlen($name), $c->nameMaxLength));
        }
    }

    /** @param list<Violation> $v */
    private function description(array &$v, ?string $description, string $path, Capabilities $c): void
    {
        if (null !== $description && null !== $c->descriptionMaxLength && mb_strlen($description) > $c->descriptionMaxLength) {
            $v[] = new Violation($path, \sprintf('%d characters, at most %d.', mb_strlen($description), $c->descriptionMaxLength));
        }
    }

    /** @param list<Violation> $v */
    private function price(array &$v, Money $price, string $path, string $currency): void
    {
        if ($price->currency !== strtoupper($currency)) {
            $v[] = new Violation($path, \sprintf('In %s, the menu is in %s.', $price->currency, strtoupper($currency)));
        }
        if ($price->amount < 0) {
            $v[] = new Violation($path, \sprintf('%s: a price is not negative.', $price));
        }
    }

    /** @param list<Violation> $v */
    private function vat(array &$v, ?float $rate, string $path): void
    {
        if (null !== $rate && ($rate < 0 || $rate > 100)) {
            $v[] = new Violation($path, \sprintf('%s %%: a rate is between 0 and 100.', $rate));
        }
    }
}
