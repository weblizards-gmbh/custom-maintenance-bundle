<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Twig;

use PHPUnit\Framework\TestCase;
use Pimcore\Twig\Extension\Templating\HeadLink;
use Psr\Log\AbstractLogger;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\ConfigPersistenceInterface;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\LegacyConfigLoaderInterface;
use Weblizards\CustomMaintenanceBundle\Service\MaintenanceConfigManager;
use Weblizards\CustomMaintenanceBundle\Service\StatusService;
use Weblizards\CustomMaintenanceBundle\Twig\Extensions;

final class ExtensionsTest extends TestCase
{
    public function testTwigExtensionKeepsExpectedRuntimeFunctionNamesAndCallbacks(): void
    {
        $extension = new Extensions(
            new Environment(new ArrayLoader()),
            $this->createMock(StatusService::class)
        );

        $functions = [];
        foreach ($extension->getFunctions() as $function) {
            $functions[$function->getName()] = $function;
        }

        self::assertSame(
            [
                'indicateCustomMaintenance',
                'indicateUpcomingMaintenance',
                'indicateCurrentMaintenance',
                'isMaintenanceActive',
            ],
            array_keys($functions)
        );
        self::assertSame([$extension, 'isActive'], $functions['isMaintenanceActive']->getCallable());
    }

    public function testIsMaintenanceActiveDelegatesToSharedStatusService(): void
    {
        $statusService = $this->createMock(StatusService::class);
        $statusService
            ->expects(self::once())
            ->method('isActive')
            ->with('prices')
            ->willReturn(true);

        $extension = new Extensions(new Environment(new ArrayLoader()), $statusService);

        self::assertTrue($extension->isActive('prices'));
    }

    public function testIsMaintenanceActiveMatchesRealStatusServiceEvaluationForKnownTokens(): void
    {
        $store = new class($this->buildRuntimeConfig(StatusService::STATUS_ACTIVE)) implements ConfigPersistenceInterface {
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

        $statusService = new StatusService(
            new MaintenanceConfigManager($store, $legacyLoader),
            $this->createHeadLinkMock(),
            $this->createRecordingLogger()
        );
        $extension = new Extensions(new Environment(new ArrayLoader()), $statusService);

        self::assertSame($statusService->isActive('prices'), $extension->isActive('prices'));
        self::assertSame($statusService->isActive(), $extension->isActive());
    }

    public function testIsMaintenanceActiveLogsUnknownTokensThroughSharedStatusService(): void
    {
        $store = new class($this->buildRuntimeConfig(StatusService::STATUS_ACTIVE)) implements ConfigPersistenceInterface {
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

        $logger = $this->createRecordingLogger();
        $statusService = new StatusService(
            new MaintenanceConfigManager($store, $legacyLoader),
            $this->createHeadLinkMock(),
            $logger
        );
        $extension = new Extensions(new Environment(new ArrayLoader()), $statusService);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid token: prices-legacy');

        try {
            $extension->isActive('prices-legacy');
        } finally {
            self::assertCount(1, $logger->records);
            self::assertSame('prices-legacy', $logger->records[0]['context']['token']);
            self::assertStringContainsString('unknown or deleted', $logger->records[0]['message']);
        }
    }

    public function testNoticeFunctionsDelegateToTheSharedStatusServiceUnchanged(): void
    {
        $statusService = $this->createMock(StatusService::class);
        $statusService
            ->expects(self::once())
            ->method('indicateUpcomingMaintenance')
            ->with(self::isInstanceOf(Environment::class))
            ->willReturn('<div>upcoming</div>');
        $statusService
            ->expects(self::once())
            ->method('indicateCurrentMaintenance')
            ->with(self::isInstanceOf(Environment::class))
            ->willReturn('<div>current</div>');

        $environment = new Environment(new ArrayLoader());
        $extension = new Extensions($environment, $statusService);

        self::assertSame('<div>upcoming</div>', $extension->indicateUpcomingMaintenance());
        self::assertSame('<div>current</div>', $extension->indicateCurrentMaintenance());
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

    private function buildRuntimeConfig(string $activeStatus): array
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
