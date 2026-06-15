<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Service;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Pimcore\Twig\Extension\Templating\HeadLink;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\ConfigPersistenceInterface;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\LegacyConfigLoaderInterface;
use Weblizards\CustomMaintenanceBundle\Service\MaintenanceConfigManager;
use Weblizards\CustomMaintenanceBundle\Service\StatusService;

final class StatusServiceTest extends TestCase
{
    public function testServiceEvaluatesStatusesAgainstTheDomainModel(): void
    {
        Carbon::setTestNow('2026-02-01 10:30');

        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [
                    'indication_upcoming' => ['de' => 'Upcoming %s %s'],
                    'indication_current' => ['de' => 'Current %s %s'],
                    'more' => ['de' => 'Mehr'],
                    'fulltimeformat' => ['de' => 'd.m.Y H:i'],
                ],
                'pimcore' => [
                    'show_info' => 'never',
                    'show_info_from' => ['date' => '01.01.2026', 'time' => '00:00'],
                    'planned' => [
                        'from' => ['date' => '01.01.2026', 'time' => '00:00'],
                        'to' => ['date' => '01.01.2026', 'time' => '01:00'],
                    ],
                    'document' => '',
                ],
                'custom' => [
                    'prices' => [
                        'active' => StatusService::STATUS_ACTIVE,
                        'fixed' => StatusService::STATUS_ACTIVE,
                        'description' => 'ERP',
                        'show_info' => 'always',
                        'show_info_from' => ['date' => '01.02.2026', 'time' => '08:00'],
                        'planned' => [
                            'from' => ['date' => '01.02.2026', 'time' => '09:00'],
                            'to' => ['date' => '01.02.2026', 'time' => '11:00'],
                        ],
                        'document' => '/de/erp',
                    ],
                ],
            ]);
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');
        $manager = new MaintenanceConfigManager($settingsStore, $legacyLoader);

        $headLink = $this
            ->getMockBuilder(HeadLink::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $headLink
            ->expects(self::once())
            ->method('__call')
            ->with('appendStylesheet', ['/bundles/weblizardscustommaintenance/css/frontend.css']);

        $service = new StatusService($manager, $headLink);

        self::assertSame(['prices'], $service->getValidTokens());
        self::assertSame(StatusService::STATUS_ACTIVE, $service->getStatus('prices'));
        self::assertTrue($service->isActive('prices'));
        self::assertTrue($service->isFixedMode('prices'));
        self::assertTrue($service->isHandsOff('prices'));
        self::assertTrue($service->showUpcoming('prices'));
        self::assertSame('/de/erp', $service->getDocumentPath('prices'));

        Carbon::setTestNow();
    }

    public function testHandsOffFallsBackToTimeslotEvaluationForNonFixedMaintenances(): void
    {
        Carbon::setTestNow('2026-02-01 10:30');

        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [
                    'indication_upcoming' => ['de' => 'Upcoming %s %s'],
                    'indication_current' => ['de' => 'Current %s %s'],
                    'more' => ['de' => 'Mehr'],
                    'fulltimeformat' => ['de' => 'd.m.Y H:i'],
                ],
                'pimcore' => [
                    'show_info' => 'never',
                    'show_info_from' => ['date' => '01.01.2026', 'time' => '00:00'],
                    'planned' => [
                        'from' => ['date' => '01.01.2026', 'time' => '00:00'],
                        'to' => ['date' => '01.01.2026', 'time' => '01:00'],
                    ],
                    'document' => '',
                ],
                'custom' => [
                    'prices' => [
                        'active' => StatusService::STATUS_INACTIVE,
                        'fixed' => StatusService::STATUS_INACTIVE,
                        'description' => 'ERP',
                        'show_info' => 'automatic',
                        'show_info_from' => ['date' => '01.02.2026', 'time' => '08:00'],
                        'planned' => [
                            'from' => ['date' => '01.02.2026', 'time' => '09:00'],
                            'to' => ['date' => '01.02.2026', 'time' => '11:00'],
                        ],
                        'document' => '/de/erp',
                    ],
                ],
            ]);
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

        $service = new StatusService(new MaintenanceConfigManager($settingsStore, $legacyLoader), $headLink);

        self::assertTrue($service->isHandsOff('prices'));

        Carbon::setTestNow();
    }
}
