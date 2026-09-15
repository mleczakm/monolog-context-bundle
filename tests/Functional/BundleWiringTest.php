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
