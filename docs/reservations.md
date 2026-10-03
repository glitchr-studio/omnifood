# Reservations

`ReservationsInterface`: `reservations($from, $to)`, `reservation($ref)`,
`createReservation(Reservation)`, `updateReservation(Reservation)`, `cancelReservation($ref, $reason)`,
`setStatus($ref, ReservationStatus)`, `pushAvailability(list<Slot>)`. The writing methods are named
after the reservation so that one platform may take orders and reservations both.

```php
$taken = new Reservation(new \DateTimeImmutable('2026-10-12 19:30'), 4,
    new Guest('Jo', 'Martin', 'jo@example.com', '+33612345678', 'fr_FR'), notes: 'A high chair');
$recorded = $thefork->createReservation($taken);          // its reference at TheFork
$thefork->updateReservation($recorded->with(covers: 5));
$thefork->cancelReservation($recorded->reference);
```

`ReservationStatus`: `requested`, `confirmed`, `arrived`, `seated`, `finished`, `no_show`,
`cancelled`, `refused`. `Slot`: a start, the covers still bookable (0: full), an end, an area.

| | TheFork | Zenchef |
|---|---|---|
| read, list | yes (and the guest's profile) | refused: documentation on request |
| create, update, cancel | yes (date, party size, staff note) | refused |
| `setStatus()` | cancellation only (the service's statuses are read, not written) | refused |
| `pushAvailability()` | slots opened or closed online | refused |
