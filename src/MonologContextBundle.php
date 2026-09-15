<?php

declare(strict_types=1);

namespace Mleczakm\MonologContextBundle;

use Mleczakm\MonologContextBundle\Ip\MaxMindGeoLocationResolver;
use Mleczakm\MonologContextBundle\Processor\BrowserProcessor;
use Mleczakm\MonologContextBundle\Processor\GeoLocationProcessor;
use Mleczakm\MonologContextBundle\Processor\RequestProcessor;
use Mleczakm\MonologContextBundle\Processor\TagProcessor;
use Mleczakm\MonologContextBundle\Processor\UserProcessor;
use Mleczakm\MonologContextBundle\RequestId\RequestIdListener;
use Mleczakm\MonologContextBundle\RequestId\RequestIdStorage;
use Mleczakm\MonologContextBundle\RequestId\UuidRequestIdGenerator;
use Mleczakm\MonologContextBundle\UserAgent\CachedParser;
use Mleczakm\MonologContextBundle\UserAgent\NativeParser;
use Mleczakm\MonologContextBundle\UserAgent\PhpUserAgentParser;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class MonologContextBundle extends AbstractBundle
{
    protected string $extensionAlias = 'monolog_context';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('user')
                    ->info('Appends the authenticated user (identifier, roles, email) from Symfony Security')
                    ->canBeDisabled()
                ->end()
                ->arrayNode('request')
                    ->info('Appends request id, method, URI, route and client IP')
                    ->canBeDisabled()
                ->end()
                ->arrayNode('browser')
                    ->info('Parses the User-Agent header into browser name/version/platform')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('parser')
                            ->info('"native", "phpuseragent" or a service id implementing ParserInterface')
                            ->defaultValue('phpuseragent')
                        ->end()
                        ->scalarNode('cache')
                            ->info('Service id implementing Psr\SimpleCache\CacheInterface, used to cache parsed user agents')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('geo_location')
                    ->info('Resolves the client IP to a country/city')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('resolver')
                            ->info('Service id implementing GeoLocationResolverInterface; defaults to the built-in MaxMind resolver')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('database_path')
                            ->info('Path to a MaxMind GeoLite2/GeoIP2 .mmdb database, required when using the default resolver')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('tags')
                    ->info('Static key => value tags appended to every log record\'s "extra.tags"')
                    ->normalizeKeys(false)
                    ->scalarPrototype()->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ($config['user']['enabled']) {
            $builder->register(UserProcessor::class, UserProcessor::class)
                ->setAutowired(true)
                ->addTag('monolog.processor')
            ;
        }

        if ($config['request']['enabled']) {
            $builder->register(UuidRequestIdGenerator::class, UuidRequestIdGenerator::class);

            $builder->register(RequestIdStorage::class, RequestIdStorage::class)
                ->setAutowired(true)
            ;

            $builder->register(RequestIdListener::class, RequestIdListener::class)
                ->setAutowired(true)
                ->addTag('kernel.event_subscriber')
            ;

            $builder->register(RequestProcessor::class, RequestProcessor::class)
                ->setAutowired(true)
                ->addTag('monolog.processor')
            ;
        }

        if ($config['browser']['enabled']) {
            $parserId = match ($config['browser']['parser']) {
                'native' => NativeParser::class,
                'phpuseragent' => PhpUserAgentParser::class,
                default => $config['browser']['parser'],
            };

            if ($parserId === NativeParser::class || $parserId === PhpUserAgentParser::class) {
                $builder->register($parserId, $parserId);
            }

            if ($config['browser']['cache'] !== null) {
                $builder->register(CachedParser::class, CachedParser::class)
                    ->setArgument('$cache', new Reference($config['browser']['cache']))
                    ->setArgument('$parser', new Reference($parserId))
                ;

                $parserId = CachedParser::class;
            }

            $builder->register(BrowserProcessor::class, BrowserProcessor::class)
                ->setAutowired(true)
                ->setArgument('$parser', new Reference($parserId))
                ->addTag('monolog.processor')
            ;
        }

        if ($config['geo_location']['enabled']) {
            $resolverId = $config['geo_location']['resolver'] ?? MaxMindGeoLocationResolver::class;

            if ($resolverId === MaxMindGeoLocationResolver::class) {
                if ($config['geo_location']['database_path'] === null) {
                    throw new \InvalidArgumentException(
                        '"monolog_context.geo_location.database_path" must be set when using the default MaxMind '
                        . 'resolver, or configure "monolog_context.geo_location.resolver" with your own service id.',
                    );
                }

                $builder->register(MaxMindGeoLocationResolver::class, MaxMindGeoLocationResolver::class)
                    ->setArgument('$databasePath', $config['geo_location']['database_path'])
                ;
            }

            $builder->register(GeoLocationProcessor::class, GeoLocationProcessor::class)
                ->setAutowired(true)
                ->setArgument('$resolver', new Reference($resolverId))
                ->addTag('monolog.processor')
            ;
        }

        if ($config['tags'] !== []) {
            $builder->register(TagProcessor::class, TagProcessor::class)
                ->setArgument('$tags', $config['tags'])
                ->addTag('monolog.processor')
            ;
        }
    }
}
