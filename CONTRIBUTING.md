# Contributing

Thanks for your interest in EXT:nb_headless_content_blocks. This page
explains how to work on the code. For what the extension does, see the
[README](README.md) and the [documentation index](docs/README.md);
machine-oriented onboarding lives in [AGENTS.md](AGENTS.md).

## Workflow

1. Create a feature branch off `master`: `feature/<name>`.
2. Work there until stable.
3. Open a pull request against `master`. CI (CGL, PHPStan, unit +
   functional tests on TYPO3 13.4 and 14.3) must be green before merge.
4. Before every commit: run CGL and PHPStan (see below).

## Development setup

The project runs on [DDEV](https://ddev.com):

```bash
ddev start
ddev composer install
touch .Build/public/FIRST_INSTALL
ddev launch
```

Dependencies are installed into `.Build/vendor` (TYPO3 web root:
`.Build/public`). There is no PHP on the host — use `ddev exec php ...`
for direct tool calls; composer binaries are in `.Build/bin/`.

Exclude `.Build/public/typo3temp` from IDE indexing — functional tests
create isolated TYPO3 instances below `typo3temp/var/tests`:

- **PhpStorm**: right-click the directory → *Mark Directory as* →
  *Excluded*.
- **VS Code** (`.vscode/settings.json`):

```json
{
    "files.exclude": {
        "**/.Build/public/typo3temp": true
    },
    "search.exclude": {
        "**/.Build/public/typo3temp": true
    },
    "files.watcherExclude": {
        "**/.Build/public/typo3temp/**": true
    }
}
```

## Tests

```bash
Build/Scripts/runTests.sh -s cgl        # PHP CS Fixer (coding standards)
Build/Scripts/runTests.sh -s phpstan    # static analysis (level 5)
Build/Scripts/runTests.sh -s unit       # unit tests (no DI container)
Build/Scripts/runTests.sh -s functional -d sqlite   # functional tests
```

- All suites must pass on **both TYPO3 versions** — switch with the
  composer commands in [AGENTS.md](AGENTS.md) (TYPO3 Version
  Compatibility) and restore `composer.json` afterwards.
- Pitfalls of the test setup itself (version switches, `act`, extension
  loading) are collected in
  [Testing troubleshooting](docs/testing-troubleshooting.md).
- The JSON output contract is frozen by characterization tests
  (`ContentBlocksJsonDataProcessorCharTest`). If a change deliberately
  alters the contract, update the frozen fixtures and document it in the
  [CHANGELOG](CHANGELOG.md) and the
  [JSON contract](docs/reference/json-contract.md) in the same PR.

## Running the GitHub Actions workflows locally (act)

[nektos/act](https://github.com/nektos/act) runs the CI jobs from
`.github/workflows/` locally — no push needed:

```bash
# installation (or download a release binary and put it on your PATH)
curl --proto '=https' --tlsv1.2 -sSf https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash

# list the available jobs
act -l

# single jobs
act -j early_cgl
act -j PHPStan

# functional tests - TYPO3 13.4
act -j functional_tests --matrix typo3:^13.4 --matrix php:8.2 --matrix content-blocks:^1.2 --matrix headless:^4.5 --matrix container:^3.1

# functional tests - TYPO3 14.3 (only AFTER the 13.4 run has finished)
act -j functional_tests --matrix typo3:^14.3 --matrix php:8.4 --matrix content-blocks:^2.0 --matrix headless:^5.0@RC --matrix container:^4.0
```

> **Warning:** never run both functional test matrix entries at the same
> time (e.g. one per terminal). Each job starts 4 docker containers
> (redis, memcached, DB, phpunit) on the shared docker daemon, and
> `runTests.sh`'s `waitFor()` aborts after ~10s — parallel runs fail with
> `Can not connect ... Aborting`. Run them strictly one after another.

More `act` pitfalls (composer cache eviction, root-owned test folders):
[Testing troubleshooting](docs/testing-troubleshooting.md) and
[AGENTS.md](AGENTS.md).

## Documentation

User-facing documentation lives in `docs/` and is organized by topic type
(concepts, how-to guides, reference — see `docs/README.md`):

- A PR that changes user-facing behavior updates the affected page **in
  the same PR**.
- One page = one topic type, with a first-line purpose statement.
- All shipped content (code, docs, comments) is in English.

Design records for larger decisions live in `docs/design/` — historical
records with a status header; where wording differs from the code, the
code wins.

Before committing docs changes, run the link checker:

```bash
Build/Scripts/checkDocs.sh
```

## Releasing

Do not bump the version unless a maintainer asks for a release. The
version is tracked in `composer.json` (`extra.typo3/cms.version`) and
mirrored in `ext_emconf.php`.
