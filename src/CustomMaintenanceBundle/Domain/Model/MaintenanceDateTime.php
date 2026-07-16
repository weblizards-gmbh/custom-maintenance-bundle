<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Domain\Model;

use Carbon\Carbon;

final class MaintenanceDateTime
{
    private const LEGACY_DEFAULT_DATE = '01.01.1970';

    private const LEGACY_DEFAULT_TIME = '00:00';

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
            (string) ($dateTime['date'] ?? self::LEGACY_DEFAULT_DATE),
            (string) ($dateTime['time'] ?? self::LEGACY_DEFAULT_TIME)
        );
    }

    public function isConfigured(): bool
    {
        if (trim($this->date) === '' || trim($this->time) === '') {
            return false;
        }

        return !($this->date === self::LEGACY_DEFAULT_DATE && $this->time === self::LEGACY_DEFAULT_TIME);
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
