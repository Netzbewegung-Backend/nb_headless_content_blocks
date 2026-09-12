# Plan: JSON Schema for the full page response

> Status: **PROPOSAL (2026-09-12) — not scheduled.** Trigger: the issue
> #22 feedback question *"Will the CMS later extend the endpoint to cover
> the entire page response (navigation, SEO, i18n) rather than just
> content blocks?"* This record analyzes where such a schema could live
> and recommends a staged path. Nothing here blocks or changes the
> current content block schema. This is a planning record; where wording
> differs from the code, **the code wins**.

Date: 2026-09-12
Scope: analysis only — no `Classes/` changes. Concerns a potential
follow-up to the schema endpoint shipped in issue #22 (see
[json_schema_generation.md](json_schema_generation.md)).

---

## 1. The question

The schema generated today describes **content block elements** — the
items of the page's `content` column arrays, including fallback branches
for non-Content-Block elements and recursive container children. The
frontend asked whether the same endpoint could later describe the
**whole page response**: navigation, SEO, i18n — everything EXT:headless
delivers around the content.

Short answer: yes, that is describable — but not from a single source,
and therefore not from this extension alone. The page response is
composed by **three different owners**, and each part of it needs a
different description strategy.

## 2. What the page response actually consists of

Facts first — where each top-level part of the JSON comes from:

| Part | Owner | Source of shape | Examples |
|---|---|---|---|
| `id`, `type`, `slug`, `media` | EXT:headless | core TypoScript (`Configuration/TypoScript/Configuration/PageConfiguration.typoscript`), stable per headless major | page uid, doktype label, slug, og images |
| `seo` | EXT:headless | placeholder in TypoScript, filled via PSR-14 event from the core MetaTag API — effectively an open `string` map | `title`, `description`, `canonical`, `og:*` |
| `meta` | EXT:headless (legacy) | `lib.meta`, removed in headless 5.0 — **the contract drifts across headless majors** | `title`, `subtitle` |
| `breadcrumbs`, `appearance` | EXT:headless | TypoScript (`lib.breadcrumbs`, `lib.pageAppearance`) | breadcrumb menu, layout classes |
| `i18n` | EXT:headless + core | `lib.i18n` wraps the core `LanguageMenuProcessor` — a stable core contract | `languages` array with per-language links |
| `content` | site set + this extension | `lib.content` selects colPos arrays; the elements themselves are our schema's domain | `colPos0: contentBlockElement[]` |
| navigation, `user`, `commonPages` | **site package** | Initial-data endpoint (`?type=834`) composed from headless `MenuProcessor`/`USER` cObjects in the site package's TypoScript (e.g. `nb_frontend_api`: `primaryNavigation`, `footerNavigation`, `commonPages`, `user`) | project-specific keys, freely named |

Derived from that, the **derivability** of each part:

1. **`content.*`** — derivable (already done): generated from Content
   Block definitions + TCA fallbacks + `headless.yaml` children.
2. **Headless envelope** (`id`, `type`, `slug`, `media`, `seo`,
   `breadcrumbs`, `appearance`, `i18n`) — *not* derivable from Content
   Block definitions, but describable as **static schema fragments
   maintained per headless major version**. The shapes are small and
   change rarely, but they *do* change across majors (the `meta` removal
   in headless 5.0 already forced version switches in our own e2e tests).
3. **Site-package parts** (navigation, initial data) — **not derivable
   at all**. The keys are freely named in TypoScript (`as =`, field
   names), and walking TypoScript was already rejected in
   [json_schema_generation.md](json_schema_generation.md) (the building
   machinery is `@internal` core API). The only workable strategy is
   **declarative**: the site package states what it renders — the same
   pattern as `children:` in `headless.yaml`.

## 3. Constraints

- **Version coupling**: whoever describes the envelope must track
  EXT:headless majors (4.x vs 5.x contract differences are real).
- **No TypoScript introspection**: site-package shapes can only be
  declared, not discovered.
- **Ownership**: the party that can *change* the response shape should
  own (or at least co-own) its description — otherwise the schema lies.
- **Consumer demand**: today the frontend needs typed content elements;
  whole-page validation/types are a *nice-to-have*, not requested as a
  blocking feature.

## 4. Options

### Option A — extend `nb_headless_content_blocks`

Add a "page mode" to the existing generator/endpoint: envelope fragment +
content schema + a `headless.yaml`-style declaration for initial-data
keys.

- **Pros**: infrastructure already exists (middleware, gating, command,
  artifact drift guard); one schema URL for consumers.
- **Cons**: scope mismatch — this extension's product is the Content
  Block **conversion**; page envelope, SEO semantics and headless major
  tracking are a different concern with different dependencies; the
  extension would grow static fragments that break with every headless
  major regardless of Content Block changes.
- **Verdict**: works, but the wrong owner. Rejected as the primary home;
  acceptable only as a stopgap if demand is urgent and small.

