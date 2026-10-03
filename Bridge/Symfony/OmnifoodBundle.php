<?php

namespace Omnifood\Bridge\Symfony;

use Omnifood\Deliveroo\DeliverooPlatformFactory;
use Omnifood\JustEat\JustEatPlatformFactory;
use Omnifood\MenuInterface;
use Omnifood\NotifiableInterface;
use Omnifood\OrdersInterface;
use Omnifood\PlatformFactoryInterface;
use Omnifood\PlatformInterface;
use Omnifood\Registry;
use Omnifood\ReservationsInterface;
use Omnifood\StoreInterface;
use Omnifood\TheFork\TheForkPlatformFactory;
use Omnifood\UberEats\UberEatsPlatformFactory;
use Omnifood\Validator;
use Omnifood\Zenchef\ZenchefPlatformFactory;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

/**
 * Omnifood in a Symfony application: the platform packages installed
 * (omnifood/ubereats, omnifood/deliveroo, omnifood/justeat, omnifood/thefork,
 * omnifood/zenchef) registered, the restaurant's platforms built from
 * configuration, Omnifood\Registry and Omnifood\Validator autowired, and
 * each platform injectable by its name, as what it does:
 *
 *     omnifood:
 *         platforms:
 *             ubereats: { factory: ubereats, options: { client_id: '%env(default::UBEREATS_CLIENT_ID)%', client_secret: '%env(default::UBEREATS_CLIENT_SECRET)%', store_id: '%env(default::UBEREATS_STORE_ID)%' } }
 *             thefork:  { factory: thefork, options: { client_id: '%env(default::THEFORK_CLIENT_ID)%', client_secret: '%env(default::THEFORK_CLIENT_SECRET)%', restaurant_id: '%env(default::THEFORK_RESTAURANT_ID)%' } }
 *
 *     public function __construct(OrdersInterface $ubereats, ReservationsInterface $thefork) {}
 *
 * Nothing is built, nor checked, when the container compiles: a platform is
 * built the first time it is asked for, and a credential left empty only
 * shows when a call needs it (InvalidConfigException). So a site with no
 * keys yet still boots; Registry::has() says a platform is declared.
 *
 * An application's own factories (a PlatformFactoryInterface) are
 * registered too, autoconfigured.
 */
final class OmnifoodBundle extends AbstractBundle
{
    protected string $extensionAlias = 'omnifood';

    /** The platform packages this bundle knows, registered when installed. */
    public const FACTORIES = [
        UberEatsPlatformFactory::class,
        DeliverooPlatformFactory::class,
        JustEatPlatformFactory::class,
        TheForkPlatformFactory::class,
        ZenchefPlatformFactory::class,
    ];

    /** What a platform may be injected as, by its name. */
    public const CAPABILITIES = [
        PlatformInterface::class,
        OrdersInterface::class,
        MenuInterface::class,
        StoreInterface::class,
        ReservationsInterface::class,
        NotifiableInterface::class,
    ];

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('platforms')
                    ->info('The restaurant\'s platforms, by name: a factory (ubereats, deliveroo, justeat, thefork, zenchef...) and its options.')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('factory')->isRequired()->cannotBeEmpty()->end()
                            ->variableNode('options')->defaultValue([])->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /** @param array{platforms: array<string, array{factory: string, options: array<string, mixed>}>} $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->registerForAutoconfiguration(PlatformFactoryInterface::class)->addTag('omnifood.platform_factory');

        $services = $container->services();
        foreach (self::FACTORIES as $factory) {
            if (class_exists($factory) && is_subclass_of($factory, PlatformFactoryInterface::class)) {
                $services->set($factory)->args([service('http_client')->nullOnInvalid()])->tag('omnifood.platform_factory');
            }
        }

        $services->set(Registry::class)
            ->args([tagged_iterator('omnifood.platform_factory'), $config['platforms']])
            ->public();
        $services->set(Validator::class);

        foreach (array_keys($config['platforms']) as $name) {
            $id = 'omnifood.platform.'.$name;
            $services->set($id, PlatformInterface::class)->factory([service(Registry::class), 'get'])->args([$name]);
            // A platform that does not take orders (or reservations) fails where it is injected as one.
            foreach (self::CAPABILITIES as $type) {
                $builder->registerAliasForArgument($id, $type, $name);
            }
        }
    }
}
