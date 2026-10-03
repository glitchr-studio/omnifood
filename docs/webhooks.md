# Webhooks

`NotifiableInterface::notify(string $body, array $headers): Notification` checks a webhook - a
signature, a key, a token - and reads it. It throws `InvalidSignatureException` when the check
fails: answer 401 and do nothing.

```php
#[Route('/webhooks/ubereats', methods: ['POST'])]
public function ubereats(Request $request, NotifiableInterface $ubereats, MessageBusInterface $bus): Response
{
    try {
        $notification = $ubereats->notify($request->getContent(), $request->headers->all());
    } catch (InvalidSignatureException) {
        return new Response('', 401);
    }
    $bus->dispatch(new HandleNotification($notification->channel->value, $notification->id, $notification->reference));

    return new Response('', 200);
}
```

Answer fast and work later (Messenger): the platforms retry when the answer is slow or not 2xx, and
send the same event more than once, out of order. `Notification::$id` is the platform's event id
(or one composed from the order and its status, the same on a retry): keep the ids handled.

`Notification`: `channel`, `event` (the platform's word), `subject` (`order`, `courier`,
`reservation`, `store`, `menu`, `other`), `reference` (the order's or reservation's id), `id`,
`order` or `reservation` when the webhook carries it whole, `status`, `occurredAt`, `store`, `raw`.

| | Check | Carries |
|---|---|---|
| Uber Eats | `X-Uber-Signature`: hex HMAC-SHA256 of the body, the client secret | the event and the order's id |
| Deliveroo | `X-Deliveroo-Hmac-Sha256`: hex HMAC-SHA256 of guid + " " + body, the webhook secret | the whole order |
| Just Eat | `Authorization` (the key given to JET), `X-JET-Connect-Hash` (base64 HMAC-SHA256) | the whole order; cancellations, drivers |
| TheFork | the token in the URL (pass it as the `token` header) | the entity and its id |
| Zenchef | not public | - |

Just Eat's Receive Order webhook is answered `{"OrderId": "..."}` with 200 (taken in) or 202 (taken
in later: then `accept()` or `deny()` within 5 minutes). TheFork's is answered `{"data": {}}`.
