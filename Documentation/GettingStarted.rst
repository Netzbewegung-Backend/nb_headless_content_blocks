===============
Getting started
===============

This page walks you through installing the extension and verifying your first JSON output — it is a linear tutorial; background and next steps follow at the end.

Prerequisites
=============

*   TYPO3 ≥ 13.4 (13.4 and 14.3 are tested in CI)
*   A headless frontend based on `EXT:headless <https://github.com/TYPO3-Headless/headless>`__ ≥ 4.5
*   Content Blocks created with `EXT:content_blocks <https://github.com/FriendsOfTYPO3/content-blocks>`__ ≥ 1.2.3
*   `Composer <https://getcomposer.org>`__ — the recommended installation method; the extension is also published to the `TER <https://extensions.typo3.org/extension/nb_headless_content_blocks>`__ (from version 0.1.2)

EXT:headless and EXT:content_blocks are installed automatically as dependencies when you require this extension.

Install
=======

..  code-block:: bash

    composer require netzbewegung/nb-headless-content-blocks

Then include the extension's Site Set in your site package's `config/sites/<site>/config.yaml`:

..  code-block:: yaml

    dependencies:
      - nb-headless-content-blocks/headless-content-blocks

That's it — no TypoScript mapping is needed for your Content Blocks: EXT:content_blocks maps them automatically onto `lib.contentBlock` (see :ref:`How it works <getting-started-how-it-works>`).

Verify
======

Open a page of your headless site that contains a Content Block element. The JSON response of the page should contain the block with a `data` object whose keys are the **field identifiers** from your Content Block YAML (`fields: - identifier: my_field`), alphabetically sorted:

..  code-block:: json

    {
        "id": 1,
        "type": "vendor_mycontentblock",
        "colPos": 0,
        "data": {
            "header": "My header",
            "my_datetime": "2023-10-20T14:08:34+00:00",
            "my_link": {
                "url": "https://example.com",
                "target": "",
                "type": "url",
                "title": "https://example.com",
                "config": { "parameter": "https://example.com" },
                "attr": { "href": "https://example.com" }
            },
            "my_text": "Some text"
        }
    }

The `id`/`type`/`colPos` wrapper is built by EXT:headless around this extension's processor output; everything inside `data` comes from `nb-content-blocks-json`. The exact shapes per field type are listed in the `JSON contract <Reference/JsonContract.rst>`__.

If the block renders but a field is missing or `null`, see :ref:`Troubleshooting <troubleshooting>`.

.. _getting-started-how-it-works:

How it works
============

`lib.contentBlock` comes from EXT:content_blocks — a FLUIDTEMPLATE served by the `content-blocks` data processor. For every Content Block with a frontend template (`templates/frontend.html`), EXT:content_blocks auto-generates the mapping

..  code-block:: typoscript

    tt_content.vendor_mycontentblock =< lib.contentBlock

This extension's Site Set replaces `lib.contentBlock` with a clone of EXT:headless' `lib.contentElement` whose `fields.data` is produced by the `nb-content-blocks-json` data processor:

..  code-block:: typoscript

    lib.contentBlock < lib.contentElement
    lib.contentBlock {
        fields {
            data = TEXT
            data {
                dataProcessing {
                    10 = nb-content-blocks-json
                    10.as = data
                }
            }
        }
    }

Because the auto-generated mapping points at `lib.contentBlock`, your Content Blocks render as JSON without any TypoScript on your side. Only two cases need a manual mapping:

*   Content Blocks **without** a frontend template are not auto-mapped.
*   Custom content elements that are not Content Blocks.

..  code-block:: typoscript

    tt_content.vendor_puredatablock =< lib.contentBlock

How the processor turns a record into the `data` JSON is covered in `Architecture <Concepts/Index.rst>`__.

Next steps
==========

*   Responsive image variants without PHP: `Define image variants <Howto/DefineImageVariants.rst>`__
*   Add menus or other TypoScript data to a block: `Add sub data processors <Howto/AddSubDataprocessors.rst>`__
*   Use containers: `Render containers <Howto/RenderContainers.rst>`__
*   Understand the pipeline: `Architecture <Concepts/Index.rst>`__
