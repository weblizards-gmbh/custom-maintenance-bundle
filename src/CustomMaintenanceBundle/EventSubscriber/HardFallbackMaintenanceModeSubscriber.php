<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\EventSubscriber;

use Pimcore\Event\SystemEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Weblizards\CustomMaintenanceBundle\Service\HardFallbackMaintenanceRuntimeService;

final class HardFallbackMaintenanceModeSubscriber implements EventSubscriberInterface
{
    private HardFallbackMaintenanceRuntimeService $runtimeService;

    public function __construct(HardFallbackMaintenanceRuntimeService $runtimeService)
    {
        $this->runtimeService = $runtimeService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::MAINTENANCE_MODE_ACTIVATE => 'onMaintenanceModeActivate',
            SystemEvents::MAINTENANCE_MODE_DEACTIVATE => 'onMaintenanceModeDeactivate',
        ];
    }

    public function onMaintenanceModeActivate(GenericEvent $event): void
    {
        $this->runtimeService->synchronizeActivation();
    }

    public function onMaintenanceModeDeactivate(GenericEvent $event): void
    {
        $this->runtimeService->synchronizeDeactivation();
    }
}
