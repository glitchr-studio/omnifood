# Orders

`OrdersInterface`: `order($ref)`, `orders($since)`, `accept($ref, $readyAt)`, `deny($ref, $reason, $note)`,
`ready($ref)`, `cancel($ref, $reason, $note)`. `$ref` is always the platform's id for the order.

## The cycle

1. A webhook says an order arrived (see [webhooks](webhooks.md)); `Notification::$order` carries it
   when the platform sends it whole (Deliveroo, Just Eat), `order($notification->reference)` reads
   it otherwise (Uber Eats).
2. The kitchen accepts or denies it within the platform's delay - `capabilities()->acceptanceDelay`,
   in seconds: 690 at Uber Eats, 600 at Deliveroo - or the platform cancels it.
3. `ready()` when it is ready for the courier or the customer.

```php
$order = $platform->order($ref);
foreach ($order->lines as $line) {
    echo $line->quantity, ' x ', $line->name, ' (', $line->posRef, ')';
    foreach ($line->modifiers as $modifier) {
        echo ' + ', $modifier->name;
    }
}
$platform->accept($order->reference, new \DateTimeImmutable('+15 minutes'));
```

`DenyReason` is Omnifood's: `item_unavailable`, `closed`, `too_busy`, `customer_request`, `address`,
`pricing`, `special_instructions`, `technical`, `other`; each package maps it to its platform's codes.

## The Order

`channel`, `reference`, `displayId` (what the courier says), `type` (`delivery`, `pickup`,
`dine_in`), `status` (`new`, `accepted`, `ready`, `picked_up`, `delivered`, `denied`, `cancelled`),
`lines`, `customer`, `total`, `subtotal`, `discount`, `fees`, `tax` (all `Money`, minor units),
`placedAt`, `pickupAt`, `deliverAt`, `courier`, `note`, `store`, `raw`.

## What each platform does

| | Uber Eats | Deliveroo | Just Eat |
|---|---|---|---|
| `order()` | yes | yes | no (pushed only) |
| `orders()` | yes (60 days) | yes (30 days) | no |
| `accept()` | yes, with a ready time | yes (tablet sites: sync status) | the till's confirmation after a 202 |
| `deny()` | yes | yes (tablet sites: sync status) | to the tablet's backup flow |
| `ready()` | yes | prep stage `ready_for_collection` | no |
| `cancel()` | yes | no (customer service) | no (Orderpad) |
