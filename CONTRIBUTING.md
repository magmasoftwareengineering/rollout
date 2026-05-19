# Contributing to `magmasoftwareengineering/rollout`

Thanks for considering a contribution. This package is the maintained successor to `opensoft/rollout` and is open to external contributions. The maintainers (MagmaSoftware Engineering) cannot promise an SLA on review turnaround, but we'll engage with substantive issues and pull requests.

## Where things live

The project is hosted on **Bitbucket**. The historical GitHub fork at `github.com/magmasoftwareengineering/rollout` is archived and read-only.

- **Repository:** <https://bitbucket.org/magmasoftwareengineering/rollout>
- **Issues:** <https://bitbucket.org/magmasoftwareengineering/rollout/issues>
- **Pull requests:** <https://bitbucket.org/magmasoftwareengineering/rollout/pull-requests>

If you're arriving from the GitHub fork, please open issues and pull requests on Bitbucket. A free Bitbucket account is sufficient. If for some reason that's not viable, please email the maintainer (see below) — we'd rather hear about a problem in any form than miss it.

## Reporting issues

Before opening an issue, please:

1. Search existing issues to see if it's already reported.
2. Include the PHP version (`php --version`), the package version (`composer show magmasoftwareengineering/rollout`), and a minimal reproduction.
3. For bugs in storage adapters, mention which adapter and the relevant extension or library version (`ext-mongodb`, `mongodb/mongodb`, `predis/predis`, etc.).

Security-sensitive issues should be emailed privately to <hello@phpcodemonkey.me.uk> rather than filed publicly.

## Setting up locally

```sh
git clone git@bitbucket.org:magmasoftwareengineering/rollout.git
cd rollout
composer install
```

If you don't have `ext-mongodb` installed locally, the install needs a flag:

```sh
composer install --ignore-platform-req=ext-mongodb
```

Tests for `MongoDBStorageAdapter` and `Psr16StorageAdapter` use mocks, so they pass without those services running locally — you only need the extensions if you want to run those specific tests against real backends in future.

### Running the test suite

```sh
vendor/bin/codecept run Unit
```

### Running static analysis

```sh
vendor/bin/psalm
```

Both must pass before opening a pull request.

## Branching model

- **`main`** — tagged releases only. Don't commit directly.
- **`develop`** — the integration branch. Pull requests merge here. Don't commit directly.
- **`feature/**`** — your work-in-progress branches. CI runs on push.

When starting a change, branch off `develop`:

```sh
git checkout develop
git pull origin develop
git checkout -b feature/short-description-of-change
```

## Code style and conventions

- **PHP 8.3+ idioms.** `declare(strict_types=1);` at the top of every PHP file. Real return and parameter types throughout — no `@param` / `@return` docblocks for things the type system can express. Use constructor property promotion where it simplifies the class.
- **Modern syntax.** `[]` rather than `array()`; arrow functions where they aid clarity; `match` over chained `switch` for value mapping.
- **Override attributes.** Every method that implements an interface or overrides a parent method must carry `#[\Override]`. Psalm enforces this.
- **Naming.** Class names match file names. Test class names end in `Test`. Test method names are descriptive (`activatingAFeatureGloballyMakesItActiveForAllUsers`) rather than `test1`, `test2`.
- **British English** in user-facing strings, comments, and documentation.

## Tests

- **Required for behavioural change.** Bug fixes need a regression test that fails before the fix and passes after; new features need tests that exercise them.
- **Test base class.** Extend `Codeception\Test\Unit`, not `PHPUnit\Framework\TestCase` directly. The Codeception base extends PHPUnit's `TestCase` underneath, so PHPUnit attributes (`#[Test]`) and assertions (`self::assertSame`, etc.) all continue to work.
- **Lifecycle hooks.** Use Codeception's `_before()` and `_after()`, not PHPUnit's `setUp()` / `tearDown()`. Don't call `parent::setUp()`.
- **Layout.** Test files live under `tests/Unit/` mirroring the `src/` namespace structure. The autoload mapping is in `composer.json` under `autoload-dev`.

## Static analysis

Psalm runs at `errorLevel="4"` with `findUnusedBaselineEntry="true"`. New code must keep the suite at zero errors. If you genuinely need a suppression, use `@psalm-suppress` with a brief comment explaining why; don't add baseline entries for new code.

## Continuous integration

The Gitea Actions workflow file (`.gitea/workflows/test.yml`) is checked into the repository so contributors can see the exact CI matrix the maintainers run against — PHP 8.3 and PHP 8.5, with `composer validate`, Psalm, and the Codeception Unit suite.

**Automated CI runs happen only on the maintainers' private Gitea mirrors**, not on Bitbucket. External contributors will not see automated CI status on their pull requests, and the workflow file is provided as documentation of the matrix rather than as a runner you can rely on observing.

**Before opening a pull request**, please run the suite locally:

```sh
vendor/bin/codecept run Unit
vendor/bin/psalm
```

Both must pass. The maintainers will run the same checks on the internal mirrors before merging.

## Submitting a pull request

1. Push your feature branch to Bitbucket.
2. Open a pull request targeting `develop`.
3. Describe **what** changed and **why**. Reference any issue it addresses.
4. Confirm the test suite and Psalm pass locally on your machine (include the output if you like — it speeds review).
5. Be responsive to review feedback. We'll be the same in return.

## Commit messages

- Subject line: imperative, capitalised first word, no trailing full stop, ideally under 70 characters.
- Body (if needed): wrap at ~72 columns; explain the *why*, not the *what* (the diff already shows the what).
- **No marketing lines, no `Co-Authored-By` lines, no `Generated with` signatures.**

A good example:

```
Widen mongodb/mongodb constraint to support ext-mongodb 2.x

ext-mongodb 2.x is the current PHP MongoDB extension; mongodb/mongodb
1.x only supports ext-mongodb 1.x, so the previous constraint failed
on any modern runtime. mongodb/mongodb 2.0 exposes \MongoDB\Collection
with the same findOne/updateOne/deleteOne API used by the adapter, so
no source change is needed.
```

## Code of Conduct

Participation in this project is governed by the [Code of Conduct](CODE_OF_CONDUCT.md). By contributing — issues, comments, pull requests, or otherwise — you agree to abide by it.

## Licensing

By submitting a contribution, you agree it is licensed under the same [MIT License](LICENSE) as the project. We don't require a DCO sign-off on commits.

## Credits

This package builds directly on the work of Richard Fullmer and the original `opensoft/rollout` contributors, itself a port of [FetLife's `rollout`](https://github.com/FetLife/rollout) for Ruby. Their commit history is preserved in this repository, and the original copyright notice is retained in `LICENSE`. Thanks to everyone who contributed to upstream over the years.
