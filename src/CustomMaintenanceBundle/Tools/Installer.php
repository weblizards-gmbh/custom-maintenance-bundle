<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Tools;

use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Model\Translation;
use Pimcore\Model\Tool\SettingsStore;
use Pimcore\Tool;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\SettingsStorePersistenceAdapter;

class Installer extends AbstractInstaller implements InstallerInterface
{
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
        return $this->hasSharedTranslations();
    }

    /**
     * @throws \Exception
     */
    public function install(): void
    {
        parent::install();
        $this->installSharedTranslations();
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

    protected function hasSharedTranslations(): bool
    {
        try {
            return Translation::getByKey('custommaintenance.fulltimeformat') !== null;
        } catch (\Exception $exception) {
            return false;
        }
    }

    protected function fileExists(string $path): bool
    {
        return file_exists($path);
    }

    public function needsReloadAfterInstall(): bool
    {
        return false;
    }
}
