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
├── ContentBlocks/
│   ├── ContentBlocksIdentifierMapper.php     # column -> field identifier mapping (ContentBlocks)
│   ├── HeadlessYamlLoader.php                # loads headless.yaml image processing config
│   └── IdentifierMapperInterface.php
├── DataProcessing/
│   ├── ContentBlocksJsonDataProcessor.php    # Main processor for Content Blocks
│   └── ContainerJsonDataProcessor.php        # Processor for EXT:container
├── FieldTransformer/
│   ├── FieldValueTransformerChain.php
│   ├── FieldValueTransformerInterface.php
│   └── String/
│       ├── PasswordBlanker.php
│       └── RichtextParser.php
├── Normalization/
│   ├── Context.php                           # per-run state (schema, options, field processing)
│   ├── NormalizerChain.php                   # dispatches to tagged normalizers
│   ├── NormalizerInterface.php
│   ├── RecordArrayBuilder.php                # DI entry point of the conversion
│   ├── UnknownTypeNormalizer.php             # null + debug log for unknown types
│   ├── Normalizer/
│   │   ├── ScalarNormalizer.php
│   │   ├── DateTimeNormalizer.php
│   │   ├── FlexFormNormalizer.php
│   │   ├── TypolinkNormalizer.php
│   │   ├── RecordNormalizer.php
│   │   ├── RecordCollectionNormalizer.php
│   │   ├── FileReferenceNormalizer.php       # incl. crop + declarative thumbnails
│   │   └── FolderCollectionNormalizer.php
└── Event/
    └── ModifyArrayRecursiveToArrayEvent.php  # PSR-14 Event (deprecated, still fired)

Configuration/
├── Services.yaml                             # tagged services: nb_headless.normalizer,
│                                             # nb_headless.field_value_transformer
└── Sets/HeadlessContentBlock/
    ├── setup.typoscript
    └── config.yaml

docs/
├── README.md                                 # documentation index (Diátaxis)
├── getting-started.md                        # tutorial: install → include Site Set → verify
├── troubleshooting.md                        # symptom → cause → fix
├── concepts/                                 # why it works this way
│   └── architecture.md
├── how-to/                                   # task guides (image variants, normalizers, ...)
├── reference/                                # lookup (JSON contract, normalizers, options)
└── design/
    └── IMPROVE_TO_ARRAY.md                   # design record: the ToArray rewrite
```

See `docs/design/IMPROVE_TO_ARRAY.md` for the architecture rationale.

## Documentation Rules

- **Docs change with the code in the same PR/commit** — a behavior change
  without a docs change is incomplete.
- **One page = one topic type** (tutorial / how-to / reference / concept),
  with a first-line purpose statement. The docs index is `docs/README.md`.
- **Design records** (`docs/design/`) open with a status blockquote
  (`> Status: IMPLEMENTED|CURRENT|...`) and are historical records — where
  wording differs from the code, **the code wins**.
- **Troubleshooting** entries follow **symptom → cause → fix**, the
  heading is the literal symptom.
- All shipped content is **English** (American spelling).
- Before committing docs changes: run `Build/Scripts/checkDocs.sh` (link
  checker for relative Markdown links and anchors).

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
- `RecordArrayBuilder`
- `ContentDataProcessor`

**ContainerJsonDataProcessor:**
- `ContainerProcessor` (b13/container)

### PSR-14 Event

`ModifyArrayRecursiveToArrayEvent` - fired by `RecordArrayBuilder` per field
(deprecated, kept for backwards compatibility).

## Important Notes

### Git Workflow

- Before every commit: Run CGL and PHPStan (`Build/Scripts/runTests.sh -s cgl` / `-s phpstan`)
- New releases/tags: `Build/Scripts/tag-version.sh <x.y.z>` — sets the version in
  `composer.json` (`extra.typo3/cms.version`) and `ext_emconf.php`, commits both
  and creates the git tag (requires a clean working tree).

### Language

- All documentation and comments in this project are written in **English**.

### Code Changes

- Use `readonly` class declarations (PHP 8.2+)
- No `GeneralUtility::makeInstance()` / `$GLOBALS['TYPO3_REQUEST']` in `Classes/`
  — dependencies go through DI; the frontend request and the processor's
  `ContentObjectRenderer` are threaded through `RecordArrayBuilder` into the
  normalization `Context` (the only exceptions are static path helpers like
  `GeneralUtility::getFileAbsFileName()`)
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
│   └── Normalization/
│       ├── RecordArrayBuilderTest.php
│       └── Normalizer/
│           └── TypolinkNormalizerTest.php
├── Functional/
│   ├── DataProcessing/
│   │   ├── ContentBlocksJsonDataProcessorTest.php
│   │   ├── ContentBlocksJsonDataProcessorCharTest.php  # frozen JSON contract
│   │   ├── ContainerJsonDataProcessorTest.php
│   │   └── Fixtures/
│   │       ├── DataSet/ (CSV fixtures)
│   │       └── Files/ (test images)
│   └── Frontend/
│       ├── ContentBlocksJsonResponseTest.php           # e2e: full frontend request,
│       │                                               # headless page JSON frozen (issue #18)
│       └── Fixtures/DataSet/e2e_page.csv               # pages row of the e2e site
└── Fixtures/Extensions/test_nb_headless_content_blocks/
    ├── Configuration/Sets/TestFrontend/                 # fixture site set: maps test
    │                                                   # blocks onto lib.contentBlock
    ├── ContentBlocks/ContentElements/
    │   ├── simple/       # Text, Number, DateTime, Select, Password, Json, Link, Category, Collection
    │   ├── headless/     # headless.php processing
    │   └── filetest/     # File/FAL (oneToOne, oneToMany, headless.yaml thumbnails)
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

- **act basics** (installation, available jobs, matrix invocation):
  `.github/TEST-GITHUB-WORKFLOWS.md`.
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

- **Unit Tests**: `RecordArrayBuilder`, `TypolinkNormalizer`, event — pure, container-less
- **Functional Tests**: DataProcessors with TYPO3 context (InMemory-PDO) + characterization
  tests (`ContentBlocksJsonDataProcessorCharTest`) freezing the complete JSON contract
- **E2E Tests** (`Tests/Functional/Frontend/`): full frontend request against a headless
  site (site config + fixture site set `test_nb_headless_content_blocks/test-frontend`,
  which depends on `friendsoftypo3/headless` and this extension's set) via
  `executeFrontendSubRequest()`; freezes the complete headless page JSON response
  (`ContentBlocksJsonResponseTest`, issue #18). Runs on sqlite; absolute file URLs are
  normalized to `https://` across TYPO3 13/14 by setting `$_SERVER['HTTPS']`
  (TYPO3 13 builds hosts via `getIndpEnv()`, TYPO3 14 via the request object)

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
  "friendsoftypo3/headless:^5.0@RC"

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
