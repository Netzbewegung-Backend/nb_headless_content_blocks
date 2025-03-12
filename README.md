# TYPO3 Extension EXT:nb_headless_content_blocks
Connects together EXT:headless and EXT:content_blocks

## TYPO3 Installation
Install extension using composer

``composer require netzbewegung/nb_headless_content_blocks``

and then, include Site Set "Headless Content Blocks", and you are ready to go.

## Custom Configuration per Content Block Type

Create headless.php inside each Content Block.

your_extension/ContentBlocks/ContentElements/your_content_block_element/headless.php

```
<?php

use TYPO3\CMS\Fluid\ViewHelpers\Uri\ImageViewHelper;

$generateThumbail = function (array $arguments): string {
    if (array_key_exists('absolute', $arguments) === false) {
        $arguments['absolute'] = true;
    }

    $imageUriViewHelper = new ImageViewHelper();
    $imageUriViewHelper->setArguments($arguments);

    return $imageUriViewHelper->render();
};

foreach ($data->items ?? [] as $itemKey => $item) {

    if ($item->image) {
        $image = $item->image;

        $data->items[$itemKey]->image->thumbnails = [
            'mobile' => $generateThumbail(['src' => $image->publicUrl, 'width' => 320]),
            'desktop' => $generateThumbail(['src' => $image->publicUrl, 'width' => 800]),
        ];
    }
}

return $data;
```