<?php

namespace Omnifood\Tests;

use Omnifood\Model\Hours;
use PHPUnit\Framework\TestCase;

final class HoursTest extends TestCase
{
    private static function hours(): Hours
    {
        return new Hours(
            [5 => [['19:00', '01:00'], ['11:30', '14:30']], 1 => [['11:30', '14:30']], 6 => [['19:00', '23:00']]],
            ['2026-12-25' => [], '2026-12-27' => [['12:00', '15:00']]],
        );
    }

    public function testTheWeekSortedAndADayWithoutRangesClosed(): void
    {
        $hours = self::hours();

        self::assertSame([1, 5, 6], array_keys($hours->week));
        self::assertSame([['11:30', '14:30'], ['19:00', '01:00']], $hours->day(5), 'ranges sorted');
        self::assertSame([], $hours->day(2));
    }

    public function testAnExceptionReplacesItsDayOfTheWeek(): void
    {
        $hours = self::hours();

        self::assertSame([], $hours->on(new \DateTimeImmutable('2026-12-25')), 'a Friday, closed for Christmas');
        self::assertSame([['12:00', '15:00']], $hours->on(new \DateTimeImmutable('2026-12-27')), 'a Sunday, open that once');
        self::assertSame([['11:30', '14:30'], ['19:00', '01:00']], $hours->on(new \DateTimeImmutable('2026-12-18')));
    }

    public function testOpenAtCountsARangePastMidnightOnTheDayItStarts(): void
    {
        $hours = self::hours();

        self::assertTrue($hours->isOpenAt(new \DateTimeImmutable('2026-12-18 12:00')), 'Friday lunch');
        self::assertFalse($hours->isOpenAt(new \DateTimeImmutable('2026-12-18 14:30')), 'the end excluded');
        self::assertTrue($hours->isOpenAt(new \DateTimeImmutable('2026-12-18 23:30')));
        self::assertTrue($hours->isOpenAt(new \DateTimeImmutable('2026-12-19 00:30')), 'Friday night, on Saturday');
        self::assertFalse($hours->isOpenAt(new \DateTimeImmutable('2026-12-19 01:30')));
        self::assertFalse($hours->isOpenAt(new \DateTimeImmutable('2026-12-15 12:00')), 'Tuesday, closed');
        self::assertFalse($hours->isOpenAt(new \DateTimeImmutable('2026-12-26 00:30')), 'Christmas closed: its night too');
    }

    public function testWhatIsNotATimeIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Day 1: "25:00" is not a time, HH:MM.');
        new Hours([1 => [['11:00', '25:00']]]);
    }

    public function testADayIsOneToSeven(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Hours([0 => [['11:00', '14:00']]]);
    }
}
