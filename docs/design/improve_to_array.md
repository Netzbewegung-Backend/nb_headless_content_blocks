# Design: Improve / Rewrite the ToArray conversion

> Status: **IMPLEMENTED (all phases done), 2026-08-26** — see git history
> on `feature/improve-to-array`. This is a historical design record;
> where wording differs from the code, **the code wins**.

Date: 2026-08-26
Scope: `Classes/DataProcessing/ToArray/*` (rewritten into `Classes/Normalization/*`), `ContentBlocksJsonDataProcessor`, `ContainerJsonDataProcessor`

---

## 1. Goal

The core job of this extension: convert complex, rich TYPO3 domain objects
(`Record`, `FileReference`, `LazyRecordCollection`, `TypolinkParameter`, ...)
into plain PHP arrays so they can be serialized to JSON for headless responses.

Goals of the rewrite:

1. **Replaceable architecture** instead of one giant `switch (true)` block.
2. **Use TYPO3 Core APIs** (Schema API, Record API) instead of ContentBlocks
   internals where possible.
3. **Full control over the JSON contract** (key mapping, field ordering,
   value shaping) — that is our product, not a side effect.
4. Keep the PSR-14 extension point.
5. Keep (or improve) the current 100 % test coverage as a safety net.

Non-goals:

- No generic serializer framework (we only serialize TYPO3 domain objects).
- No denormalization (JSON → objects).
- We do not change the headless extension itself.

---

## 2. Current situation

```
ContentBlocksJsonDataProcessor / ContainerJsonDataProcessor
 └─ RecordToArray(Record)                    → toArray(), strips system fields
     └─ ArrayRecursiveToArray(array)         → THE switch, per-value dispatch
         ├─ RecordToArray                    → recursion
         ├─ TypolinkParameterToArray
         ├─ FileReferenceToArray             → crop + public URL (ImageService)
         ├─ LazyFileReferenceCollectionToArray
         ├─ LazyFolderCollectionToArray
         ├─ LazyRecordCollectionToArray      → recursion per item
         └─ LazyRecordCollectionSysCategoryToArray
 └─ event: ModifyArrayRecursiveToArrayEvent  → PSR-14 per field/value
 └─ headless.php                             → per Content Block PHP post-processing
```

Pain points:

| # | Pain | Where |
|---|------|-------|
| 1 | Monolithic `switch (true)` with instanceof chains; unknown types silently dropped (default case commented out) | `ArrayRecursiveToArray` |
| 2 | Heavy dependency on ContentBlocks internals: `TableDefinitionCollection`, `TcaFieldDefinition`, ~15 `*FieldType` instanceof checks | everywhere |
| 3 | Manual relation-target parsing (`foreign_table`, `allowed` splitting) | `getTableNameByKey()` |
| 4 | Version hacks `property_exists(TcaFieldDefinition, 'parentTable')` etc. — only needed because we construct ContentBlocks internals in tests | tests |
| 5 | `GeneralUtility::makeInstance()` everywhere instead of DI → hard to mock, hidden dependencies | all ToArray classes |
| 6 | String field handling (password blanking, richtext, passthrough) mixed into the same switch | `processStringField()` |
| 7 | No way to configure output (absolute URLs? include system fields? per-block options) — hard-coded | various |

---

## 3. Research: What do TYPO3 13 / 14 and others offer?

### 3.1 Core Record API (since 13.0, enhanced 13.3)

