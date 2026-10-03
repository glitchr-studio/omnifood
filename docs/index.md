# Omnifood

Omnifood is one PHP contract for the restaurant platforms: the delivery orders of Uber Eats,
Deliveroo and Just Eat, the table reservations of TheFork and Zenchef. A site holds its menu, its
hours and its bookings once; each platform's package turns them into that platform's calls, and
turns what the platform sends back - orders, reservations, webhooks - into the same models.

- [Installation](installation.md)
- [Configuration](configuration.md): the Symfony bundle, the registry, keys from a back office
- [Orders](orders.md): reading, accepting, denying, marking ready, cancelling
- [Menu](menu.md): the model, the validator, pushing it, items out of stock
- [Store](store.md): open, paused, hours
- [Reservations](reservations.md): reading, creating, updating, the service's statuses, availability
- [Webhooks](webhooks.md): checking and reading them, handling each once
- [Harness](harness.md): the Docker console, with your keys

## The packages

| Package | Platform | Orders | Menu | Store | Reservations | Webhooks |
|---|---|---|---|---|---|---|
| `omnifood/ubereats` | Uber Eats | yes | yes | yes | - | signed (HMAC) |
| `omnifood/deliveroo` | Deliveroo | yes (no cancel) | yes | yes | - | signed (HMAC) |
| `omnifood/justeat` | Just Eat (JET Connect) | pushed only | yes | yes (no read) | - | key / HMAC |
| `omnifood/thefork` | TheFork | - | - | - | yes (statuses read only) | URL token |
| `omnifood/zenchef` | Zenchef | - | - | - | refused until documented | refused |

What a platform offers but does not document publicly throws `NotSupportedException`. No platform
gives keys without a partner agreement: the packages are tested against recorded answers, not
against the live services.
