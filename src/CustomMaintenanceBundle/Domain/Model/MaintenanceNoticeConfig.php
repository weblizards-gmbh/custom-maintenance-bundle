<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Domain\Model;

final class MaintenanceNoticeConfig
{
    private string $showInfo;

    private MaintenanceDateTime $showInfoFrom;

    private string $document;

    public function __construct(string $showInfo, MaintenanceDateTime $showInfoFrom, string $document)
    {
        $this->showInfo = $showInfo;
        $this->showInfoFrom = $showInfoFrom;
        $this->document = $document;
    }

    public static function fromLegacyArray(array $config): self
    {
        return new self(
            (string) ($config['show_info'] ?? 'never'),
            MaintenanceDateTime::fromLegacyArray((array) ($config['show_info_from'] ?? [])),
            (string) ($config['document'] ?? '')
        );
    }

    public function getShowInfo(): string
    {
        return $this->showInfo;
    }

    public function getShowInfoFrom(): MaintenanceDateTime
    {
        return $this->showInfoFrom;
    }

    public function getDocument(): string
    {
        return $this->document;
    }

    public function toLegacyArray(): array
    {
        return [
            'show_info' => $this->showInfo,
            'show_info_from' => $this->showInfoFrom->toLegacyArray(),
            'document' => $this->document,
        ];
    }
}
