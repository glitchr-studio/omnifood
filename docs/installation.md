# Installation

```sh
composer require glitchr/omnifood
composer require omnifood/ubereats omnifood/deliveroo omnifood/justeat omnifood/thefork omnifood/zenchef
```

Take only the platforms the restaurant is on. PHP 8.2 or later; the core needs nothing but
`symfony/http-client-contracts`, the platform packages `symfony/http-client`.

In a Symfony application, register the bundle (Flex does it when a recipe exists; otherwise
`config/bundles.php`):

```php
Omnifood\Bridge\Symfony\OmnifoodBundle::class => ['all' => true],
```
