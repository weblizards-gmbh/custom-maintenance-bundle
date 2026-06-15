<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence;

use Pimcore\Model\Tool\SettingsStore;

final class PimcoreSettingsStoreGateway implements SettingsStoreGatewayInterface
{
    public function get(string $id, ?string $scope = null): ?string
    {
        $entry = SettingsStore::get($id, $scope);

        return $entry ? (string) $entry->getData() : null;
    }

    public function set(string $id, string $data, string $type = 'string', ?string $scope = null): bool
    {
        return SettingsStore::set($id, $data, $type, $scope);
    }
}
