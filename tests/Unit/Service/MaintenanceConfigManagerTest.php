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
            'pimcore_show_info' => 'automatic',
            'pimcore_show_info_from_date' => '2026-02-01',
            'pimcore_show_info_from_time' => '2026-02-01 07:15',
            'pimcore_from_date' => '2026-02-02',
            'pimcore_from_time' => '2026-02-02 08:00',
            'pimcore_to_date' => '2026-02-02',
            'pimcore_to_time' => '2026-02-02 09:30',
            'pimcore_document' => '/de/pimcore',
            'prices_active' => 'true',
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
        self::assertSame('automatic', $persistedData['pimcore']['show_info']);
        self::assertSame('01.02.2026', $persistedData['pimcore']['show_info_from']['date']);
        self::assertSame('07:15', $persistedData['pimcore']['show_info_from']['time']);
        self::assertSame('true', $persistedData['custom']['prices']['active']);
        self::assertSame('true', $persistedData['custom']['prices']['fixed']);
        self::assertSame('/de/erp-new', $persistedData['custom']['prices']['document']);
        self::assertSame('preserve-me', $persistedData['pimcore']['legacy_flag']);
        self::assertSame('keep-me', $persistedData['custom']['prices']['legacy_note']);
        self::assertSame('keep-root', $persistedData['legacy_root']);
        self::assertSame('keep-frontend', $persistedData['frontend']['legacy_frontend']);
        self::assertSame('more...', $persistedData['frontend']['more']['en']);
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

        self::assertSame('true', $persistedData['custom']['prices']['active']);
    }
}
