<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Storage;

/**
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
class ArrayStorage implements StorageInterface
{
    /** @var array<string, string> */
    private array $storage = [];

    public function get(string $key): ?string
    {
        return $this->storage[$key] ?? null;
    }

    public function set(string $key, string $value): void
    {
        $this->storage[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->storage[$key]);
    }
}
