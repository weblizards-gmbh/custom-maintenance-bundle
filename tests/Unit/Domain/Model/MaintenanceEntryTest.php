<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceDateTime;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceEntry;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceNoticeConfig;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceSchedule;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceToken;

final class MaintenanceEntryTest extends TestCase
{
    public function testCustomEntryPreservesLegacyShapeWhenConvertedBack(): void
    {
        $entry = new MaintenanceEntry(
            new MaintenanceToken('prices'),
            'ERP',
            'true',
            'false',
            new MaintenanceSchedule(
                new MaintenanceDateTime('01.02.2026', '08:00'),
                new MaintenanceDateTime('02.02.2026', '09:30')
            ),
            new MaintenanceNoticeConfig(
                'automatic',
                new MaintenanceDateTime('31.01.2026', '18:00'),
                '/de/maintenance/erp'
            )
        );

        self::assertEquals(
            [
                'show_info' => 'automatic',
                'show_info_from' => [
                    'date' => '31.01.2026',
                    'time' => '18:00',
                ],
                'planned' => [
                    'from' => [
                        'date' => '01.02.2026',
                        'time' => '08:00',
                    ],
                    'to' => [
                        'date' => '02.02.2026',
                        'time' => '09:30',
                    ],
                ],
                'document' => '/de/maintenance/erp',
                'active' => 'true',
                'fixed' => 'false',
                'description' => 'ERP',
            ],
            $entry->toLegacyArray()
        );
    }
}
