<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @see http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class WeblizardsCustomMaintenanceExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter(
            'weblizards_custom_maintenance.notice_template.upcoming',
            $config['notice_templates']['upcoming']
        );
        $container->setParameter(
            'weblizards_custom_maintenance.notice_template.current',
            $config['notice_templates']['current']
        );
        $container->setParameter(
            'weblizards_custom_maintenance.hard_fallback_target.maintenance',
            $config['hard_fallback_targets']['maintenance']
        );
        $container->setParameter(
            'weblizards_custom_maintenance.hard_fallback_target.error',
            $config['hard_fallback_targets']['error']
        );
        $container->setParameter(
            'weblizards_custom_maintenance.hard_fallback_runtime.directory',
            $config['hard_fallback_runtime']['directory']
        );
        $container->setParameter(
            'weblizards_custom_maintenance.hard_fallback_runtime.allowed_ips',
            $config['hard_fallback_runtime']['allowed_ips']
        );
        $container->setParameter('weblizards_custommaintenance.config', $config);
    }
}
