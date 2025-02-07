<?php

declare(strict_types=1);

namespace Pheature\Community\Symfony\DependencyInjection;

use Doctrine\DBAL\Connection;
use Pheature\Core\Toggle\Read\ChainToggleStrategyFactory;
use Pheature\Core\Toggle\Read\FeatureFinder;
use Pheature\Core\Toggle\Write\FeatureRepository;
use Pheature\Crud\Psr11\Toggle\FeatureFinderFactory;
use Pheature\Crud\Psr11\Toggle\FeatureRepositoryFactory;
use Pheature\Crud\Psr11\Toggle\ToggleConfig;
use Pheature\InMemory\Toggle\InMemoryFeatureFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Webmozart\Assert\Assert;

final class FeatureFinderFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /** @var array<array<mixed>> $pheatureFlagsConfig */
        $pheatureFlagsConfig = $container->getExtensionConfig('pheature_flags');
        $mergedConfig = array_replace(...$pheatureFlagsConfig);

        $finder = $container->register(FeatureFinder::class, FeatureFinder::class)
            ->setAutowired(false)
            ->setLazy(false)
            ->setFactory([FeatureFinderFactory::class, 'create'])
            ->addArgument(new Reference(ToggleConfig::class))
            ->addArgument(new Reference(ChainToggleStrategyFactory::class));

        Assert::keyExists($mergedConfig, 'driver');
        Assert::string($mergedConfig['driver']);
        Assert::keyExists($mergedConfig, 'driver_options');
        Assert::isArray($mergedConfig['driver_options']);

        if (
            ToggleConfig::DRIVER_DBAL === $mergedConfig['driver']
            || true === in_array(ToggleConfig::DRIVER_DBAL, $mergedConfig['driver_options'], true)
        ) {
            $finder->addArgument(new Reference(Connection::class));
        } else {
            $finder->addArgument(null);
        }

        if (
            ToggleConfig::DRIVER_IN_MEMORY === $mergedConfig['driver']
            || true === in_array(ToggleConfig::DRIVER_IN_MEMORY, $mergedConfig['driver_options'], true)
        ) {
            $container->register(InMemoryFeatureFactory::class, InMemoryFeatureFactory::class)
                ->setAutowired(false)
                ->setLazy(true)
                ->addArgument(new Reference(ChainToggleStrategyFactory::class));
        }
    }
}
