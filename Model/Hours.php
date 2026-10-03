<?php

namespace Omnifood\Model;

/**
 * Opening hours: for each day of the week (1 Monday ... 7 Sunday) its ranges
 * ["11:30", "14:30"], and dates that differ (a holiday closed, a Sunday
 * open). A day with no range is closed; a range may end after midnight
 * ("19:00", "01:00").
 */
final readonly class Hours
{
    /** @var array<int, list<array{string, string}>> */
    public array $week;

    /** @var array<string, list<array{string, string}>> "Y-m-d" => ranges; [] closed that day */
    public array $exceptions;

    /**
     * @param array<int, list<array{string, string}>>    $week
     * @param array<string, list<array{string, string}>> $exceptions
     */
    public function __construct(array $week, array $exceptions = [])
    {
        $days = [];
        foreach ($week as $day => $ranges) {
            if (!\is_int($day) || $day < 1 || $day > 7) {
                throw new \InvalidArgumentException(\sprintf('Day %s: 1 (Monday) to 7 (Sunday).', $day));
            }
            $days[$day] = self::ranges($ranges, "day $day");
        }
        ksort($days);
        $this->week = $days;
        $dates = [];
        foreach ($exceptions as $date => $ranges) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
                throw new \InvalidArgumentException(\sprintf('Exception "%s": a date, Y-m-d.', $date));
            }
            $dates[(string) $date] = self::ranges($ranges, (string) $date);
        }
        ksort($dates);
        $this->exceptions = $dates;
    }

    /** @return list<array{string, string}> the ranges of that day of the week, [] when closed */
    public function day(int $day): array
    {
        return $this->week[$day] ?? [];
    }

    /** @return list<array{string, string}> that date's ranges: its exception, or its day of the week's */
    public function on(\DateTimeInterface $date): array
    {
        return $this->exceptions[$date->format('Y-m-d')] ?? $this->day((int) $date->format('N'));
    }

    /** Whether $at falls in one of its date's ranges (a range past midnight counted on the day it starts). */
    public function isOpenAt(\DateTimeImmutable $at): bool
    {
        $time = $at->format('H:i');
        foreach ($this->on($at) as [$from, $to]) {
            if ($to > $from ? ($time >= $from && $time < $to) : $time >= $from) {
                return true;
            }
        }
        foreach ($this->on($at->modify('-1 day')) as [$from, $to]) {
            if ($to <= $from && $time < $to) {
                return true;
            }
        }

        return false;
    }

    /** @return list<array{string, string}> */
    private static function ranges(array $ranges, string $where): array
    {
        $out = [];
        foreach (array_values($ranges) as $range) {
            [$from, $to] = array_values((array) $range) + [null, null];
            foreach ([$from, $to] as $time) {
                if (!\is_string($time) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time)) {
                    throw new \InvalidArgumentException(\sprintf('%s: "%s" is not a time, HH:MM.', ucfirst($where), \is_scalar($time) ? $time : get_debug_type($time)));
                }
            }
            if ($from === $to) {
                throw new \InvalidArgumentException(\sprintf('%s: a range from %s to %s is empty.', ucfirst($where), $from, $to));
            }
            $out[] = [$from, $to];
        }
        usort($out, static fn (array $a, array $b) => strcmp($a[0], $b[0]));

        return $out;
    }
}
