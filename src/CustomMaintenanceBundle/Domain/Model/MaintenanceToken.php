<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Domain\Model;

use Weblizards\CustomMaintenanceBundle\Config;

final class MaintenanceToken
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmedValue = trim($value);
        if ('' === $trimmedValue) {
            throw new \InvalidArgumentException('Maintenance token must not be empty.');
        }

        $this->value = $trimmedValue;
    }

    public function equals(string $token): bool
    {
        return $this->value === $token;
    }

    public function isPimcore(): bool
    {
        return $this->value === Config::TOKEN_PIMCORE;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
