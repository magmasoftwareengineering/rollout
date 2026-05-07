<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Storage;

use Doctrine\Common\Cache\Cache;

/**
 * Use any available doctrine/cache Cache implementation.
 *
 * @deprecated since 3.0 — doctrine/cache itself is deprecated upstream.
 *             Migrate to {@see Psr16StorageAdapter} (PSR-16 SimpleCache),
 *             which works with most modern cache libraries including
 *             symfony/cache, cache/cache, and laminas/laminas-cache.
 *             This adapter will be removed in 4.0.
 *
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class DoctrineCacheStorageAdapter implements StorageInterface
{
    public function __construct(private readonly Cache $cache) {}

    #[\Override]
    public function get(string $key): ?string
    {
        $value = $this->cache->fetch($key);

        return $value === false ? null : (string) $value;
    }

    #[\Override]
    public function set(string $key, string $value): void
    {
        $this->cache->save($key, $value);
    }

    #[\Override]
    public function remove(string $key): void
    {
        $this->cache->delete($key);
    }
}
