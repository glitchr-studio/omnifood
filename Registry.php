<?php

namespace Omnifood;

use Omnifood\Exception\InvalidConfigException;

/**
 * The platforms by name, each built once from its factory and options:
 *
 *   new Registry([new UberEatsPlatformFactory($http)], [
 *       'ubereats' => ['factory' => 'ubereats', 'options' => ['client_id' => '...', 'client_secret' => '...', 'store_id' => '...']],
 *   ]);
 */
final class Registry
{
    /** @var array<string, PlatformFactoryInterface> */
    private array $factories = [];

    /** @var array<string, PlatformInterface> */
    private array $platforms = [];

    /**
     * @param iterable<PlatformFactoryInterface>                                     $factories
     * @param array<string, array{factory: string, options?: array<string, mixed>}> $config
     */
    public function __construct(iterable $factories, private readonly array $config)
    {
        foreach ($factories as $factory) {
            $this->factories[$factory->getName()] = $factory;
        }
    }

    public function get(string $name): PlatformInterface
    {
        return $this->platforms[$name] ??= $this->factory($name)->create($this->config[$name]['options'] ?? []);
    }

    /**
     * A platform built afresh, its configured options with $overrides over
     * them - keys typed in a back office, a token kept in a database. Not
     * kept: get() still gives the configured one.
     *
     * @param array<string, mixed> $overrides
     */
    public function create(string $name, array $overrides = []): PlatformInterface
    {
        return $this->factory($name)->create(array_replace($this->config[$name]['options'] ?? [], $overrides));
    }

    public function has(string $name): bool
    {
        return isset($this->config[$name]);
    }

    /** @return array<string, mixed> */
    public function options(string $name): array
    {
        return $this->config[$name]['options'] ?? [];
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->config);
    }

    /** @return array<string, PlatformInterface> */
    public function all(): array
    {
        $all = [];
        foreach (array_keys($this->config) as $name) {
            $all[$name] = $this->get($name);
        }

        return $all;
    }

    /** @return list<OrdersInterface> */
    public function orders(): array
    {
        return $this->having(OrdersInterface::class);
    }

    /** @return list<MenuInterface> */
    public function menus(): array
    {
        return $this->having(MenuInterface::class);
    }

    /** @return list<StoreInterface> */
    public function stores(): array
    {
        return $this->having(StoreInterface::class);
    }

    /** @return list<ReservationsInterface> */
    public function reservations(): array
    {
        return $this->having(ReservationsInterface::class);
    }

    /** @return list<NotifiableInterface> */
    public function notifiables(): array
    {
        return $this->having(NotifiableInterface::class);
    }

    /** @return list<string> the factories installed */
    public function factories(): array
    {
        return array_keys($this->factories);
    }

    private function factory(string $name): PlatformFactoryInterface
    {
        $platform = $this->config[$name] ?? throw new InvalidConfigException(\sprintf('No "%s" platform; configured: %s.', $name, implode(', ', array_keys($this->config)) ?: 'none'));

        return $this->factories[$platform['factory']] ?? throw new InvalidConfigException(\sprintf('No "%s" factory for the "%s" platform; installed: %s.', $platform['factory'], $name, implode(', ', array_keys($this->factories)) ?: 'none'));
    }

    /**
     * @template T of PlatformInterface
     *
     * @param class-string<T> $interface
     *
     * @return list<T>
     */
    private function having(string $interface): array
    {
        return array_values(array_filter($this->all(), static fn (PlatformInterface $p) => $p instanceof $interface));
    }
}