### Option B — upstream EXT:headless ships the envelope schema

`friendsoftypo3/headless` publishes **static JSON Schema fragments** for
the page envelope and initial data (like opencode publishes
`config.json`): `page-envelope.schema.json`, `i18n.schema.json`,
`seo.schema.json` — versioned with each headless release.

- **Pros**: the natural owner — headless *defines* the contract, so its
  repo is where the description belongs; benefits the whole community,
  not just our projects; version coupling happens where versions are
  released; our extension would simply `$ref` the envelope fragments.
- **Cons**: upstream contribution overhead and uncertain acceptance
  (community project, own release rhythm); fragments must be maintained
  in two majors; navigation/initial data remain site-specific either way.
- **Verdict**: best long-term home for the **envelope** part. Worth an
  upstream issue/PR *independently* of what we build locally — low cost,
  high leverage.

### Option C — new extension `nb_headless_json_schema`

A small aggregation extension that **depends on EXT:headless** (and
optionally on `nb_headless_content_blocks`):

- ships the static envelope fragments per headless major (selects by
  installed version),
- embeds the content element schema by reusing our
  `JsonSchemaGenerator` output (it is pure DI, no coupling problem),
- offers a **PSR-14 event** (`ModifyPageSchemaEvent`-style) and/or a
  declarative `schema.yaml` for site-package parts (navigation,
  initial-data keys) — the `headless.yaml` children pattern, one level up,
- serves the combined page schema via its own middleware/endpoint,
  reusing the gating pattern.

- **Pros**: clean layering — conversion (this extension) stays focused,
  schema *description* of the whole page gets its own bounded context
  with its own version constraints; headless majors isolated; testable
  with the same artifact/contract patterns.
- **Cons**: a third extension to maintain; only pays off once whole-page
  schemas are actually consumed; risk of over-engineering.
- **Verdict**: the right shape **when** the demand becomes concrete
  (e.g. the frontend wants one `page.schema.json` to validate/store whole
  responses). Not now.

### Option D — the site package owns the page schema

The site package (e.g. `nb_frontend_api`) — the party that composes
initial data and overrides headless fields — declares the full page
schema itself and `$ref`s the content schema from our endpoint.

- **Pros**: the composer of the response owns its description; no
  extension changes needed at all; perfectly accurate per project.
- **Cons**: every project reinvents the declaration unless we ship a
  helper; no shared artifact; envelope fragments copied around.
- **Verdict**: valid **today** for project-specific keys (navigation),
  and the natural integration point Option C would plug into.

### Option E — alternatives outside JSON Schema

- **OpenAPI** describing the endpoints (`/`, `?type=834`, schema
  endpoint) with response schemas embedded — useful for API *docs*,
  heavier for type generation; was already noted as a possible follow-up
  in [json_schema_generation.md](json_schema_generation.md).
- **Frontend-side composition**: consumers assemble page types
  themselves from the generated `contentBlockElement` types plus small
  hand-written envelope types — zero CMS work, and honestly close to
  what a generated envelope fragment would produce anyway.

## 5. Evaluation matrix

| Criterion | A: this ext | B: upstream headless | C: new ext | D: site package |
|---|---|---|---|---|
| Right owner for envelope | – | **++** | + | – |
| Right owner for navigation | – | – | + | **++** |
| Headless-major elasticity | – | **++** (ships with release) | + | o |
| Content integration | **++** (already there) | – (loose `object`) | **++** (reuses generator) | + (`$ref` to endpoint) |
| Effort | medium | small (static files) | high | per-project |
| Community value | o | **++** | o | – |
| Needed now? | no | cheap either way | no | o |

## 6. Recommendation (staged)

1. **Now — do nothing structural.** Document the boundary (this record)
   and answer the issue question with it. The frontend can compose
   `EnvelopeTypes + GeneratedContentTypes` by hand; the envelope types
   are small and rarely change.
2. **Cheap, anytime — propose Option B upstream**: an issue (later PR)
   in `friendsoftypo3/headless` to ship versioned static envelope schema
   fragments. Independent of everything else.
3. **When whole-page schemas become a real consumer need — Option C**:
   build `nb_headless_json_schema` (on EXT:headless, embedding this
   extension's generator output, PSR-14/declarative hooks for
   site-package parts per Option D). Keep `nb_headless_content_blocks`
   scoped to Content Blocks.
4. **Never**: walking TypoScript to "discover" the site-package shapes,
   and hardcoding headless envelope fragments into
   `nb_headless_content_blocks`.

## 7. Open questions

- Does the frontend actually want to *validate* whole pages, or only get
  *types* for them? (Types → Option E composition may be enough.)
- Should the initial-data endpoint (`?type=834`) be part of the same
  schema document or a second one (`initial-data.schema.json`)?
- If Option C happens: does the schema endpoint move there (breaking
  URLs), or stay per-extension and get aggregated?
