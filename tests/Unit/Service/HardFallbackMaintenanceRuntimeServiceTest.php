<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Service;

use PHPUnit\Framework\TestCase;
use Weblizards\CustomMaintenanceBundle\Service\HardFallbackMaintenanceRuntimeService;

final class HardFallbackMaintenanceRuntimeServiceTest extends TestCase
{
    public function testSynchronizeActivationCreatesGlobalAndSessionFlagsForBrowserSession(): void
    {
        $runtimeDirectory = sys_get_temp_dir() . '/custom-maintenance-runtime-' . uniqid('', true);
        $maintenanceFile = $runtimeDirectory . '-maintenance.php';
        file_put_contents($maintenanceFile, "<?php return ['sessionId' => 'abc123'];");

        $service = new HardFallbackMaintenanceRuntimeService(
            $runtimeDirectory,
            ['10.0.0.5', '127.0.0.1'],
            $maintenanceFile
        );

        $state = $service->synchronizeActivation();

        self::assertTrue($state['active']);
        self::assertSame(['127.0.0.1', '10.0.0.5'], $state['allowed_ips']);
        self::assertTrue($state['session_bypass']['enabled']);
        self::assertSame('enabled', $state['session_bypass']['reason']);
        self::assertFileExists($runtimeDirectory . '/maintenance-active.flag');
        self::assertFileExists($runtimeDirectory . '/session-abc123.flag');
        self::assertFileExists($runtimeDirectory . '/state.json');

        $persistedState = json_decode((string) file_get_contents($runtimeDirectory . '/state.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertTrue($persistedState['active']);
        self::assertSame('abc123', $persistedState['session_bypass']['session_id']);

        @unlink($maintenanceFile);
        @unlink($runtimeDirectory . '/maintenance-active.flag');
        @unlink($runtimeDirectory . '/session-abc123.flag');
        @unlink($runtimeDirectory . '/state.json');
        @rmdir($runtimeDirectory);
    }

    public function testSynchronizeActivationSkipsSessionBypassForDummySessionId(): void
    {
        $runtimeDirectory = sys_get_temp_dir() . '/custom-maintenance-runtime-' . uniqid('', true);
        $maintenanceFile = $runtimeDirectory . '-maintenance.php';
        file_put_contents($maintenanceFile, "<?php return ['sessionId' => 'command-line-dummy-session-id'];");

        $service = new HardFallbackMaintenanceRuntimeService($runtimeDirectory, [], $maintenanceFile);

        $state = $service->synchronizeActivation();

        self::assertTrue($state['active']);
        self::assertFalse($state['session_bypass']['enabled']);
        self::assertSame('dummy_session_id', $state['session_bypass']['reason']);
        self::assertFileExists($runtimeDirectory . '/maintenance-active.flag');
        self::assertFileDoesNotExist($runtimeDirectory . '/session-command-line-dummy-session-id.flag');

        @unlink($maintenanceFile);
        @unlink($runtimeDirectory . '/maintenance-active.flag');
        @unlink($runtimeDirectory . '/state.json');
        @rmdir($runtimeDirectory);
    }

    public function testSynchronizeDeactivationRemovesFlagsAndWritesInactiveState(): void
    {
        $runtimeDirectory = sys_get_temp_dir() . '/custom-maintenance-runtime-' . uniqid('', true);
        mkdir($runtimeDirectory, 0777, true);
        file_put_contents($runtimeDirectory . '/maintenance-active.flag', "1\n");
        file_put_contents($runtimeDirectory . '/session-abc123.flag', "1\n");

        $service = new HardFallbackMaintenanceRuntimeService($runtimeDirectory, ['10.0.0.5'], $runtimeDirectory . '/missing-maintenance.php');

        $state = $service->synchronizeDeactivation();

        self::assertFalse($state['active']);
        self::assertFileDoesNotExist($runtimeDirectory . '/maintenance-active.flag');
        self::assertFileDoesNotExist($runtimeDirectory . '/session-abc123.flag');
        self::assertFileExists($runtimeDirectory . '/state.json');

        $persistedState = json_decode((string) file_get_contents($runtimeDirectory . '/state.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($persistedState['active']);
        self::assertSame('maintenance_disabled', $persistedState['session_bypass']['reason']);

        @unlink($runtimeDirectory . '/state.json');
        @rmdir($runtimeDirectory);
    }
}
