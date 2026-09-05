# Documentation

This page explains what the extension's documentation covers and where to find
it. The docs are written for developers who build a headless TYPO3 frontend
with EXT:headless and EXT:content_blocks — see the [README](../README.md)
whether the extension fits your setup.

## Getting started

- [Getting started](getting-started.md) — install the extension, include the
  Site Set, and verify your first JSON response

## Concepts (why it works this way)

- [Architecture](concepts/architecture.md) — the normalization pipeline from
  Content Block record to JSON: DataProcessor, `RecordArrayBuilder`,
  normalizers, field value transformers, and the extension points

## How-to guides (solve a task)

- [Define image variants](how-to/define-image-variants.md) — responsive
  thumbnails per field via `headless.yaml`, with per-site TypoScript overrides
- [Migrate legacy thumbnails](how-to/migrate-legacy-thumbnails.md) — replace
  the old `headless.php` thumbnail generators
- [Post-process JSON with headless.php](how-to/post-process-with-headless-php.md)
- [Add sub data processors](how-to/add-sub-dataprocessors.md) — menus,
  record lists and other TypoScript data inside a block's `data`
- [Render containers](how-to/render-containers.md) — EXT:container columns
  via the `nb-container-json` processor
- [Register a custom normalizer](how-to/register-custom-normalizer.md) — own
  value types in the JSON output
- [Register a field value transformer](how-to/register-field-value-transformer.md) —
  own string field shaping (like password blanking)
- [Generate JSON Schema](how-to/generate-json-schema.md) — JSON Schema
  files describing the block output, for IDEs, generated types and
  contract tests
- [Modify fields with the PSR-14 event](how-to/modify-fields-with-event.md)
  (deprecated — prefer normalizers/transformers)

## Reference (look it up)

- [JSON contract](reference/json-contract.md) — the exact output shape per
  field type, frozen by characterization tests
- [Normalizers and transformers](reference/normalizers.md) — built-in
  services, interfaces and DI tags
- [Processor options](reference/processor-options.md) — TypoScript options of
  `nb-content-blocks-json` and `nb-container-json`

## Troubleshooting

- [Troubleshooting](troubleshooting.md) — symptom → cause → fix
- [Testing troubleshooting](testing-troubleshooting.md) — symptom →
  cause → fix for the extension's own test setup (contributors)

## Design records (internal)

`design/` holds planning and analysis records — where wording differs from
the code, the code wins. Notable: [improve_to_array.md](design/improve_to_array.md)
— the 2026-08 rewrite of the ToArray conversion (normalizer registry, Schema
API migration, declarative image variants) with its decisions and rationale;
[json_schema_generation.md](design/json_schema_generation.md) — the plan
for generating JSON Schema from the Content Block definitions (issue #22;
phase 1 — CLI generator and contract tests — is implemented, see
[Generate JSON Schema](how-to/generate-json-schema.md)).

## Archive (internal)

[_archive/](_archive/README.md) holds superseded documentation — how the
extension got here, not how it works today. Notable:
[legacy thumbnails via headless.php](_archive/legacy-headless-php-thumbnails.md)
— the `ImageViewHelper` pattern that declarative image variants replaced.
