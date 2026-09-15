<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle\Tests\Functional;

use Mleczakm\MonologContextBundle\MonologContextBundle;
use Mleczakm\MonologContextBundle\Processor\BrowserProcessor;
use Mleczakm\MonologContextBundle\Processor\GeoLocationProcessor;
use Mleczakm\MonologContextBundle\Processor\RequestProcessor;
use Mleczakm\MonologContextBundle\Processor\TagProcessor;
use Mleczakm\MonologContextBundle\Processor\UserProcessor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class BundleWiringTest extends TestCase
{
    public function testUserAndRequestProcessorsAreRegisteredByDefault(): void
    {
        $builder = $this->loadContainer([]);

        self::assertTrue($builder->hasDefinition(UserProcessor::class));
        self::assertTrue($builder->hasDefinition(RequestProcessor::class));
        self::assertFalse($builder->hasDefinition(BrowserProcessor::class));
        self::assertFalse($builder->hasDefinition(GeoLocationProcessor::class));
        self::assertFalse($builder->hasDefinition(TagProcessor::class));
    }

    public function testUserProcessorCanBeDisabled(): void
    {
        $builder = $this->loadContainer(['user' => false]);

        self::assertFalse($builder->hasDefinition(UserProcessor::class));
    }

    public function testBrowserProcessorCanBeEnabled(): void
    {
        $builder = $this->loadContainer(['browser' => ['enabled' => true, 'parser' => 'native']]);

        self::assertTrue($builder->hasDefinition(BrowserProcessor::class));
    }

    public function testTagsProcessorIsRegisteredWhenTagsAreConfigured(): void
    {
        $builder = $this->loadContainer(['tags' => ['app_version' => '1.0.0']]);

        self::assertTrue($builder->hasDefinition(TagProcessor::class));
        self::assertSame(['app_version' => '1.0.0'], $builder->getDefinition(TagProcessor::class)->getArgument('$tags'));
    }

    public function testGeoLocationRequiresADatabasePathForTheDefaultResolver(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->loadContainer(['geo_location' => ['enabled' => true]]);
    }

    /**
     * Registering definitions with hasDefinition() alone doesn't catch autowiring
     * mistakes (e.g. a missing interface alias) - only a full compile does.
     */
    public function testDefaultConfigurationCompilesWithoutErrors(): void
    {
        $builder = $this->loadContainer([]);
        $builder->register('request_stack', RequestStack::class)->setPublic(true);
        $builder->register(TokenStorageInterface::class, TokenStorage::class)->setPublic(true);

        $builder->compile();

        // The service isn't public and nothing here consumes the "monolog.processor"
        // tag, so it's compiled away - proof the container reached that point without
        // an autowiring exception is that it's listed as a removed, not missing, id.
        self::assertArrayHasKey(RequestProcessor::class, $builder->getRemovedIds());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function loadContainer(array $config): ContainerBuilder
    {
        $builder = new ContainerBuilder();
        $bundle = new MonologContextBundle();
        $extension = $bundle->getContainerExtension();
        self::assertNotNull($extension);

        $builder->registerExtension($extension);
        $extension->load([$config], $builder);

        return $builder;
    }
}
