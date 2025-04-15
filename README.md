# TYPO3 Extension EXT:nb_headless_content_blocks
Connects together EXT:headless (friendsoftypo3/headless) and EXT:content_blocks (friendsoftypo3/content-blocks)

## TYPO3 Installation
Install extension using composer

``composer require netzbewegung/nb_headless_content_blocks``

and then, include Site Set "Headless Content Blocks", and you are ready to go.

## Custom Configuration per Content Block Type

Create headless.php inside each Content Block.

your_extension/ContentBlocks/ContentElements/your-content-block-element/headless.php

```
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

foreach ($data->items ?? [] as $itemKey => $item) {

    if ($item->image) {
        $image = $item->image;

        $data->items[$itemKey]->image->thumbnails = [
            'mobile' => $generateThumbnail(['src' => $image->id, 'treatIdAsReference' => true, 'width' => 320]),
            'desktop' => $generateThumbnail(['src' => $image->id, 'treatIdAsReference' => true, 'width' => 800]),
        ];
    }
}

return $data;
```

## Custom Configuration for EXT:headless (b13/container) 

### TypoScript Setup

```
lib.content.select.where = colPos NOT IN (201, 202)

tt_content.b13_2_columns_container =< lib.contentElement
tt_content.b13_2_columns_container {
    fields {
        left = TEXT
        left {
            dataProcessing {
                10 = nb-container-json 
                10.colPos = 201
                10.as = left
            }
        }
        right = TEXT
        right {
            dataProcessing {
                10 = nb-container-json 
                10.colPos = 202
                10.as = right
            }
        }
    }
}
```
