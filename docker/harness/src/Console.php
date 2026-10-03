<?php

namespace Omnifood\Harness;

use Omnifood\Auth\OAuthInterface;
use Omnifood\Auth\RefreshableInterface;
use Omnifood\Exception\InvalidConfigException;
use Omnifood\Exception\InvalidMenuException;
use Omnifood\Exception\OmnifoodException;
use Omnifood\MenuInterface;
use Omnifood\Model\Allergen;
use Omnifood\Model\DenyReason;
use Omnifood\Model\Hours;
use Omnifood\Model\Menu\Category;
use Omnifood\Model\Menu\Item;
use Omnifood\Model\Menu\Menu;
use Omnifood\Model\Menu\Modifier;
use Omnifood\Model\Menu\ModifierGroup;
use Omnifood\Model\Money;
use Omnifood\NotifiableInterface;
use Omnifood\OrdersInterface;
use Omnifood\PlatformFactoryInterface;
use Omnifood\PlatformInterface;
use Omnifood\Registry;
use Omnifood\ReservationsInterface;
use Omnifood\StoreInterface;
use Omnifood\Validator;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

/**
 * The console that exercises every platform with the keys in .env:
 * platforms, capabilities, orders, order, accept, deny, ready, menu:push,
 * store:status, reservations, notify. Results are printed whole, as JSON.
 */
final class Console
{
    /** @var array<string, array{factory: string, needs: list<string>, options: array<string, mixed>}> */
    private array $config;

    /** @var array<string, PlatformFactoryInterface> */
    private array $factories = [];

    private Registry $registry;

    private function __construct()
    {
        $this->config = require __DIR__.'/../config/platforms.php';
        $http = HttpClient::create();
        foreach (require __DIR__.'/../plugins.php' as [, $class]) {
            if (class_exists($class)) {
                $factory = new $class($http);
                $this->factories[$factory->getName()] = $factory;
            }
        }
        // Every installed platform, configured or not: what needs no key works without one.
        $installed = array_filter($this->config, fn (array $p) => isset($this->factories[$p['factory']]));
        $this->registry = new Registry($this->factories, array_map(static fn (array $p) => ['factory' => $p['factory'], 'options' => $p['options']], $installed));
    }

    public static function create(): Application
    {
        $self = new self();
        $platform = new InputArgument('platform', InputArgument::REQUIRED, 'ubereats, deliveroo, justeat, thefork, zenchef');
        $ref = new InputArgument('ref', InputArgument::REQUIRED, 'The order\'s (or reservation\'s) id on the platform');
        $app = new Application('omnifood', '1.x');
        $app->addCommand($self->command('platforms', 'Which platforms are installed, configured, and what each does', [], fn ($in, $out) => $self->platforms($out)));
        $app->addCommand($self->command('capabilities', 'What the platform takes and imposes (no key needed)', [$platform], fn ($in, $out) => $self->print($out, $self->platform($in)->capabilities())));
        $app->addCommand($self->command('orders', 'The orders placed since then, newest first', [$platform, new InputOption('since', 's', InputOption::VALUE_REQUIRED, 'A date PHP reads', '-1 day')], fn ($in, $out) => $self->print($out, $self->orders($in)->orders(new \DateTimeImmutable($in->getOption('since'))))));
        $app->addCommand($self->command('order', 'One order, whole', [$platform, $ref], fn ($in, $out) => $self->print($out, $self->orders($in)->order($in->getArgument('ref')))));
        $app->addCommand($self->command('accept', 'An order accepted', [$platform, $ref, new InputOption('ready-at', null, InputOption::VALUE_REQUIRED, 'When it will be ready: "+20 minutes"')], fn ($in, $out) => $self->done($out, $self->orders($in)->accept($in->getArgument('ref'), $in->getOption('ready-at') ? new \DateTimeImmutable($in->getOption('ready-at')) : null))));
        $app->addCommand($self->command('deny', 'An order refused', [$platform, $ref, new InputOption('reason', null, InputOption::VALUE_REQUIRED, implode(', ', array_map(static fn (DenyReason $r) => $r->value, DenyReason::cases())), 'other'), new InputOption('note', null, InputOption::VALUE_REQUIRED)], fn ($in, $out) => $self->done($out, $self->orders($in)->deny($in->getArgument('ref'), DenyReason::from($in->getOption('reason')), $in->getOption('note')))));
        $app->addCommand($self->command('ready', 'An order ready for the courier', [$platform, $ref], fn ($in, $out) => $self->done($out, $self->orders($in)->ready($in->getArgument('ref')))));
        $app->addCommand($self->command('menu:push', 'A menu from a JSON file (see menu.example.json) checked, then sent', [$platform, new InputArgument('file', InputArgument::REQUIRED), new InputOption('validate', null, InputOption::VALUE_NONE, 'Only check it: nothing is sent')], fn ($in, $out) => $self->menuPush($in, $out)));
        $app->addCommand($self->command('store:status', 'Whether the store is open', [$platform], fn ($in, $out) => $self->print($out, $self->store($in)->status())));
        $app->addCommand($self->command('reservations', 'The reservations of a period, oldest first', [$platform, new InputOption('from', null, InputOption::VALUE_REQUIRED, '', 'today'), new InputOption('to', null, InputOption::VALUE_REQUIRED, '', '+7 days')], fn ($in, $out) => $self->print($out, $self->reservations($in)->reservations(new \DateTimeImmutable($in->getOption('from')), new \DateTimeImmutable($in->getOption('to'))))));
        $app->addCommand($self->command('notify', 'A webhook\'s body (a file) and headers, checked and read', [$platform, new InputArgument('body', InputArgument::REQUIRED, 'The file holding the raw body'), new InputOption('header', 'H', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, '"Name: value"')], fn ($in, $out) => $self->notify($in, $out)));
        $app->addCommand($self->command('refresh', 'A fresh token, printed as a site would keep it', [$platform], fn ($in, $out) => $self->print($out, $self->refreshable($in)->refresh()->toArray())));
        $app->addCommand($self->command('authorize', 'The URL to link the account', [$platform, new InputArgument('redirect-uri', InputArgument::REQUIRED), new InputOption('state', null, InputOption::VALUE_REQUIRED, '', 'omnifood')], fn ($in, $out) => $out->writeln($self->oauth($in)->authorizationUrl($in->getArgument('redirect-uri'), $in->getOption('state')))));

        return $app;
    }

