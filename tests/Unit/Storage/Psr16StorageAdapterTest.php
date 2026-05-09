<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Tests\Unit\Storage;

use Codeception\Test\Unit;
use MagmaSoftwareEngineering\Rollout\Storage\Psr16StorageAdapter;
use PHPUnit\Framework\Attributes\Test;
use Psr\SimpleCache\CacheInterface;

class Psr16StorageAdapterTest extends Unit
{
    #[Test]
    public function getReturnsCachedString(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::once())
            ->method('get')
            ->with('feature:chat')
            ->willReturn('serialised-payload');

        $adapter = new Psr16StorageAdapter($cache);

        self::assertSame('serialised-payload', $adapter->get('feature:chat'));
    }

    #[Test]
    public function getReturnsNullWhenCacheMissing(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(null);

        $adapter = new Psr16StorageAdapter($cache);

        self::assertNull($adapter->get('absent'));
    }

    #[Test]
    public function getReturnsNullWhenCachedValueIsNotAString(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->method('get')->willReturn(['not', 'a', 'string']);

        $adapter = new Psr16StorageAdapter($cache);

        self::assertNull($adapter->get('weird'));
    }

    #[Test]
    public function setDelegatesToCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::once())
            ->method('set')
            ->with('feature:chat', 'serialised-payload');

        $adapter = new Psr16StorageAdapter($cache);

        $adapter->set('feature:chat', 'serialised-payload');
    }

    #[Test]
    public function removeDelegatesToCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects(self::once())
            ->method('delete')
            ->with('feature:chat');

        $adapter = new Psr16StorageAdapter($cache);

        $adapter->remove('feature:chat');
    }
}
