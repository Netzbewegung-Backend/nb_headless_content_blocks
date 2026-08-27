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
- The JSON output contract is frozen by characterization tests
  (`ContentBlocksJsonDataProcessorCharTest`). If a change deliberately
  alters the contract, update the frozen fixtures and document it in the
  [CHANGELOG](CHANGELOG.md) and the
  [JSON contract](docs/reference/json-contract.md) in the same PR.
- There is no PHP on the host: use `ddev exec php ...` for direct tool
  calls; composer binaries are in `.Build/bin/`.

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
