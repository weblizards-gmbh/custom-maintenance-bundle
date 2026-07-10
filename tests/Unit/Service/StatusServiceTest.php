<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Service;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Pimcore\Twig\Extension\Templating\HeadLink;
use Psr\Log\AbstractLogger;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\ConfigPersistenceInterface;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\LegacyConfigLoaderInterface;
use Weblizards\CustomMaintenanceBundle\Service\MaintenanceConfigManager;
use Weblizards\CustomMaintenanceBundle\Service\StatusService;

final class StatusServiceTest extends TestCase
{
    public function testPlannedTimeslotActivatesMaintenanceWithinWindowEvenIfManualStatusIsInactive(): void
    {
        Carbon::setTestNow('2026-02-01 10:30');

        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_INACTIVE));

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $service = new StatusService(new MaintenanceConfigManager($settingsStore, $legacyLoader), $this->createHeadLinkMock());

        self::assertSame(StatusService::STATUS_INACTIVE, $service->getStatus('prices'));
        self::assertTrue($service->isActive('prices'));

        Carbon::setTestNow();
    }

    public function testPlannedTimeslotDoesNotActivateMaintenanceOutsideWindow(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_INACTIVE));

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $service = new StatusService(new MaintenanceConfigManager($settingsStore, $legacyLoader), $this->createHeadLinkMock());

        Carbon::setTestNow('2026-02-01 08:59');
        self::assertFalse($service->isActive('prices'));

        Carbon::setTestNow('2026-02-01 09:00');
        self::assertTrue($service->isActive('prices'));

        Carbon::setTestNow('2026-02-01 11:00');
        self::assertTrue($service->isActive('prices'));

        Carbon::setTestNow('2026-02-01 11:01');
        self::assertFalse($service->isActive('prices'));

        Carbon::setTestNow();
    }

    public function testConfiguredTimeslotFormatsRemainCompatibleWithBundleFormat(): void
    {
        Carbon::setTestNow('2026-02-01 10:30');

        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_INACTIVE));

        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $service = new StatusService(new MaintenanceConfigManager($settingsStore, $legacyLoader), $this->createHeadLinkMock());

        self::assertSame('01.02.2026 09:00', $service->getMaintenanceFrom('prices')->format('d.m.Y H:i'));
        self::assertSame('01.02.2026 11:00', $service->getMaintenanceTo('prices')->format('d.m.Y H:i'));

        Carbon::setTestNow();
    }

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

    public function testSetStatusBlocksFixedMaintenancesWithoutOverride(): void
    {
        $store = new class([
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
        ]) implements ConfigPersistenceInterface {
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

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Admin priority mode is active, nothing changed');

        try {
            $service->setStatus('prices', StatusService::STATUS_INACTIVE);
        } finally {
            self::assertSame(StatusService::STATUS_ACTIVE, $service->getStatus('prices'));
        }
    }

    public function testSetStatusAllowsFixedMaintenancesWithOverride(): void
    {
        $store = new class([
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
        ]) implements ConfigPersistenceInterface {
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

        $service->setStatus('prices', StatusService::STATUS_INACTIVE, true);

        self::assertSame(StatusService::STATUS_INACTIVE, $service->getStatus('prices'));
    }

    public function testSetStatusAlsoBlocksActivationForFixedMaintenancesWithoutOverride(): void
    {
        $store = new class([
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
        ]) implements ConfigPersistenceInterface {
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

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Admin priority mode is active, nothing changed');

        try {
            $service->setStatus('prices', StatusService::STATUS_ACTIVE);
        } finally {
            self::assertSame(StatusService::STATUS_INACTIVE, $service->getStatus('prices'));
        }
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

    public function testIsActiveWithoutTokenAggregatesAcrossCustomMaintenances(): void
    {
        Carbon::setTestNow('2026-02-01 12:30');

        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::exactly(2))
            ->method('load')
            ->willReturnOnConsecutiveCalls(
                $this->buildMultiTokenStatusServiceConfig(
                    StatusService::STATUS_ACTIVE,
                    StatusService::STATUS_INACTIVE
                ),
                $this->buildMultiTokenStatusServiceConfig(
                    StatusService::STATUS_INACTIVE,
                    StatusService::STATUS_INACTIVE
                )
            );
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $serviceWithActiveToken = new StatusService(
            new MaintenanceConfigManager($settingsStore, $legacyLoader),
            $this->createHeadLinkMock()
        );
        self::assertTrue($serviceWithActiveToken->isActive());

        $serviceWithoutActiveToken = new StatusService(
            new MaintenanceConfigManager($settingsStore, $legacyLoader),
            $this->createHeadLinkMock()
        );
        self::assertFalse($serviceWithoutActiveToken->isActive());

        Carbon::setTestNow();
    }

    public function testIsActiveTreatsPimcoreAsNonRuntimeTokenEvenDuringGlobalEvaluation(): void
    {
        Carbon::setTestNow('2026-01-01 00:30');

        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_INACTIVE));
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $service = new StatusService(
            new MaintenanceConfigManager($settingsStore, $legacyLoader),
            $this->createHeadLinkMock()
        );

        self::assertFalse($service->isActive('pimcore'));
        self::assertFalse($service->isActive());

        Carbon::setTestNow();
    }

    public function testGetStatusLogsUnknownTokenAsRuntimeError(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_ACTIVE));
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');
        $logger = $this->createRecordingLogger();

        $service = new StatusService(
            new MaintenanceConfigManager($settingsStore, $legacyLoader),
            $this->createHeadLinkMock(),
            $logger
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid token: ghost');

        try {
            $service->getStatus('ghost');
        } finally {
            self::assertCount(1, $logger->records);
            self::assertSame('error', $logger->records[0]['level']);
            self::assertSame('ghost', $logger->records[0]['context']['token']);
            self::assertStringContainsString('unknown or deleted', $logger->records[0]['message']);
        }
    }

    public function testGetStatusKeepsPimcoreExcludedFromTheCustomTokenContract(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore->expects(self::never())->method('load');
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');
        $logger = $this->createRecordingLogger();

        $service = new StatusService(
            new MaintenanceConfigManager($settingsStore, $legacyLoader),
            $this->createHeadLinkMock(),
            $logger
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid token: pimcore');

        try {
            $service->getStatus('pimcore');
        } finally {
            self::assertCount(0, $logger->records);
        }
    }

    public function testUnknownAndDeletedReferenceTokensShareTheSameLoggingSemantics(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_ACTIVE));
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');
        $logger = $this->createRecordingLogger();

        $service = new StatusService(
            new MaintenanceConfigManager($settingsStore, $legacyLoader),
            $this->createHeadLinkMock(),
            $logger
        );

        foreach (['ghost', 'prices-legacy'] as $token) {
            try {
                $service->isActive($token);
                self::fail('Expected unknown token exception for ' . $token);
            } catch (\Exception $exception) {
                self::assertSame('Invalid token: ' . $token, $exception->getMessage());
            }
        }

        self::assertCount(2, $logger->records);
        self::assertSame('ghost', $logger->records[0]['context']['token']);
        self::assertSame('prices-legacy', $logger->records[1]['context']['token']);
        self::assertStringContainsString('unknown or deleted', $logger->records[0]['message']);
        self::assertStringContainsString('unknown or deleted', $logger->records[1]['message']);
    }

    public function testIndicateUpcomingMaintenanceRendersCanonicalTwigTemplateWithLinkPayload(): void
    {
        Carbon::setTestNow('2026-02-01 08:30');

        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_INACTIVE));
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $engine = new Environment(new ArrayLoader([
            '@WeblizardsCustomMaintenance/partials/indicateupcoming.html.twig' => '{{ message }}|{{ link.caption }}|{{ link.url }}',
        ]));

        $service = new StatusService(
            new MaintenanceConfigManager($settingsStore, $legacyLoader),
            $this->createHeadLinkMock()
        );

        self::assertSame(
            'Upcoming 01.02.2026 09:00 01.02.2026 11:00|Mehr|/de/erp',
            $service->indicateUpcomingMaintenance($engine)
        );

        Carbon::setTestNow();
    }

    public function testIndicateUpcomingMaintenanceUsesInjectedTemplatePath(): void
    {
        Carbon::setTestNow('2026-02-01 08:30');

        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_INACTIVE));
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $engine = new Environment(new ArrayLoader([
            '@App/custom/upcoming.html.twig' => 'custom-template|{{ message }}',
        ]));

        $service = new StatusService(
            new MaintenanceConfigManager($settingsStore, $legacyLoader),
            $this->createHeadLinkMock(),
            null,
            '@App/custom/upcoming.html.twig'
        );

        self::assertSame(
            'custom-template|Upcoming 01.02.2026 09:00 01.02.2026 11:00',
            $service->indicateUpcomingMaintenance($engine)
        );

        Carbon::setTestNow();
    }

    public function testIndicateCurrentMaintenanceRendersCanonicalTwigTemplateWithoutPhpFallbackOrLinkStub(): void
    {
        Carbon::setTestNow('2026-02-01 10:30');

        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn($this->buildStatusServiceConfig(StatusService::STATUS_ACTIVE, ''));
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $engine = new Environment(new ArrayLoader([
            '@WeblizardsCustomMaintenance/partials/indicatecurrent.html.twig' => '{{ message }}|{% if link is null %}NULL{% else %}NOT_NULL{% endif %}',
        ]));

        $service = new StatusService(
            new MaintenanceConfigManager($settingsStore, $legacyLoader),
            $this->createHeadLinkMock()
        );

        self::assertSame(
            'Current 01.02.2026 09:00 01.02.2026 11:00|NULL',
            $service->indicateCurrentMaintenance($engine)
        );

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

    private function createRecordingLogger(): AbstractLogger
    {
        return new class() extends AbstractLogger {
            /**
             * @var array<int, array{level:string, message:string, context:array<string, mixed>}>
             */
            public array $records = [];

            public function log($level, $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => (string) $level,
                    'message' => (string) $message,
                    'context' => $context,
                ];
            }
        };
    }

    private function buildStatusServiceConfig(string $activeStatus, string $documentPath = '/de/erp'): array
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
                    'document' => $documentPath,
                ],
            ],
        ];
    }

    private function buildMultiTokenStatusServiceConfig(string $pricesStatus, string $searchStatus): array
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
                    'active' => $pricesStatus,
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
                'search' => [
                    'active' => $searchStatus,
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
        ];
    }
}
