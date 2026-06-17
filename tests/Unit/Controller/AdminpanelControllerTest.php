<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Controller;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Controller\UserAwareController;
use Pimcore\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Weblizards\CustomMaintenanceBundle\Controller\AdminpanelController;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\ConfigPersistenceInterface;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\LegacyConfigLoaderInterface;
use Weblizards\CustomMaintenanceBundle\Service\MaintenanceConfigManager;

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

        $controller = new AdminpanelController();
        $configManager = new MaintenanceConfigManager($settingsStore, $legacyLoader);
        $request = new Request([
            'data' => json_encode([
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
            ], JSON_THROW_ON_ERROR),
        ]);

        $response = $controller->saveAction($request, $configManager, $translator);

        self::assertSame(
            [
                'success' => true,
                'message' => 'Speicher erfolgreich',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
        self::assertSame('Upcoming %s %s', $persistedData['frontend']['indication_upcoming']['de']);
        self::assertSame('automatic', $persistedData['pimcore']['show_info']);
        self::assertSame('01.02.2026', $persistedData['pimcore']['show_info_from']['date']);
        self::assertSame('07:15', $persistedData['pimcore']['show_info_from']['time']);
        self::assertSame('/de/pimcore', $persistedData['pimcore']['document']);
    }
}
