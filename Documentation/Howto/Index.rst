=============
How-to guides
=============

Step-by-step guides for common tasks around the JSON output of EXT:nb_headless_content_blocks.

*   `Define image variants <DefineImageVariants.rst>`__ — responsive thumbnails per field via `headless.yaml`, with per-site TypoScript overrides
*   `Migrate legacy thumbnails <MigrateLegacyThumbnails.rst>`__ — replace the old `headless.php` thumbnail generators
*   `Post-process JSON with headless.php <PostProcessWithHeadlessPhp.rst>`__
*   `Add sub data processors <AddSubDataprocessors.rst>`__ — menus, record lists and other TypoScript data inside a block's `data`
*   `Render containers <RenderContainers.rst>`__ — EXT:container columns via the `nb-container-json` processor
*   `Register a custom normalizer <RegisterCustomNormalizer.rst>`__ — own value types in the JSON output
*   `Register a field value transformer <RegisterFieldValueTransformer.rst>`__ — own string field shaping (like password blanking)
*   `Modify fields with the PSR-14 event <ModifyFieldsWithEvent.rst>`__ (deprecated — prefer normalizers/transformers)

..  toctree::
    :hidden:
    :titlesonly:

    AddSubDataprocessors
    DefineImageVariants
    MigrateLegacyThumbnails
    ModifyFieldsWithEvent
    PostProcessWithHeadlessPhp
    RegisterCustomNormalizer
    RegisterFieldValueTransformer
    RenderContainers
