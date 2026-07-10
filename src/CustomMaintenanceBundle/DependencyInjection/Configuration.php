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
            ->end();

        return $treeBuilder;
    }
}
