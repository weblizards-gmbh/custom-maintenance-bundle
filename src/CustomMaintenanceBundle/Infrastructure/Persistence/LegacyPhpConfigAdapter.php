<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence;

use Weblizards\CustomMaintenanceBundle\Config;

final class LegacyPhpConfigAdapter implements LegacyConfigLoaderInterface
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function load(): ?array
    {
        $data = $this->config->getData();

        if ($data === []) {
            return null;
        }

        return $data;
    }
}
