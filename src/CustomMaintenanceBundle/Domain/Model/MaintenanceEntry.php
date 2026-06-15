<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Domain\Model;

final class MaintenanceEntry
{
    private MaintenanceToken $token;

    private string $description;

    private string $active;

    private string $fixed;

    private MaintenanceSchedule $schedule;

    private MaintenanceNoticeConfig $noticeConfig;

    public function __construct(
        MaintenanceToken $token,
        string $description,
        string $active,
        string $fixed,
        MaintenanceSchedule $schedule,
        MaintenanceNoticeConfig $noticeConfig
    ) {
        $this->token = $token;
        $this->description = $description;
        $this->active = $active;
        $this->fixed = $fixed;
        $this->schedule = $schedule;
        $this->noticeConfig = $noticeConfig;
    }

    public static function fromLegacyPimcoreConfig(array $config): self
    {
        return new self(
            new MaintenanceToken('pimcore'),
            'Pimcore',
            'false',
            'false',
            MaintenanceSchedule::fromLegacyArray((array) ($config['planned'] ?? [])),
            MaintenanceNoticeConfig::fromLegacyArray($config)
        );
    }

    public static function fromLegacyCustomConfig(string $token, array $config): self
    {
        return new self(
            new MaintenanceToken($token),
            (string) ($config['description'] ?? $token),
            (string) ($config['active'] ?? 'false'),
            (string) ($config['fixed'] ?? 'false'),
            MaintenanceSchedule::fromLegacyArray((array) ($config['planned'] ?? [])),
            MaintenanceNoticeConfig::fromLegacyArray($config)
        );
    }

    public function getToken(): MaintenanceToken
    {
        return $this->token;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getActiveStatus(): string
    {
        return $this->active;
    }

    public function isMarkedActive(): bool
    {
        return $this->active === 'true';
    }

    public function isFixedMode(): bool
    {
        return $this->fixed === 'true';
    }

    public function getSchedule(): MaintenanceSchedule
    {
        return $this->schedule;
    }

    public function getNoticeConfig(): MaintenanceNoticeConfig
    {
        return $this->noticeConfig;
    }

    public function toLegacyArray(): array
    {
        $data = [
            'show_info' => $this->noticeConfig->getShowInfo(),
            'show_info_from' => $this->noticeConfig->getShowInfoFrom()->toLegacyArray(),
            'planned' => $this->schedule->toLegacyArray(),
            'document' => $this->noticeConfig->getDocument(),
        ];

        if (!$this->token->isPimcore()) {
            $data['active'] = $this->active;
            $data['fixed'] = $this->fixed;
            $data['description'] = $this->description;
        }

        return $data;
    }
}
