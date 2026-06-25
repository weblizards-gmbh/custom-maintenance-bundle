<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Yaml\Yaml;
use Weblizards\CustomMaintenanceBundle\DependencyInjection\Configuration;
use Weblizards\CustomMaintenanceBundle\DependencyInjection\WeblizardsCustomMaintenanceExtension;

final class ConfigurationTest extends TestCase
{
    public function testConfigTreeBuilderUsesBundleSpecificRootName(): void
    {
        $builder = (new Configuration())->getConfigTreeBuilder();

        self::assertInstanceOf(TreeBuilder::class, $builder);
        self::assertSame('weblizards_custom_maintenance', $builder->buildTree()->getName());
    }

    public function testExtensionLoadsBundleServicesAndStoresResolvedConfigParameter(): void
    {
        $container = new ContainerBuilder();
        $extension = new WeblizardsCustomMaintenanceExtension();

        $extension->load([], $container);

        self::assertTrue($container->hasDefinition('Weblizards\\CustomMaintenanceBundle\\Config'));
        self::assertTrue($container->hasDefinition('Weblizards\\CustomMaintenanceBundle\\Service\\StatusService'));
        self::assertTrue($container->getDefinition('Weblizards\\CustomMaintenanceBundle\\Service\\StatusService')->isPublic());
        self::assertTrue($container->hasDefinition('Weblizards\\CustomMaintenanceBundle\\Twig\\Extensions'));
        self::assertTrue($container->getDefinition('Weblizards\\CustomMaintenanceBundle\\Twig\\Extensions')->hasTag('twig.extension'));
        self::assertTrue($container->hasParameter('weblizards_custommaintenance.config'));
        self::assertSame([], $container->getParameter('weblizards_custommaintenance.config'));
    }

    public function testRoutingFileKeepsExpectedImportKeyAndAdminPrefix(): void
    {
        $routing = Yaml::parseFile(__DIR__ . '/../../../src/CustomMaintenanceBundle/Resources/config/pimcore/routing.yml');

        self::assertArrayHasKey('weblizards_custom_maintenance', $routing);
        self::assertSame(
            '@WeblizardsCustomMaintenanceBundle/Controller/',
            $routing['weblizards_custom_maintenance']['resource']
        );
        self::assertSame('annotation', $routing['weblizards_custom_maintenance']['type']);
        self::assertSame(
            '/admin/weblizards_custom_maintenance',
            $routing['weblizards_custom_maintenance']['prefix']
        );
    }
}
