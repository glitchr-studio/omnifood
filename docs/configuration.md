# Configuration

## The registry

```php
use Omnifood\Registry;
use Omnifood\TheFork\TheForkPlatformFactory;
use Omnifood\UberEats\UberEatsPlatformFactory;
use Symfony\Component\HttpClient\HttpClient;

$http = HttpClient::create();
$registry = new Registry([new UberEatsPlatformFactory($http), new TheForkPlatformFactory($http)], [
    'ubereats' => ['factory' => 'ubereats', 'options' => [
        'client_id' => getenv('UBEREATS_CLIENT_ID') ?: null,
        'client_secret' => getenv('UBEREATS_CLIENT_SECRET') ?: null,
        'store_id' => getenv('UBEREATS_STORE_ID') ?: null,
    ]],
    'thefork' => ['factory' => 'thefork', 'options' => [
        'client_id' => getenv('THEFORK_CLIENT_ID') ?: null,
        'client_secret' => getenv('THEFORK_CLIENT_SECRET') ?: null,
        'restaurant_id' => getenv('THEFORK_RESTAURANT_ID') ?: null,
        'webhook_token' => getenv('THEFORK_WEBHOOK_TOKEN') ?: null,
    ]],
]);

$ubereats = $registry->get('ubereats');   // built once, the first time it is asked for
$registry->orders();                       // the platforms that take orders
$registry->reservations();                 // the ones that take reservations
```

Each entry is a name (any), a `factory` (the package's: `ubereats`, `deliveroo`, `justeat`,
`thefork`, `zenchef`) and its `options` (each package's README lists them). Two Uber Eats stores
are two entries with the same factory. An option left empty - `null` or `''`, a variable not
set - is as if not given: the factory's default stands.

In a Symfony application the same entries are written in YAML, under `omnifood.platforms`, and
each platform is injectable by its name: see [Symfony](symfony.md).

## Nothing breaks before a key is set

A platform is built the first time it is asked for, and a key left empty (a variable not set)
only shows when a call needs it, as an `InvalidConfigException`. `capabilities()` needs no key. So
a site boots before its accounts exist:

```php
$registry->has('ubereats');   // declared
try {
    $registry->get('ubereats')->orders();
} catch (InvalidConfigException) {
    // declared, not configured
}
```

## Keys typed in a back office

```php
$platform = $registry->create('ubereats', [
    'client_id' => $settings->get('ubereats.client_id'),
    'client_secret' => $settings->get('ubereats.client_secret'),
]);
```

`create()` builds a platform afresh, the configured options with the given ones over them; it is not
kept (`get()` still gives the configured one).

## Tokens

Uber Eats, Deliveroo and TheFork hand out client-credentials tokens: the platform asks for one when
it needs it and keeps it while it lives. TheFork and Uber Eats ask that tokens not be requested more
than needed: a site that runs many processes keeps the token (`token()`, `refresh()` - a
`RefreshableInterface`) and gives it back as the `access_token` / `token_expires_at` options.
