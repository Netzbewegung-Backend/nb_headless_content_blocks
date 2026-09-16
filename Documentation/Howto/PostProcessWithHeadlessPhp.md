# Post-process JSON with headless.php

This guide shows how to modify a Content Block's complete `data` array in
PHP — the escape hatch when no declarative feature covers your case.

## When to use it (and when not)

Reach for `headless.php` only for what the built-in features cannot do:

| Need | Use instead |
|---|---|
| Responsive image variants | [Define image variants](DefineImageVariants.md) (`headless.yaml`) |
| Menus, record lists, TypoScript data | [Add sub data processors](AddSubDataprocessors.md) |
| Custom value/string shaping | [Custom normalizer](RegisterCustomNormalizer.md) / [transformer](RegisterFieldValueTransformer.md) |

`headless.php` remains for genuinely block-specific logic — merging two
fields, calling an API, reshaping the whole payload.

> **Maintenance note:** on the production site this extension was built
> for, 13 `headless.php` files existed whose only job was thumbnail
> generation. All of them are candidates for deletion since declarative
> image variants exist — see
> [Migrate legacy thumbnails](MigrateLegacyThumbnails.md) and the
> archived [legacy pattern](../_archive/LegacyHeadlessPhpThumbnails.md).

## Create the file

Place a `headless.php` next to the Content Block's `config.yaml`:

```
your_extension/ContentBlocks/ContentElements/your-block/headless.php
```

The file receives the fully built `data` array and returns the modified
one:

```php
<?php

declare(strict_types=1);

// $data: the complete JSON array of this block, field identifiers as keys

foreach ($data['items'] ?? [] as $itemKey => $item) {
    $data['items'][$itemKey]['combinedTitle'] = trim($item['title'] . ' ' . $item['subtitle']);
}

// You can also add completely new keys...
$data['lastModified'] = date(\DateTimeInterface::W3C);

// ...or remove fields from the output
unset($data['subtitle']);

return $data;
```

Rules:

- The file is plain PHP included via `require` — keep it side-effect free
  apart from building the return value.
- Return the array. A file that returns anything else (e.g. no `return`
  statement at all) throws an exception in a Development application
  context; in Production the processor falls back to the unmodified data.
- The file runs on **every** render of this block type.

## Compatibility

The extension plans to pass a second parameter with the normalization
`Context` (`function (array $data, Context $context)`) — not yet part of
the stable API. Do not rely on it yet; see the
[design record](../Design/ImproveToArray.md) (decision 4).

## Troubleshooting

Changes do not show up? `headless.php` is executed on every request —
check that the file is really next to the block's `config.yaml` and that
you are editing the Content Block the record actually uses (CType).
