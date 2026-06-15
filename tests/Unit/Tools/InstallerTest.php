<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Tools;

use PHPUnit\Framework\TestCase;
use Weblizards\CustomMaintenanceBundle\Tools\Installer;

final class InstallerTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!\defined('PIMCORE_PRIVATE_VAR')) {
            \define('PIMCORE_PRIVATE_VAR', '/tmp');
        }
    }

    public function testIsInstalledReturnsTrueWhenSettingsStoreConfigExists(): void
    {
        $installer = new class (true, false) extends Installer {
            public function __construct(
                private bool $settingsStoreConfigExists,
                private bool $legacyFileExists
            )
            {
            }

            protected function hasSettingsStoreConfig(): bool
            {
                return $this->settingsStoreConfigExists;
            }

            protected function fileExists(string $path): bool
            {
                return $this->legacyFileExists;
            }
        };

        self::assertTrue($installer->isInstalled());
    }

    public function testIsInstalledReturnsTrueWhenLegacyFileExists(): void
    {
        $installer = new class (false, true) extends Installer {
            public function __construct(
                private bool $settingsStoreConfigExists,
                private bool $legacyFileExists
            )
            {
            }

            protected function hasSettingsStoreConfig(): bool
            {
                return $this->settingsStoreConfigExists;
            }

            protected function fileExists(string $path): bool
            {
                return $this->legacyFileExists;
            }
        };

        self::assertTrue($installer->isInstalled());
    }

    public function testIsInstalledReturnsFalseWhenNoPersistenceSourceExists(): void
    {
        $installer = new class (false, false) extends Installer {
            public function __construct(
                private bool $settingsStoreConfigExists,
                private bool $legacyFileExists
            )
            {
            }

            protected function hasSettingsStoreConfig(): bool
            {
                return $this->settingsStoreConfigExists;
            }

            protected function fileExists(string $path): bool
            {
                return $this->legacyFileExists;
            }
        };

        self::assertFalse($installer->isInstalled());
    }
}
