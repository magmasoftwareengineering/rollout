<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Storage;

/**
 * Storage adapter using Redis.
 *
 * Accepts either a \Redis instance (ext-redis) or a \Predis\Client.
 * Both clients support the hget/hset/hdel commands used here.
 *
 * Stored values are JSON-wrapped for compatibility with data written by
 * older versions of this adapter; values written by 3.0+ are unwrapped
 * transparently on read.
 *
 * @author Woody Gilk <@shadowhand>
 */
class RedisStorageAdapter implements StorageInterface
{
    public const DEFAULT_GROUP = 'rollout_feature';

    private readonly string $group;

    public function __construct(
        private readonly object $redis,
        ?string $group = null,
    ) {
        $this->group = $group ?? self::DEFAULT_GROUP;
    }

    public function get(string $key): ?string
    {
        /** @psalm-suppress MixedAssignment */
        $result = $this->redis->hget($this->group, $key);
        if ($result === null || $result === false || $result === '') {
            return null;
        }

        $decoded = json_decode((string) $result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return is_string($decoded) ? $decoded : null;
    }

    public function set(string $key, string $value): void
    {
        $this->redis->hset($this->group, $key, json_encode($value));
    }

    public function remove(string $key): void
    {
        $this->redis->hdel($this->group, $key);
    }
}
