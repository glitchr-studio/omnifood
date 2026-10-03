<?php

namespace Omnifood\Tests;

use Omnifood\Model\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testMinorUnitsAndDecimals(): void
    {
        self::assertSame('12.50', Money::of(1250, 'eur')->decimal());
        self::assertSame('EUR', Money::of(1250, 'eur')->currency);
        self::assertSame('1250', Money::of(1250, 'JPY')->decimal(), 'the yen has no minor unit');
        self::assertTrue(Money::fromDecimal('12.5', 'EUR')->equals(Money::of(1250, 'EUR')));
        self::assertSame(1999, Money::fromDecimal(19.99, 'EUR')->amount, 'rounded, not truncated');
        self::assertSame('12.50 EUR', (string) Money::of(1250, 'EUR'));
    }
}
