<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Domain\Model;

final class MaintenanceSchedule
{
    private MaintenanceDateTime $from;

    private MaintenanceDateTime $to;

    public function __construct(MaintenanceDateTime $from, MaintenanceDateTime $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public static function fromLegacyArray(array $schedule): self
    {
        return new self(
            MaintenanceDateTime::fromLegacyArray((array) ($schedule['from'] ?? [])),
            MaintenanceDateTime::fromLegacyArray((array) ($schedule['to'] ?? []))
        );
    }

    public function getFrom(): MaintenanceDateTime
    {
        return $this->from;
    }

    public function getTo(): MaintenanceDateTime
    {
        return $this->to;
    }

    public function toLegacyArray(): array
    {
        return [
            'from' => $this->from->toLegacyArray(),
            'to' => $this->to->toLegacyArray(),
        ];
    }
}
