<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    private const DEFAULT_UPCOMING_NOTICE_TEMPLATE = '@WeblizardsCustomMaintenance/partials/indicateupcoming.html.twig';

    private const DEFAULT_CURRENT_NOTICE_TEMPLATE = '@WeblizardsCustomMaintenance/partials/indicatecurrent.html.twig';

    private const DEFAULT_MAINTENANCE_EXPORT_TARGET = '%kernel.project_dir%/public/_maintenance/maintenance.html';

    private const DEFAULT_ERROR_EXPORT_TARGET = '%kernel.project_dir%/public/_maintenance/error.html';

    private const DEFAULT_RUNTIME_DIRECTORY = '%kernel.project_dir%/public/_maintenance/runtime';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('weblizards_custom_maintenance');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('notice_templates')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('upcoming')
                            ->cannotBeEmpty()
                            ->defaultValue(self::DEFAULT_UPCOMING_NOTICE_TEMPLATE)
                        ->end()
                        ->scalarNode('current')
                            ->cannotBeEmpty()
                            ->defaultValue(self::DEFAULT_CURRENT_NOTICE_TEMPLATE)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('hard_fallback_targets')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('maintenance')
                            ->cannotBeEmpty()
                            ->defaultValue(self::DEFAULT_MAINTENANCE_EXPORT_TARGET)
                        ->end()
                        ->scalarNode('error')
                            ->cannotBeEmpty()
                            ->defaultValue(self::DEFAULT_ERROR_EXPORT_TARGET)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('hard_fallback_runtime')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('directory')
                            ->cannotBeEmpty()
                            ->defaultValue(self::DEFAULT_RUNTIME_DIRECTORY)
                        ->end()
                        ->arrayNode('allowed_ips')
                            ->scalarPrototype()->end()
                            ->defaultValue([])
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
