<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Weblizards\CustomMaintenanceBundle\Tools\Installer;

class WeblizardsCustomMaintenanceBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;

    public function getNiceName(): string
    {
        return 'Custom Maintenance Bundle';
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/weblizardscustommaintenance/js/pimcore/startup.js',
            '/bundles/weblizardscustommaintenance/js/pimcore/AdminPanel.js',
        ];
    }

    public function getCssPaths():array
    {
        return [
            '/bundles/weblizardscustommaintenance/css/backend.css',
        ];
    }

    public function getInstaller(): ?InstallerInterface
    {
        return $this->container->get(Installer::class);
    }

    public function getDescription(): string
    {
        return 'Schedule custom maintenance times and display them to the customer';
    }

    public function getVersion(): string
    {
        return '0.9.9';
    }
}