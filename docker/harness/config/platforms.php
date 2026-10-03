<?php

/**
 * One platform each, its options from the environment (.env). Every
 * installed platform is built - capabilities and menu:push --validate need
 * no key - and "needs" lists the variables it takes to call it.
 *
 * @return array<string, array{factory: string, needs: list<string>, options: array<string, mixed>}>
 */
$env = static fn (string $key, mixed $default = null): mixed => (false !== ($v = getenv($key)) && '' !== $v) ? $v : $default;

return [
    'ubereats' => [
        'factory' => 'ubereats',
        'needs' => ['UBEREATS_CLIENT_ID', 'UBEREATS_CLIENT_SECRET', 'UBEREATS_STORE_ID'],
        'options' => ['client_id' => $env('UBEREATS_CLIENT_ID'), 'client_secret' => $env('UBEREATS_CLIENT_SECRET'), 'store_id' => $env('UBEREATS_STORE_ID'), 'sandbox' => $env('UBEREATS_SANDBOX', false)],
    ],
    'deliveroo' => [
        'factory' => 'deliveroo',
        'needs' => ['DELIVEROO_CLIENT_ID', 'DELIVEROO_CLIENT_SECRET', 'DELIVEROO_BRAND_ID', 'DELIVEROO_SITE_ID'],
        'options' => ['client_id' => $env('DELIVEROO_CLIENT_ID'), 'client_secret' => $env('DELIVEROO_CLIENT_SECRET'), 'brand_id' => $env('DELIVEROO_BRAND_ID'), 'site_id' => $env('DELIVEROO_SITE_ID'), 'menu_id' => $env('DELIVEROO_MENU_ID'), 'webhook_secret' => $env('DELIVEROO_WEBHOOK_SECRET'), 'sandbox' => $env('DELIVEROO_SANDBOX', false), 'tablet' => $env('DELIVEROO_TABLET', false)],
    ],
    'justeat' => [
        'factory' => 'justeat',
        'needs' => ['JUSTEAT_API_KEY', 'JUSTEAT_RESTAURANT'],
        'options' => ['api_key' => $env('JUSTEAT_API_KEY'), 'restaurant' => $env('JUSTEAT_RESTAURANT'), 'webhook_key' => $env('JUSTEAT_WEBHOOK_KEY'), 'webhook_secret' => $env('JUSTEAT_WEBHOOK_SECRET'), 'currency' => $env('JUSTEAT_CURRENCY'), 'timezone' => $env('JUSTEAT_TIMEZONE')],
    ],
    'thefork' => [
        'factory' => 'thefork',
        'needs' => ['THEFORK_CLIENT_ID', 'THEFORK_CLIENT_SECRET', 'THEFORK_RESTAURANT_ID'],
        'options' => ['client_id' => $env('THEFORK_CLIENT_ID'), 'client_secret' => $env('THEFORK_CLIENT_SECRET'), 'restaurant_id' => $env('THEFORK_RESTAURANT_ID'), 'webhook_token' => $env('THEFORK_WEBHOOK_TOKEN')],
    ],
    'zenchef' => [
        'factory' => 'zenchef',
        'needs' => ['ZENCHEF_TOKEN', 'ZENCHEF_RESTAURANT_ID', 'ZENCHEF_BASE_URI'],
        'options' => ['token' => $env('ZENCHEF_TOKEN'), 'restaurant_id' => $env('ZENCHEF_RESTAURANT_ID'), 'base_uri' => $env('ZENCHEF_BASE_URI')],
    ],
];
