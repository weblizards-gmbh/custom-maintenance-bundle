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
