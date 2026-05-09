# Upgrading from `opensoft/rollout` 2.x to `magmasoftwareengineering/rollout` 3.0

This guide covers the changes you need to make when migrating from the unmaintained upstream `opensoft/rollout` package to the maintained successor.

## TL;DR

For most projects, three steps:

1. Swap the composer dependency (one line).
2. Verify your PHP version is 8.3+.
3. Run your test suite.

Optionally, update `use` statements to the new namespace at your own pace — the legacy names continue to resolve via `class_alias` shims throughout 3.x.

## Step-by-step

### 1. Composer dependency

```diff
{
    "require": {
-       "opensoft/rollout": "^2.3"
+       "magmasoftwareengineering/rollout": "^3.0"
    }
}
```

```sh
composer update
```

### 2. PHP version

3.0 requires PHP 8.3 or newer. If you're on 8.2 or older, upgrade PHP first; the package will not install on older versions.

### 3. Namespaces

The PHP namespace has changed from `Opensoft\Rollout\*` to `MagmaSoftwareEngineering\Rollout\*`.

**You don't have to update right away.** `class_alias` shims are loaded automatically and the old names keep resolving. So the following continues to work in 3.0:

```php
use Opensoft\Rollout\Rollout;
use Opensoft\Rollout\Storage\ArrayStorage;
```

For new code, use the modern namespace:

```php
use MagmaSoftwareEngineering\Rollout\Rollout;
use MagmaSoftwareEngineering\Rollout\Storage\ArrayStorage;
```

**The legacy aliases will be removed in 4.0.** Migrating now is recommended; a project-wide find-and-replace from `Opensoft\Rollout` to `MagmaSoftwareEngineering\Rollout` is sufficient.

## Breaking changes that may need code updates

### `StorageInterface` is strictly typed

If you have a custom storage implementation, its method signatures must match:

```php
public function get(string $key): ?string;
public function set(string $key, string $value): void;
public function remove(string $key): void;
```

The 2.x interface was untyped. Strict types will fail at class load if your implementation has different signatures.

### `MongoDBStorageAdapter` constructor

The old adapter targeted ext-mongo (deprecated, removed in PHP 7.0). The new adapter targets the modern `mongodb/mongodb` library and ext-mongodb. Construction has changed:

```diff
- $client = new MongoClient();
- $storage = new MongoDBStorageAdapter($client->mydb, 'rollout_feature');
+ $client = new \MongoDB\Client('mongodb://localhost:27017');
+ $collection = $client->selectCollection('mydb', 'rollout_feature');
+ $storage = new MongoDBStorageAdapter($collection);
```

If you were using the legacy ext-mongo path: it has not worked since PHP 7.0, so you've already been on ext-mongodb in some form. Otherwise: install `mongodb/mongodb` and ext-mongodb if you haven't already.

### `Rollout::save()` returns `void`

If you subclass `Rollout` and override `save()` (e.g. for write-fanout to multiple storages):

```diff
- public function save(Feature $feature): self
+ public function save(FeatureInterface $feature): void
```

The parameter type also widens from concrete `Feature` to `FeatureInterface`, which is a non-breaking change for callers.

### Wire format

3.0 writes JSON, 2.x writes pipe-delimited strings. **3.0 reads both formats**, so existing data already in your Redis/PDO/Doctrine cache/Mongo storage continues to load.

**The mid-upgrade-fleet caveat:** if you run a heterogeneous fleet (some servers on 2.x, some on 3.0) sharing the same storage backend, 2.x servers will fail to read records written by 3.0 servers (2.x doesn't understand JSON). The reverse is fine.

Mitigations:

- Roll the upgrade out atomically, or
- Drain feature reads from older servers during the upgrade window, or
- If you control deployment, deploy 3.0 servers first as readers (3.0 can read 2.x format), then promote them to writers once the fleet is fully on 3.0.

There is no migration script; once all servers are on 3.0, the next write to each feature transparently upgrades it to JSON format.

### `DoctrineCacheStorageAdapter` is deprecated

`doctrine/cache` is deprecated upstream. The adapter still works in 3.0 but is marked `@deprecated` and will be removed in 4.0.

Migrate to `Psr16StorageAdapter`, which works with any PSR-16 SimpleCache library:

```diff
- use Doctrine\Common\Cache\FilesystemCache;
- use Opensoft\Rollout\Storage\DoctrineCacheStorageAdapter;
- $cache = new FilesystemCache('/tmp/rollout');
- $storage = new DoctrineCacheStorageAdapter($cache);
+ use Symfony\Component\Cache\Adapter\FilesystemAdapter;
+ use Symfony\Component\Cache\Psr16Cache;
+ use MagmaSoftwareEngineering\Rollout\Storage\Psr16StorageAdapter;
+ $cache = new Psr16Cache(new FilesystemAdapter(directory: '/tmp/rollout'));
+ $storage = new Psr16StorageAdapter($cache);
```

Most modern cache libraries already provide a PSR-16 wrapper.

## Things that did NOT change

- `Rollout` public method signatures (other than added types — argument types are unchanged).
- `Feature` public method signatures.
- `RolloutUserInterface::getRolloutIdentifier()` signature.
- The percentage-rollout algorithm: `abs(crc32($id) % 100) < $percentage`. Identifier-to-bucket mapping is identical.
- `defineGroup()` closure semantics.
- Request-parameter gating semantics.

If your application code only calls `Rollout::isActive`, `Rollout::activateGroup`, `Rollout::activatePercentage`, etc., **it will continue to work unchanged on 3.0** beyond updating the composer dependency.

## Reporting issues

[bitbucket.org/magmasoftwareengineering/rollout/issues](https://bitbucket.org/magmasoftwareengineering/rollout/issues)
