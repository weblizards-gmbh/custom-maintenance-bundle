<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence;

final class SettingsStorePersistenceAdapter implements ConfigPersistenceInterface
{
    public const SCOPE = 'weblizards_custom_maintenance';

    public const KEY = 'config';

    private SettingsStoreGatewayInterface $settingsStoreGateway;

    public function __construct(SettingsStoreGatewayInterface $settingsStoreGateway)
    {
        $this->settingsStoreGateway = $settingsStoreGateway;
    }

    public function load(): ?array
    {
        $payload = $this->settingsStoreGateway->get(self::KEY, self::SCOPE);
        if ($payload === null) {
            return null;
        }

        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \UnexpectedValueException('Invalid settings store payload for custom maintenance config.', 0, $exception);
        }

        if (!is_array($data)) {
            throw new \UnexpectedValueException('Invalid settings store payload for custom maintenance config.');
        }

        return $data;
    }

    public function save(array $data): void
    {
        try {
            $encodedData = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \UnexpectedValueException('Unable to serialize custom maintenance config for settings store.', 0, $exception);
        }

        $saved = $this->settingsStoreGateway->set(self::KEY, $encodedData, 'string', self::SCOPE);
        if (!$saved) {
            throw new \RuntimeException('Unable to persist custom maintenance config to settings store.');
        }
    }
}
