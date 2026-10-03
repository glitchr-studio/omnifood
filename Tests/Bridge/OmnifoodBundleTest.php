<?php

namespace Omnifood\Tests\Bridge;

use Omnifood\Bridge\Symfony\OmnifoodBundle;
use Omnifood\Exception\InvalidConfigException;
use Omnifood\MenuInterface;
use Omnifood\OrdersInterface;
use Omnifood\PlatformInterface;
use Omnifood\Registry;
use Omnifood\ReservationsInterface;
use Omnifood\Tests\StubBookFactory;
use Omnifood\Tests\StubFactory;
use Omnifood\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpKernel\Kernel;

final class OmnifoodBundleTest extends TestCase
{
    private ?OmnifoodTestKernel $kernel = null;

    protected function tearDown(): void
    {
        if (null !== $this->kernel) {
            $dir = $this->kernel->getProjectDir();
            $this->kernel->shutdown();
            $this->kernel = null;
            self::remove($dir);
        }
        restore_exception_handler();
    }

    public function testTheKernelBootsAndThePlatformsAreInjectableByNameAsWhatTheyDo(): void
    {
        $container = $this->boot([]);

        $registry = $container->get(Registry::class);
        self::assertSame(['kitchen', 'tables'], $registry->names());

        $site = $container->get(Site::class);
        self::assertSame($registry, $site->registry, 'the registry autowired');
        self::assertInstanceOf(Validator::class, $site->validator);
        self::assertSame($registry->get('kitchen'), $site->kitchen, 'injected by its name, as what it does');
        self::assertSame($registry->get('kitchen'), $site->menu);
        self::assertSame($registry->get('tables'), $site->tables);
        $site->kitchen->accept('42');
        self::assertSame(['42' => 'accepted'], $registry->get('kitchen')->done);
        self::assertSame($registry->get('tables'), $container->get(Wall::class)->tables, 'and as a platform');
    }

    public function testThePackagesInstalledAreRegisteredAndAPlatformWithNoKeysBreaksNothing(): void
    {
        $installed = array_values(array_filter(OmnifoodBundle::FACTORIES, 'class_exists'));
        if (!$installed) {
            self::markTestSkipped('No omnifood/* platform package is installed.');
        }
        $names = ['Omnifood\UberEats\UberEatsPlatformFactory' => 'ubereats', 'Omnifood\Deliveroo\DeliverooPlatformFactory' => 'deliveroo', 'Omnifood\JustEat\JustEatPlatformFactory' => 'justeat', 'Omnifood\TheFork\TheForkPlatformFactory' => 'thefork', 'Omnifood\Zenchef\ZenchefPlatformFactory' => 'zenchef'];
        $platforms = [];
        foreach ($installed as $class) {
            // No variable set in the environment: every option empty.
            $platforms[$names[$class]] = ['factory' => $names[$class], 'options' => ['client_id' => '%env(default::NOT_SET)%']];
        }
        $container = $this->boot($platforms);

        $registry = $container->get(Registry::class);
        $factories = $registry->factories();
        sort($factories);
        $expected = array_merge(array_values(array_map(static fn (string $c) => $names[$c], $installed)), ['book', 'stub']);
        sort($expected);
        self::assertSame($expected, $factories, 'the packages installed, and the application\'s own');
        foreach (array_keys($platforms) as $name) {
            $platform = $registry->get($name);
            self::assertInstanceOf(PlatformInterface::class, $platform);
            self::assertSame($name, $platform->getName());
            self::assertNotNull($platform->capabilities(), 'what needs no key works');
        }
        if (isset($platforms['ubereats'])) {
            $this->expectException(InvalidConfigException::class);
            $registry->get('ubereats')->order('abc');
        }
    }

    /** @param array<string, array{factory: string, options?: array<string, mixed>}> $platforms */
    private function boot(array $platforms): ContainerInterface
    {
        $platforms += ['kitchen' => ['factory' => 'stub', 'options' => ['key' => 'k']], 'tables' => ['factory' => 'book']];
        $this->kernel = new OmnifoodTestKernel($platforms);
        $this->kernel->boot();

        return $this->kernel->getContainer();
    }

    private static function remove(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
}

/** An application of one bundle, its configuration as the YAML would give it. */
final class OmnifoodTestKernel extends Kernel
{
    private readonly string $dir;

    /** @param array<string, array{factory: string, options?: array<string, mixed>}> $platforms */
    public function __construct(private readonly array $platforms)
    {
        $this->dir = sys_get_temp_dir().'/omnifood-'.bin2hex(random_bytes(6));
        parent::__construct('test', true);
    }

    public function registerBundles(): iterable
    {
        return [new OmnifoodBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('omnifood', ['platforms' => $this->platforms]);
            $container->register('http_client', MockHttpClient::class);
            // An application's own platforms, autoconfigured.
            $container->register(StubFactory::class)->setAutoconfigured(true);
            $container->register(StubBookFactory::class)->setAutoconfigured(true);
            $container->register(Site::class)->setAutowired(true)->setPublic(true);
            $container->register(Wall::class)->setAutowired(true)->setPublic(true);
        });
    }

    public function getProjectDir(): string
    {
        return $this->dir;
    }
}

final class Site
{
    public function __construct(
        public readonly Registry $registry,
        public readonly Validator $validator,
        public readonly OrdersInterface $kitchen,
        #[\Symfony\Component\DependencyInjection\Attribute\Target('kitchen')]
        public readonly MenuInterface $menu,
        public readonly ReservationsInterface $tables,
    ) {
    }
}

final class Wall
{
    public function __construct(public readonly PlatformInterface $tables)
    {
    }
}
