# Publish JSON Schema

This guide shows how to deliver the generated JSON Schemas to their
consumers — the frontend build, IDEs and contract tests — either as
static files or through the extension's HTTP endpoint.

How to generate the files in the first place:
[Generate JSON Schema](generate-json-schema.md).

## Option 1: static files (recommended for production)

Run the generator in CI (or locally) and copy the files wherever they
are served from:

```bash
bin/typo3 nbheadlesscontentblocks:generate-schema \
    --target public/api/schema \
    --id-base https://cms.example.org/api/schema
```

- **Commit them** into the site package when the schemas should be
  reviewed like code — a diff in the merge request shows the contract
  change explicitly.
- **Copy them in CI** when the frontend repository or a static file
  server should receive them automatically on deploy.

Keep the `$id` URLs (`--id-base`) **stable** — they become the public
identifiers tools reference. A pragmatic URL strategy:

- `https://<cms-host>/api/schema/<ctype>.schema.json` for one block
- `https://<cms-host>/api/schema/content-blocks.schema.json` for the
  combined schema
- When the contract changes in a breaking way, generate into a versioned
  path (`/api/schema/v2/`) and keep the old files available.

This extension's own test suite freezes the generated combined schema
byte-exactly
(`Tests/Functional/Schema/CommittedSchemaArtifactTest.php`) — the same
pattern works for your site package: commit the artifact and let CI
regenerate and diff it.

## Option 2: HTTP endpoint

The extension ships a page type that serves the combined schema over
HTTP, so no file copy step is needed:

```
https://cms.example.org/?type=1788873600
```

The response has the content type `application/schema+json` and is
always generated from the currently registered Content Block
definitions (the page is rendered uncached).

Access is gated:

- **Development application contexts** (e.g. local DDEV instances):
  the endpoint is available out of the box.
- **Any other context** (Production, Staging): the endpoint answers
  with `404` unless the site setting `schemaEndpoint.enabled` is turned
  on — Settings → Site Settings → "JSON Schema endpoint" or directly in
  the site's `config.yaml`:

  ```yaml
  settings:
    schemaEndpoint.enabled: true
    schemaEndpoint.idBase: 'https://cms.example.org/api/schema'
  ```

| Setting | Default | Meaning |
|---|---|---|
| `schemaEndpoint.enabled` | `false` | serve the schema outside Development contexts |
| `schemaEndpoint.idBase` | `''` | `$id` base of the served schema |
| `schemaEndpoint.token` | `''` | require this token on every request (any context) |

With a configured `token`, every request must authenticate via the
`X-API-Token` request header. A missing or wrong token answers with
`404`, indistinguishable from a disabled endpoint, so the route does
not leak whether it exists. (A query parameter is deliberately not
supported: the frontend cHash mechanism strips unknown GET parameters
from page-type URLs, and tokens in URLs would leak into access logs.)

Because the endpoint is a page type of the site, the setting applies
**per site** — a multisite installation can publish the schema on one
site and keep it disabled on the others.

Sites using a `PageTypeSuffix` route enhancer must map the type to a
URL segment first, e.g.:

```yaml
routeEnhancers:
  PageTypeSuffix:
    type: PageTypeSuffix
    map:
      schema.json: 1788873600
```

**Security note:** the endpoint publishes your whole content model
(all Content Blocks, field identifiers, field types). That is usually
fine for development and staging — which is why it is disabled for
everything else by default. On production, prefer option 1 (static
files), enable the setting deliberately, or protect the endpoint with
`schemaEndpoint.token`.

## URL strategy and versioning

The `$id` URLs become public API once consumers reference them. Keep
them stable:

- Do not change the `$id` base without a migration plan; the `$id` can
  carry a version segment (e.g. `.../v1/content-blocks.schema.json`).
- The endpoint always reflects the current installation; static files
  let you pin a version explicitly.

## Browser-based tooling and CORS

Editors and IDE extensions fetch schemas without a browser origin, so
CORS is usually not an issue. If browser-based tooling must fetch the
endpoint directly, add `Access-Control-Allow-Origin` headers on a
reverse proxy in front of the CMS.

Background and the phased delivery plan:
[JSON Schema generation](../design/json_schema_generation.md) (design
record).
