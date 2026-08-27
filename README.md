[![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-13.4-ff8700?maxAge=3600&logo=typo3)](https://get.typo3.org/)
[![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-14.3-ff8700?maxAge=3600&logo=typo3)](https://get.typo3.org/)

# EXT:nb_headless_content_blocks

Connects [EXT:headless](https://github.com/TYPO3-Headless/headless) and
[EXT:content_blocks](https://github.com/FriendsOfTYPO3/content-blocks): it
converts Content Block records into JSON-compatible arrays for headless
frontends — with a stable, test-frozen JSON contract.

**Documentation:** [docs/README.md](docs/README.md) — start with
[Getting started](docs/getting-started.md). Changes are tracked in the
[CHANGELOG](CHANGELOG.md).

## What it does

- Converts every Content Block field type to JSON without extra
  configuration: richtext via `parseFunc_RTE`, links as
  `{url, target, type, title, config, attr}`, files as
  `{id, alt, title, publicUrl}`, categories, collections, relations,
  FlexForms, date times, and more — see the
  [JSON contract](docs/reference/json-contract.md).
- Field identifiers (not database columns) as JSON keys, alphabetically
  sorted — stable for frontend consumers.
- **Declarative image variants:** responsive thumbnails per field via an
  optional `headless.yaml` in the Content Block, overridable per site via
  TypoScript — no PHP needed.
- Extensible conversion pipeline: register your own
  [normalizers](docs/how-to/register-custom-normalizer.md) and
  [field value transformers](docs/how-to/register-field-value-transformer.md)
  via DI tags.
- Escape hatches: per-block [headless.php](docs/how-to/post-process-with-headless-php.md),
  [sub data processors](docs/how-to/add-sub-dataprocessors.md), and a
  [PSR-14 event](docs/how-to/modify-fields-with-event.md) (deprecated).
- Support for [EXT:container](docs/how-to/render-containers.md) via the
  `nb-container-json` processor.

## Installation

```bash
composer require netzbewegung/nb_headless_content_blocks
```

Include the Site Set "Headless Content Blocks" in your site's
`config.yaml`:

```yaml
sets:
  - nb-headless-content-blocks/headless-content-blocks
```

You are ready to go — the walkthrough with an example response lives in
[Getting started](docs/getting-started.md).

## Development

Setup, tests and contribution rules live in
[CONTRIBUTING.md](CONTRIBUTING.md); machine-oriented onboarding (testing
gotchas, TYPO3 version switching) in [AGENTS.md](AGENTS.md).

## License

GPL-2.0-or-later
