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
        if ($this->hasSettingsStoreConfig()) {
            return true;
        }

        foreach ($this->files as $file) {
            if (!$this->fileExists($file['target'])) {
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
            if (!$this->fileExists($target)) {
                copy($source, $target);
            }
        }
    }

    protected function hasSettingsStoreConfig(): bool
    {
        return SettingsStore::get(SettingsStorePersistenceAdapter::KEY, SettingsStorePersistenceAdapter::SCOPE) !== null;
    }

    protected function fileExists(string $path): bool
    {
        return file_exists($path);
    }

    public function needsReloadAfterInstall(): bool
    {
        return true;
    }
}
