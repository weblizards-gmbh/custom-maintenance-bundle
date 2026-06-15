<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence;

interface ConfigPersistenceInterface
{
    public function load(): ?array;

    public function save(array $data): void;
}
