<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Storage;

use Psr\SimpleCache\CacheInterface;

/**
 * Storage adapter using any PSR-16 SimpleCache implementation.
 *
 * Works with symfony/cache (`Psr16Cache`), cache/cache, laminas/laminas-cache,
 * and any other library that exposes a {@see CacheInterface}. This is the
 * recommended replacement for {@see DoctrineCacheStorageAdapter}, which
 * targets the deprecated doctrine/cache package.
 *
 * Suggested setup with symfony/cache:
 *
 *     use Symfony\Component\Cache\Psr16Cache;
 *     use Symfony\Component\Cache\Adapter\RedisAdapter;
 *
 *     $cache = new Psr16Cache(new RedisAdapter($redis));
 *     $storage = new Psr16StorageAdapter($cache);
 */
class Psr16StorageAdapter implements StorageInterface
{
    public function __construct(private readonly CacheInterface $cache) {}

    public function get(string $key): ?string
    {
        /** @var mixed $value */
        $value = $this->cache->get($key);

        return is_string($value) ? $value : null;
    }

    public function set(string $key, string $value): void
    {
        $this->cache->set($key, $value);
    }

    public function remove(string $key): void
    {
        $this->cache->delete($key);
    }
}
