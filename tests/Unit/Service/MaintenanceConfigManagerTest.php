<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Service;

use PHPUnit\Framework\TestCase;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\ConfigPersistenceInterface;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\LegacyConfigLoaderInterface;
use Weblizards\CustomMaintenanceBundle\Service\MaintenanceConfigManager;

final class MaintenanceConfigManagerTest extends TestCase
{
    public function testGetConfigSetPrefersSettingsStoreData(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [
                    'indication_upcoming' => [
                        'de' => 'Store upcoming',
                    ],
                ],
                'pimcore' => [
                    'show_info' => 'always',
                    'show_info_from' => ['date' => '01.01.2026', 'time' => '00:00'],
                    'planned' => [
                        'from' => ['date' => '02.01.2026', 'time' => '08:00'],
                        'to' => ['date' => '02.01.2026', 'time' => '12:00'],
                    ],
                    'document' => '/de/pimcore',
                ],
                'custom' => [
                    'prices' => [
                        'active' => 'true',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'automatic',
                        'show_info_from' => ['date' => '01.01.2026', 'time' => '10:00'],
                        'planned' => [
                            'from' => ['date' => '03.01.2026', 'time' => '10:00'],
                            'to' => ['date' => '03.01.2026', 'time' => '12:00'],
                        ],
                        'document' => '/de/erp',
                    ],
                ],
            ]);
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $configSet = $manager->getConfigSet();

        self::assertSame(['prices'], $configSet->getCustomTokens());
        self::assertSame(['pimcore', 'prices'], $configSet->getAllTokens());
        self::assertSame('ERP', $configSet->getEntry('prices')->getDescription());
        self::assertTrue($configSet->getEntry('prices')->isMarkedActive());
        self::assertSame('/de/erp', $configSet->getEntry('prices')->getNoticeConfig()->getDocument());
        self::assertSame(
            'Store upcoming',
            $configSet->getFrontendConfig()->getUpcomingMessage('de')
        );
    }

    public function testGetConfigSetFallsBackToLegacyDataWhenSettingsStoreIsEmpty(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn(null);

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'custom' => [
                    'prices' => [
                        'active' => 'true',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'automatic',
                        'show_info_from' => ['date' => '01.01.2026', 'time' => '10:00'],
                        'planned' => [
                            'from' => ['date' => '03.01.2026', 'time' => '10:00'],
                            'to' => ['date' => '03.01.2026', 'time' => '12:00'],
                        ],
                        'document' => '/de/erp',
                    ],
                ],
            ]);

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);

        self::assertSame(['prices'], $manager->getConfigSet()->getCustomTokens());
        self::assertSame('/de/erp', $manager->getConfigSet()->getEntry('prices')->getNoticeConfig()->getDocument());
    }

    public function testGetConfigSetBuildsDefaultConfigWhenNoPersistenceSourceExists(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn(null);

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader
            ->expects(self::once())
            ->method('load')
            ->willReturn(null);

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $configSet = $manager->getConfigSet();

        self::assertSame([], $configSet->getCustomTokens());
        self::assertSame('never', $configSet->getPimcoreEntry()->getNoticeConfig()->getShowInfo());
        self::assertSame('', $configSet->getPimcoreEntry()->getNoticeConfig()->getDocument());
    }

    public function testGetAdminDataExposesCanonicalPayloadForPimcoreAndCustomEntries(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [
                    'indication_upcoming' => [
                        'de' => 'Store upcoming',
                    ],
                    'indication_current' => [
                        'de' => 'Store current',
                    ],
                ],
                'hard_fallback' => [
                    'maintenance_document' => ['id' => 123, 'path' => '/de/system/maintenance'],
                    'error_document' => ['id' => 456, 'path' => '/de/system/error'],
                ],
                'pimcore' => [
                    'show_info' => 'always',
                    'show_info_from' => ['date' => '01.01.2026', 'time' => '07:00'],
                    'planned' => [
                        'from' => ['date' => '02.01.2026', 'time' => '08:00'],
                        'to' => ['date' => '02.01.2026', 'time' => '10:00'],
                    ],
                    'document' => '/de/pimcore',
                ],
                'custom' => [
                    'prices' => [
                        'active' => 'true',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'automatic',
                        'show_info_from' => ['date' => '03.01.2026', 'time' => '09:00'],
                        'planned' => [
                            'from' => ['date' => '04.01.2026', 'time' => '10:00'],
                            'to' => ['date' => '04.01.2026', 'time' => '12:00'],
                        ],
                        'document' => '/de/erp',
                    ],
                    'search' => [
                        'active' => 'false',
                        'fixed' => 'true',
                        'description' => 'Search',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '05.01.2026', 'time' => '10:00'],
                        'planned' => [
                            'from' => ['date' => '06.01.2026', 'time' => '11:00'],
                            'to' => ['date' => '06.01.2026', 'time' => '13:00'],
                        ],
                        'document' => '/de/search',
                    ],
                ],
            ]);

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $adminData = $manager->getAdminData();

        self::assertEqualsCanonicalizing(['prices', 'search'], $adminData['tokens']);
        self::assertSame('Store upcoming', $adminData['frontend']['indication_upcoming']['de']);
        self::assertSame('Store current', $adminData['frontend']['indication_current']['de']);
        self::assertSame(123, $adminData['hard_fallback']['maintenance_document']['id']);
        self::assertSame('/de/system/error', $adminData['hard_fallback']['error_document']['path']);
        self::assertSame('always', $adminData['pimcore']['show_info']);
        self::assertSame('01.01.2026', $adminData['pimcore']['show_info_from']['date']);
        self::assertSame('07:00', $adminData['pimcore']['show_info_from']['time']);
        self::assertSame('02.01.2026', $adminData['pimcore']['planned']['from']['date']);
        self::assertSame('10:00', $adminData['pimcore']['planned']['to']['time']);
        self::assertSame('/de/pimcore', $adminData['pimcore']['document']);
        self::assertSame('ERP', $adminData['custom']['prices']['description']);
        self::assertSame('false', $adminData['custom']['prices']['temporary_active']);
        self::assertSame('03.01.2026', $adminData['custom']['prices']['show_info_from']['date']);
        self::assertSame('09:00', $adminData['custom']['prices']['show_info_from']['time']);
        self::assertSame('04.01.2026', $adminData['custom']['prices']['planned']['from']['date']);
        self::assertSame('12:00', $adminData['custom']['prices']['planned']['to']['time']);
        self::assertSame('false', $adminData['custom']['search']['active']);
        self::assertSame('true', $adminData['custom']['search']['fixed']);
        self::assertSame('05.01.2026', $adminData['custom']['search']['show_info_from']['date']);
        self::assertSame('13:00', $adminData['custom']['search']['planned']['to']['time']);
    }

    public function testGetAdminDataFallsBackToLegacyDataWhenSettingsStoreIsEmpty(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn(null);

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [
                    'indication_upcoming' => [
                        'de' => 'Legacy upcoming',
                    ],
                ],
                'hard_fallback' => [
                    'maintenance_document' => ['id' => 321, 'path' => '/de/legacy-maintenance'],
                    'error_document' => ['id' => null, 'path' => ''],
                ],
                'pimcore' => [
                    'show_info' => 'automatic',
                    'show_info_from' => ['date' => '07.01.2026', 'time' => '06:00'],
                    'planned' => [
                        'from' => ['date' => '08.01.2026', 'time' => '07:00'],
                        'to' => ['date' => '08.01.2026', 'time' => '09:00'],
                    ],
                    'document' => '/de/legacy-pimcore',
                ],
                'custom' => [
                    'prices' => [
                        'active' => 'true',
                        'fixed' => 'false',
                        'description' => 'Legacy ERP',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '09.01.2026', 'time' => '10:00'],
                        'planned' => [
                            'from' => ['date' => '10.01.2026', 'time' => '11:00'],
                            'to' => ['date' => '10.01.2026', 'time' => '13:00'],
                        ],
                        'document' => '/de/legacy-erp',
                    ],
                ],
            ]);

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $adminData = $manager->getAdminData();

        self::assertSame(['prices'], $adminData['tokens']);
        self::assertSame('Legacy upcoming', $adminData['frontend']['indication_upcoming']['de']);
        self::assertSame(321, $adminData['hard_fallback']['maintenance_document']['id']);
        self::assertSame('automatic', $adminData['pimcore']['show_info']);
        self::assertSame('/de/legacy-pimcore', $adminData['pimcore']['document']);
        self::assertSame('Legacy ERP', $adminData['custom']['prices']['description']);
        self::assertSame('/de/legacy-erp', $adminData['custom']['prices']['document']);
    }

    public function testGetAdminDataBuildsDefaultPayloadWhenNoPersistenceSourceExists(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn(null);

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader
            ->expects(self::once())
            ->method('load')
            ->willReturn(null);

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $adminData = $manager->getAdminData();

        self::assertSame([], $adminData['tokens']);
        self::assertSame([], $adminData['custom']);
        self::assertSame('Geplante Wartungsarbeiten von %s bis %s.', $adminData['frontend']['indication_upcoming']['de']);
        self::assertSame('Gegenwärtige Wartungsarbeiten von %s bis %s.', $adminData['frontend']['indication_current']['de']);
        self::assertSame('Mehr Informationen...', $adminData['frontend']['more']['de']);
        self::assertSame('d.m.Y H:i', $adminData['frontend']['fulltimeformat']['de']);
        self::assertNull($adminData['hard_fallback']['maintenance_document']['id']);
        self::assertSame('', $adminData['hard_fallback']['error_document']['path']);
        self::assertSame('never', $adminData['pimcore']['show_info']);
        self::assertSame('01.01.1970', $adminData['pimcore']['planned']['from']['date']);
        self::assertSame('00:00', $adminData['pimcore']['planned']['to']['time']);
        self::assertSame('', $adminData['pimcore']['document']);
    }

    public function testGetConfigSetRejectsStructurallyInvalidSettingsStorePayload(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'custom' => 'invalid',
            ]);

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);

        $this->expectException(\UnexpectedValueException::class);
        $manager->getConfigSet();
    }

    public function testSaveFromAdminPayloadPersistsToSettingsStoreAndPreservesUnknownFields(): void
    {
        $persistedData = null;
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn(null);
        $settingsStore
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (array $data) use (&$persistedData): void {
                $persistedData = $data;
            });

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'pimcore' => [
                    'show_info' => 'never',
                    'show_info_from' => ['date' => '01.01.2026', 'time' => '00:00'],
                    'planned' => [
                        'from' => ['date' => '01.01.2026', 'time' => '08:00'],
                        'to' => ['date' => '01.01.2026', 'time' => '10:00'],
                    ],
                    'document' => '',
                    'legacy_flag' => 'preserve-me',
                ],
                'custom' => [
                    'prices' => [
                        'active' => 'false',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '01.01.2026', 'time' => '00:00'],
                        'planned' => [
                            'from' => ['date' => '01.01.2026', 'time' => '08:00'],
                            'to' => ['date' => '01.01.2026', 'time' => '10:00'],
                        ],
                        'document' => '/de/erp',
                        'legacy_note' => 'keep-me',
                    ],
                ],
                'legacy_root' => 'keep-root',
                'frontend' => [
                    'more' => [
                        'en' => 'more...',
                    ],
                    'legacy_frontend' => 'keep-frontend',
                ],
            ]);

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $manager->saveFromAdminPayload([
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'hard_fallback_maintenance_document_id' => '123',
            'hard_fallback_maintenance_document_path' => '/de/system/maintenance',
            'hard_fallback_error_document_id' => '456',
            'hard_fallback_error_document_path' => '/de/system/error',
            'pimcore_show_info' => 'automatic',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
            'prices_maintenance_mode' => 'scheduled',
            'prices_temporary_active' => 'true',
            'prices_fixed' => 'true',
            'prices_description' => 'ERP',
            'prices_show_info' => 'always',
            'prices_show_info_from_date' => '2026-02-01',
            'prices_show_info_from_time' => '2026-02-01 06:30',
            'prices_from_date' => '2026-02-02',
            'prices_from_time' => '2026-02-02 10:00',
            'prices_to_date' => '2026-02-02',
            'prices_to_time' => '2026-02-02 12:00',
            'prices_document' => '/de/erp-new',
        ]);

        self::assertSame('Upcoming %s %s', $persistedData['frontend']['indication_upcoming']['de']);
        self::assertSame(123, $persistedData['hard_fallback']['maintenance_document']['id']);
        self::assertSame('/de/system/error', $persistedData['hard_fallback']['error_document']['path']);
        self::assertSame('automatic', $persistedData['pimcore']['show_info']);
        self::assertSame('01.02.2026', $persistedData['pimcore']['show_info_from']['date']);
        self::assertSame('07:15', $persistedData['pimcore']['show_info_from']['time']);
        self::assertSame('false', $persistedData['custom']['prices']['active']);
        self::assertSame('true', $persistedData['custom']['prices']['temporary_active']);
        self::assertSame('true', $persistedData['custom']['prices']['fixed']);
        self::assertSame('02.02.2026', $persistedData['custom']['prices']['planned']['from']['date']);
        self::assertSame('/de/erp-new', $persistedData['custom']['prices']['document']);
        self::assertSame('preserve-me', $persistedData['pimcore']['legacy_flag']);
        self::assertSame('keep-me', $persistedData['custom']['prices']['legacy_note']);
        self::assertSame('keep-root', $persistedData['legacy_root']);
        self::assertSame('keep-frontend', $persistedData['frontend']['legacy_frontend']);
        self::assertSame('more...', $persistedData['frontend']['more']['en']);
    }

    public function testSaveFromAdminPayloadCreatesNewCustomEntryWithDefaultsAndPersistsIt(): void
    {
        $persistedData = null;
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [
                    'prices' => [
                        'active' => 'true',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'automatic',
                        'show_info_from' => ['date' => '01.01.2026', 'time' => '10:00'],
                        'planned' => [
                            'from' => ['date' => '03.01.2026', 'time' => '10:00'],
                            'to' => ['date' => '03.01.2026', 'time' => '12:00'],
                        ],
                        'document' => '/de/erp',
                    ],
                ],
                'legacy_root' => 'keep-root',
            ]);
        $settingsStore
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (array $data) use (&$persistedData): void {
                $persistedData = $data;
            });

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $manager->saveFromAdminPayload([
            'custom_tokens' => 'prices,search',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
            'prices_token' => 'prices',
            'prices_maintenance_mode' => 'scheduled',
            'prices_temporary_active' => 'true',
            'prices_fixed' => 'false',
            'prices_description' => 'ERP',
            'prices_show_info' => 'automatic',
            'prices_show_info_from_date' => '2026-02-01',
            'prices_show_info_from_time' => '2026-02-01 06:30',
            'prices_from_date' => '2026-02-02',
            'prices_from_time' => '2026-02-02 10:00',
            'prices_to_date' => '2026-02-02',
            'prices_to_time' => '2026-02-02 12:00',
            'prices_document' => '/de/erp-new',
            'search_maintenance_mode' => 'inactive',
            'search_temporary_active' => 'false',
            'search_fixed' => 'false',
            'search_description' => '',
            'search_token' => 'search',
            'search_show_info' => 'never',
            'search_show_info_from_date' => '',
            'search_show_info_from_time' => '',
            'search_from_date' => '',
            'search_from_time' => '',
            'search_to_date' => '',
            'search_to_time' => '',
            'search_document' => '',
        ]);

        self::assertSame('keep-root', $persistedData['legacy_root']);
        self::assertSame('false', $persistedData['custom']['search']['active']);
        self::assertSame('false', $persistedData['custom']['search']['temporary_active']);
        self::assertSame('false', $persistedData['custom']['search']['fixed']);
        self::assertSame('', $persistedData['custom']['search']['description']);
        self::assertSame('never', $persistedData['custom']['search']['show_info']);
        self::assertSame('', $persistedData['custom']['search']['show_info_from']['date']);
        self::assertSame('', $persistedData['custom']['search']['show_info_from']['time']);
        self::assertSame('', $persistedData['custom']['search']['planned']['from']['date']);
        self::assertSame('', $persistedData['custom']['search']['planned']['from']['time']);
        self::assertSame('', $persistedData['custom']['search']['planned']['to']['date']);
        self::assertSame('', $persistedData['custom']['search']['planned']['to']['time']);
        self::assertSame('', $persistedData['custom']['search']['document']);
        self::assertSame('true', $persistedData['custom']['prices']['temporary_active']);
        self::assertSame('/de/erp-new', $persistedData['custom']['prices']['document']);
    }

    public function testSaveFromAdminPayloadRenamesExistingCustomTokenAndPersistsChangedFields(): void
    {
        $persistedData = null;
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [
                    'prices' => [
                        'active' => 'true',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'automatic',
                        'show_info_from' => ['date' => '01.01.2026', 'time' => '10:00'],
                        'planned' => [
                            'from' => ['date' => '03.01.2026', 'time' => '10:00'],
                            'to' => ['date' => '03.01.2026', 'time' => '12:00'],
                        ],
                        'document' => '/de/erp',
                    ],
                ],
                'legacy_root' => 'keep-root',
            ]);
        $settingsStore
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (array $data) use (&$persistedData): void {
                $persistedData = $data;
            });

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $manager->saveFromAdminPayload([
            'custom_tokens' => 'prices',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
            'prices_token' => 'catalog',
            'prices_maintenance_mode' => 'scheduled',
            'prices_temporary_active' => 'false',
            'prices_fixed' => 'true',
            'prices_description' => 'Catalog',
            'prices_show_info' => 'always',
            'prices_show_info_from_date' => '2026-02-03',
            'prices_show_info_from_time' => '2026-02-03 06:30',
            'prices_from_date' => '2026-02-04',
            'prices_from_time' => '2026-02-04 10:00',
            'prices_to_date' => '2026-02-04',
            'prices_to_time' => '2026-02-04 12:00',
            'prices_document' => '/de/catalog',
        ]);

        self::assertSame('keep-root', $persistedData['legacy_root']);
        self::assertArrayNotHasKey('prices', $persistedData['custom']);
        self::assertSame('false', $persistedData['custom']['catalog']['active']);
        self::assertSame('false', $persistedData['custom']['catalog']['temporary_active']);
        self::assertSame('true', $persistedData['custom']['catalog']['fixed']);
        self::assertSame('Catalog', $persistedData['custom']['catalog']['description']);
        self::assertSame('always', $persistedData['custom']['catalog']['show_info']);
        self::assertSame('', $persistedData['custom']['catalog']['show_info_from']['date']);
        self::assertSame('', $persistedData['custom']['catalog']['show_info_from']['time']);
        self::assertSame('04.02.2026', $persistedData['custom']['catalog']['planned']['from']['date']);
        self::assertSame('12:00', $persistedData['custom']['catalog']['planned']['to']['time']);
        self::assertSame('/de/catalog', $persistedData['custom']['catalog']['document']);
    }

    public function testSaveFromAdminPayloadDeletesOmittedCustomEntryFromCanonicalConfiguration(): void
    {
        $persistedData = null;
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [
                    'prices' => [
                        'active' => 'false',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '', 'time' => ''],
                        'planned' => [
                            'from' => ['date' => '', 'time' => ''],
                            'to' => ['date' => '', 'time' => ''],
                        ],
                        'document' => '/de/erp',
                    ],
                    'search' => [
                        'active' => 'true',
                        'fixed' => 'false',
                        'description' => 'Search',
                        'show_info' => 'always',
                        'show_info_from' => ['date' => '01.01.2026', 'time' => '08:00'],
                        'planned' => [
                            'from' => ['date' => '02.01.2026', 'time' => '09:00'],
                            'to' => ['date' => '02.01.2026', 'time' => '11:00'],
                        ],
                        'document' => '/de/search',
                    ],
                ],
            ]);
        $settingsStore
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (array $data) use (&$persistedData): void {
                $persistedData = $data;
            });

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $manager->saveFromAdminPayload([
            'custom_tokens' => 'search',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
            'search_token' => 'search',
            'search_active' => 'true',
            'search_fixed' => 'false',
            'search_description' => 'Search',
            'search_show_info' => 'always',
            'search_show_info_from_date' => '2026-02-01',
            'search_show_info_from_time' => '2026-02-01 08:00',
            'search_from_date' => '2026-02-02',
            'search_from_time' => '2026-02-02 09:00',
            'search_to_date' => '2026-02-02',
            'search_to_time' => '2026-02-02 11:00',
            'search_document' => '/de/search',
        ]);

        self::assertArrayNotHasKey('prices', $persistedData['custom']);
        self::assertSame(['search'], array_keys($persistedData['custom']));
        self::assertSame('Search', $persistedData['custom']['search']['description']);
    }

    public function testSaveFromAdminPayloadAllowsDeletingAnActiveCustomEntryWithoutAdditionalBlockade(): void
    {
        $persistedData = null;
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [
                    'search' => [
                        'active' => 'true',
                        'fixed' => 'false',
                        'description' => 'Search',
                        'show_info' => 'always',
                        'show_info_from' => ['date' => '01.01.2026', 'time' => '08:00'],
                        'planned' => [
                            'from' => ['date' => '02.01.2026', 'time' => '09:00'],
                            'to' => ['date' => '02.01.2026', 'time' => '11:00'],
                        ],
                        'document' => '/de/search',
                    ],
                ],
            ]);
        $settingsStore
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (array $data) use (&$persistedData): void {
                $persistedData = $data;
            });

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $manager->saveFromAdminPayload([
            'custom_tokens' => '',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
        ]);

        self::assertSame([], $persistedData['custom']);
    }

    public function testSaveFromAdminPayloadRejectsReservedTokenForNewCustomEntry(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [],
            ]);
        $settingsStore->expects(self::never())->method('save');

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom maintenance token "pimcore" is reserved.');

        $manager->saveFromAdminPayload([
            'custom_tokens' => 'pimcore',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
        ]);
    }

    public function testSaveFromAdminPayloadRejectsTechnicalDeleteAttemptForNativePimcoreEntry(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::never())
            ->method('load');
        $settingsStore->expects(self::never())->method('save');

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Native Pimcore maintenance cannot be deleted.');

        $manager->saveFromAdminPayload([
            'pimcore_delete' => 'true',
        ]);
    }

    public function testSaveFromAdminPayloadRejectsDuplicateTokenListForNewCustomEntry(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [],
            ]);
        $settingsStore->expects(self::never())->method('save');

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate custom maintenance token: search');

        $manager->saveFromAdminPayload([
            'custom_tokens' => 'search,search',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
        ]);
    }

    public function testSaveFromAdminPayloadRejectsRenameCollisionWithExistingCustomToken(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [
                    'prices' => [
                        'active' => 'false',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '', 'time' => ''],
                        'planned' => [
                            'from' => ['date' => '', 'time' => ''],
                            'to' => ['date' => '', 'time' => ''],
                        ],
                        'document' => '',
                    ],
                    'search' => [
                        'active' => 'false',
                        'fixed' => 'false',
                        'description' => 'Search',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '', 'time' => ''],
                        'planned' => [
                            'from' => ['date' => '', 'time' => ''],
                            'to' => ['date' => '', 'time' => ''],
                        ],
                        'document' => '',
                    ],
                ],
            ]);
        $settingsStore->expects(self::never())->method('save');

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate custom maintenance token: search');

        $manager->saveFromAdminPayload([
            'custom_tokens' => 'prices,search',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
            'prices_token' => 'search',
            'search_token' => 'search',
        ]);
    }

    public function testSaveFromAdminPayloadRejectsRenameCollisionWithOmittedExistingToken(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [
                    'prices' => [
                        'active' => 'false',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '', 'time' => ''],
                        'planned' => [
                            'from' => ['date' => '', 'time' => ''],
                            'to' => ['date' => '', 'time' => ''],
                        ],
                        'document' => '',
                    ],
                    'search' => [
                        'active' => 'false',
                        'fixed' => 'false',
                        'description' => 'Search',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '', 'time' => ''],
                        'planned' => [
                            'from' => ['date' => '', 'time' => ''],
                            'to' => ['date' => '', 'time' => ''],
                        ],
                        'document' => '',
                    ],
                ],
            ]);
        $settingsStore->expects(self::never())->method('save');

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate custom maintenance token: search');

        $manager->saveFromAdminPayload([
            'custom_tokens' => 'prices',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
            'prices_token' => 'search',
        ]);
    }

    public function testSaveFromAdminPayloadSupportsSwappingTwoExistingCustomTokens(): void
    {
        $persistedData = null;
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [
                    'prices' => [
                        'active' => 'true',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'automatic',
                        'show_info_from' => ['date' => '01.01.2026', 'time' => '10:00'],
                        'planned' => [
                            'from' => ['date' => '03.01.2026', 'time' => '10:00'],
                            'to' => ['date' => '03.01.2026', 'time' => '12:00'],
                        ],
                        'document' => '/de/erp',
                    ],
                    'search' => [
                        'active' => 'false',
                        'fixed' => 'true',
                        'description' => 'Search',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '', 'time' => ''],
                        'planned' => [
                            'from' => ['date' => '', 'time' => ''],
                            'to' => ['date' => '', 'time' => ''],
                        ],
                        'document' => '/de/search',
                    ],
                ],
            ]);
        $settingsStore
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (array $data) use (&$persistedData): void {
                $persistedData = $data;
            });

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $manager->saveFromAdminPayload([
            'custom_tokens' => 'prices,search',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
            'prices_token' => 'search',
            'prices_active' => 'true',
            'prices_fixed' => 'false',
            'prices_description' => 'ERP',
            'prices_show_info' => 'automatic',
            'prices_show_info_from_date' => '2026-02-01',
            'prices_show_info_from_time' => '2026-02-01 06:30',
            'prices_from_date' => '2026-02-02',
            'prices_from_time' => '2026-02-02 10:00',
            'prices_to_date' => '2026-02-02',
            'prices_to_time' => '2026-02-02 12:00',
            'prices_document' => '/de/erp',
            'search_token' => 'prices',
            'search_active' => 'false',
            'search_fixed' => 'true',
            'search_description' => 'Search',
            'search_show_info' => 'never',
            'search_show_info_from_date' => '',
            'search_show_info_from_time' => '',
            'search_from_date' => '',
            'search_from_time' => '',
            'search_to_date' => '',
            'search_to_time' => '',
            'search_document' => '/de/search',
        ]);

        self::assertSame('ERP', $persistedData['custom']['search']['description']);
        self::assertSame('/de/erp', $persistedData['custom']['search']['document']);
        self::assertSame('Search', $persistedData['custom']['prices']['description']);
        self::assertSame('/de/search', $persistedData['custom']['prices']['document']);
        self::assertCount(2, $persistedData['custom']);
    }

    public function testSaveFromAdminPayloadRejectsScheduledMaintenanceWithoutStartDateTime(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [
                    'prices' => [
                        'active' => 'false',
                        'temporary_active' => 'false',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '', 'time' => ''],
                        'planned' => [
                            'from' => ['date' => '', 'time' => ''],
                            'to' => ['date' => '', 'time' => ''],
                        ],
                        'document' => '/de/erp',
                    ],
                ],
            ]);
        $settingsStore->expects(self::never())->method('save');

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Zeitgesteuerte Maintenance benötigt einen Startzeitpunkt mit Datum und Uhrzeit.');

        $manager->saveFromAdminPayload([
            'custom_tokens' => 'prices',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
            'prices_token' => 'prices',
            'prices_maintenance_mode' => 'scheduled',
            'prices_temporary_active' => 'false',
            'prices_fixed' => 'false',
            'prices_description' => 'ERP',
            'prices_show_info' => 'never',
            'prices_show_info_from_date' => '',
            'prices_show_info_from_time' => '',
            'prices_from_date' => '',
            'prices_from_time' => '',
            'prices_to_date' => '',
            'prices_to_time' => '',
            'prices_document' => '/de/erp',
        ]);
    }

    public function testSaveFromAdminPayloadRejectsPartialScheduledMaintenanceEndDateTime(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [
                    'prices' => [
                        'active' => 'false',
                        'temporary_active' => 'false',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '', 'time' => ''],
                        'planned' => [
                            'from' => ['date' => '', 'time' => ''],
                            'to' => ['date' => '', 'time' => ''],
                        ],
                        'document' => '/de/erp',
                    ],
                ],
            ]);
        $settingsStore->expects(self::never())->method('save');

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Das optionale Ende einer zeitgesteuerten Maintenance muss Datum und Uhrzeit vollständig enthalten.');

        $manager->saveFromAdminPayload([
            'custom_tokens' => 'prices',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
            'prices_token' => 'prices',
            'prices_maintenance_mode' => 'scheduled',
            'prices_temporary_active' => 'false',
            'prices_fixed' => 'false',
            'prices_description' => 'ERP',
            'prices_show_info' => 'never',
            'prices_show_info_from_date' => '',
            'prices_show_info_from_time' => '',
            'prices_from_date' => '2026-02-02',
            'prices_from_time' => '2026-02-02 10:00',
            'prices_to_date' => '2026-02-02',
            'prices_to_time' => '',
            'prices_document' => '/de/erp',
        ]);
    }

    public function testSetCustomStatusWritesOnlyToSettingsStore(): void
    {
        $persistedData = null;
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [
                    'prices' => [
                        'active' => 'false',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '01.01.2026', 'time' => '00:00'],
                        'planned' => [
                            'from' => ['date' => '01.01.2026', 'time' => '08:00'],
                            'to' => ['date' => '01.01.2026', 'time' => '10:00'],
                        ],
                        'document' => '/de/erp',
                    ],
                ],
            ]);
        $settingsStore
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (array $data) use (&$persistedData): void {
                $persistedData = $data;
            });

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $manager->setCustomStatus('prices', 'true');

        self::assertSame('false', $persistedData['custom']['prices']['active']);
        self::assertSame('true', $persistedData['custom']['prices']['temporary_active']);
    }

    public function testSaveFromAdminPayloadRejectsAutomaticNoticeWithoutStartDateTime(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [
                    'prices' => [
                        'active' => 'false',
                        'temporary_active' => 'false',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'never',
                        'show_info_from' => ['date' => '', 'time' => ''],
                        'planned' => [
                            'from' => ['date' => '', 'time' => ''],
                            'to' => ['date' => '', 'time' => ''],
                        ],
                        'document' => '/de/erp',
                    ],
                ],
            ]);
        $settingsStore->expects(self::never())->method('save');

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Zeitgeplanter Hinweis benötigt einen Startzeitpunkt mit Datum und Uhrzeit.');

        $manager->saveFromAdminPayload([
            'custom_tokens' => 'prices',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
            'prices_token' => 'prices',
            'prices_maintenance_mode' => 'inactive',
            'prices_temporary_active' => 'false',
            'prices_fixed' => 'false',
            'prices_description' => 'ERP',
            'prices_show_info' => 'automatic',
            'prices_show_info_from_date' => '',
            'prices_show_info_from_time' => '',
            'prices_from_date' => '',
            'prices_from_time' => '',
            'prices_to_date' => '',
            'prices_to_time' => '',
            'prices_document' => '/de/erp',
        ]);
    }

    public function testSaveFromAdminPayloadRejectsPimcoreTimingWithoutCompleteStartDateTime(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [],
            ]);
        $settingsStore->expects(self::never())->method('save');

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Die Pimcore-Zeitsteuerung benötigt einen Startzeitpunkt mit Datum und Uhrzeit.');

        $manager->saveFromAdminPayload([
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'never',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '',
            'pimcore_from_time' => '',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
        ]);
    }

    public function testSaveFromAdminPayloadPersistsLocalFormattedUiDateTimeValuesWithoutTimezoneShift(): void
    {
        $persistedData = null;
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [],
            ]);
        $settingsStore
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (array $data) use (&$persistedData): void {
                $persistedData = $data;
            });

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $manager->saveFromAdminPayload([
            'custom_tokens' => 'search',
            'frontend_indication_upcoming' => 'Upcoming %s %s',
            'frontend_indication_current' => 'Current %s %s',
            'frontend_more' => 'Mehr',
            'frontend_fulltimeformat' => 'd.m.Y H:i',
            'pimcore_show_info' => 'automatic',
            'pimcore_show_info_from_date' => '2026-07-17',
            'pimcore_show_info_from_time' => '01:30',
            'pimcore_from_date' => '2026-07-17',
            'pimcore_from_time' => '01:30',
            'pimcore_to_date' => '2026-07-18',
            'pimcore_to_time' => '03:15',
            'pimcore_document' => '/de/pimcore',
            'search_maintenance_mode' => 'scheduled',
            'search_temporary_active' => 'false',
            'search_fixed' => 'false',
            'search_description' => 'Search',
            'search_show_info' => 'automatic',
            'search_show_info_from_date' => '2026-07-17',
            'search_show_info_from_time' => '01:30',
            'search_from_date' => '2026-07-17',
            'search_from_time' => '01:30',
            'search_to_date' => '2026-07-18',
            'search_to_time' => '03:15',
            'search_document' => '/de/search',
        ]);

        self::assertSame('17.07.2026', $persistedData['pimcore']['show_info_from']['date']);
        self::assertSame('01:30', $persistedData['pimcore']['show_info_from']['time']);
        self::assertSame('17.07.2026', $persistedData['pimcore']['planned']['from']['date']);
        self::assertSame('01:30', $persistedData['pimcore']['planned']['from']['time']);
        self::assertSame('18.07.2026', $persistedData['pimcore']['planned']['to']['date']);
        self::assertSame('03:15', $persistedData['pimcore']['planned']['to']['time']);
        self::assertSame('17.07.2026', $persistedData['custom']['search']['planned']['from']['date']);
        self::assertSame('01:30', $persistedData['custom']['search']['planned']['from']['time']);
        self::assertSame('18.07.2026', $persistedData['custom']['search']['planned']['to']['date']);
        self::assertSame('03:15', $persistedData['custom']['search']['planned']['to']['time']);
    }
}
