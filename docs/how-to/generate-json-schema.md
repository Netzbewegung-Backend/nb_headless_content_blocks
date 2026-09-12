# Generate JSON Schema

This guide shows how to generate JSON Schema files describing the JSON
output of your Content Blocks — for IDE validation, generated frontend
types and contract tests.

## Generate the schemas

```bash
bin/typo3 nbheadlesscontentblocks:generate-schema \
    --target public/api/schema \
    --id-base https://cms.example.org/api/schema
```

The command writes into `--target` (created if needed):

- one `<ctype>.schema.json` per Content Block — describes the block's
  `data` object (field identifiers as properties, one JSON Schema type
  per Content Block field type)
- `content-blocks.schema.json` — all blocks combined in the
  `contentBlockElement` `oneOf` (discriminated by the `type` wrapper
  field), including the `id`/`type`/`colPos`/`appearance` wrapper built
  by EXT:headless

`--id-base` (optional) sets stable `$id` URLs on all files so editors
and tools can reference them, e.g.:

```json
{
    "$schema": "http://my-schema.json",
    "$ref": "https://cms.example.org/api/schema/test_myblock.schema.json"
}
```

Re-run the command whenever Content Block definitions change — the
schemas are derived from the same definitions the JSON conversion uses.

How to deliver the generated files to their consumers (static files or
the extension's HTTP endpoint):
[Publish JSON Schema](publish-json-schema.md).

## What the schemas describe

The schemas describe the **base contract** (see
[JSON contract](../reference/json-contract.md)):

- shared shapes live in `$defs`: `linkObject`, `fileObject`
  (with optional `thumbnails`), `categoryObject` and the
  `__errorMessage` `errorObject`. File fields with variants declared in
  the Content Block's `headless.yaml` get a dedicated definition with
  the concrete `thumbnail` variant names as properties — see
  [Define image variants](define-image-variants.md)
- Checkbox fields are `integer` (TCA check fields are delivered as
  `0`/`1`, or a bitmask for multi-checkbox fields)
- Select fields with a `foreign_table` resolve to records in the JSON
  output (single object for `renderType: selectSingle`, array of
  objects otherwise — same as Relation fields), so the schema uses the
  record schema of the target table when it is defined as Content Block
  RecordType, a loose `object` otherwise; static selects (fixed items)
  stay `string`/`array of strings`
- every tt_content type registered in TCA but **not** defined as
  Content Block (core types like `html` or `shortcut`, classic plugins
  like form framework or Extbase plugins) gets a loose fallback branch:
  the element envelope with `data: object`, because their JSON shape is
  not derivable from Content Block definitions
- container blocks can declare their rendered child element lists in
  `headless.yaml` (see below); they become arrays of `contentBlockElement`
- unknown properties are allowed (`additionalProperties` is not
  restricted), because sub data processors, `headless.php` and
  non-Content-Block columns may add keys at runtime; `thumbnails`
  likewise stays open for TypoScript-only variants
  (`options.processing`) that `headless.yaml` does not declare
- DateTime fields are `format: date-time` (the default W3C format);
  a per-site `options.dateTimeFormat` override is not reflected

The schemas use **JSON Schema 2020-12** (the current standard dialect —
same vocabulary as draft-07 for everything used here, supported by VS
Code, JetBrains, ajv 8 and common code generators).

## Declare container children

Container blocks render their children into JSON keys defined by the
site package's TypoScript (`as` of the container data processor, e.g.
`main`, `left`, `right`) — not derivable from the Content Block
definition. Declare them in the block's `headless.yaml` so they appear
in the generated schema as arrays of content block elements:

```yaml
children:
  - main
  # or, for a two-column container:
  # - left
  # - right
```

The keys must match the TypoScript `as` values exactly, and should not
collide with field identifiers of the block.

## Declare additional rendered keys

Sub data processors and `headless.php` can add keys to the block's
`data` object that are not derivable from the Content Block definition
(their schema is assembled at runtime). Declare them in the block's
`headless.yaml` with verbatim JSON Schema fragments:

```yaml
properties:
  categories:
    type: array
    items:
      type: string
```

They are merged after the field-derived properties and `children`, so a
declaration also overrides a derived mapping on key collision. Use them
sparingly — for everything the JSON conversion itself outputs, the
generated mapping is the source of truth.

## Using the schemas

- **IDE**: bind the schema to fixture/mock files via `$schema`
- **Frontend types**: feed `content-blocks.schema.json` to a code
  generator (`quicktype`, `json-schema-to-typescript`, ...)
- **Contract tests**: validate API responses with any 2020-12-capable
  validator (e.g. `ajv` in the frontend CI)

Data that the CMS assembles at runtime (`headless.php` results, sub
data processors) is not derivable from the Content Block definitions —
declare such keys in the block's `headless.yaml` (see above), or keep
manual view models for them in the frontend. The loose fallback
branches make sure page columns containing non-Content-Block elements
still validate.

The extension's own test suite validates its frozen characterization
fixtures against the generated schemas
(`Tests/Functional/Schema/JsonSchemaContractTest.php`) — if the JSON
output and the generated schema drift apart, the build fails.

Background, benefits and the phased plan:
[JSON Schema generation](../design/json_schema_generation.md) (design
record).
