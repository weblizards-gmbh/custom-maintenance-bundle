<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\EventSubscriber;

use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\GenericEvent;
use Weblizards\CustomMaintenanceBundle\EventSubscriber\HardFallbackMaintenanceModeSubscriber;
use Weblizards\CustomMaintenanceBundle\Service\HardFallbackMaintenanceRuntimeService;

final class HardFallbackMaintenanceModeSubscriberTest extends TestCase
{
    public function testSubscriberTriggersRuntimeArtifactsForActivationAndDeactivation(): void
    {
        $runtimeService = $this->createMock(HardFallbackMaintenanceRuntimeService::class);
        $runtimeService->expects(self::once())->method('synchronizeActivation')->willReturn([]);
        $runtimeService->expects(self::once())->method('synchronizeDeactivation')->willReturn([]);

        $subscriber = new HardFallbackMaintenanceModeSubscriber($runtimeService);

        $subscriber->onMaintenanceModeActivate(new GenericEvent());
        $subscriber->onMaintenanceModeDeactivate(new GenericEvent());
    }
}
