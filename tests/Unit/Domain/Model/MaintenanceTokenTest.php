<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Domain\Model;

use PHPUnit\Framework\TestCase;
use Weblizards\CustomMaintenanceBundle\Domain\Model\MaintenanceToken;

final class MaintenanceTokenTest extends TestCase
{
    public function testFromNewCustomTokenAcceptsAlphanumericValue(): void
    {
        $token = MaintenanceToken::fromNewCustomToken(' prices123 ');

        self::assertSame('prices123', $token->toString());
    }

    public function testFromNewCustomTokenRejectsNonAlphanumericValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maintenance token must be alphanumeric.');

        MaintenanceToken::fromNewCustomToken('prices-api');
    }

    public function testFromNewCustomTokenRejectsEmptyValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Maintenance token must not be empty.');

        MaintenanceToken::fromNewCustomToken('   ');
    }
}
