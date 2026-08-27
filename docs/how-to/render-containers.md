# Render containers

This guide shows how to output EXT:container (b13/container) elements with
this extension — the container's columns as JSON alongside or inside the
container's own `data`.

## Prerequisites

- EXT:container installed (`composer require b13/container`)
- Container Content Types defined (e.g. the `b13_2_columns_container` used
  below, with child columns `colPos` 201 and 202)

`b13/container` is a *suggested* dependency — the extension works without
it; the `nb-container-json` processor only loads when EXT:container is
present.

## TypoScript setup

### Variant 1: `left`/`right` parallel to `data`

```typoscript
lib.content.select.where = colPos NOT IN (201, 202)

tt_content.b13_2_columns_container =< lib.contentElement
tt_content.b13_2_columns_container {
    fields {
        left = TEXT
        left {
            dataProcessing {
                10 = nb-container-json
                10 {
                    colPos = 201
                    as = left
                }
            }
        }
        right = TEXT
        right {
            dataProcessing {
                10 = nb-container-json
                10 {
                    colPos = 202
                    as = right
                }
            }
        }
    }
}
```

Result: the container element carries `left`/`right` next to `data`:

```json
{
    "id": 5,
    "type": "b13_2_columns_container",
    "colPos": 0,
    "left": [ { "id": 6, "type": "vendor_text", "data": { "...": "..." } } ],
    "right": [ { "id": 7, "type": "vendor_image", "data": { "...": "..." } } ]
}
```

### Variant 2: `left`/`right` inside `data`

```typoscript
lib.content.select.where = colPos NOT IN (201, 202)

tt_content.b13_2_columns_container.fields.data.dataProcessing.10 {
    dataProcessing {
        10 = nb-container-json
        10 {
            colPos = 201
            as = left
        }

        20 = nb-container-json
        20 {
            colPos = 202
            as = right
        }
    }
}
```

This is the sub data processor pattern from
[Add sub data processors](add-sub-dataprocessors.md) — the columns land as
keys inside `data`.

## How it works

`nb-container-json` (`ContainerJsonDataProcessor`) fetches the container's
children for the given `colPos` via b13/container's `ContainerProcessor`
and renders each child through the same conversion as
`nb-content-blocks-json` — field identifiers as keys, same
[JSON contract](../reference/json-contract.md).

## The `colPos` exclusion matters

`lib.content.select.where = colPos NOT IN (201, 202)` keeps the container
children out of the regular page content query. Without it, children
appear twice: once in the page's content array and once in their column.

## Options

The processor accepts the same options as `nb-content-blocks-json`
(`as`, `options.processing`, `options.dateTimeFormat` — see
[Processor options](../reference/processor-options.md)) plus `colPos`
(container column to fetch, required).
