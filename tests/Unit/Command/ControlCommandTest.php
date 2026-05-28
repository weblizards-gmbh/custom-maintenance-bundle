<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Weblizards\CustomMaintenanceBundle\Command\ControlCommand;
use Weblizards\CustomMaintenanceBundle\Service\StatusService;

final class ControlCommandTest extends TestCase
{
    public function testDefaultCommandNameRemainsStable(): void
    {
        self::assertSame('weblizards:custommaintenance:control', ControlCommand::getDefaultName());
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
            ->method('getStatus')
            ->with('prices')
            ->willReturn(StatusService::STATUS_ACTIVE);

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
            ->method('isFixedMode')
            ->with('prices')
            ->willReturn(false);
        $statusService
            ->expects(self::exactly(2))
            ->method('setStatus')
            ->willReturnCallback(function (string $token, string $status) use (&$receivedStatuses): void {
                self::assertSame('prices', $token);
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
            ->method('isFixedMode')
            ->with('prices')
            ->willReturn(true);
        $statusService
            ->expects(self::once())
            ->method('setStatus')
            ->with('prices', StatusService::STATUS_ACTIVE);

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
}
