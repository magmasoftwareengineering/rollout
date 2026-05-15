# magmasoftwareengineering/rollout

Feature flags for PHP. A maintained successor to [`opensoft/rollout`](https://github.com/opensoft/rollout), itself a port of Ruby's [FetLife/rollout](https://github.com/FetLife/rollout).

## Status

> [`opensoft/rollout`](https://github.com/opensoft/rollout) has been unmaintained since 2017. The last upstream release predates PHP 7.4, the test suite targets a PHPUnit version EOL'd in 2017, and the legacy `ext-mongo` extension it relies on was removed from PHP itself in 7.0. Pull requests have sat unmerged for years.
>
> **This package is the active successor.** It targets the current PHP runtime (8.3+) on a modern toolchain, ships the storage adapters that 2.x lacked or had broken, and tracks the same wire-compatible behaviour so existing applications can migrate with a one-line `composer.json` change. See [Migrating from `opensoft/rollout`](#migrating-from-opensoftrollout) below.

## What's new in 3.0

- **PHP 8.3+** baseline with strict types, return/parameter types, and `#[\Override]` attributes throughout.
- **JSON wire format** for serialised feature definitions, replacing the pipe-delimited string used by 2.x. Pipe-delimited records already in storage are read transparently — no migration script needed.
- **Real interfaces.** `RolloutInterface` and `FeatureInterface` extracted so consumers can DI, mock, and decorate against the contract rather than the concrete classes.
- **Modernised storage adapters.** `MongoDBStorageAdapter` rewritten for the modern `mongodb/mongodb` library and `ext-mongodb` (1.x or 2.x). New `Psr16StorageAdapter` works with any PSR-16 SimpleCache library; `DoctrineCacheStorageAdapter` is now `@deprecated` (will be removed in 4.0).
- **Codeception 5 test suite** with full coverage of `Feature`, `Rollout`, every storage adapter, and the wire-format reader for both formats.
- **Psalm static analysis** at level 4. Zero errors.
- **Gitea Actions workflow** defining a PHP 8.3 + 8.5 CI matrix (run on the maintainers' internal Gitea mirrors).

For a complete BC-break list see [`CHANGELOG.md`](CHANGELOG.md); for migration guidance see [`UPGRADE-3.0.md`](UPGRADE-3.0.md).

## Installation

```sh
composer require magmasoftwareengineering/rollout
```

Requires PHP 8.3+.

## Migrating from `opensoft/rollout`

Replace the dependency:

```diff
- "opensoft/rollout": "^2.3"
+ "magmasoftwareengineering/rollout": "^3.0"
```

Existing `use Opensoft\Rollout\Rollout;` statements continue to resolve via `class_alias` shims. New code should use the modern namespace:

```php
use MagmaSoftwareEngineering\Rollout\Rollout;
use MagmaSoftwareEngineering\Rollout\Storage\ArrayStorage;
```

For the full migration guide including BC breaks, see [`UPGRADE-3.0.md`](UPGRADE-3.0.md).

## Quick start

```php
use MagmaSoftwareEngineering\Rollout\Rollout;
use MagmaSoftwareEngineering\Rollout\Storage\ArrayStorage;

$rollout = new Rollout(new ArrayStorage());

// Activate globally
$rollout->activate('chat');

// Is the feature active?
$rollout->isActive('chat');                  // true
$rollout->isActive('chat', $user);           // true
```

## Targeting

### Groups

`all` is registered by default. Define your own:

```php
$rollout->defineGroup('caretakers', static function (?RolloutUserInterface $user): bool {
    return $user?->isCaretaker() ?? false;
});

$rollout->activateGroup('chat', 'caretakers');
$rollout->isActive('chat', $user);          // depends on the closure
```

Multiple groups can be active per feature.

### Specific users

```php
$rollout->activateUser('chat', $user);
$rollout->deactivateUser('chat', $user);
```

User objects must implement `RolloutUserInterface` (a single `getRolloutIdentifier(): string` method).

### Percentages

```php
$rollout->activatePercentage('chat', 20);
```

Activation is deterministic given the same identifier:

```php
abs(crc32($user->getRolloutIdentifier()) % 100) < $percentage
```

Activating at 100% is equivalent to a global activation.

### Request parameters

```php
$rollout->activateRequestParam('chat', 'FF_facebookIntegration=1');
$rollout->isActive('chat', null, ['FF_facebookIntegration' => '1']);  // true
```

### Combining

A feature is active for a given user when **any** of the configured rules match: explicit user list, percentage, group, or request parameter.

## Storage adapters

| Adapter                          | Use case                                                       | Required |
| -------------------------------- | -------------------------------------------------------------- | -------- |
| `ArrayStorage`                   | Default. In-process, non-persistent. Fine for tests.           | bundled  |
| `Psr16StorageAdapter`            | **Recommended for new projects.** Any PSR-16 cache library — `symfony/cache`, `cache/cache`, `laminas/laminas-cache`. | `psr/simple-cache` |
| `RedisStorageAdapter`            | Persistent via Redis. Accepts `\Redis` (ext-redis) or `\Predis\Client`. | `ext-redis` or `predis/predis` |
| `PDOStorageAdapter`              | Persistent via any RDBMS supported by PDO.                     | `ext-pdo` |
| `MongoDBStorageAdapter`          | Persistent via MongoDB. Rewritten in 3.0 for the modern `mongodb/mongodb` library. Supports both `ext-mongodb` 1.x (with `mongodb/mongodb ^1.20`) and 2.x (with `mongodb/mongodb ^2.0`). | `ext-mongodb`, `mongodb/mongodb: ^1.20 \|\| ^2.0` |
| `DoctrineCacheStorageAdapter`    | **Deprecated.** `doctrine/cache` itself is deprecated upstream. Use `Psr16StorageAdapter` instead. Will be removed in 4.0. | `doctrine/cache` |

All adapters implement `Storage\StorageInterface` and can be substituted freely.

## Removing a feature

```php
$rollout->remove('chat');
```

Code that still calls `isActive('chat')` will recreate the feature with default (deactivated) settings on the next call.

## Wire format

3.0 serialises feature definitions as JSON:

```json
{"percentage":50,"groups":["admins"],"users":["user-72"],"requestParam":null,"data":{}}
```

Existing data written by `opensoft/rollout` 2.x (pipe-delimited, e.g. `50|user-72|admins||{}`) is read transparently. New writes always emit JSON. See [`UPGRADE-3.0.md`](UPGRADE-3.0.md) for the mid-upgrade-fleet caveat.

## Framework integrations

- **Slim 4:** [`magmasoftwareengineering/rollout-group-slim`](https://bitbucket.org/magmasoftwareengineering/rollout-group-slim) provides Slim integration with multi-storage replication, a Symfony Console command for admin, Twig extensions, and a `FeatureFlaggableTrait`.
- **OpenFeature:** an OpenFeature Provider built on this package is in progress (see `magmasoftwareengineering/rollout-openfeature-provider`).

## Implementations in other languages

- Ruby: <https://github.com/FetLife/rollout> (the original)
- Python: <https://github.com/asenchi/proclaim>

## Licence

MIT. See [`LICENSE`](LICENSE).

Original copyright © 2017 James Golick, BitLove, Inc. and Opensoft. 3.0 modernisation copyright © 2026 MagmaSoftware Engineering.
