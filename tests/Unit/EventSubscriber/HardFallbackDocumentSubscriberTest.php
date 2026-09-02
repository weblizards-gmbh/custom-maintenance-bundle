<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Pimcore\Document\Renderer\DocumentRendererInterface;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Model\Document\Page;
use Weblizards\CustomMaintenanceBundle\EventSubscriber\HardFallbackDocumentSubscriber;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\ConfigPersistenceInterface;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\LegacyConfigLoaderInterface;
use Weblizards\CustomMaintenanceBundle\Service\HardFallbackExportService;
use Weblizards\CustomMaintenanceBundle\Service\MaintenanceConfigManager;

final class HardFallbackDocumentSubscriberTest extends TestCase
{
    public function testSubscriberExportsWhenConfiguredFallbackDocumentIsUpdated(): void
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
        $renderer
            ->expects(self::once())
            ->method('render')
            ->willReturn('<html><body>from subscriber</body></html>');

        $targetFile = sys_get_temp_dir() . '/custom-maintenance-subscriber-maintenance.html';
        @unlink($targetFile);

        $exportService = new HardFallbackExportService(
            new MaintenanceConfigManager($settingsStore, $legacyLoader),
            $renderer,
            $targetFile,
            sys_get_temp_dir() . '/custom-maintenance-subscriber-error.html'
        );
        $subscriber = new HardFallbackDocumentSubscriber($exportService);

        $document = new Page();
        $document->setId(123);
        $document->setPublished(true);

        $subscriber->onDocumentChanged(new DocumentEvent($document));

        self::assertFileExists($targetFile);
        self::assertSame('<html><body>from subscriber</body></html>', file_get_contents($targetFile));

        @unlink($targetFile);
    }
}
