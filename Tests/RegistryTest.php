<?php

namespace Omnifood\Tests;

use Omnifood\Exception\InvalidConfigException;
use Omnifood\Registry;
use PHPUnit\Framework\TestCase;

final class RegistryTest extends TestCase
{
    private static function registry(): Registry
    {
        return new Registry([new StubFactory(), new StubBookFactory()], [
            'kitchen' => ['factory' => 'stub', 'options' => ['key' => 'a']],
            'tables' => ['factory' => 'book'],
        ]);
    }

    public function testEachPlatformIsBuiltOnceByName(): void
    {
        $registry = self::registry();

        self::assertTrue($registry->has('kitchen'));
        self::assertFalse($registry->has('ubereats'));
        self::assertSame($registry->get('kitchen'), $registry->get('kitchen'));
        self::assertSame('a', $registry->get('kitchen')->key);
        self::assertSame(['kitchen', 'tables'], $registry->names());
        self::assertSame(['kitchen', 'tables'], array_keys($registry->all()));
        self::assertSame(['stub', 'book'], $registry->factories());
    }

    public function testThePlatformsByWhatTheyDo(): void
    {
        $registry = self::registry();

        self::assertSame([$registry->get('kitchen')], $registry->orders());
        self::assertSame([$registry->get('kitchen')], $registry->menus());
        self::assertSame([$registry->get('tables')], $registry->reservations());
        self::assertSame([], $registry->stores());
        self::assertSame([], $registry->notifiables());
    }

    public function testAPlatformBuiltAfreshWithKeysTypedInABackOffice(): void
    {
        $registry = self::registry();

        self::assertSame(['key' => 'a'], $registry->options('kitchen'));
        $typed = $registry->create('kitchen', ['key' => 'b', 'store_id' => 's-9']);
        self::assertNotSame($registry->get('kitchen'), $typed);
        self::assertSame('b', $typed->key);
        self::assertSame('s-9', $typed->storeId);
        self::assertSame('a', $registry->get('kitchen')->key, 'the configured one unchanged');
    }

    public function testAnUnknownPlatformSaysWhichAreConfigured(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('No "ubereats" platform; configured: kitchen, tables.');
        self::registry()->get('ubereats');
    }

    public function testAnUnknownFactorySaysWhichAreInstalled(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('No "ubereats" factory for the "ubereats" platform; installed: stub.');
        (new Registry([new StubFactory()], ['ubereats' => ['factory' => 'ubereats']]))->get('ubereats');
    }

    public function testRequiredOptionsAreCheckedWhenThePlatformIsAskedFor(): void
    {
        $registry = new Registry([new StubFactory()], ['kitchen' => ['factory' => 'stub', 'options' => ['key' => '', 'store_id' => null]]]);
        self::assertTrue($registry->has('kitchen'), 'nothing is built, nor checked, before get()');

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The "stub" platform needs: key.');
        $registry->get('kitchen');
    }

    public function testAnOptionLeftEmptyIsItsDefault(): void
    {
        $platform = (new StubFactory())->create(['key' => 'k', 'store_id' => null]);

        self::assertSame('stub', $platform->storeId);
        self::assertSame('stub', (new StubFactory())->getName());
    }
}
