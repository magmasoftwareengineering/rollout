# Changelog

## 3.0.0 — 2026-05-10

**Maintained successor to `opensoft/rollout`.** The runtime semantics are unchanged; this is a packaging, modernisation, and quality-of-life release.

### BC breaks

- **Package renamed.** Replace `opensoft/rollout` with `magmasoftwareengineering/rollout` in `composer.json`. See [`UPGRADE-3.0.md`](UPGRADE-3.0.md) for the migration guide.
- **PHP 8.3+ required** (was PHP 5.3.3+).
- **Namespace renamed** to `MagmaSoftwareEngineering\Rollout\*`. Legacy `Opensoft\Rollout\*` names continue to resolve via `class_alias` shims for one major version; will be removed in 4.0.
- **`MongoDBStorageAdapter` constructor signature changed** — now takes a `\MongoDB\Collection` directly (was an opaque `$mongo` plus collection name). The adapter is rewritten against the modern `mongodb/mongodb` library and ext-mongodb; the legacy ext-mongo path is gone. The `require-dev` constraint is `mongodb/mongodb: ^1.20 || ^2.0`, so both `ext-mongodb` 1.x and the current 2.x are supported.
- **`StorageInterface` methods are now strictly typed.** `get(string $key): ?string`, `set(string $key, string $value): void`, `remove(string $key): void`. Custom storage implementations must update their signatures.
- **`Rollout::save()` returns `void`** (was untyped). Subclasses overriding `save()` must adjust.
- **Wire format is now JSON.** Existing pipe-delimited data already in storage is read transparently; new writes always emit JSON. See `UPGRADE-3.0.md` for the mid-upgrade fleet caveat.

### Added

- **`RolloutInterface` and `FeatureInterface`** — extracted public surfaces of `Rollout` and `Feature`. Consumers should depend on the interfaces for DI, mocking, and decoration.
- **`Psr16StorageAdapter`** — works with any PSR-16 SimpleCache implementation (`symfony/cache`, `cache/cache`, `laminas/laminas-cache`, etc). Recommended replacement for `DoctrineCacheStorageAdapter`.
- **Strict types and return/parameter types** throughout. Constructor property promotion where it simplifies the class. Modern `[]` array syntax replaces `array()`.
- **`#[\Override]` attributes** on every interface implementation method.
- **Codeception 5 test suite** with 59 tests covering Feature, Rollout, all five storage adapters, and the new JSON / legacy-pipe wire-format reader.
- **Psalm static analysis** at `errorLevel="4"`. Zero errors.
- **Gitea Actions workflow** (in `.gitea/workflows/test.yml`) defining a PHP 8.3 + 8.5 matrix that runs `composer validate`, Psalm, and the Unit suite. The workflow runs on the maintainers' internal Gitea mirrors; the file is checked in so contributors can see the matrix.

### Deprecated

- **`DoctrineCacheStorageAdapter`** — `doctrine/cache` itself is deprecated upstream. Use `Psr16StorageAdapter` instead. Will be removed in 4.0.
- **`Opensoft\Rollout\*` namespace aliases** — kept for one major version. Migrate `use` statements to `MagmaSoftwareEngineering\Rollout\*`.

### Fixed

- `Feature::removeUser` and `Feature::removeGroup` now re-pack the underlying array via `array_values()` after `unset`, so iteration order is consistent. Serialised output is identical (implode ignores keys).
- `isParamInRequestParams` uses strict comparison after string-coercing the request value, replacing the loose `==` check that could mis-match across types.
- Implicitly-nullable parameter syntax fixed throughout for PHP 8.4+ compatibility (carried forward from the previous fork).

---

## Pre-3.0 (from `opensoft/rollout`)

### 2.0.0

(BC Break) The `StorageInterface` interface had a `remove` function added to its definition. If you have your own implementation of this interface, you'll need to update your implementation to include this functionality.

- Added support for removing a feature

### 1.0.4

- Added redis support [PR #9](https://github.com/opensoft/rollout/pull/9)
