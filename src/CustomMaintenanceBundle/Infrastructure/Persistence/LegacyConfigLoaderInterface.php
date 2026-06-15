<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence;

interface LegacyConfigLoaderInterface
{
    public function load(): ?array;
}
