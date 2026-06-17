<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Domain\Model;

use Carbon\Carbon;

final class MaintenanceDateTime
{
    private string $date;

    private string $time;

    public function __construct(string $date, string $time)
    {
        $this->date = $date;
        $this->time = $time;
    }

    public static function fromLegacyArray(array $dateTime): self
    {
        return new self(
            (string) ($dateTime['date'] ?? '01.01.1970'),
            (string) ($dateTime['time'] ?? '00:00')
        );
    }

    public function toCarbon(): Carbon
    {
        if (trim($this->date) === '' || trim($this->time) === '') {
            return Carbon::createFromFormat('d.m.Y H:i', '01.01.1970 00:00');
        }

        return Carbon::createFromFormat('d.m.Y H:i', $this->date . ' ' . $this->time);
    }

    public function toLegacyArray(): array
    {
        return [
            'date' => $this->date,
            'time' => $this->time,
        ];
    }
}
