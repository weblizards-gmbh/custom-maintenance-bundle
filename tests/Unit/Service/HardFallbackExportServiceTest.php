<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Service;

use PHPUnit\Framework\TestCase;
use Pimcore\Document\Renderer\DocumentRendererInterface;
use Pimcore\Model\Document\Page;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\ConfigPersistenceInterface;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\LegacyConfigLoaderInterface;
use Weblizards\CustomMaintenanceBundle\Service\HardFallbackExportService;
use Weblizards\CustomMaintenanceBundle\Service\MaintenanceConfigManager;

final class HardFallbackExportServiceTest extends TestCase
{
    public function testExportIfConfiguredDocumentMatchesWritesRenderedMaintenanceArtifact(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [],
                'hard_fallback' => [
                    'maintenance_document' => ['id' => 123, 'path' => '/de/system/maintenance'],
                    'error_document' => ['id' => 456, 'path' => '/de/system/error'],
                ],
            ]);
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $renderer = $this->createMock(DocumentRendererInterface::class);
        $renderer
            ->expects(self::once())
            ->method('render')
            ->willReturn('<html><body>maintenance export</body></html>');

        $targetFile = sys_get_temp_dir() . '/custom-maintenance-test-maintenance.html';
        @unlink($targetFile);

        $service = new HardFallbackExportService(
            new MaintenanceConfigManager($settingsStore, $legacyLoader),
            $renderer,
            $targetFile,
            sys_get_temp_dir() . '/custom-maintenance-test-error.html'
        );

        $document = new Page();
        $document->setId(123);
        $document->setPublished(true);

        $result = $service->exportIfConfiguredDocumentMatches($document);

        self::assertSame('exported', $result['status']);
        self::assertFileExists($targetFile);
        self::assertSame('<html><body>maintenance export</body></html>', file_get_contents($targetFile));

        @unlink($targetFile);
    }

    public function testExportIfConfiguredDocumentMatchesKeepsExistingArtifactForUnpublishedSource(): void
    {
        $settingsStore = $this->createMock(ConfigPersistenceInterface::class);
        $settingsStore
            ->expects(self::once())
            ->method('load')
            ->willReturn([
                'frontend' => [],
                'pimcore' => [],
                'custom' => [],
                'hard_fallback' => [
                    'maintenance_document' => ['id' => 123, 'path' => '/de/system/maintenance'],
                    'error_document' => ['id' => null, 'path' => ''],
                ],
            ]);
        $legacyLoader = $this->createMock(LegacyConfigLoaderInterface::class);
        $legacyLoader->expects(self::never())->method('load');

        $renderer = $this->createMock(DocumentRendererInterface::class);
        $renderer->expects(self::never())->method('render');

        $targetFile = sys_get_temp_dir() . '/custom-maintenance-test-maintenance.html';
        file_put_contents($targetFile, 'existing-artifact');

        $service = new HardFallbackExportService(
            new MaintenanceConfigManager($settingsStore, $legacyLoader),
            $renderer,
            $targetFile,
            sys_get_temp_dir() . '/custom-maintenance-test-error.html'
        );

        $document = new Page();
        $document->setId(123);
        $document->setPublished(false);

        $result = $service->exportIfConfiguredDocumentMatches($document);

        self::assertSame('skipped_unpublished', $result['status']);
        self::assertSame('existing-artifact', file_get_contents($targetFile));

        @unlink($targetFile);
    }
}
