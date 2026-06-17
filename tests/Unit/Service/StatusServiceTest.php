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
    public function testManualActiveStatusIsEvaluatedFromCanonicalConfigOutsideTimeslot(): void
    {
        Carbon::setTestNow('2026-02-01 12:30');

        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_ACTIVE));

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $service = new StatusService(new MaintenanceConfigManager($settingsStore, $legacyLoader), $this->createHeadLinkMock());

        self::assertSame(StatusService::STATUS_ACTIVE, $service->getStatus('prices'));
        self::assertTrue($service->isActive('prices'));

        Carbon::setTestNow();
    }

    public function testManualInactiveStatusIsEvaluatedFromCanonicalConfigOutsideTimeslot(): void
    {
        Carbon::setTestNow('2026-02-01 12:30');

        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_INACTIVE));

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $service = new StatusService(new MaintenanceConfigManager($settingsStore, $legacyLoader), $this->createHeadLinkMock());

        self::assertSame(StatusService::STATUS_INACTIVE, $service->getStatus('prices'));
        self::assertFalse($service->isActive('prices'));

        Carbon::setTestNow();
    }

    public function testSetStatusPersistsAndIsReadBackThroughTheSameCanonicalConfigurationPath(): void
    {
        Carbon::setTestNow('2026-02-01 12:30');

        $store = new class($this->buildStatusServiceConfig(StatusService::STATUS_INACTIVE)) implements ConfigPersistenceInterface {
            private ?array $data;

            public function __construct(?array $data)
            {
                $this->data = $data;
            }

            public function load(): ?array
            {
                return $this->data;
            }

            public function save(array $data): void
            {
                $this->data = $data;
            }
        };

        $legacyLoader = new class() implements LegacyConfigLoaderInterface {
            public function load(): ?array
            {
                return null;
            }
        };

        $service = new StatusService(new MaintenanceConfigManager($store, $legacyLoader), $this->createHeadLinkMock());

        self::assertSame(StatusService::STATUS_INACTIVE, $service->getStatus('prices'));
        self::assertFalse($service->isActive('prices'));

        $service->setStatus('prices', StatusService::STATUS_ACTIVE);

        self::assertSame(StatusService::STATUS_ACTIVE, $service->getStatus('prices'));
        self::assertTrue($service->isActive('prices'));

        $service->setStatus('prices', StatusService::STATUS_INACTIVE);

        self::assertSame(StatusService::STATUS_INACTIVE, $service->getStatus('prices'));
        self::assertFalse($service->isActive('prices'));

        Carbon::setTestNow();
    }

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

    public function testServiceHandlesEmptyScheduleValuesForNewCustomEntries(): void
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
                    'search' => [
                        'active' => StatusService::STATUS_INACTIVE,
                        'fixed' => StatusService::STATUS_INACTIVE,
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

        self::assertFalse($service->isActive('search'));
        self::assertFalse($service->isHandsOff('search'));
        self::assertFalse($service->showUpcoming('search'));

        Carbon::setTestNow();
    }

    private function createHeadLinkMock(): HeadLink
    {
        $headLink = $this
            ->getMockBuilder(HeadLink::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $headLink
            ->expects(self::once())
            ->method('__call')
            ->with('appendStylesheet', ['/bundles/weblizardscustommaintenance/css/frontend.css']);

        return $headLink;
    }

    private function buildStatusServiceConfig(string $activeStatus): array
    {
        return [
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
                    'active' => $activeStatus,
                    'fixed' => StatusService::STATUS_INACTIVE,
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
        ];
    }
}
