# Processor options

This page lists the TypoScript options of the two data processors. Look
them up here; for the surrounding pipeline see
[Architecture](../concepts/architecture.md).

## nb-content-blocks-json

Used as `dataProcessing.10 = nb-content-blocks-json` (wired by the Site
Set's `lib.contentBlock`):

| Option | Type | Default | Description |
|---|---|---|---|
| `as` | string | `data` | key of the built array in the processor result |
| `dataProcessing.` | array | — | sub data processors, results merged into `data` — see [Add sub data processors](../how-to/add-sub-dataprocessors.md) |
| `options.processing.<field>.<variant>` | string | — | image variant override per field identifier; merges over `headless.yaml` (TypoScript wins) — see [Define image variants](../how-to/define-image-variants.md) |
| `options.dateTimeFormat` | string | `DATE_W3C` | format for DateTime fields (PHP date format, e.g. `U` for timestamps) |

Example:

```typoscript
tt_content.vendor_myblock.fields.data.dataProcessing.10 {
    as = data
    options {
        dateTimeFormat = Y-m-d
        processing {
            image {
                desktop = width=1600c,fileExtension=webp
            }
        }
    }
    dataProcessing {
        10 = menu
        10 {
            levels = 2
            as = navigation
        }
    }
}
```

Processing strings use the ext:headless syntax: `key=value` pairs,
comma-separated (`width=883c,fileExtension=webp`) — accepted keys are the
TYPO3 image processing instructions (`width`, `height`, `fileExtension`,
`crop`, `additionalParameters`, ...).

## nb-container-json

Used inside container Content Types (see
[Render containers](../how-to/render-containers.md)):

| Option | Type | Default | Description |
|---|---|---|---|
| `colPos` | int | — | container column whose children are fetched (e.g. `201`) |
| `as` | string | — | key for the children array |

Children are rendered through their own `tt_content.<CType>` mapping —
their conversion options (`options.processing`, `options.dateTimeFormat`)
are therefore configured there, not on `nb-container-json`.

## Site Set

The extension ships the Site Set
`nb-headless-content-blocks/headless-content-blocks` which replaces
`lib.contentBlock` (originally defined by EXT:content_blocks) with a
`lib.contentElement` clone with the processor wired. EXT:content_blocks
auto-maps every Content Block with a frontend template onto
`lib.contentBlock`, so no manual TypoScript is needed — see
[Getting started → How it works](../getting-started.md#how-it-works)
for the two cases that do need one. Include the set in your site's
`config.yaml`:

```yaml
dependencies:
  - nb-headless-content-blocks/headless-content-blocks
```
