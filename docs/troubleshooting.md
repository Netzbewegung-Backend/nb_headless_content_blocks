# Troubleshooting

This page lists known failure modes **when using the extension** — each
entry follows **symptom → cause → fix**. Problems with the extension's own
test setup are covered in
[Testing troubleshooting](testing-troubleshooting.md); if your case is
missing, open an issue.

## A field is `null` in the JSON and the log mentions an unknown type

**Symptom:** a field renders as `null` and the TYPO3 debug log (writer
`NbHeadlessContentBlocks...` / channel of your site) contains an entry about
an unconvertible or unknown value type.

**Cause:** the value reached the end of the normalizer chain without any
normalizer claiming it. Unconvertible values become `null` **by design**
(never break the whole response) — this includes booleans and floats, which
do not occur in real Content Block records. Previously such values were
dropped silently; now they are visible.

**Fix:** register a [custom normalizer](how-to/register-custom-normalizer.md)
for the value type. If the field should not be in the response at all, remove
it from the Content Block.

## JSON keys are database columns (`tx_myext_field`) instead of identifiers

**Symptom:** some keys in `data` are the prefixed column names instead of the
Content Block field identifiers (`my_field`).

**Cause:** the column → identifier mapping only covers fields that are
**defined in the Content Block's `config.yaml`**. Columns that TYPO3 or
another extension added to the table (but that are not Content Block fields)
pass through unmapped — the raw column name is the documented fallback.

**Fix:** none needed if the column is intentional (e.g.
`useExistingField` entries are mapped when defined). Otherwise define the
field in the Content Block YAML, or drop the column from the output with a
[field value transformer](how-to/register-field-value-transformer.md) /
event listener.

## `thumbnails` are missing on image fields

**Symptom:** `my_image` has `id/alt/title/publicUrl` but no `thumbnails` key,
although a `headless.yaml` exists.

**Cause — pick one:**

- The `headless.yaml` is not directly next to the Content Block's
  `config.yaml` (wrong folder or wrong file name).
- The YAML structure is wrong: processing is keyed **by field identifier**
  (`fields.<identifier>.processing.<variant>`), not by column name.
- The parsed `headless.yaml` is cached: after changes, clear the TYPO3 cache
  (`ddev typo3 cache:flush` in DDEV setups).
- The field is not a File/FileReference field.

**Fix:** correct the file and clear the cache. See
[Define image variants](how-to/define-image-variants.md) for the schema and
the TypoScript override that wins over `headless.yaml`.

## JSON contains a `__errorMessage` key

**Symptom:** the block's `data` (or a link object) contains an
`__errorMessage` entry instead of the expected value.

**Cause:** a referenced file no longer exists (`FileDoesNotExistException`),
or a typolink target could not be resolved (`UnableToLinkException`). In both
cases the rest of the response stays intact on purpose — the error is
reported in place instead of breaking the page.

**Fix:** repair the record — re-select the file in the Backend, or fix the
link target. The message text names the affected record/target.

## Password fields are empty strings

**Symptom:** every `Password` field renders as `""`.

**Cause:** by design — `PasswordBlanker` blanks password values before they
reach any headless client. See
[Normalizers and transformers](reference/normalizers.md).

**Fix:** none — expected behavior. Do not send password hashes to the
frontend.

## Keys inside `data` are alphabetically sorted

**Symptom:** field order in the JSON differs from the order in the Content
Block YAML.

**Cause:** by design — the output is `ksort`ed. Consumers already rely on
the sorted order; it is part of the frozen
[JSON contract](reference/json-contract.md).

**Fix:** none — expected behavior. Sort concerns belong to the frontend.

## Rich text renders without the expected wrapping (`<p>` classes, etc.)

**Symptom:** richtext fields come back as HTML, but without the site's
`parseFunc` classes/wrappers.

**Cause:** richtext fields are rendered through
`ContentObjectRenderer::parseFunc($value, null, '< lib.parseFunc_RTE')`.
The wrapper classes come from *your* TypoScript `lib.parseFunc_RTE`, not
from this extension. Since TYPO3 13.2 `lib.parseFunc` is always provided by
ext:frontend, but site-specific classes require your site package's setup.

**Fix:** configure `lib.parseFunc_RTE` in your site package (typically via
fluid_styled_content's richContentObject or your own setup).

## Container children do not render / render twice

**Symptom:** the container's `left`/`right` columns are empty, or the
children appear in the normal page content *and* in the column.

**Cause:** the container's TypoScript wiring. `lib.content` must exclude the
container column positions, otherwise the children are rendered by the page
content query as well.

**Fix:** add the exclusion and map the columns via `nb-container-json` —
see [Render containers](how-to/render-containers.md).
