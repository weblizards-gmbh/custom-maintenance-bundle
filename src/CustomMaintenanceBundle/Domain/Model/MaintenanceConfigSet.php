<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Domain\Model;

use Weblizards\CustomMaintenanceBundle\Config;

final class MaintenanceConfigSet
{
    private MaintenanceEntry $pimcoreEntry;

    /**
     * @var array<string, MaintenanceEntry>
     */
    private array $customEntries;

    private FrontendConfig $frontendConfig;

    /**
     * @param array<string, MaintenanceEntry> $customEntries
     */
    public function __construct(MaintenanceEntry $pimcoreEntry, array $customEntries, FrontendConfig $frontendConfig)
    {
        $this->pimcoreEntry = $pimcoreEntry;
        $this->customEntries = $customEntries;
        $this->frontendConfig = $frontendConfig;
    }

    public function getPimcoreEntry(): MaintenanceEntry
    {
        return $this->pimcoreEntry;
    }

    /**
     * @return array<string, MaintenanceEntry>
     */
    public function getCustomEntries(): array
    {
        return $this->customEntries;
    }

    public function getEntry(string $token): MaintenanceEntry
    {
        if ($token === Config::TOKEN_PIMCORE) {
            return $this->pimcoreEntry;
        }

        if (!array_key_exists($token, $this->customEntries)) {
            throw new \InvalidArgumentException('Invalid Token: ' . $token);
        }

        return $this->customEntries[$token];
    }

    /**
     * @return string[]
     */
    public function getCustomTokens(): array
    {
        return array_keys($this->customEntries);
    }

    /**
     * @return string[]
     */
    public function getAllTokens(): array
    {
        return array_merge([Config::TOKEN_PIMCORE], $this->getCustomTokens());
    }

    public function getFrontendConfig(): FrontendConfig
    {
        return $this->frontendConfig;
    }

    public function toLegacyArray(): array
    {
        $custom = [];
        foreach ($this->customEntries as $token => $entry) {
            $custom[$token] = $entry->toLegacyArray();
        }

        return [
            'frontend' => $this->frontendConfig->toLegacyArray(),
            Config::TOKEN_PIMCORE => $this->pimcoreEntry->toLegacyArray(),
            'custom' => $custom,
        ];
    }
}
