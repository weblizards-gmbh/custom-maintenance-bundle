<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Service;

use Pimcore\Document\Renderer\DocumentRendererInterface;
use Pimcore\Model\Document;
use Pimcore\Model\Document\PageSnippet;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class HardFallbackExportService
{
    public const TYPE_MAINTENANCE = 'maintenance';

    public const TYPE_ERROR = 'error';

    private MaintenanceConfigManager $configManager;

    private DocumentRendererInterface $documentRenderer;

    private LoggerInterface $logger;

    private string $maintenanceExportTarget;

    private string $errorExportTarget;

    public function __construct(
        MaintenanceConfigManager $configManager,
        DocumentRendererInterface $documentRenderer,
        string $maintenanceExportTarget,
        string $errorExportTarget,
        ?LoggerInterface $logger = null
    )
    {
        $this->configManager = $configManager;
        $this->documentRenderer = $documentRenderer;
        $this->maintenanceExportTarget = $maintenanceExportTarget;
        $this->errorExportTarget = $errorExportTarget;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param array{
     *   maintenance_document_changed:bool,
     *   error_document_changed:bool,
     *   maintenance_document_id:int|null,
     *   error_document_id:int|null
     * } $changeSet
     *
     * @return array<int, array<string, mixed>>
     */
    public function synchronizeAfterConfigSave(array $changeSet): array
    {
        $results = [];

        if ($changeSet['maintenance_document_changed'] ?? false) {
            $results[] = $this->exportDocumentById(self::TYPE_MAINTENANCE, $changeSet['maintenance_document_id'] ?? null);
        }

        if ($changeSet['error_document_changed'] ?? false) {
            $results[] = $this->exportDocumentById(self::TYPE_ERROR, $changeSet['error_document_id'] ?? null);
        }

        return $results;
    }

    /**
     * @return array<string, mixed>
     */
    public function exportConfiguredMaintenanceDocument(): array
    {
        $config = $this->configManager->getHardFallbackConfig();

        return $this->exportDocumentById(self::TYPE_MAINTENANCE, $config['maintenance_document']['id'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    public function exportConfiguredErrorDocument(): array
    {
        $config = $this->configManager->getHardFallbackConfig();

        return $this->exportDocumentById(self::TYPE_ERROR, $config['error_document']['id'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    public function exportIfConfiguredDocumentMatches(Document $document): array
    {
        $config = $this->configManager->getHardFallbackConfig();
        $documentId = (int) $document->getId();

        if (($config['maintenance_document']['id'] ?? null) === $documentId) {
            return $this->exportDocument(self::TYPE_MAINTENANCE, $document);
        }

        if (($config['error_document']['id'] ?? null) === $documentId) {
            return $this->exportDocument(self::TYPE_ERROR, $document);
        }

        return [
            'type' => 'none',
            'status' => 'ignored',
            'document_id' => $documentId,
        ];
    }

    /**
     * @param int|null $documentId
     *
     * @return array<string, mixed>
     */
    public function exportDocumentById(string $type, $documentId): array
    {
        if (!is_int($documentId) || $documentId < 1) {
            $this->logger->info('Hard fallback export skipped because no source document is configured.', [
                'type' => $type,
            ]);

            return [
                'type' => $type,
                'status' => 'skipped_unconfigured',
                'document_id' => null,
            ];
        }

        $document = Document::getById($documentId, ['force' => true]);
        if (!$document instanceof Document) {
            $this->logger->warning('Hard fallback export skipped because the configured source document was not found.', [
                'type' => $type,
                'document_id' => $documentId,
            ]);

            return [
                'type' => $type,
                'status' => 'skipped_missing_document',
                'document_id' => $documentId,
            ];
        }

        return $this->exportDocument($type, $document);
    }

    /**
     * @return array<string, mixed>
     */
    private function exportDocument(string $type, Document $document): array
    {
        $documentId = (int) $document->getId();

        if (!$document instanceof PageSnippet) {
            $this->logger->warning('Hard fallback export skipped because the source document is not renderable as page/snippet.', [
                'type' => $type,
                'document_id' => $documentId,
                'document_type' => $document->getType(),
            ]);

            return [
                'type' => $type,
                'status' => 'skipped_unsupported_document',
                'document_id' => $documentId,
            ];
        }

        if (!$document->isPublished()) {
            $this->logger->info('Hard fallback export skipped because the source document is not published. Existing artifact stays untouched.', [
                'type' => $type,
                'document_id' => $documentId,
            ]);

            return [
                'type' => $type,
                'status' => 'skipped_unpublished',
                'document_id' => $documentId,
            ];
        }

        $target = $this->resolveTargetPath($type);
        $targetDirectory = dirname($target);

        if (!is_dir($targetDirectory) && !@mkdir($targetDirectory, 0777, true) && !is_dir($targetDirectory)) {
            $this->logger->error('Hard fallback export failed because the target directory could not be created.', [
                'type' => $type,
                'document_id' => $documentId,
                'target' => $target,
            ]);

            return [
                'type' => $type,
                'status' => 'failed_directory_creation',
                'document_id' => $documentId,
                'target' => $target,
            ];
        }

        try {
            $markup = $this->documentRenderer->render($document);
            $temporaryFile = tempnam($targetDirectory, 'cmf_');
            if ($temporaryFile === false) {
                throw new \RuntimeException('Unable to create temporary export file.');
            }

            file_put_contents($temporaryFile, $markup);
            @chmod($temporaryFile, 0664);

            if (!@rename($temporaryFile, $target)) {
                @unlink($temporaryFile);

                throw new \RuntimeException('Unable to move temporary export file to target path.');
            }
        } catch (\Throwable $exception) {
            $this->logger->error('Hard fallback export failed. Existing artifact stays untouched.', [
                'type' => $type,
                'document_id' => $documentId,
                'target' => $target,
                'exception' => $exception,
            ]);

            return [
                'type' => $type,
                'status' => 'failed',
                'document_id' => $documentId,
                'target' => $target,
                'message' => $exception->getMessage(),
            ];
        }

        $this->logger->info('Hard fallback artifact exported successfully.', [
            'type' => $type,
            'document_id' => $documentId,
            'target' => $target,
        ]);

        return [
            'type' => $type,
            'status' => 'exported',
            'document_id' => $documentId,
            'target' => $target,
        ];
    }

    private function resolveTargetPath(string $type): string
    {
        if ($type === self::TYPE_ERROR) {
            return $this->errorExportTarget;
        }

        return $this->maintenanceExportTarget;
    }
}
