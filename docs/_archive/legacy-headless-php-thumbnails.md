# Legacy: thumbnails via headless.php (ImageViewHelper)

> Status: **SUPERSEDED by declarative image variants** — for File fields of
> a Content Block, replace this pattern with
> [`headless.yaml`](../how-to/define-image-variants.md). Kept as the
> historical reference for migrating existing blocks; production sites
> carry many copies of this file (13 on the site this extension was built
> for). One case is **not** yet replaceable: images inside Collection
> items — see the [migration guide](../how-to/migrate-legacy-thumbnails.md#known-limitation).

## The pattern

`your_extension/ContentBlocks/ContentElements/your-content-block-element/headless.php`
with thumbnail generation through the Fluid `ImageViewHelper`:

```php
<?php

use TYPO3\CMS\Fluid\ViewHelpers\Uri\ImageViewHelper;

$generateThumbnail = function (array $arguments): string {
    if (array_key_exists('absolute', $arguments) === false) {
        $arguments['absolute'] = true;
    }

    $imageViewHelper = new ImageViewHelper();

    foreach ($imageViewHelper->prepareArguments() as $argumentKey => $argumentDefinition) {
        if (array_key_exists($argumentKey, $arguments) === false) {
            $arguments[$argumentKey] = $argumentDefinition->getDefaultValue();
        }
    }

    $imageViewHelper->setArguments($arguments);

    return $imageViewHelper->initializeArgumentsAndRender();

};

foreach ($data['items'] ?? [] as $itemKey => $item) {

    if ($item['image']) {
        $image = $item['image'];

        $data['items'][$itemKey]['image']['thumbnails'] = [
            'mobile' => $generateThumbnail(['src' => $image['id'], 'treatIdAsReference' => true, 'width' => 320]),
            'desktop' => $generateThumbnail(['src' => $image['id'], 'treatIdAsReference' => true, 'width' => 800]),
        ];
    }
}

return $data;
```

## Why it was replaced

- Every Content Block repeated the same generator with different widths —
  pure duplication across site packages.
- Driving a Fluid ViewHelper from PHP for URL generation is an abuse of
  the ViewHelper API (no DI, awkward argument filling, hidden
  dependencies).
- Nothing about the variants was declarative or reviewable — widths hid
  in PHP closures.

The declarative replacement produces the same `thumbnails` map next to
the frozen `id/alt/title/publicUrl` shape:
[Define image variants](../how-to/define-image-variants.md).
