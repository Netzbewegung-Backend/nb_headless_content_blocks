# Architecture

This page explains how a Content Block record becomes JSON — the pipeline,
the building blocks and the extension points. It is background, not a
tutorial; for concrete tasks see the [how-to guides](../README.md#how-to-guides-solve-a-task).

## The short version

The extension's whole job is *array shaping*: it takes rich TYPO3 domain
objects and turns them into plain, JSON-compatible arrays — with a stable,
frozen output contract. Object *resolution* is done by the TYPO3 Core
(`RecordFactory`); key mapping, field ordering and value shaping are done
here. That split is deliberate: it is what allows the same code to run on
TYPO3 13 and 14 without version forks.

## The pipeline

```
TypoScript data processor (Site Set: lib.contentBlock)
 └─ nb-content-blocks-json  (ContentBlocksJsonDataProcessor)
     ├─ RecordFactory::createResolvedRecordFromDatabaseRow()   [Core]
     │    DB row → rich objects (Record, FileReference, TypolinkParameter,
     │    DateTimeImmutable, LazyRecordCollection, ...)
     └─ RecordArrayBuilder::build()
          ├─ strip system fields (uid, pid, colPos, CType, ...)
          ├─ map columns → Content Block field identifiers
          │    (tx_myext_field → my_field, via ContentBlocksIdentifierMapper)
          ├─ dispatch ModifyArrayRecursiveToArrayEvent per field (deprecated)
          ├─ strings → FieldValueTransformerChain
          │    (PasswordBlanker, RichtextParser — schema-driven)
          ├─ everything else → NormalizerChain
          │    first registered normalizer with supports() === true wins;
          │    unknown types → UnknownTypeNormalizer (null + debug log)
          └─ ksort
 └─ headless.php include (optional, per Content Block)
 └─ sub data processors (optional, TypoScript dataProcessing.)
```

`nb-container-json` (`ContainerJsonDataProcessor`) follows the same shape
for EXT:container children: it fetches the children of a container column
and renders each through the same `RecordArrayBuilder`.

## Building blocks

| Building block | Purpose |
|---|---|
| `DataProcessing/ContentBlocksJsonDataProcessor` | TypoScript entry point (`nb-content-blocks-json`): resolves the record, delegates to `RecordArrayBuilder`, includes `headless.php`, runs sub data processors |
| `DataProcessing/ContainerJsonDataProcessor` | Same for EXT:container children (`nb-container-json`), via b13/container's `ContainerProcessor` |
| `Normalization/RecordArrayBuilder` | Orchestrates one record: system fields, identifier mapping, event, transformers, normalizers, `ksort` |
| `Normalization/NormalizerChain` | Iterates the tagged normalizers; falls back to `UnknownTypeNormalizer` |
| `Normalization/Normalizer/*` | One class per value type — see [Normalizers and transformers](../reference/normalizers.md) |
| `FieldTransformer/*` | String shaping driven by the field's schema type (password → `""`, richtext → `parseFunc_RTE`) |
| `ContentBlocks/ContentBlocksIdentifierMapper` | Column → field identifier mapping; the only ContentBlocks definition still in the conversion path |
| `ContentBlocks/HeadlessYamlLoader` | Loads the optional per-block `headless.yaml` (declarative image variants), with caching |
| `Normalization/Context` | Per-run state: current `TcaSchema`, request and `ContentObjectRenderer` of the originating DataProcessor, TypoScript `options.`, per-field image processing; lets normalizers recurse without circular DI |

## Why normalizers (and not one big converter)

Before the 2026-08 rewrite, one class held a giant `switch (true)` with
instanceof chains over ContentBlocks internals, `makeInstance()` everywhere,
and version hacks (`property_exists(...)`) to construct those internals in
tests. The rewrite (see the
[design record](../design/improve_to_array.md)) replaced it with a
**normalizer registry** — the Symfony Serializer pattern, hand-rolled
without the dependency:

- each normalizer is small and independently testable,
- site packages can add their own via DI tag (`nb_headless.normalizer`),
- field metadata comes from the **Core Schema API** (`TcaSchemaFactory`),
  which ships in TYPO3 13 *and* 14 — the version hacks disappeared because
  the code stopped constructing ContentBlocks internals.

ContentBlocks stays a hard dependency for exactly two things that have no
Core equivalent: the **identifier mapping** (dropping it would change every
consumer's JSON contract) and resolving the Content Block folder
(`headless.php`, `headless.yaml`).

## Design principles

- **The JSON contract is the product.** Output shapes are frozen by
  characterization tests (`ContentBlocksJsonDataProcessorCharTest`) that
  assert the complete JSON per fixture record. A refactor must not change
  them; a contract change is a deliberate, documented decision.
- **Never break the response.** Unknown value types become `null` with a
  debug log entry; a missing file becomes an `__errorMessage` entry in
  place — the page still renders.
- **Config before code.** What used to need a `headless.php` (image
  variants) is now declarative (`headless.yaml` + TypoScript override).
  `headless.php` remains as an escape hatch.

## Extension points

| Extension point | Mechanism | Use for | Documentation |
|---|---|---|---|
| Normalizer | DI tag `nb_headless.normalizer` | own value types in the JSON output | [How-to](../how-to/register-custom-normalizer.md) |
| Field value transformer | DI tag `nb_headless.field_value_transformer` | own string field shaping | [How-to](../how-to/register-field-value-transformer.md) |
| `headless.php` | per-Content Block PHP file | per-block post-processing of the whole `data` array | [How-to](../how-to/post-process-with-headless-php.md) |
| `ModifyArrayRecursiveToArrayEvent` | PSR-14 event per field | legacy field overrides | [How-to](../how-to/modify-fields-with-event.md) (deprecated) |
| Sub data processors | TypoScript `dataProcessing.` | TypoScript-computed data (menus, record lists) | [How-to](../how-to/add-sub-dataprocessors.md) |
| Image variants | `headless.yaml` + TypoScript `options.processing.` | responsive thumbnails | [How-to](../how-to/define-image-variants.md) |

When several extension points could solve a problem, prefer the most
declarative one (image variants over `headless.php`, transformer over event).
