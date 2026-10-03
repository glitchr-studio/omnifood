# Harness

`docker/` in this package runs it with every `omnifood/*` platform installed and a console to call
them with real keys.

```sh
cd vendor/glitchr/omnifood/docker   # or the checkout's docker/
cp .env.dist .env                   # fill in the keys at hand
docker compose run --rm omnifood platforms
```

The platforms come from GitHub (branch 1.x), or from the checkouts beside the core when
`OMNIFOOD_PLUGINS=../..` is set in `.env` (the workspace layout).

| Command | Does |
|---|---|
| `platforms` | installed, configured (which variables are missing), what each does |
| `capabilities <platform>` | what it takes, as JSON (no key) |
| `menu:push <platform> <file> [--validate]` | a menu from JSON (`menu.example.json`): checked, or sent |
| `orders <platform> [--since]`, `order <platform> <id>` | the orders, as JSON |
| `accept`, `deny --reason --note`, `ready` | the order's cycle |
| `store:status <platform>` | open or not |
| `reservations <platform> [--from --to]` | the reservations, as JSON |
| `notify <platform> <body file> -H "Name: value"` | a webhook checked and read |
| `refresh <platform>`, `authorize <platform> <redirect-uri>` | tokens |
| `test` | every package's tests |
