# Getting started

This page walks you through installing the extension and verifying your first
JSON output — it is a linear tutorial; alternatives and background are linked
at the end.

## Prerequisites

- TYPO3 ≥ 13.4 (13.4 and 14.3 are tested in CI)
- A headless frontend based on [EXT:headless](https://github.com/TYPO3-Headless/headless) ≥ 4.5
- Content Blocks created with [EXT:content_blocks](https://github.com/FriendsOfTYPO3/content-blocks) ≥ 1.2.3
- Composer — the extension is not published to TER

EXT:headless and EXT:content_blocks are installed automatically as
dependencies when you require this extension.

## Install

```bash
composer require netzbewegung/nb_headless_content_blocks
```

Then include the extension's Site Set in your site package's
`config/sites/<site>/config.yaml`:

```yaml
dependencies:
  - nb-headless-content-blocks/headless-content-blocks
```

The Site Set defines `lib.contentBlock` — a clone of `lib.contentElement`
whose `fields.data` is produced by the `nb-content-blocks-json` data
processor:

```typoscript
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
```

Map your Content Blocks onto it in your site package's TypoScript:

```typoscript
tt_content.vendor_mycontentblock =< lib.contentBlock
```

## Verify

Open a page of your headless site that contains a Content Block element.
The JSON response of the page should contain the block with a `data` object
whose keys are the **field identifiers** from your Content Block YAML
(`fields: - identifier: my_field`), alphabetically sorted:

```json
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
```

The `id`/`type`/`colPos` wrapper is built by EXT:headless around this
extension's processor output; everything inside `data` comes from
`nb-content-blocks-json`. The exact shapes per field type are listed in the
[JSON contract](reference/json-contract.md).

If the block renders but a field is missing or `null`, see
[Troubleshooting](troubleshooting.md).

## Next steps

- Responsive image variants without PHP:
  [Define image variants](how-to/define-image-variants.md)
- Add menus or other TypoScript data to a block:
  [Add sub data processors](how-to/add-sub-dataprocessors.md)
- Use containers: [Render containers](how-to/render-containers.md)
- Understand the pipeline: [Architecture](concepts/architecture.md)
