# Design: Automatically generate JSON Schema

> Status: **PHASE 1 IMPLEMENTED (2026-09-05)** — `JsonSchemaGenerator`,
> the `nbheadlesscontentblocks:generate-schema` command and the schema
> contract tests are shipped (issue #22). Phases 2–3 remain open. This is
> a historical design record; where wording differs from the code, **the
> code wins** — notably the implementation emits **draft-07** with
> `definitions` (instead of the 2020-12/`$defs` sketch below) for the
> widest tool support.

Date: 2026-09-05
Scope: potential new `SchemaGenerator` + CLI command / HTTP endpoint; no change
to `Classes/Normalization/*`, no change to the JSON output.

---

## 1. What is JSON Schema, in one paragraph

JSON Schema (https://json-schema.org/) is a standardized, machine-readable way
to describe *what a valid JSON document looks like*: which properties exist,
their types, which are optional, enums, patterns, nested object shapes. Tools
understand it universally: editors validate and autocomplete JSON against a
schema URL (VS Code, JetBrains), validators exist in every language, and code
generators turn schemas into TypeScript types, Zod schemas or Pydantic models.
The `https://opencode.ai/config.json` example referenced in the issue is
exactly that: a published schema that lets any tooling understand the config
file. We could publish the same kind of artifact for our page/content JSON.

## 2. What we could describe

Our [JSON contract](../reference/json-contract.md) — currently maintained as
prose plus frozen characterization tests — is deterministic per field type.
That makes it describable:

- **Per Content Block**: a schema for the `data` object of one block
  (field identifiers as properties, one JSON Schema type per Content Block
  field type).
- **Per installation**: one combined schema for the whole content array —
  a `oneOf` over all registered Content Blocks, discriminated by the `type`
  wrapper field (`const: "vendor_myblock"` + `$ref` to the block's `data`
  schema). This is the artifact comparable to opencode's config schema.

Sketch (shortened):

```json
{
  "$id": "https://cms.example.org/api/schema/content-blocks.json",
  "oneOf": [
    {
      "properties": {
        "id": { "type": "integer" },
        "type": { "const": "vendor_mycontentblock" },
        "colPos": { "type": "integer" },
        "data": {
          "type": "object",
          "properties": {
            "header": { "type": "string" },
            "my_datetime": { "type": "string", "format": "date-time" },
            "my_link": { "$ref": "#/$defs/linkObject", "type": ["object", "null"] },
            "my_text": { "type": "string" }
          },
          "required": ["header", "my_datetime", "my_link", "my_text"],
          "additionalProperties": false
        }
      }
    }
  ],
  "$defs": {
    "linkObject": { "type": "object", "properties": { "url": { "type": "string" } } }
  }
}
```

Shared shapes (`link object`, `file object`, the category `uid/pid/title`
reduction) would live in `$defs` — the schema twin of the contract page.

## 3. Benefits

1. **Frontend DX: generated types.** `json-schema-to-typescript`, `quicktype`
   or `datamodel-code-generator` turn the schema into TypeScript interfaces /
   Zod / Pydantic — no more hand-written interfaces drifting from the API.
2. **IDE validation & autocomplete** of fixtures and mocks in both the
   TYPO3 and the frontend repository (VS Code binds `$schema` URLs to files).
3. **Contract testing on both sides.** We can validate our own
   characterization-test fixtures against the generated schema in CI (the
   schema then *proves* the prose contract), and the frontend can validate
   recorded responses — regressions surface as schema violations.
4. **Machine-readable documentation.** Docs renderers (Redocly & friends)
   and AI tooling / MCP servers can consume the schema to understand the API
   without reading our Markdown.
5. **Cheap to keep truthful.** The generator reads the same source the
   converter reads (`TableDefinitionCollection`), so a new field or block
   appears in the schema without manual work.

## 4. Downsides and risks

1. **Runtime variability vs. static schema.** Three things are decided by
   TypoScript at runtime, not by the Content Block definition:
   `options.processing` (which `thumbnail` keys exist), `options.dateTimeFormat`
   (format of DateTime strings) and sub data processors (extra keys inside
   `data` via `dataProcessing.` `as` names). A schema generated only from the
   block definitions must either stay loose (`additionalProperties: true`) or
   be generated *per site* with TypoScript loaded.
2. **`headless.php` escapes the schema.** A block's `headless.php` may reshape
   `data` arbitrarily — the schema describes the declarative contract only.
3. **A second artifact to maintain.** Schema, prose contract and
   characterization tests describe the same thing; drift between them is the
   real risk. Mitigation: CI validates the frozen fixtures against the
   generated schema (one enforcement point, failure = drift).
4. **Contract freeze tightens.** Published schema URLs become public API;
   contract changes then also mean schema versioning (arguably healthy, but
   real).
5. **Endpoint risks.** An HTTP route exposing the whole content model is an
   information leak on production; needs gating (dev context / auth) and
   caching. Static file delivery avoids this entirely.
6. **Effort.** Generator + type mappers + tests: roughly 3–5 days for the
   CLI proof (phase 1); per-site/TypoScript-aware generation adds more.

## 5. Use cases

- Frontend repo: generated TS types + Storybook/mock validation.
- Frontend CI: validate recorded API responses (breaking-backend detection).
- Our CI: characterization fixtures validated against the schema.
- Onboarding: "here is the URL, your editor now understands our JSON".
- AI assistants / MCP tooling building frontend features against the API.

## 6. Delivery options (phased)

### Phase 1 — CLI proof (recommended start)

- New `SchemaGenerator` (+ per-Content-Block-type → JSON-Schema-type mappers,
  mirroring the normalizer claims table from the
  [contract](../reference/json-contract.md)).
- Symfony command, e.g.
  `bin/typo3 nbheadlesscontentblocks:generate-schema --target <dir>`: writes
  one `schema/<ctype>.json` per block plus the combined
  `content-blocks.schema.json`, into a configurable directory (site package,
  or a public folder the frontend can fetch).
- CI test: validate the characterization fixtures against the generated
  schemas (PHP validator as dev dependency, e.g. `opis/json-schema`).
- Zero runtime impact, zero contract change, immediately useful.

### Phase 2 — delivery to consumers

- Publish the generated files (commit them, or copy them in CI into the
  frontend repo / static file server) with stable `$id` URLs.
- Optional: HTTP endpoint (page type or middleware) serving the combined
  schema, restricted to non-production or authenticated consumers.

### Phase 3 — TypoScript-aware refinements (experimental)

- Fold in `options.processing` variant names → concrete `thumbnail`
  properties; `options.dateTimeFormat` → `format`/pattern; sub processor
  `as` keys → explicit properties (requires walking the site's TypoScript).
- Collection/relation item schemas resolved recursively per target table.
- OpenAPI-style rendered documentation.

### Explicitly rejected (for now)

- Embedding a `$schema` key **into the JSON responses**: would change the
  frozen output contract (sorted keys, no extra keys). The schema is a
  sidecar artifact, not part of the response.

## 7. Type mapping (generator input table)

| Content Block type | JSON Schema |
|---|---|
| Text / Textarea / Email / Color / Slug | `{"type": "string"}` |
| Text (richtext) | `{"type": "string"}` (HTML) |
| Number | `{"type": ["integer", "number"]}` |
| DateTime | `{"type": "string", "format": "date-time"}` (default W3C; per-site format makes it a pattern) |
| Select | `{"type": ["string", "array"], "items": {"type": "string"}}` |
| Password | `{"const": ""}` |
| Json | `{}` / `{"type": ["object", "array", "null"]}` |
| Link | `$defs/linkObject` or `null`; unresolvable → object with `__errorMessage` |
| File (`oneToOne`) | `$defs/fileObject` (incl. optional `thumbnails`) or `__errorMessage` object |
| File (`oneToMany`) | array of `$defs/fileObject` |
| Folder | `{"type": "array", "items": {"type": "string"}}` |
| Category | array of `{uid: integer, pid: integer, title: string}` |
| Collection | array of item schema (recursive, per collection definition) |
| Relation | array of record schema per target table (recursive or loose object) |
| FlexForm | `{"type": "object"}` (dynamic — `additionalProperties: true`) |
| Checkbox / unknown | `null` + debug log (see contract) |

## 8. Open questions

1. Base contract (loose, block-definitions-only) vs. per-site schema
   (TypoScript-aware) — or both, clearly named?
2. Where do published schemas live and how are they versioned (URL strategy)?
3. PHP validator dependency for CI (`opis/json-schema` vs
   `justinrainbow/json-schema`) — dev-only, so low risk either way.
4. Do we include the EXT:headless wrapper (`id`/`type`/`colPos`/`appearance`)
   in the combined schema? (Recommended: yes — it is part of what the
   frontend consumes.)

## 9. Recommendation

Proceed with **phase 1** as a standalone proof: it is cheap, touches no
runtime code, hardens our own contract via CI, and gives the frontend team
generated types immediately. Decide on phase 2/3 after the first real-world
feedback. Track in issue #22.
