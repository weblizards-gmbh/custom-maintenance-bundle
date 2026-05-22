<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Tools;

use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Model\Translation;
use Pimcore\Tool;

class Installer extends AbstractInstaller implements InstallerInterface
{
    private array $files = [
        'settings' => [
            'source' => __DIR__ . '/../Resources/install/custommaintenance.php',
            'target' => PIMCORE_PRIVATE_VAR . '/config/custommaintenance.php',
        ],
    ];

    public function canBeInstalled(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeUninstalled(): bool
    {
        return $this->isInstalled();
    }

    public function isInstalled(): bool
    {
        foreach ($this->files as $file) {
            if (!file_exists($file['target'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws \Exception
     */
    public function install(): void
    {
        parent::install();
        $this->installAdminTranslations();
        $this->installSharedTranslations();
        $this->installFiles();
    }

    /**
     * @throws \Exception
     */
    public function installAdminTranslations(): void
    {
        $csv = __DIR__ . '/../Resources/install/admin_translations.csv';
        if (file_exists($csv)) {
            Translation::importTranslationsFromFile($csv, Translation::DOMAIN_ADMIN, true, Tool\Admin::getLanguages());
        }
    }

    /**
     * @throws \Exception
     */
    public function installSharedTranslations(): void
    {
        $csv = __DIR__ . '/../Resources/install/shared_translations.csv';
        if (file_exists($csv)) {
            Translation::importTranslationsFromFile($csv, Translation::DOMAIN_DEFAULT, true, Tool\Admin::getLanguages());
        }
    }

    public function installFiles(): void
    {
        foreach ($this->files as $file) {
            $target = $file['target'];
            $source = $file['source'];
            if (!file_exists($target)) {
                copy($source, $target);
            }
        }
    }

    public function needsReloadAfterInstall(): bool
    {
        return true;
    }
}
