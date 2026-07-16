<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Command;

use Pimcore\Twig\Extension\Templating\HeadLink;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;
use Weblizards\CustomMaintenanceBundle\Command\ControlCommand;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\ConfigPersistenceInterface;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\LegacyConfigLoaderInterface;
use Weblizards\CustomMaintenanceBundle\Service\MaintenanceConfigManager;
use Weblizards\CustomMaintenanceBundle\Service\StatusService;

final class ControlCommandTest extends TestCase
{
    public function testDefaultCommandNameRemainsStable(): void
    {
        self::assertSame('weblizards:custommaintenance:control|maintenance', ControlCommand::getDefaultName());
    }

    public function testListTokensUsesTheSharedStatusService(): void
    {
        $statusService = $this->createMock(StatusService::class);
        $statusService
            ->expects(self::once())
            ->method('getValidTokens')
            ->willReturn(['prices', 'orders']);

        $command = new ControlCommand($statusService);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['task' => 'list-tokens']);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Valid tokens are:', $tester->getDisplay());
        self::assertStringContainsString('prices', $tester->getDisplay());
        self::assertStringContainsString('orders', $tester->getDisplay());
    }

    public function testShowStatusSupportsHumanReadableAndPorcelainOutput(): void
    {
        $statusService = $this->createMock(StatusService::class);
        $statusService
            ->expects(self::exactly(2))
            ->method('isActive')
            ->with('prices')
            ->willReturn(true);

        $command = new ControlCommand($statusService);

        $humanTester = new CommandTester($command);
        self::assertSame(0, $humanTester->execute(['task' => 'show-status', '--token' => 'prices']));
        self::assertStringContainsString('Custom Maintenance prices is active', $humanTester->getDisplay());

        $porcelainTester = new CommandTester($command);
        self::assertSame(
            0,
            $porcelainTester->execute(['task' => 'show-status', '--token' => 'prices', '--porcelain' => true])
        );
        self::assertSame("active\n", $porcelainTester->getDisplay());
    }

    public function testActivateAndDeactivateDelegateToStatusService(): void
    {
        $receivedStatuses = [];
        $statusService = $this->createMock(StatusService::class);
        $statusService
            ->expects(self::exactly(2))
            ->method('setStatus')
            ->willReturnCallback(function (string $token, string $status, bool $overrideFixed) use (&$receivedStatuses): void {
                self::assertSame('prices', $token);
                self::assertFalse($overrideFixed);
                $receivedStatuses[] = $status;
            });

        $command = new ControlCommand($statusService);

        $activateTester = new CommandTester($command);
        self::assertSame(0, $activateTester->execute(['task' => 'activate', '--token' => 'prices']));
        self::assertStringContainsString('has been activated', $activateTester->getDisplay());

        $deactivateTester = new CommandTester($command);
        self::assertSame(0, $deactivateTester->execute(['task' => 'deactivate', '--token' => 'prices']));
        self::assertStringContainsString('has been deactivated', $deactivateTester->getDisplay());
        self::assertSame(
            [StatusService::STATUS_ACTIVE, StatusService::STATUS_INACTIVE],
            $receivedStatuses
        );
    }

    public function testOverrideFixedAllowsPorcelainActivation(): void
    {
        $statusService = $this->createMock(StatusService::class);
        $statusService
            ->expects(self::once())
            ->method('setStatus')
            ->with('prices', StatusService::STATUS_ACTIVE, true);

        $command = new ControlCommand($statusService);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'task' => 'activate',
            '--token' => 'prices',
            '--override-fixed' => true,
            '--porcelain' => true,
        ]);

        self::assertSame(0, $exitCode);
        self::assertSame("OK\n", $tester->getDisplay());
    }

    public function testDeactivateReturnsErrorWhenFixedProtectionBlocksRollback(): void
    {
        $command = $this->createRealControlCommand(
            $this->buildCliConfig(StatusService::STATUS_INACTIVE, StatusService::STATUS_ACTIVE, StatusService::STATUS_ACTIVE)
        );
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['task' => 'deactivate', '--token' => 'prices', '--porcelain' => true]);

        self::assertSame(1, $exitCode);

        $showTester = new CommandTester($command);
        self::assertSame(0, $showTester->execute(['task' => 'show-status', '--token' => 'prices', '--porcelain' => true]));
        self::assertSame("active\n", $showTester->getDisplay());
    }

    public function testOverrideFixedAllowsPorcelainDeactivationViaSharedStatusPath(): void
    {
        $command = $this->createRealControlCommand(
            $this->buildCliConfig(StatusService::STATUS_INACTIVE, StatusService::STATUS_ACTIVE, StatusService::STATUS_ACTIVE)
        );

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            'task' => 'deactivate',
            '--token' => 'prices',
            '--override-fixed' => true,
            '--porcelain' => true,
        ]);

        self::assertSame(0, $exitCode);
        self::assertSame("OK\n", $tester->getDisplay());

        $showTester = new CommandTester($command);
        self::assertSame(0, $showTester->execute(['task' => 'show-status', '--token' => 'prices', '--porcelain' => true]));
        self::assertSame("inactive\n", $showTester->getDisplay());
    }

    public function testListTokensReadsTokensFromCanonicalConfigurationSource(): void
    {
        $command = $this->createRealControlCommand($this->buildCliConfig(StatusService::STATUS_INACTIVE));
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['task' => 'list-tokens', '--porcelain' => true]);

        self::assertSame(0, $exitCode);
        self::assertSame("prices\norders\n", $tester->getDisplay());
    }

    public function testShowStatusAndActivationCycleUseTheSharedCanonicalStatusPath(): void
    {
        $command = $this->createRealControlCommand($this->buildCliConfig(StatusService::STATUS_INACTIVE));

        $showBeforeTester = new CommandTester($command);
        self::assertSame(0, $showBeforeTester->execute(['task' => 'show-status', '--token' => 'prices', '--porcelain' => true]));
        self::assertSame("inactive\n", $showBeforeTester->getDisplay());

        $activateTester = new CommandTester($command);
        self::assertSame(0, $activateTester->execute(['task' => 'activate', '--token' => 'prices', '--porcelain' => true]));
        self::assertSame("OK\n", $activateTester->getDisplay());

        $showAfterActivateTester = new CommandTester($command);
        self::assertSame(
            0,
            $showAfterActivateTester->execute(['task' => 'show-status', '--token' => 'prices', '--porcelain' => true])
        );
        self::assertSame("active\n", $showAfterActivateTester->getDisplay());

        $deactivateTester = new CommandTester($command);
        self::assertSame(
            0,
            $deactivateTester->execute(['task' => 'deactivate', '--token' => 'prices', '--porcelain' => true])
        );
        self::assertSame("OK\n", $deactivateTester->getDisplay());

        $showAfterDeactivateTester = new CommandTester($command);
        self::assertSame(
            0,
            $showAfterDeactivateTester->execute(['task' => 'show-status', '--token' => 'prices', '--porcelain' => true])
        );
        self::assertSame("inactive\n", $showAfterDeactivateTester->getDisplay());
    }

    public function testShowStatusUsesEffectiveStatusForOpenEndedTimeControlledMaintenance(): void
    {
        $command = $this->createRealControlCommand([
            'frontend' => [
                'indication_upcoming' => ['de' => 'Upcoming %s %s'],
                'indication_current' => ['de' => 'Current %s %s'],
                'more' => ['de' => 'Mehr'],
                'fulltimeformat' => ['de' => 'd.m.Y H:i'],
            ],
            'pimcore' => [
                'show_info' => 'never',
                'show_info_from' => ['date' => '01.01.1970', 'time' => '00:00'],
                'planned' => [
                    'from' => ['date' => '01.01.1970', 'time' => '00:00'],
                    'to' => ['date' => '01.01.1970', 'time' => '00:00'],
                ],
                'document' => '',
            ],
            'custom' => [
                'prices' => [
                    'active' => StatusService::STATUS_INACTIVE,
                    'fixed' => StatusService::STATUS_INACTIVE,
                    'description' => 'ERP',
                    'show_info' => 'never',
                    'show_info_from' => ['date' => '01.01.1970', 'time' => '00:00'],
                    'planned' => [
                        'from' => ['date' => '01.02.2026', 'time' => '09:00'],
                        'to' => ['date' => '', 'time' => ''],
                    ],
                    'document' => '',
                ],
                'orders' => [
                    'active' => StatusService::STATUS_INACTIVE,
                    'fixed' => StatusService::STATUS_INACTIVE,
                    'description' => 'Orders',
                    'show_info' => 'never',
                    'show_info_from' => ['date' => '01.01.1970', 'time' => '00:00'],
                    'planned' => [
                        'from' => ['date' => '01.01.1970', 'time' => '00:00'],
                        'to' => ['date' => '01.01.1970', 'time' => '00:00'],
                    ],
                    'document' => '',
                ],
            ],
        ]);

        $tester = new CommandTester($command);
        \Carbon\Carbon::setTestNow('2026-02-01 12:30');

        try {
            self::assertSame(0, $tester->execute(['task' => 'show-status', '--token' => 'prices', '--porcelain' => true]));
            self::assertSame("active\n", $tester->getDisplay());
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    private function createRealControlCommand(?array $initialConfig): ControlCommand
    {
        $store = new class($initialConfig) implements ConfigPersistenceInterface {
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

        $headLink = $this
            ->getMockBuilder(HeadLink::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__call'])
            ->getMock();
        $headLink
            ->expects(self::once())
            ->method('__call')
            ->with('appendStylesheet', ['/bundles/weblizardscustommaintenance/css/frontend.css']);

        $statusService = new StatusService(new MaintenanceConfigManager($store, $legacyLoader), $headLink, new NullLogger());

        return new ControlCommand($statusService);
    }

    private function buildCliConfig(
        string $activeStatus,
        string $fixedStatus = StatusService::STATUS_INACTIVE,
        string $temporaryActiveStatus = StatusService::STATUS_INACTIVE
    ): array
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
                'show_info_from' => ['date' => '01.01.1970', 'time' => '00:00'],
                'planned' => [
                    'from' => ['date' => '01.01.1970', 'time' => '00:00'],
                    'to' => ['date' => '01.01.1970', 'time' => '00:00'],
                ],
                'document' => '',
            ],
            'custom' => [
                'prices' => [
                    'active' => $activeStatus,
                    'temporary_active' => $temporaryActiveStatus,
                    'fixed' => $fixedStatus,
                    'description' => 'ERP',
                    'show_info' => 'never',
                    'show_info_from' => ['date' => '01.01.1970', 'time' => '00:00'],
                    'planned' => [
                        'from' => ['date' => '01.02.2026', 'time' => '09:00'],
                        'to' => ['date' => '01.02.2026', 'time' => '11:00'],
                    ],
                    'document' => '',
                ],
                'orders' => [
                    'active' => StatusService::STATUS_INACTIVE,
                    'temporary_active' => StatusService::STATUS_INACTIVE,
                    'fixed' => StatusService::STATUS_INACTIVE,
                    'description' => 'Orders',
                    'show_info' => 'never',
                    'show_info_from' => ['date' => '01.01.1970', 'time' => '00:00'],
                    'planned' => [
                        'from' => ['date' => '01.01.1970', 'time' => '00:00'],
                        'to' => ['date' => '01.01.1970', 'time' => '00:00'],
                    ],
                    'document' => '',
                ],
            ],
        ];
    }
}
