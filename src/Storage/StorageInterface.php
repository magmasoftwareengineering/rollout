<?php

declare(strict_types=1);

namespace MagmaSoftwareEngineering\Rollout\Storage;

/**
 * @author Richard Fullmer <richard.fullmer@opensoftdev.com>
 */
interface StorageInterface
{
    public function get(string $key): ?string;

    public function set(string $key, string $value): void;

    public function remove(string $key): void;
}
