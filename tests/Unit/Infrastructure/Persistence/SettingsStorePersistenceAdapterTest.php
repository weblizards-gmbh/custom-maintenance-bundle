<?php

declare(strict_types=1);

namespace Weblizards\CustomMaintenanceBundle\Test\Unit\Infrastructure\Persistence;

use PHPUnit\Framework\TestCase;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\SettingsStoreGatewayInterface;
use Weblizards\CustomMaintenanceBundle\Infrastructure\Persistence\SettingsStorePersistenceAdapter;

final class SettingsStorePersistenceAdapterTest extends TestCase
{
    public function testLoadUsesExpectedScopeAndKeyAndDecodesJsonPayload(): void
    {
        $gateway = $this->createMock(SettingsStoreGatewayInterface::class);
        $gateway
            ->expects(self::once())
            ->method('get')
            ->with(SettingsStorePersistenceAdapter::KEY, SettingsStorePersistenceAdapter::SCOPE)
            ->willReturn('{"frontend":[],"pimcore":[],"custom":[]}');

        $adapter = new SettingsStorePersistenceAdapter($gateway);

        self::assertSame(
            [
                'frontend' => [],
                'pimcore' => [],
                'custom' => [],
            ],
            $adapter->load()
        );
    }

    public function testLoadThrowsForInvalidJsonPayload(): void
    {
        $gateway = $this->createMock(SettingsStoreGatewayInterface::class);
        $gateway
            ->expects(self::once())
            ->method('get')
            ->with(SettingsStorePersistenceAdapter::KEY, SettingsStorePersistenceAdapter::SCOPE)
            ->willReturn('{invalid');

        $adapter = new SettingsStorePersistenceAdapter($gateway);

        $this->expectException(\UnexpectedValueException::class);
        $adapter->load();
    }

    public function testSaveUsesExpectedScopeKeyAndJsonStringPayload(): void
    {
        $gateway = $this->createMock(SettingsStoreGatewayInterface::class);
        $gateway
            ->expects(self::once())
            ->method('set')
            ->with(
                SettingsStorePersistenceAdapter::KEY,
                '{"frontend":[],"pimcore":[],"custom":[]}',
                'string',
                SettingsStorePersistenceAdapter::SCOPE
            )
            ->willReturn(true);

        $adapter = new SettingsStorePersistenceAdapter($gateway);
        $adapter->save([
            'frontend' => [],
            'pimcore' => [],
            'custom' => [],
        ]);
    }

    public function testSaveThrowsWhenGatewayCannotPersistPayload(): void
    {
        $gateway = $this->createMock(SettingsStoreGatewayInterface::class);
        $gateway
            ->expects(self::once())
            ->method('set')
            ->with(
                SettingsStorePersistenceAdapter::KEY,
                '{"frontend":[],"pimcore":[],"custom":[]}',
                'string',
                SettingsStorePersistenceAdapter::SCOPE
            )
            ->willReturn(false);

        $adapter = new SettingsStorePersistenceAdapter($gateway);

        $this->expectException(\RuntimeException::class);
        $adapter->save([
            'frontend' => [],
            'pimcore' => [],
            'custom' => [],
        ]);
    }
}