    /** @param list<InputArgument|InputOption> $definition */
    private function command(string $name, string $description, array $definition, \Closure $code): Command
    {
        $command = new Command($name);
        $command->setDescription($description)->setDefinition($definition);
        $command->setCode(function (InputInterface $in, OutputInterface $out) use ($code): int {
            try {
                return (int) ($code($in, $out) ?? Command::SUCCESS);
            } catch (InvalidConfigException $e) {
                $out->writeln('<error>'.$e->getMessage().'</error>');

                return Command::INVALID;
            } catch (InvalidMenuException $e) {
                $out->writeln('<error>Refused before anything was sent:</error>');
                foreach ($e->violations as $violation) {
                    $out->writeln('  - '.$violation);
                }

                return Command::FAILURE;
            } catch (OmnifoodException $e) {
                $out->writeln('<error>'.$e->getMessage().'</error>');

                return Command::FAILURE;
            }
        });

        return $command;
    }

    private function platforms(OutputInterface $out): void
    {
        $set = static fn (string $key) => false !== getenv($key) && '' !== getenv($key);
        $table = new Table($out);
        $table->setHeaders(['Platform', 'Installed', 'Configured', 'Does']);
        foreach ($this->config as $name => $p) {
            $installed = isset($this->factories[$p['factory']]);
            $missing = array_values(array_filter($p['needs'], static fn (string $k) => !$set($k)));
            $does = '';
            if ($installed) {
                $platform = $this->registry->get($name);
                $does = implode(' ', array_keys(array_filter([
                    'orders' => $platform instanceof OrdersInterface,
                    'menu' => $platform instanceof MenuInterface,
                    'store' => $platform instanceof StoreInterface,
                    'reservations' => $platform instanceof ReservationsInterface,
                    'notify' => $platform instanceof NotifiableInterface,
                    'refresh' => $platform instanceof RefreshableInterface,
                    'oauth' => $platform instanceof OAuthInterface,
                ])));
            }
            $table->addRow([$name, $installed ? '<info>yes</info>' : '<comment>no</comment>', !$installed ? '' : ($missing ? '<comment>needs '.implode(', ', $missing).'</comment>' : '<info>yes</info>'), $does]);
        }
        $table->render();
    }

    private function menuPush(InputInterface $in, OutputInterface $out): int
    {
        $platform = $this->platform($in);
        $data = json_decode((string) file_get_contents($in->getArgument('file')), true, flags: \JSON_THROW_ON_ERROR);
        $menu = self::menu($data);
        if ($in->getOption('validate') || !$platform instanceof MenuInterface) {
            $violations = (new Validator())->validate($menu, $platform->capabilities());
            foreach ($violations as $violation) {
                $out->writeln('  - '.$violation);
            }
            if (!$violations) {
                $out->writeln('<info>No violation: '.$platform->getName().' would take it'.($platform instanceof MenuInterface ? '' : ', if it took menus').'.</info>');
            }

            return $violations ? Command::FAILURE : Command::SUCCESS;
        }
        $platform->pushMenu($menu);
        $out->writeln('<info>Sent.</info>');

        return Command::SUCCESS;
    }

