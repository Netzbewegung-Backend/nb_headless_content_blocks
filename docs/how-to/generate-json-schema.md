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
- `content-blocks.schema.json` — all blocks combined in a `oneOf`,
  discriminated by the `type` wrapper field, including the
  `id`/`type`/`colPos`/`appearance` wrapper built by EXT:headless

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

## What the schemas describe

The schemas describe the **base contract** (see
[JSON contract](../reference/json-contract.md)):

- shared shapes live in `definitions`: `linkObject`, `fileObject`
  (with optional `thumbnails`), `categoryObject` and the
  `__errorMessage` `errorObject`
- unknown properties are allowed (`additionalProperties` is not
  restricted), because sub data processors, `headless.php` and
  non-Content-Block columns may add keys at runtime
- DateTime fields are `format: date-time` (the default W3C format);
  a per-site `options.dateTimeFormat` override is not reflected

The schemas use JSON Schema **draft-07** — the dialect with the widest
tool support (VS Code, JetBrains, ajv, most code generators).

## Using the schemas

- **IDE**: bind the schema to fixture/mock files via `$schema`
- **Frontend types**: feed `content-blocks.schema.json` to a code
  generator (`quicktype`, `json-schema-to-typescript`, ...)
- **Contract tests**: validate API responses with any draft-07
  validator (e.g. `ajv` in the frontend CI)

The extension's own test suite validates its frozen characterization
fixtures against the generated schemas
(`Tests/Functional/Schema/JsonSchemaContractTest.php`) — if the JSON
output and the generated schema drift apart, the build fails.

Background, benefits and the phased plan:
[JSON Schema generation](../design/json_schema_generation.md) (design
record).
