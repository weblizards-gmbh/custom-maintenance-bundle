<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence;

interface SettingsStoreGatewayInterface
{
    public function get(string $id, ?string $scope = null): ?string;

    public function set(string $id, string $data, string $type = 'string', ?string $scope = null): bool;
}
