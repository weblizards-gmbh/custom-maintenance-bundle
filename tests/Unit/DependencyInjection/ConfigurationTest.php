<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
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

    public function testConfigurationProvidesDefaultNoticeTemplates(): void
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [[]]);

        self::assertSame(
            '@WeblizardsCustomMaintenance/partials/indicateupcoming.html.twig',
            $config['notice_templates']['upcoming']
        );
        self::assertSame(
            '@WeblizardsCustomMaintenance/partials/indicatecurrent.html.twig',
            $config['notice_templates']['current']
        );
    }

    public function testExtensionLoadsBundleServicesAndStoresResolvedConfigParameter(): void
    {
        $container = new ContainerBuilder();
        $extension = new WeblizardsCustomMaintenanceExtension();

        $extension->load([], $container);

        self::assertTrue($container->hasDefinition('Weblizards\\CustomMaintenanceBundle\\Config'));
        self::assertTrue($container->hasDefinition('Weblizards\\CustomMaintenanceBundle\\Service\\StatusService'));
        $statusServiceDefinition = $container->getDefinition('Weblizards\\CustomMaintenanceBundle\\Service\\StatusService');
        self::assertTrue($statusServiceDefinition->isPublic());
        self::assertTrue($container->hasParameter('weblizards_custom_maintenance.notice_template.upcoming'));
        self::assertTrue($container->hasParameter('weblizards_custom_maintenance.notice_template.current'));
        self::assertSame(
            '@WeblizardsCustomMaintenance/partials/indicateupcoming.html.twig',
            $container->getParameter('weblizards_custom_maintenance.notice_template.upcoming')
        );
        self::assertSame(
            '@WeblizardsCustomMaintenance/partials/indicatecurrent.html.twig',
            $container->getParameter('weblizards_custom_maintenance.notice_template.current')
        );
        self::assertSame(
            '%weblizards_custom_maintenance.notice_template.upcoming%',
            (string) $statusServiceDefinition->getArgument('$upcomingNoticeTemplate')
        );
        self::assertSame(
            '%weblizards_custom_maintenance.notice_template.current%',
            (string) $statusServiceDefinition->getArgument('$currentNoticeTemplate')
        );
        self::assertTrue($container->hasDefinition('Weblizards\\CustomMaintenanceBundle\\Twig\\Extensions'));
        self::assertTrue($container->getDefinition('Weblizards\\CustomMaintenanceBundle\\Twig\\Extensions')->hasTag('twig.extension'));
        self::assertTrue($container->hasParameter('weblizards_custommaintenance.config'));
        self::assertSame(
            [
                'notice_templates' => [
                    'upcoming' => '@WeblizardsCustomMaintenance/partials/indicateupcoming.html.twig',
                    'current' => '@WeblizardsCustomMaintenance/partials/indicatecurrent.html.twig',
                ],
            ],
            $container->getParameter('weblizards_custommaintenance.config')
        );
    }

    public function testExtensionAppliesConfiguredNoticeTemplates(): void
    {
        $container = new ContainerBuilder();
        $extension = new WeblizardsCustomMaintenanceExtension();

        $extension->load([
            [
                'notice_templates' => [
                    'upcoming' => '@App/custom/upcoming.html.twig',
                    'current' => '@App/custom/current.html.twig',
                ],
            ],
        ], $container);

        self::assertSame(
            '@App/custom/upcoming.html.twig',
            $container->getParameter('weblizards_custom_maintenance.notice_template.upcoming')
        );
        self::assertSame(
            '@App/custom/current.html.twig',
            $container->getParameter('weblizards_custom_maintenance.notice_template.current')
        );
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
