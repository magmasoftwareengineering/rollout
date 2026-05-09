<?php

declare(strict_types=1);

/**
 * Legacy class aliases — Opensoft\Rollout\* → MagmaSoftwareEngineering\Rollout\*
 *
 * Loaded automatically via composer "files" autoload. Allows downstream code
 * still using `use Opensoft\Rollout\Rollout;` to keep working unchanged after
 * upgrading from opensoft/rollout to magmasoftwareengineering/rollout.
 *
 * Deprecated. The aliases will be removed in a future major version. Migrate
 * to the MagmaSoftwareEngineering\Rollout namespace at your convenience.
 */

class_alias(
    \MagmaSoftwareEngineering\Rollout\Rollout::class,
    'Opensoft\\Rollout\\Rollout',
);
class_alias(
    \MagmaSoftwareEngineering\Rollout\Feature::class,
    'Opensoft\\Rollout\\Feature',
);
class_alias(
    \MagmaSoftwareEngineering\Rollout\RolloutUserInterface::class,
    'Opensoft\\Rollout\\RolloutUserInterface',
);
class_alias(
    \MagmaSoftwareEngineering\Rollout\Storage\StorageInterface::class,
    'Opensoft\\Rollout\\Storage\\StorageInterface',
);
class_alias(
    \MagmaSoftwareEngineering\Rollout\Storage\ArrayStorage::class,
    'Opensoft\\Rollout\\Storage\\ArrayStorage',
);
class_alias(
    \MagmaSoftwareEngineering\Rollout\Storage\RedisStorageAdapter::class,
    'Opensoft\\Rollout\\Storage\\RedisStorageAdapter',
);
class_alias(
    \MagmaSoftwareEngineering\Rollout\Storage\PDOStorageAdapter::class,
    'Opensoft\\Rollout\\Storage\\PDOStorageAdapter',
);
class_alias(
    \MagmaSoftwareEngineering\Rollout\Storage\DoctrineCacheStorageAdapter::class,
    'Opensoft\\Rollout\\Storage\\DoctrineCacheStorageAdapter',
);
class_alias(
    \MagmaSoftwareEngineering\Rollout\Storage\MongoDBStorageAdapter::class,
    'Opensoft\\Rollout\\Storage\\MongoDBStorageAdapter',
);
