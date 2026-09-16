=============
Documentation
=============

This page explains what the extension's documentation covers and where to find it. The docs are written for developers who build a headless TYPO3 frontend with EXT:headless and EXT:content_blocks — see the `README <https://github.com/Netzbewegung-Backend/nb_headless_content_blocks>`__ whether the extension fits your setup. The same documentation is rendered on `docs.typo3.org <https://docs.typo3.org/p/netzbewegung/nb-headless-content-blocks/main/en-us/>`__.

Getting started
===============

*   `Getting started <GettingStarted.rst>`__ — install the extension, include the Site Set, and verify your first JSON response

Concepts (why it works this way)
================================

*   `Architecture <Concepts/Index.rst>`__ — the normalization pipeline from Content Block record to JSON: DataProcessor, `RecordArrayBuilder`, normalizers, field value transformers, and the extension points

How-to guides (solve a task)
============================

*   `Define image variants <Howto/DefineImageVariants.rst>`__ — responsive thumbnails per field via `headless.yaml`, with per-site TypoScript overrides
*   `Migrate legacy thumbnails <Howto/MigrateLegacyThumbnails.rst>`__ — replace the old `headless.php` thumbnail generators
*   `Post-process JSON with headless.php <Howto/PostProcessWithHeadlessPhp.rst>`__
*   `Add sub data processors <Howto/AddSubDataprocessors.rst>`__ — menus, record lists and other TypoScript data inside a block's `data`
*   `Render containers <Howto/RenderContainers.rst>`__ — EXT:container columns via the `nb-container-json` processor
*   `Register a custom normalizer <Howto/RegisterCustomNormalizer.rst>`__ — own value types in the JSON output
*   `Register a field value transformer <Howto/RegisterFieldValueTransformer.rst>`__ — own string field shaping (like password blanking)
*   `Modify fields with the PSR-14 event <Howto/ModifyFieldsWithEvent.rst>`__ (deprecated — prefer normalizers/transformers)

Reference (look it up)
======================

*   `JSON contract <Reference/JsonContract.rst>`__ — the exact output shape per field type, frozen by characterization tests
*   `Normalizers and transformers <Reference/Normalizers.rst>`__ — built-in services, interfaces and DI tags
*   `Processor options <Reference/ProcessorOptions.rst>`__ — TypoScript options of `nb-content-blocks-json` and `nb-container-json`

Troubleshooting
===============

*   `Troubleshooting <Troubleshooting.rst>`__ — symptom → cause → fix
*   `Testing troubleshooting <Contributing/TestingTroubleshooting.rst>`__ — symptom → cause → fix for the extension's own test setup (contributors)

Design records (internal)
=========================

`Design/` holds planning and analysis records — where wording differs from the code, the code wins. Notable: `Improve ToArray design record <Design/ImproveToArray.rst>`__ — the 2026-08 rewrite of the ToArray conversion (normalizer registry, Schema API migration, declarative image variants) with its decisions and rationale.

Archive (internal)
==================

`_archive/ <_archive/README.rst>`__ holds superseded documentation — how the extension got here, not how it works today. Notable: `legacy thumbnails via headless.php <_archive/LegacyHeadlessPhpThumbnails.rst>`__ — the `ImageViewHelper` pattern that declarative image variants replaced.

..  toctree::
    :hidden:
    :titlesonly:

    GettingStarted
    Concepts/Index
    Howto/Index
    Reference/Index
    Troubleshooting
    Contributing/Index
