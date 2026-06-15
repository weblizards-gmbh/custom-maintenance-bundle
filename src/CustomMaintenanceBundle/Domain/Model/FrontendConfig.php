<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Domain\Model;

final class FrontendConfig
{
    private array $indicationUpcoming;

    private array $indicationCurrent;

    private array $more;

    private array $fulltimeformat;

    public function __construct(
        array $indicationUpcoming,
        array $indicationCurrent,
        array $more,
        array $fulltimeformat
    ) {
        $this->indicationUpcoming = $indicationUpcoming;
        $this->indicationCurrent = $indicationCurrent;
        $this->more = $more;
        $this->fulltimeformat = $fulltimeformat;
    }

    public static function fromLegacyArray(array $config): self
    {
        return new self(
            (array) ($config['indication_upcoming'] ?? []),
            (array) ($config['indication_current'] ?? []),
            (array) ($config['more'] ?? []),
            (array) ($config['fulltimeformat'] ?? [])
        );
    }

    public function getUpcomingMessage(string $locale): string
    {
        return (string) ($this->indicationUpcoming[$locale] ?? '');
    }

    public function getCurrentMessage(string $locale): string
    {
        return (string) ($this->indicationCurrent[$locale] ?? '');
    }

    public function getMoreCaption(string $locale): string
    {
        return (string) ($this->more[$locale] ?? '');
    }

    public function getFulltimeFormat(string $locale): string
    {
        return (string) ($this->fulltimeformat[$locale] ?? 'd.m.Y H:i');
    }

    public function toLegacyArray(): array
    {
        return [
            'indication_upcoming' => $this->indicationUpcoming,
            'indication_current' => $this->indicationCurrent,
            'more' => $this->more,
            'fulltimeformat' => $this->fulltimeformat,
        ];
    }
}
