# Store

`StoreInterface`: `status()`, `pause($until, $reason)`, `resume()`, `setHours(Hours)`.

```php
$platform->pause(new \DateTimeImmutable('+30 minutes'), 'Rush');   // no new order for half an hour
$platform->resume();
$platform->setHours(new Hours(
    [1 => [['12:00', '14:30'], ['19:00', '22:30']], 2 => [['12:00', '14:30']]],
    ['2026-12-25' => []],                                            // closed that day
));
```

`Hours`: per day of the week (1 Monday ... 7 Sunday) its ranges `['HH:MM', 'HH:MM']`, a range past
midnight allowed; `exceptions` per date. `isOpenAt()` and `on()` answer for a given time.

| | Uber Eats | Deliveroo | Just Eat |
|---|---|---|---|
| `status()` | online / offline, until | open / closed | no (a webhook says) |
| `pause($until)` | with its end | closes; no end: `resume()` | offline until a local time |
| `setHours()` | the dates apart (holiday hours); the week is the menu's | the week | the week, delivery and collection |
