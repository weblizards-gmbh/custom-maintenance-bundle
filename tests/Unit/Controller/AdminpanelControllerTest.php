<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Controller;

use Pimcore\Twig\Extension\Templating\HeadLink;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\UserAwareController;
use Pimcore\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Weblizards\CustomMaintenanceBundle\Controller\AdminpanelController;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\ConfigPersistenceInterface;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\LegacyConfigLoaderInterface;
use Weblizards\CustomMaintenanceBundle\Service\HardFallbackExportService;
use Weblizards\CustomMaintenanceBundle\Service\MaintenanceConfigManager;
use Weblizards\CustomMaintenanceBundle\Service\StatusService;

final class AdminpanelControllerTest extends TestCase
{
    public function testAdminpanelControllerExtendsUserAwareControllerInsteadOfDeprecatedAdminController(): void
    {
        self::assertTrue(is_subclass_of(AdminpanelController::class, UserAwareController::class));
        self::assertFalse(is_subclass_of(AdminpanelController::class, AdminController::class));
    }

    public function testLoadActionReturnsCanonicalAdminDataFromConfigManager(): void
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
                    'more' => [
                        'de' => 'Mehr',
                    ],
                    'fulltimeformat' => [
                        'de' => 'd.m.Y H:i',
                    ],
                ],
                'hard_fallback' => [
                    'maintenance_document' => ['id' => 123, 'path' => '/de/system/maintenance'],
                    'error_document' => ['id' => 456, 'path' => '/de/system/error'],
                ],
                'pimcore' => [
                    'show_info' => 'automatic',
                    'show_info_from' => ['date' => '01.01.2026', 'time' => '08:30'],
                    'planned' => [
                        'from' => ['date' => '02.01.2026', 'time' => '09:00'],
                        'to' => ['date' => '02.01.2026', 'time' => '11:00'],
                    ],
                    'document' => '/de/pimcore',
                ],
                'custom' => [
                    'prices' => [
                        'active' => 'true',
                        'fixed' => 'false',
                        'description' => 'ERP',
                        'show_info' => 'always',
                        'show_info_from' => ['date' => '03.01.2026', 'time' => '08:00'],
                        'planned' => [
                            'from' => ['date' => '04.01.2026', 'time' => '10:00'],
                            'to' => ['date' => '04.01.2026', 'time' => '12:00'],
                        ],
                        'document' => '/de/erp',
                    ],
                ],
            ]);
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $configManager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $controller = new AdminpanelController();
        $response = $controller->loadAction($configManager);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(['prices'], $payload['tokens']);
        self::assertSame('Store upcoming', $payload['frontend']['indication_upcoming']['de']);
        self::assertSame('Store current', $payload['frontend']['indication_current']['de']);
        self::assertSame('Mehr', $payload['frontend']['more']['de']);
        self::assertSame('d.m.Y H:i', $payload['frontend']['fulltimeformat']['de']);
        self::assertSame(123, $payload['hard_fallback']['maintenance_document']['id']);
        self::assertSame('/de/system/error', $payload['hard_fallback']['error_document']['path']);
        self::assertSame('automatic', $payload['pimcore']['show_info']);
        self::assertSame('01.01.2026', $payload['pimcore']['show_info_from']['date']);
        self::assertSame('08:30', $payload['pimcore']['show_info_from']['time']);
        self::assertSame('02.01.2026', $payload['pimcore']['planned']['from']['date']);
        self::assertSame('11:00', $payload['pimcore']['planned']['to']['time']);
        self::assertSame('/de/pimcore', $payload['pimcore']['document']);
        self::assertSame('ERP', $payload['custom']['prices']['description']);
        self::assertSame('true', $payload['custom']['prices']['active']);
        self::assertSame('false', $payload['custom']['prices']['fixed']);
        self::assertSame('03.01.2026', $payload['custom']['prices']['show_info_from']['date']);
        self::assertSame('04.01.2026', $payload['custom']['prices']['planned']['from']['date']);
        self::assertSame('12:00', $payload['custom']['prices']['planned']['to']['time']);
        self::assertSame('/de/erp', $payload['custom']['prices']['document']);
    }

    public function testSaveActionPersistsDecodedPayloadAndReturnsTranslatedSuccessMessage(): void
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

        $translator = $this->createMock(Translator::class);
        $translator
            ->expects(self::once())
            ->method('trans')
            ->with('custommaintenance_adminpanel_save_success')
            ->willReturn('Speicher erfolgreich');
        $hardFallbackExportService = $this->createMock(HardFallbackExportService::class);
        $hardFallbackExportService
            ->expects(self::once())
            ->method('synchronizeAfterConfigSave')
            ->with([
                'maintenance_document_changed' => true,
                'error_document_changed' => true,
                'maintenance_document_id' => 123,
                'error_document_id' => 456,
            ])
            ->willReturn([]);

        $controller = new AdminpanelController();
        $configManager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $request = new Request([
            'data' => json_encode([
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
            ], JSON_THROW_ON_ERROR),
        ]);

        $response = $controller->saveAction($request, $configManager, $translator, $hardFallbackExportService);

        self::assertSame(
            [
                'success' => true,
                'message' => 'Speicher erfolgreich',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
        self::assertSame('Upcoming %s %s', $persistedData['frontend']['indication_upcoming']['de']);
        self::assertSame(123, $persistedData['hard_fallback']['maintenance_document']['id']);
        self::assertSame('/de/system/error', $persistedData['hard_fallback']['error_document']['path']);
        self::assertSame('automatic', $persistedData['pimcore']['show_info']);
        self::assertSame('01.02.2026', $persistedData['pimcore']['show_info_from']['date']);
        self::assertSame('07:15', $persistedData['pimcore']['show_info_from']['time']);
        self::assertSame('/de/pimcore', $persistedData['pimcore']['document']);
    }

    public function testSaveActionCreatesNewCustomEntryAndReturnsTranslatedSuccessMessage(): void
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

        $translator = $this->createMock(Translator::class);
        $translator
            ->expects(self::once())
            ->method('trans')
            ->with('custommaintenance_adminpanel_save_success')
            ->willReturn('Speicher erfolgreich');
        $hardFallbackExportService = $this->createMock(HardFallbackExportService::class);
        $hardFallbackExportService->expects(self::once())->method('synchronizeAfterConfigSave')->willReturn([]);

        $controller = new AdminpanelController();
        $configManager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $request = new Request([
            'data' => json_encode([
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
                'search_active' => 'false',
                'search_fixed' => 'false',
                'search_description' => '',
                'search_show_info' => 'never',
                'search_show_info_from_date' => '',
                'search_show_info_from_time' => '',
                'search_from_date' => '',
                'search_from_time' => '',
                'search_to_date' => '',
                'search_to_time' => '',
                'search_document' => '',
            ], JSON_THROW_ON_ERROR),
        ]);

        $response = $controller->saveAction($request, $configManager, $translator, $hardFallbackExportService);

        self::assertSame(
            [
                'success' => true,
                'message' => 'Speicher erfolgreich',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
        self::assertSame('false', $persistedData['custom']['search']['active']);
        self::assertSame('never', $persistedData['custom']['search']['show_info']);
        self::assertSame('', $persistedData['custom']['search']['planned']['from']['date']);
        self::assertSame('', $persistedData['custom']['search']['planned']['to']['time']);
    }

    public function testSaveActionRenamesExistingCustomEntryAndReturnsTranslatedSuccessMessage(): void
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
            ]);
        $settingsStore
            ->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (array $data) use (&$persistedData): void {
                $persistedData = $data;
            });

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $translator = $this->createMock(Translator::class);
        $translator
            ->expects(self::once())
            ->method('trans')
            ->with('custommaintenance_adminpanel_save_success')
            ->willReturn('Speicher erfolgreich');
        $hardFallbackExportService = $this->createMock(HardFallbackExportService::class);
        $hardFallbackExportService->expects(self::once())->method('synchronizeAfterConfigSave')->willReturn([]);

        $controller = new AdminpanelController();
        $configManager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $request = new Request([
            'data' => json_encode([
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
                'prices_active' => 'false',
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
            ], JSON_THROW_ON_ERROR),
        ]);

        $response = $controller->saveAction($request, $configManager, $translator, $hardFallbackExportService);

        self::assertSame(
            [
                'success' => true,
                'message' => 'Speicher erfolgreich',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
        self::assertArrayNotHasKey('prices', $persistedData['custom']);
        self::assertSame('Catalog', $persistedData['custom']['catalog']['description']);
        self::assertSame('/de/catalog', $persistedData['custom']['catalog']['document']);
    }

    public function testSaveActionDeletesCustomEntryAfterConfirmedRemovalAndReturnsTranslatedSuccessMessage(): void
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

        $translator = $this->createMock(Translator::class);
        $translator
            ->expects(self::once())
            ->method('trans')
            ->with('custommaintenance_adminpanel_save_success')
            ->willReturn('Speicher erfolgreich');
        $hardFallbackExportService = $this->createMock(HardFallbackExportService::class);
        $hardFallbackExportService->expects(self::once())->method('synchronizeAfterConfigSave')->willReturn([]);

        $controller = new AdminpanelController();
        $configManager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $request = new Request([
            'data' => json_encode([
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
            ], JSON_THROW_ON_ERROR),
        ]);

        $response = $controller->saveAction($request, $configManager, $translator, $hardFallbackExportService);

        self::assertSame(
            [
                'success' => true,
                'message' => 'Speicher erfolgreich',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
        self::assertArrayNotHasKey('prices', $persistedData['custom']);
        self::assertSame(['search'], array_keys($persistedData['custom']));
    }

    public function testSaveActionReturnsFailurePayloadForInvalidNewCustomToken(): void
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

        $translator = $this->createMock(Translator::class);
        $translator->expects(self::never())->method('trans');
        $hardFallbackExportService = $this->createMock(HardFallbackExportService::class);
        $hardFallbackExportService->expects(self::never())->method('synchronizeAfterConfigSave');

        $controller = new AdminpanelController();
        $configManager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $request = new Request([
            'data' => json_encode([
                'custom_tokens' => 'search-api',
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
            ], JSON_THROW_ON_ERROR),
        ]);

        $response = $controller->saveAction($request, $configManager, $translator, $hardFallbackExportService);

        self::assertSame(
            [
                'success' => false,
                'message' => 'Maintenance token must be alphanumeric.',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testSaveActionReturnsFailurePayloadForTechnicalPimcoreDeleteAttempt(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore->expects(self::never())->method('load');
        $settingsStore->expects(self::never())->method('save');

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $translator = $this->createMock(Translator::class);
        $translator->expects(self::never())->method('trans');
        $hardFallbackExportService = $this->createMock(HardFallbackExportService::class);
        $hardFallbackExportService->expects(self::never())->method('synchronizeAfterConfigSave');

        $controller = new AdminpanelController();
        $configManager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $request = new Request([
            'data' => json_encode([
                'pimcore_delete' => true,
            ], JSON_THROW_ON_ERROR),
        ]);

        $response = $controller->saveAction($request, $configManager, $translator, $hardFallbackExportService);

        self::assertSame(
            [
                'success' => false,
                'message' => 'Native Pimcore maintenance cannot be deleted.',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testDiagnoseActionEvaluatesUnsavedFormPayloadWithoutPersisting(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [
                    'show_info' => 'never',
                    'show_info_from' => ['date' => '01.01.1970', 'time' => '00:00'],
                    'planned' => [
                        'from' => ['date' => '01.01.1970', 'time' => '00:00'],
                        'to' => ['date' => '01.01.1970', 'time' => '00:00'],
                    ],
                    'document' => '',
                ],
                'custom' => [],
            ]);
        $settingsStore->expects(self::never())->method('save');
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $headLink = $this
            ->getMockBuilder(HeadLink::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $headLink
            ->expects(self::once())
            ->method('__call')
            ->with('appendStylesheet', ['/bundles/weblizardscustommaintenance/css/frontend.css']);

        $statusService = new StatusService(new MaintenanceConfigManager($settingsStore, $legacyLoader), $headLink);
        $controller = new AdminpanelController();
        $request = new Request([
            'data' => json_encode([
                'custom_tokens' => 'search',
                'diagnosis_reference_date' => '2026-02-01',
                'diagnosis_reference_time' => '2026-02-01 10:30',
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
                'search_maintenance_mode' => 'scheduled',
                'search_temporary_active' => 'false',
                'search_fixed' => 'false',
                'search_description' => 'Search',
                'search_show_info' => 'automatic',
                'search_show_info_from_date' => '2026-02-01',
                'search_show_info_from_time' => '2026-02-01 08:00',
                'search_from_date' => '2026-02-01',
                'search_from_time' => '2026-02-01 11:00',
                'search_to_date' => '2026-02-01',
                'search_to_time' => '2026-02-01 12:30',
                'search_document' => '/de/search',
            ], JSON_THROW_ON_ERROR),
        ]);

        $response = $controller->diagnoseAction($request, $statusService);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($payload['success']);
        self::assertSame('01.02.2026 10:30', $payload['diagnosis']['evaluated_at']);
        self::assertSame('search', $payload['diagnosis']['entries'][1]['token']);
        self::assertSame('scheduled', $payload['diagnosis']['entries'][1]['maintenance']['mode']);
        self::assertSame('upcoming', $payload['diagnosis']['entries'][1]['notice']['effective']);
    }

    public function testDiagnoseActionUsesLocalFormattedUiDateAndTimeAsSimulationTime(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [
                    'show_info' => 'never',
                    'show_info_from' => ['date' => '01.01.1970', 'time' => '00:00'],
                    'planned' => [
                        'from' => ['date' => '01.01.1970', 'time' => '00:00'],
                        'to' => ['date' => '01.01.1970', 'time' => '00:00'],
                    ],
                    'document' => '',
                ],
                'custom' => [],
            ]);
        $settingsStore->expects(self::never())->method('save');
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $headLink = $this
            ->getMockBuilder(HeadLink::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $headLink
            ->expects(self::once())
            ->method('__call')
            ->with('appendStylesheet', ['/bundles/weblizardscustommaintenance/css/frontend.css']);

        $statusService = new StatusService(new MaintenanceConfigManager($settingsStore, $legacyLoader), $headLink);
        $controller = new AdminpanelController();
        $request = new Request([
            'data' => json_encode([
                'diagnosis_reference_date' => '2026-07-17',
                'diagnosis_reference_time' => '01:30',
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
            ], JSON_THROW_ON_ERROR),
        ]);

        $response = $controller->diagnoseAction($request, $statusService);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($payload['success']);
        self::assertSame('17.07.2026 01:30', $payload['diagnosis']['evaluated_at']);
    }
}
