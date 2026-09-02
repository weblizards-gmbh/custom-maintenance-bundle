<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Service;

use Pimcore\Tool\Admin;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class HardFallbackMaintenanceRuntimeService
{
    private const ACTIVE_FLAG = 'maintenance-active.flag';

    private const STATE_FILE = 'state.json';

    /**
     * @var string[]
     */
    private const DUMMY_SESSION_IDS = [
        'command-line-dummy-session-id',
        'cache-warming-dummy-session-id',
    ];

    private string $runtimeDirectory;

    /**
     * @var string[]
     */
    private array $additionalAllowedIps;

    private string $maintenanceModeFile;

    private LoggerInterface $logger;

    /**
     * @param string[] $additionalAllowedIps
     */
    public function __construct(
        string $runtimeDirectory,
        array $additionalAllowedIps = [],
        ?string $maintenanceModeFile = null,
        ?LoggerInterface $logger = null
    )
    {
        $this->runtimeDirectory = rtrim($runtimeDirectory, '/');
        $this->additionalAllowedIps = array_values(array_unique(array_filter(array_map('strval', $additionalAllowedIps))));
        $this->maintenanceModeFile = $maintenanceModeFile ?? Admin::getMaintenanceModeFile();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @return array<string, mixed>
     */
    public function synchronizeActivation(): array
    {
        $this->ensureRuntimeDirectoryExists();
        $this->removeSessionFlags();

        $sessionId = null;
        $sessionBypassEnabled = false;
        $sessionBypassReason = 'missing_maintenance_file';

        if (is_file($this->maintenanceModeFile)) {
            $configuration = include $this->maintenanceModeFile;
            if (is_array($configuration) && isset($configuration['sessionId']) && is_string($configuration['sessionId'])) {
                $sessionId = $configuration['sessionId'];
                $sessionBypassReason = $this->resolveSessionBypassReason($sessionId);
                if ($sessionBypassReason === 'enabled') {
                    $this->writeFile($this->getSessionFlagPath($sessionId), "1\n");
                    $sessionBypassEnabled = true;
                }
            } else {
                $sessionBypassReason = 'invalid_maintenance_file';
            }
        }

        $this->writeFile($this->getActiveFlagPath(), "1\n");

        $state = [
            'active' => true,
            'updated_at' => date(DATE_ATOM),
            'maintenance_mode_file' => $this->maintenanceModeFile,
            'runtime_directory' => $this->runtimeDirectory,
            'allowed_ips' => $this->getAllowedIps(),
            'session_bypass' => [
                'enabled' => $sessionBypassEnabled,
                'session_id' => $sessionBypassEnabled ? $sessionId : null,
                'reason' => $sessionBypassReason,
            ],
        ];
        $this->writeStateFile($state);

        $this->logger->info('Hard fallback maintenance runtime artifacts activated.', $state);

        return $state;
    }

    /**
     * @return array<string, mixed>
     */
    public function synchronizeDeactivation(): array
    {
        $this->ensureRuntimeDirectoryExists();

        $this->removeFileIfExists($this->getActiveFlagPath());
        $this->removeSessionFlags();

        $state = [
            'active' => false,
            'updated_at' => date(DATE_ATOM),
            'maintenance_mode_file' => $this->maintenanceModeFile,
            'runtime_directory' => $this->runtimeDirectory,
            'allowed_ips' => $this->getAllowedIps(),
            'session_bypass' => [
                'enabled' => false,
                'session_id' => null,
                'reason' => 'maintenance_disabled',
            ],
        ];
        $this->writeStateFile($state);

        $this->logger->info('Hard fallback maintenance runtime artifacts deactivated.', $state);

        return $state;
    }

    /**
     * @return string[]
     */
    public function getAllowedIps(): array
    {
        return array_values(array_unique(array_merge(['127.0.0.1'], $this->additionalAllowedIps)));
    }

    private function ensureRuntimeDirectoryExists(): void
    {
        if (!is_dir($this->runtimeDirectory) && !@mkdir($this->runtimeDirectory, 0777, true) && !is_dir($this->runtimeDirectory)) {
            throw new \RuntimeException('Das Runtime-Verzeichnis für harte Fallback-Seiten konnte nicht angelegt werden: ' . $this->runtimeDirectory);
        }
    }

    private function getActiveFlagPath(): string
    {
        return $this->runtimeDirectory . '/' . self::ACTIVE_FLAG;
    }

    private function getStateFilePath(): string
    {
        return $this->runtimeDirectory . '/' . self::STATE_FILE;
    }

    private function getSessionFlagPath(string $sessionId): string
    {
        return $this->runtimeDirectory . '/session-' . $sessionId . '.flag';
    }

    private function removeSessionFlags(): void
    {
        foreach (glob($this->runtimeDirectory . '/session-*.flag') ?: [] as $path) {
            $this->removeFileIfExists($path);
        }
    }

    private function removeFileIfExists(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function writeStateFile(array $state): void
    {
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->writeFile($this->getStateFilePath(), $json . "\n");
    }

    private function writeFile(string $path, string $contents): void
    {
        $temporaryFile = tempnam(dirname($path), 'cmf_');
        if ($temporaryFile === false) {
            throw new \RuntimeException('Temporäre Datei für Runtime-Artefakt konnte nicht erzeugt werden: ' . $path);
        }

        file_put_contents($temporaryFile, $contents);
        @chmod($temporaryFile, 0664);

        if (!@rename($temporaryFile, $path)) {
            @unlink($temporaryFile);

            throw new \RuntimeException('Runtime-Artefakt konnte nicht geschrieben werden: ' . $path);
        }
    }

    private function resolveSessionBypassReason(string $sessionId): string
    {
        if ($sessionId === '') {
            return 'missing_session_id';
        }

        if (in_array($sessionId, self::DUMMY_SESSION_IDS, true)) {
            return 'dummy_session_id';
        }

        if (!preg_match('/\A[A-Za-z0-9,-]+\z/', $sessionId)) {
            return 'invalid_session_id_format';
        }

        return 'enabled';
    }
}
