# Define image variants

This guide shows how to get responsive thumbnail URLs next to the original
`publicUrl` of File fields — declaratively, without PHP. It replaces the
former pattern of generating thumbnails inside `headless.php`.

## The problem it solves

Headless frontends need each image in several sizes (`mobile`, `desktop`,
`2x` variants, webp). Before this feature, every Content Block carried a
`headless.php` with a handwritten thumbnail generator — on the production
site this extension was built for, **13 identical `headless.php` files**
existed, all doing the same thing with different widths. The old pattern
is archived here:
[legacy thumbnails via headless.php](../_archive/legacy-headless-php-thumbnails.md);
for migrating existing blocks see
[Migrate legacy thumbnails](migrate-legacy-thumbnails.md).

## Define variants in headless.yaml

Create a `headless.yaml` next to the Content Block's `config.yaml`:

```
your_extension/ContentBlocks/ContentElements/your-block/headless.yaml
```

```yaml
fields:
  image:
    processing:
      mobile:     "width=883c,fileExtension=webp"
      mobile2x:   "width=1766c,fileExtension=webp"
      desktop:    "width=1564c,fileExtension=webp"
      desktop2x:  "width=3128c,fileExtension=webp"
```

Schema rules:

- Keys below `fields` are **field identifiers** (as in `config.yaml`), not
  database columns.
- Keys below `processing` are free variant names — they become the keys of
  the `thumbnails` map.
- Values are processing instruction strings: `key=value` pairs,
  comma-separated (`width=883c,fileExtension=webp`) — the same syntax
  EXT:headless uses in its processors, and the same instructions TYPO3's
  image processing accepts (`width`, `height`, `fileExtension`, `crop`, ...).
  The `c` suffix means crop-scaling to exactly that width.

The parsed file is cached; run `ddev typo3 cache:flush` (or clear caches in
the Backend) after changes.

## The result

Every File field with variants gets a `thumbnails` map in addition to the
frozen base shape — for `oneToOne` and `oneToMany` relationships alike:

```json
{
    "image": {
        "id": 1,
        "alt": "",
        "title": "",
        "publicUrl": "https://example.com/fileadmin/image.jpg",
        "thumbnails": {
            "mobile": "https://example.com/fileadmin/_processed_/c/a/mobile.jpg",
            "desktop": "https://example.com/fileadmin/_processed_/7/3/desktop.jpg"
        }
    }
}
```

Fields without variants keep the plain `id/alt/title/publicUrl` shape.

## Override variants per site (TypoScript)

`headless.yaml` is versioned with the Content Block and is the default. A
site package can override or add variants per field via the processor
option `options.processing` — **TypoScript wins on conflict**,
`headless.yaml` variants without a TypoScript counterpart stay:

```typoscript
tt_content.vendor_myblock.fields.data.dataProcessing.10 {
    options {
        processing {
            image {
                desktop = width=1600c,fileExtension=webp
                xl = width=2400c,fileExtension=webp
            }
        }
    }
}
```

All other [processor options](../reference/processor-options.md) work as
usual.

## Troubleshooting

If `thumbnails` do not show up, see
[Troubleshooting → thumbnails are missing](../troubleshooting.md#thumbnails-are-missing-on-image-fields).