    private function notify(InputInterface $in, OutputInterface $out): void
    {
        $headers = [];
        foreach ((array) $in->getOption('header') as $header) {
            [$name, $value] = array_map('trim', explode(':', $header, 2) + [1 => '']);
            $headers[$name] = $value;
        }
        $platform = $this->platform($in);
        if (!$platform instanceof NotifiableInterface) {
            throw new InvalidConfigException(\sprintf('"%s" has no webhook.', $platform->getName()));
        }
        $this->print($out, $platform->notify((string) file_get_contents($in->getArgument('body')), $headers));
    }

    /**
     * A menu from its JSON: {name, currency, ref, hours: {"1": [["12:00", "14:30"]]}, categories: [{ref, name,
     * items: [{ref, name, price (minor units), vat, description, allergens, photo, labels, available,
     * groups: [{ref, name, min, max, modifiers: [{ref, name, price}]}]}]}]}.
     *
     * @param array<string, mixed> $m
     */
    private static function menu(array $m): Menu
    {
        $currency = (string) ($m['currency'] ?? 'EUR');
        $price = static fn ($v) => null === $v ? null : Money::of((int) $v, $currency);
        $groups = null;
        $groups = static function (array $list) use (&$groups, $price): array {
            return array_map(static fn (array $g) => new ModifierGroup((string) $g['ref'], (string) $g['name'], array_map(static fn (array $o) => new Modifier(
                (string) $o['ref'], (string) $o['name'], $price($o['price'] ?? null), array_map(static fn ($a) => Allergen::from($a), (array) ($o['allergens'] ?? [])), (bool) ($o['available'] ?? true), $groups((array) ($o['groups'] ?? [])),
            ), (array) ($g['modifiers'] ?? [])), (int) ($g['min'] ?? 0), isset($g['max']) ? (int) $g['max'] : null), $list);
        };

        return new Menu(
            (string) ($m['name'] ?? ''),
            array_map(static fn (array $c) => new Category((string) $c['ref'], (string) $c['name'], array_map(static fn (array $i) => new Item(
                (string) $i['ref'], (string) $i['name'], $price($i['price'] ?? 0), $i['description'] ?? null, isset($i['vat']) ? (float) $i['vat'] : null,
                array_map(static fn ($a) => Allergen::from($a), (array) ($i['allergens'] ?? [])), $i['photo'] ?? null, $groups((array) ($i['groups'] ?? [])),
                (bool) ($i['available'] ?? true), (array) ($i['labels'] ?? []),
            ), (array) ($c['items'] ?? [])), $c['description'] ?? null), (array) ($m['categories'] ?? [])),
            $currency,
            isset($m['hours']) ? new Hours(array_combine(array_map('intval', array_keys($m['hours'])), array_values($m['hours']))) : null,
            $m['ref'] ?? null,
            $m['description'] ?? null,
        );
    }

    private function done(OutputInterface $out, mixed $ignored = null): void
    {
        $out->writeln('<info>Done.</info>');
    }

    private function platform(InputInterface $in): PlatformInterface
    {
        return $this->registry->get($in->getArgument('platform'));
    }

    private function orders(InputInterface $in): OrdersInterface
    {
        $p = $this->platform($in);

        return $p instanceof OrdersInterface ? $p : throw new InvalidConfigException(\sprintf('"%s" takes no orders.', $p->getName()));
    }

    private function store(InputInterface $in): StoreInterface
    {
        $p = $this->platform($in);

        return $p instanceof StoreInterface ? $p : throw new InvalidConfigException(\sprintf('"%s" has no store.', $p->getName()));
    }

    private function reservations(InputInterface $in): ReservationsInterface
    {
        $p = $this->platform($in);

        return $p instanceof ReservationsInterface ? $p : throw new InvalidConfigException(\sprintf('"%s" takes no reservations.', $p->getName()));
    }

    private function refreshable(InputInterface $in): RefreshableInterface
    {
        $p = $this->platform($in);

        return $p instanceof RefreshableInterface ? $p : throw new InvalidConfigException(\sprintf('"%s" has no token to refresh.', $p->getName()));
    }

    private function oauth(InputInterface $in): OAuthInterface
    {
        $p = $this->platform($in);

        return $p instanceof OAuthInterface ? $p : throw new InvalidConfigException(\sprintf('"%s" is not linked through OAuth.', $p->getName()));
    }

    private function print(OutputInterface $out, mixed $result): void
    {
        $out->writeln((string) json_encode(self::plain($result), \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PARTIAL_OUTPUT_ON_ERROR));
    }

    /** Objects as their properties, dates as ISO 8601, enums as their values. */
    private static function plain(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \DateTimeInterface => $value->format(\DateTimeInterface::ATOM),
            $value instanceof \BackedEnum => $value->value,
            \is_object($value) => array_map(self::plain(...), get_object_vars($value)),
            \is_array($value) => array_map(self::plain(...), $value),
            default => $value,
        };
    }
}
