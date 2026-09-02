<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\EventSubscriber;

use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\DocumentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Weblizards\CustomMaintenanceBundle\Service\HardFallbackExportService;

final class HardFallbackDocumentSubscriber implements EventSubscriberInterface
{
    private HardFallbackExportService $hardFallbackExportService;

    public function __construct(HardFallbackExportService $hardFallbackExportService)
    {
        $this->hardFallbackExportService = $hardFallbackExportService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DocumentEvents::POST_ADD => 'onDocumentChanged',
            DocumentEvents::POST_UPDATE => 'onDocumentChanged',
        ];
    }

    public function onDocumentChanged(DocumentEvent $event): void
    {
        $this->hardFallbackExportService->exportIfConfiguredDocumentMatches($event->getDocument());
    }
}