`RecordFactory::createResolvedRecordFromDatabaseRow()` already converts raw DB
rows into rich objects (Feature #103581, "Automatically transform TCA field
values for record objects"):

- relations → `Record`, `LazyRecordCollection`, `FileReference`,
  `LazyFileReferenceCollection`, `Folder`, `LazyFolderCollection`
- datetime → `DateTimeImmutable`
- link → `TypolinkParameter`
- flex → `FlexFormFieldValues`
- json → array

**Insight:** We already feed `Record` objects into our converter. The Record API
does the *object resolution* — our job is only the *array shaping*. Nothing to
gain from replacing this part; it is the right input.

### 3.2 Core Schema API (since 13.0!) — the important one

`TYPO3\CMS\Core\Schema\*` is the Core-native replacement for the ContentBlocks
definitions we use:

| ContentBlocks (now) | Core Schema API (13+14) |
|---|---|
| `TableDefinitionCollection::hasTable()/getTable()` | `TcaSchemaFactory::has()/get()` |
| `TableDefinition->tcaFieldDefinitionCollection->hasField()` | `TcaSchema::hasField()/getField()` |
| `TcaFieldDefinition->fieldType` instanceof `*FieldType` | `Field::getType()` → `TableColumnType` enum (`CategoryFieldType::getType()` etc.) |
| manual `foreign_table` / `allowed` parsing | `RelationalFieldTypeInterface::getRelations(): ActiveRelation[]` → `ActiveRelation::toTable()` |
| `ContentTypeResolver` (CType → definition) | `TcaSchema::getSubSchema($cType)` / `hasSubSchema()` |
| `fieldType->getTca()['config'][...]` | `Field::getConfiguration()` |

**Key finding:** The Schema API exists in **both TYPO3 13 and 14**. A rewrite
based on it does **not** need a "v13 legacy / v14 only" split! The
`property_exists()` version hacks exist only because we *construct*
ContentBlocks internals — if we stop doing that, the hacks disappear.

### 3.3 Core PSR-14 events in the Record pipeline

- `RecordCreationEvent` — fired when a `Record` is created from a row.
  Tempting, but **rejected** as main mechanism: modifications there affect
  *every* consumer of the Record API (Fluid templates, other processors),
  not only the headless JSON output. Side effects we do not want.

### 3.4 TYPO3 14 specifics worth using

- `lib.parseFunc` / `lib.parseFunc_RTE` are always provided by ext:frontend
  (since 13.2, default removed from fluid_styled_content in 14 —
  Breaking-107438). Our richtext path can rely on them being present.
- System Resource API (Feature-107537) — URL generation, not needed for
  FileReference public URLs, but relevant if we ever support `EXT:` resources.
- No breaking changes in Schema/Record API that affect this rewrite.

### 3.5 What we still need ContentBlocks for

Two things have **no Core equivalent**:

1. **Identifier mapping**: ContentBlocks maps DB column `tx_ext_my_field` →
   Content Block field identifier `my_field`. Core TCA only knows the column
   name. Dropping this would change every consumer's JSON contract (breaking).
2. **headless.php**: resolving the Content Block folder via
   `ContentBlockRegistry::getContentBlockExtPath()`.

→ ContentBlocks stays a dependency, but only behind two narrow interfaces.

### 3.6 Real-world usage analysis (cms-netzbewegung-v5-2025, production site)

Investigated the example project (`/var/www/vhosts/cms-netzbewegung-v5-2025`) and
its JSON response (`https://cms.netzbewegung-v5-2025.local/de/`).

**Setup:**
- TYPO3 **13.4.32**, content-blocks 1.6.1, headless 4.7.3, b13/container 3.1.12
  → the main consumer is on v13. Confirms **V2 (unified 13/14)**; a v14-only
  rewrite would not serve production.
- Wiring lives in `nb_frontend_api` (TypoScript set): `dataProcessing` chains
  per block, `nb-container-json` for container columns (colPos 201/202 →
  `left`/`right`), extbase plugins as `USER_INT` (separate path).
- JSON wrapper `{"id":…,"type":"netzbewegung_headline","colPos":0,"data":{…}}`
  is built by nb_frontend_api/headless around our processor output.

**headless.php reality check:** 13 headless.php files exist, **all 13 do the
same thing**: generate responsive image variants (`mobile`, `mobile2x`,
`desktop`, `desktop2x`, … webp, widths like `883c`) via a static
`ThumbnailUtility` (which internally abuses the Fluid `ImageViewHelper`!).
Per block, per `mode` — dozens of hardcoded width configs.

→ **Strongest finding for the rewrite:** built-in, declarative image
processing on File/FileReference fields would eliminate ~all headless.php
files. ext:headless `FileUtility` + `ProcessingConfiguration` already
implements exactly this (TypoScript-style strings like
`width=883c,fileExtension=webp`).

**Contracts confirmed in production JSON (must not break):**
- Link fields: `url`, `target`, `type`, `title`, `config`, `attr` (+ `null`
  for empty links) — our `TypolinkParameterToArray` output
- FileReference: `id`, `alt`, `title`, `publicUrl` (+ `thumbnails` added per
  block in headless.php)
- Collections (`items`, `buttons`) with `prefixField: false` identifiers
- Basics includes (`Netzbewegung/Appearance`), `useExistingField`
  (`space_after_class`), custom collection tables (`tx_nb_buttons`),
  `prefixType: full` → `tx_nb_*` columns
- Alphabetically sorted keys in `data` (our `ksort` — consumers see it)
- richtext `bodytext` rendered with custom parseFunc classes (`p-text …`)
- `processAdditionalDataProcessors` is actively used
  (`nb-job-cards-dynamic` etc. via TypoScript `dataProcessing.20 { as = cards }`)

### 3.7 How others do "object → array/JSON"

| Approach | Example | Takeaway for us |
|---|---|---|
| **Normalizer registry** | Symfony Serializer (`NormalizerInterface::supportsNormalization()` + chaining) | Extensible, each normalizer small & testable. No new dependency needed — we implement the pattern natively (symfony/serializer is *not* installed) |
| **Resource/Transformer classes per type** | Laravel API Resources, League Fractal | Explicit, discoverable; but one class per Content Block is too much boilerplate for us |
| **Serializer with context** | Symfony groups / JMS Serializer | The *context* idea is good: per-call options (absolute URLs, include system fields, field subsets) |
| **`JsonSerializable` on wrappers** | various | Elegant, but lazy — key mapping/sorting/event hooks get awkward; harder to debug |
| **DataProcessors per type** | ext:headless itself (`FilesProcessor`, `GalleryProcessor` + `FileUtility`) | Confirms the DataProcessor *entry point*; their `ProcessingConfiguration` string syntax is a nice idea for per-field image processing options |

---

## 4. Options

### Option A — Normalizer registry (Symfony-Serializer-style, hand-rolled) ★ recommended

```
RecordNormalizer (entry, uses RecordFactory output)
 └─ NormalizerChain::normalize($value, Context)
     iterates registered Normalizers, first `supports()` wins

Normalizers (one per type, DI-registered, tagged):
  ScalarNormalizer            null/int/string/float/array-plain
  DateTimeNormalizer          → W3C (configurable format)
  RecordNormalizer            → recursion + system-field stripping + key mapping
  FileReferenceNormalizer     → crop/processing + publicUrl (reuses ext:headless FileUtility?)
  FileReferenceCollectionNormalizer
  FolderCollectionNormalizer
  RecordCollectionNormalizer  → per item, resolves target schema via ActiveRelation
  CategoryCollectionNormalizer
  FlexFormNormalizer
  TypolinkNormalizer
  UnknownTypeNormalizer       → explicit: log + null (no silent drops)
```

- Field-level shaping (password blanking, richtext parseFunc) becomes a
  separate **`FieldValueTransformer`** phase driven by Schema field type +
  config — no longer mixed into the type dispatch.
- `ContentBlocksIdentifierMapper` + `HeadlessPhpProcessor` behind interfaces.
- Normalizers get real DI (no `makeInstance`).

Pros: testable units, open for site-packages to register own normalizers
(the actual headless use case), no version hacks.
Cons: more classes (~12), slight indirection.

### Option B — Schema-driven single converter

One `SchemaArrayConverter` service: walks `Record->toArray()`, asks
`TcaSchema` for each field's `TableColumnType` + configuration and converts
in one (still large) method. Replaces instanceof chains with the enum, but
stays monolithic and hard to extend by third parties.

Pros: compact. Cons: extensibility only via events; the switch survives.

### Option C — Hook into Core `RecordCreationEvent`

Modify values at Record creation so `Record->toArray()` is already
JSON-ready.

Pros: least own code. Cons: global side effects on all Record consumers,
no per-response shaping. **Rejected.**

### Option D — `JsonSerializable` value wrappers

Wrap rich objects in lazy `JsonSerializable` adapters; `json_encode` triggers
conversion.

Pros: elegant. Cons: key mapping/k_sort/strip/events fight the pattern;
hard stack traces. **Rejected.**

### Cross-cutting decision — ContentBlocks usage

| Variant | Description |
|---|---|
| C1 keep as-is | current state, all pain points stay |
| C2 **narrow behind interfaces** ★ | Core Schema API for field metadata + relations; ContentBlocks only for identifier mapping + headless.php |
| C3 drop completely | breaking JSON contract (column names instead of identifiers), headless.php gone — only for a future 3.0 |

### Version strategy

| Variant | Description |
|---|---|
| V1 v13 legacy + v14 new | two code paths, double maintenance |
| V2 **unified via Schema API** ★ | Schema/Record API exist in 13+14 → one implementation, version hacks gone |
| V3 v14-only | unnecessary once V2 is chosen; only interesting if we wanted 14-only Core features |

---

## 5. Evaluation

| Criterion | A + C2 + V2 | B + C2 + V2 | current |
|---|---|---|---|
| Extensibility (3rd-party normalizers) | ++ | o (events only) | o (event only) |
| Testability | ++ | + | + |
| TYPO3 13 & 14, no hacks | ++ | ++ | − |
| JSON contract stable | ++ (covered by characterization tests) | ++ | ++ |
| Migration risk | + (phased) | + | – |
| Code size | ~+6 classes | − | – |

## 6. Recommendation

**Option A (normalizer registry) + C2 (narrow ContentBlocks) + V2 (unified 13/14).**

No split into v13/v14 versions — the Core APIs we need ship in both.
This is also *not* a big-bang rewrite: phases below keep the suite green.

---

## 7. Proposed architecture

```
Classes/
├── DataProcessing/
│   ├── ContentBlocksJsonDataProcessor.php   (entry, stays; slimmer)
│   └── ContainerJsonDataProcessor.php       (stays)
├── Normalization/
│   ├── NormalizerInterface.php              supports($value, Context): bool
│   ├── NormalizerChain.php                  DI-tagged 'nb_headless.normalizer'
│   ├── Context.php                          request, TcaSchema, options, eventDispatcher
│   ├── Normalizer/
│   │   ├── ScalarNormalizer.php
│   │   ├── DateTimeNormalizer.php
│   │   ├── RecordNormalizer.php             recursion, strips system fields
│   │   ├── RecordCollectionNormalizer.php   target schema via ActiveRelation
│   │   ├── CategoryCollectionNormalizer.php
│   │   ├── FileReferenceNormalizer.php      crop, publicUrl, declarative image variants (à la ext:headless ProcessingConfiguration)
│   │   ├── FileReferenceCollectionNormalizer.php
│   │   ├── FolderCollectionNormalizer.php
│   │   ├── FlexFormNormalizer.php
│   │   ├── TypolinkNormalizer.php
│   │   └── UnknownTypeNormalizer.php        explicit null + debug log
│   └── Event/
│       ├── BeforeFieldNormalizationEvent.php  (replaces ModifyArrayRecursiveToArrayEvent)
│       └── AfterRecordNormalizationEvent.php  (whole record, for headless.php alternatives)
├── FieldTransformer/
│   ├── FieldValueTransformerInterface.php
│   └── String/ PasswordBlanker, RichtextParser  (driven by TableColumnType + TCA config)
└── ContentBlocks/
    ├── IdentifierMapperInterface.php        uniqueIdentifier → identifier (passthrough fallback)
    └── HeadlessPhpProcessor.php             current headless.php include
```

Details worth discussing:

1. **Context object** carries: PSR-7 request, current `TcaSchema`, options
   (`absoluteUrls`, `includeSystemFields`, `dateTimeFormat`, per-processor
   config from TypoScript `options.`).
2. **FileReference output**: today `id/alt/title/publicUrl`. Candidate:
   reuse or align with ext:headless `FileUtility`/`ProcessingConfiguration`
   so consumers get consistent URLs across processors. Contract change → decide.
3. **Events**: keep `ModifyArrayRecursiveToArrayEvent` (deprecated alias) for
   one release; fire the new field-level event with the same payload.
4. **Services.yaml**: normalizers as tagged services; `NormalizerChain`
   receives them via tagged iterator — no `makeInstance` left in the
   normalization path.

---

## 8. Migration plan (incremental, suite stays green)

| Phase | Step | Safety net |
|---|---|---|
| 0 | Freeze the contract: convert current functional tests into **characterization tests** (assert full JSON per fixture record) | they must stay green until the end |
| 1 | Introduce `Normalization/` namespace with `NormalizerChain` + first normalizers; `ArrayRecursiveToArray` internally delegates, still the public entry | unit tests per normalizer |
| 2 | Replace ContentBlocks field-metadata lookups with `TcaSchemaFactory` (incl. `ActiveRelation` for relation targets); delete `getTableNameByKey()` parsing | characterization tests |
| 2b | Add declarative image variant processing to `FileReferenceNormalizer` (config source per open question 6); migrate the 13 production headless.php files as pilot | characterization tests |
| 3 | Move string shaping into `FieldTransformer/*`; move identifier mapping behind `IdentifierMapperInterface` | unit tests |
| 4 | Wire DI (tagged services), remove `makeInstance` in the path; deprecate `ModifyArrayRecursiveToArrayEvent` | – |
| 5 | Cleanup: remove old classes, remove version hacks from tests, update AGENTS.md | full suite + CGL + PHPStan on 13 & 14 |

## 9. Decisions (2026-08-26)

Strategy: **Option A (normalizer registry) + C2 (narrow ContentBlocks) +
V2 (unified 13/14)** — confirmed.

| # | Topic | Decision |
|---|-------|----------|
| 1 | Key ordering | Keep `ksort` — consumers already receive alphabetically sorted keys; no contract change |
| 2 | FileReference shape | Keep `id/alt/title/publicUrl`; add **opt-in declarative image variants** (`thumbnails`) to replace handwritten headless.php generators |
| 3 | Unknown value types | Emit `null` + debug log (no silent drops, never break the response) |
| 4 | headless.php API | Optional 2nd parameter `Context $context` — `function (array $data, Context $context)` — backwards compatible |
| 5 | Context options | TypoScript `options.` passthrough in v1, minimal set (`absoluteUrls`, `dateTimeFormat`) |
| 6 | Image-variant config source | **Both**: `headless.yaml` in the Content Block (default, versioned with the block) **+** TypoScript override (per site); TypoScript wins on conflict |

### headless.yaml sketch (decision 2 + 6)

```yaml
# ContentBlocks/ContentElements/image/headless.yaml
fields:
  image:
    processing:
      mobile:     "width=883c,fileExtension=webp"
      mobile2x:   "width=1766c,fileExtension=webp"
      desktop:    "width=1564c,fileExtension=webp"
      desktop2x:  "width=3128c,fileExtension=webp"
```

```typoscript
# per-site override (nb_frontend_api / site package)
tt_content.netzbewegung_image.fields.data.dataProcessing.10 {
    options {
        processing {
            image {
                desktop = width=1600c,fileExtension=webp
            }
        }
    }
}
```

Processing strings reuse the ext:headless `FileUtility`/`ProcessingConfiguration`
syntax, so output URLs match what headless consumers already get from
`FilesProcessor` etc.

## 10. Remaining open points (non-blocking, decide during implementation)

- Exact `Context` option names and defaults (v1: `absoluteUrls=true`,
  `dateTimeFormat=\DateTimeInterface::W3C`)
- Whether `UnknownTypeNormalizer` log channel is `nb_headless_content_blocks`
  or the site's default core log
- Deprecation window length for `ModifyArrayRecursiveToArrayEvent` (proposal:
  one minor release, keep firing it until then)

## 11. Additional goal: zero `GeneralUtility::makeInstance()`

End state (after Phase 4/5): no `makeInstance()` anywhere in `Classes/` —
all dependencies via constructor injection. Normalizers never inject the
chain (they recurse via `Context::getChain()`), so no circular DI problem.
Intentional non-DI leftovers: the `headless.php` include (receives
`Context` as parameter) and static utility calls
(`GeneralUtility::getFileAbsFileName()` etc.).
