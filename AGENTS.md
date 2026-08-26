# AGENTS.md - nb_headless_content_blocks

## Project Overview

TYPO3 Extension for headless Content Blocks. Converts Content Block data into JSON-compatible arrays for API output.

## Technical Details

- **Namespace**: `Netzbewegung\NbHeadlessContentBlocks\`
- **PHP-Version**: 8.2+ (required by TYPO3 v13/v14)
- **TYPO3**: ^13.4 || ^14.3
- **Dependencies**: `friendsoftypo3/content-blocks`, `friendsoftypo3/headless`

## Directory Structure

```
Classes/
├── DataProcessing/
│   ├── ContentBlocksJsonDataProcessor.php    # Main processor for Content Blocks
│   └── ContainerJsonDataProcessor.php        # Processor for EXT:container
├── DataProcessing/ToArray/
│   ├── RecordToArray.php
│   ├── ArrayRecursiveToArray.php
│   ├── FileReferenceToArray.php
│   ├── LazyFileReferenceCollectionToArray.php
│   ├── LazyRecordCollectionToArray.php
│   ├── LazyRecordCollectionSysCategoryToArray.php
│   ├── TypolinkParameterToArray.php
│   └── LazyFolderCollectionToArray.php
└── Event/
    └── ModifyArrayRecursiveToArrayEvent.php  # PSR-14 Event

Configuration/
└── Sets/HeadlessContentBlock/
    ├── setup.typoscript
    └── config.yaml
```

## Core Components

### DataProcessor

| Class | Service ID | Purpose |
|---|---|---|
| `ContentBlocksJsonDataProcessor` | `nb-content-blocks-json` | Content Blocks → JSON |
| `ContainerJsonDataProcessor` | `nb-container-json` | EXT:container → JSON |

### Dependencies (Constructor Injection)

**ContentBlocksJsonDataProcessor:**
- `TableDefinitionCollection`
- `RecordFactory`
- `ContentTypeResolver`
- `ContentBlockRegistry`
- `EventDispatcher`

**ContainerJsonDataProcessor:**
- `TableDefinitionCollection`
- `RecordFactory`
- `ContentBlockDataDecorator`
- `ContentTypeResolver`
- `ContentBlockRegistry`

### PSR-14 Event

`ModifyArrayRecursiveToArrayEvent` - fired when converting arrays.

## Important Notes

### Git Workflow

- Before every commit: Run CGL and PHPStan (`Build/Scripts/runTests.sh -s cgl` / `-s phpstan`)

### Language

- All documentation and comments in this project are written in **English**.

### Code Changes

- Use `readonly` class declarations (PHP 8.2+)
- `GeneralUtility::makeInstance()` in Utility classes (no DI)
- `autoconfigure: false` in `Services.yaml`

### External Dependencies

- `B13\Container\DataProcessing\ContainerProcessor` (only in `ContainerJsonDataProcessor`)
- `TYPO3\CMS\ContentBlocks\*` (Core Content Blocks)

## Testing Framework

### Tools

| Tool | Version | Purpose |
|---|---|---|
| PHPUnit | 11.x | Test execution |
| TYPO3 Testing Framework | ^9.5 | Bootstrap, test base classes |
| PHPStan | ^2.1 (Level 5) | Static analysis |
| PHP-CS-Fixer | ^3.22 | Coding standards |
| runTests.sh | Docker | Test runner |

### Directory Structure

```
Build/
├── phpunit/
│   ├── UnitTests.xml
│   ├── UnitTestsBootstrap.php
│   ├── FunctionalTests.xml
│   └── FunctionalTestsBootstrap.php
├── phpstan/
│   ├── phpstan.neon
│   ├── phpstan.local.neon
│   ├── phpstan.ci.neon
│   └── phpstan-constants.php
├── php-cs-fixer/
│   └── config.php
└── Scripts/
    └── runTests.sh

Tests/
├── Unit/
│   ├── Event/
│   │   └── ModifyArrayRecursiveToArrayEventTest.php
│   └── DataProcessing/ToArray/
│       ├── ArrayRecursiveToArrayTest.php
│       └── TypolinkParameterToArrayTest.php
├── Functional/
│   └── DataProcessing/
│       ├── ContentBlocksJsonDataProcessorTest.php
│       ├── ContainerJsonDataProcessorTest.php
│       └── Fixtures/
│           ├── DataSet/ (CSV fixtures)
│           └── Files/ (test images)
└── Fixtures/Extensions/test_nb_headless_content_blocks/
    ├── ContentBlocks/ContentElements/
    │   ├── simple/       # Text, Number, DateTime, Select, Password, Json, Link, Category, Collection
    │   ├── headless/     # headless.php processing
    │   └── filetest/     # File/FAL (oneToOne, oneToMany)
    └── Classes/
        └── SetRenderedContentProcessor.php  # Stub for container child rendering
```

### Commands

```bash
# All tests
Build/Scripts/runTests.sh -s unit && Build/Scripts/runTests.sh -s functional

# Unit tests only
Build/Scripts/runTests.sh -s unit

# Functional tests only
Build/Scripts/runTests.sh -s functional

# CGL check
Build/Scripts/runTests.sh -s cgl

# PHPStan
Build/Scripts/runTests.sh -s phpstan

