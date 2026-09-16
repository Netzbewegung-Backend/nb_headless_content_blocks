# How-to guides

Step-by-step guides for common tasks around the JSON output of
EXT:nb_headless_content_blocks.

- [Define image variants](DefineImageVariants.md) — responsive
  thumbnails per field via `headless.yaml`, with per-site TypoScript overrides
- [Migrate legacy thumbnails](MigrateLegacyThumbnails.md) — replace
  the old `headless.php` thumbnail generators
- [Post-process JSON with headless.php](PostProcessWithHeadlessPhp.md)
- [Add sub data processors](AddSubDataprocessors.md) — menus,
  record lists and other TypoScript data inside a block's `data`
- [Render containers](RenderContainers.md) — EXT:container columns
  via the `nb-container-json` processor
- [Register a custom normalizer](RegisterCustomNormalizer.md) — own
  value types in the JSON output
- [Register a field value transformer](RegisterFieldValueTransformer.md) —
  own string field shaping (like password blanking)
- [Modify fields with the PSR-14 event](ModifyFieldsWithEvent.md)
  (deprecated — prefer normalizers/transformers)
