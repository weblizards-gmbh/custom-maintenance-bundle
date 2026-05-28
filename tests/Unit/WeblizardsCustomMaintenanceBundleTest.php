<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit;

use PHPUnit\Framework\TestCase;
use Weblizards\CustomMaintenanceBundle\WeblizardsCustomMaintenanceBundle;

final class WeblizardsCustomMaintenanceBundleTest extends TestCase
{
    public function testBundleProvidesStableMetadataAndAdminAssets(): void
    {
        $bundle = new WeblizardsCustomMaintenanceBundle();

        self::assertSame('Custom Maintenance Bundle', $bundle->getNiceName());
        self::assertSame('Schedule custom maintenance times and display them to the customer', $bundle->getDescription());
        self::assertSame('0.9.9', $bundle->getVersion());
        self::assertSame(
            [
                '/bundles/weblizardscustommaintenance/js/pimcore/startup.js',
                '/bundles/weblizardscustommaintenance/js/pimcore/AdminPanel.js',
            ],
            $bundle->getJsPaths()
        );
        self::assertSame(
            ['/bundles/weblizardscustommaintenance/css/backend.css'],
            $bundle->getCssPaths()
        );
    }
}