# Specify PHP version
Build/Scripts/runTests.sh -s unit -p 8.4

# Functional tests on sqlite (e.g. in non-interactive environments)
Build/Scripts/runTests.sh -s functional -d sqlite -p 8.4

# Code coverage (unit + functional, HTML report, Clover XML and raw .cov data in .Build/coverage/)
Build/Scripts/runTests.sh -s unit -k
Build/Scripts/runTests.sh -s functional -d sqlite -k

# Merge coverage of both suites into combined HTML report (.Build/coverage/merged/),
# merged Clover XML and AI-friendly text summary (.Build/coverage/merged.txt).
# Use the same PHP version (-p) as for the coverage runs.
Build/Scripts/runTests.sh -s mergeCoverage
```

**Note:** `runTests.sh` detects non-interactive shells automatically and drops the
`-it` flag. `CI=true` is only required when using the CI PHPStan config
(`phpstan.ci.neon`).

**Running PHP directly:** There is no PHP on the host, but DDEV is running. Use
`ddev exec php ...` (also for composer, phpstan, php-cs-fixer, etc.). Composer
binaries are in `.Build/bin/`, e.g. `ddev exec .Build/bin/phpunit --version`.

### Testing Gotchas

- **act: run one TYPO3 matrix entry at a time** — `act -j functional_tests` runs all
  matrix entries in parallel. Each job starts 4 docker containers (redis, memcached,
  DB, phpunit) on the shared daemon; `runTests.sh`'s `waitFor()` aborts after ~10s,
  so parallel runs fail with `Can not connect ... Aborting`. Use
  `act -j functional_tests --matrix typo3:^13.4` and `--matrix typo3:^14.3` separately.
- **act: composer cache is evicted after 7 days unused** — so workflows that haven't
  run in a week re-download dependencies. The cache key is `hashFiles('**/composer.json')`,
  so switching TYPO3 versions also invalidates it.
- **Root-owned test folders after `act`** — `act` runs its job containers as root, leaving
  root-owned `.Build/public/typo3temp/var/tests/functional-*` folders. Local
  `runTests.sh -s functional` then fails with
  `TYPO3\TestingFramework\Core\Exception: Can not remove folder`. Detect with
  `find .Build/public/typo3temp/var/tests -maxdepth 1 -user root`. Do NOT run `sudo`
  yourself — tell the USER (who has root) to run `sudo rm -rf <folders>`.
- **Stale DI container / cache issues** — if services resolve wrongly or newly added
  DI configuration (Services.yaml, tagged services) is not picked up:
  `ddev typo3 cache:flush`, `ddev composer dump-autoload`, or `rm -rf var/cache/`.
  Note: unit tests (`runTests.sh -s unit`) run **without any DI container** by design —
  `GeneralUtility::makeInstance()` for container-only services (e.g.
  `TcaSchemaFactory`) fails there and code must tolerate that; functional tests
  bootstrap the container normally.

### Test Strategy

- **Unit Tests**: `ModifyArrayRecursiveToArrayEvent` — pure event object
- **Functional Tests**: DataProcessor with TYPO3 context (InMemory-PDO)

## Development

### Setup

```bash
# Install dependencies
ddev composer install

# Start DDEV
ddev start
```

### Directories

- `.Build/vendor` - Composer Vendor Directory
- `.Build/bin` - Composer Binaries
- `.Build/public` - Web Root (TYPO3)

### Workflow

1. Run `ddev composer install`
2. Run tests with `Build/Scripts/runTests.sh`
3. Before commits: Check CGL and PHPStan

## TYPO3 Version Compatibility

**All tests (unit, functional, phpstan) must pass on both TYPO3 13 and TYPO3 14.**

### Switching TYPO3 version

```bash
# Switch to TYPO3 13
Build/Scripts/runTests.sh -s composer -- require -W \
  "typo3/cms-core:^13.4" \
  "friendsoftypo3/content-blocks:^1.2.3" \
  "friendsoftypo3/headless:^4.5"

# Switch to TYPO3 14
Build/Scripts/runTests.sh -s composer -- require -W \
  "typo3/cms-core:^14.3" \
  "friendsoftypo3/content-blocks:^2.0" \
  "friendsoftypo3/headless:^5.0"

# Restore multi-version constraints in composer.json
git checkout -- composer.json
```

`b13/container` is compatible with both versions and does not need to be changed.

### API differences between TYPO3 13 and 14

- `TcaFieldDefinition::__construct()` — v14 added `$parentTable` parameter (v13 has no `$parentTable`)
- `RawRecord::__construct()` — v14 renamed `$type` to `$fullType`

When writing tests that use these classes, use `property_exists()` to detect the version
at runtime and pass the correct parameters. Add `@phpstan-ignore-line` for the
version-compatible code since PHPStan analyzes against the currently installed version.

For `RawRecord`: use positional arguments (the 5th param is `$type` in v13, `$fullType` in v14,
but the position is the same). For `TcaFieldDefinition`: use named arguments with
`property_exists()` and spread the args array, since `$parentTable` is inserted at a
different position in v14.

PHPStan version-dependent errors are handled in `Build/phpstan/phpstan.neon` via
`ignoreErrors` with `reportUnmatched: false`, so the same config works for both versions.
