# Configuration

## The bundle

```yaml
# config/packages/omnifood.yaml
omnifood:
    platforms:
        ubereats:
            factory: ubereats
            options:
                client_id: '%env(default::UBEREATS_CLIENT_ID)%'
                client_secret: '%env(default::UBEREATS_CLIENT_SECRET)%'
                store_id: '%env(default::UBEREATS_STORE_ID)%'
        thefork:
            factory: thefork
            options:
                client_id: '%env(default::THEFORK_CLIENT_ID)%'
                client_secret: '%env(default::THEFORK_CLIENT_SECRET)%'
                restaurant_id: '%env(default::THEFORK_RESTAURANT_ID)%'
                webhook_token: '%env(default::THEFORK_WEBHOOK_TOKEN)%'
```

Each entry is a name (any), a `factory` (the package's: `ubereats`, `deliveroo`, `justeat`,
`thefork`, `zenchef`) and its `options` (each package's README lists them). Two Uber Eats stores
are two entries with the same factory.

Each platform is then injectable by its name, as what it does:

```php
use Omnifood\OrdersInterface;
use Omnifood\ReservationsInterface;

public function __construct(
    private OrdersInterface $ubereats,
    private ReservationsInterface $thefork,
) {}
```

`Omnifood\Registry` and `Omnifood\Validator` are autowired too.

## Nothing breaks before a key is set

A platform is built the first time it is asked for, and a key left empty
(`%env(default::...)%` with the variable unset) only shows when a call needs it, as an
`InvalidConfigException`. `capabilities()` needs no key. So a site boots before its accounts exist:

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

## Without Symfony

```php
$http = Symfony\Component\HttpClient\HttpClient::create();
$registry = new Omnifood\Registry([new Omnifood\UberEats\UberEatsPlatformFactory($http)], [
    'ubereats' => ['factory' => 'ubereats', 'options' => ['client_id' => '...', 'client_secret' => '...', 'store_id' => '...']],
]);
$ubereats = $registry->get('ubereats');
```
