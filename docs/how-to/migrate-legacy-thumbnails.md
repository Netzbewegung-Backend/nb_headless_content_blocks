# Migrate legacy thumbnails to headless.yaml

This guide shows how to replace a legacy `headless.php` thumbnail
generator (the archived
[ImageViewHelper pattern](../_archive/legacy-headless-php-thumbnails.md))
with declarative image variants.

## Identify the block

Legacy generators all look the same: a `headless.php` next to the
block's `config.yaml` that fills `$data[...]['image']['thumbnails']` with
URLs from a `ThumbnailUtility` or an inline `ImageViewHelper` closure.

Note down, per image field:

- the **field identifier** the thumbnails belong to (`image`, `media`, …)
- the **variant names** (`mobile`, `desktop`, `mobile2x`, …)
- the **widths/extensions** used per variant

## Replace the generator

For a File field directly on the Content Block, translate each
`generateThumbnail([...])` call into one `headless.yaml` entry:

| Legacy (ImageViewHelper argument) | Declarative (processing string) |
|---|---|
| `'width' => 320` | `width=320` |
| `'width' => 883` with crop-scaling intent | `width=883c` |
| `'height' => 400` | `height=400` |
| `'fileExtension' => 'webp'` | `fileExtension=webp` |
| `'src' => $image['id']`, `'treatIdAsReference' => true` | not needed — the processor knows the field's FileReference |
| `'absolute' => true` | not needed — URLs are always absolute |

The legacy example from the archive:

```php
$data['items'][$itemKey]['image']['thumbnails'] = [
    'mobile' => $generateThumbnail(['src' => $image['id'], 'treatIdAsReference' => true, 'width' => 320]),
    'desktop' => $generateThumbnail(['src' => $image['id'], 'treatIdAsReference' => true, 'width' => 800]),
];
```

becomes — for a direct `image` field on the block —

```yaml
fields:
  image:
    processing:
      mobile: "width=320"
      desktop: "width=800"
```

Then **delete the thumbnail part from `headless.php`** (or the whole
file, if it did nothing else). The output shape stays identical: the
same `thumbnails` map appears next to `id/alt/title/publicUrl`.

## Verify

Clear caches (`ddev typo3 cache:flush`), reload the page JSON and
compare the `thumbnails` URLs of the block before/after the migration —
same variant names, same dimensions. The block's other fields must be
byte-identical.

## Per-site overrides

Sites that need different widths than the block ships no longer patch
the PHP — they override via TypoScript (TypoScript wins per variant):

```typoscript
tt_content.vendor_myblock.fields.data.dataProcessing.10 {
    options {
        processing {
            image {
                desktop = width=1600c,fileExtension=webp
            }
        }
    }
}
```

## Known limitation

The declarative variants currently cover File fields **of the Content
Block record itself**. Images **inside Collection items** (e.g.
`$data['items'][...]['image']` — exactly the archived example's shape)
are not yet covered: nested collection records are built without the
processor options, and their custom table has no Content Block
`headless.yaml`. For that case, keep the `headless.php` generator for
now — follow the design record
([`IMPROVE_TO_ARRAY.md`](../design/IMPROVE_TO_ARRAY.md)) for the
planned extension of the `Context`-based API.
