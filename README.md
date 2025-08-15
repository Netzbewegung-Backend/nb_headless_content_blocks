[![TYPO3 compatibility](https://img.shields.io/badge/TYPO3-13.4-ff8700?maxAge=3600&logo=typo3)](https://get.typo3.org/)

# TYPO3 Extension EXT:nb_headless_content_blocks
Connects together EXT:headless (friendsoftypo3/headless) and EXT:content_blocks (friendsoftypo3/content-blocks)

## TYPO3 Installation
Install extension using composer

``composer require netzbewegung/nb_headless_content_blocks``

and then, include Site Set "Headless Content Blocks", and you are ready to go.

## Features

- Converts all complex objects into an array without extra configuration
- Richtext fields are automaticly converted via `parseFunc($value, null, '< lib.parseFunc_RTE')`
- Additional thumbnails can be created via headless.php per Content Block
- Support for EXT:container

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

## Additional Data via Sub DataProcessing

```
tt_content.vendor_yourcontentblockelement.fields.data.dataProcessing.10 {
    dataProcessing {
        10 = menu
        10 {
            levels = 2
            as = navigation
        }
    }
}
```

## Custom Configuration for EXT:container (b13/container) 

### TypoScript Setup

#### `left`/`right` parallel to `data`

```
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

#### `left`/`right` inside `data` (via Sub DataProcessing)

```
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

## Developing

### Run with DDEV

There is a ddev setup ready to use. Ensure [ddev](https://github.com/ddev/ddev)
is installed on your machine. Then run:

```
ddev start
ddev composer install
touch .Build/public/FIRST_INSTALL
ddev launch
```

The default URL is https://nb-headless-content-blocks.ddev.site/.
Continue with the TYPO3 installation process.

### Testing

First install the composer dependencies:

```
composer install
```

Then run unit or functional tests by executing:

```
Build/Scripts/runTests.sh -s cgl # Code guidelines check
Build/Scripts/runTests.sh -s phpstan # PHPStan analysis
Build/Scripts/runTests.sh -s unit # Unit tests
Build/Scripts/runTests.sh -s functional # Functional tests
```

#### Hint

Be sure to exclude the `.Build/public/typo3temp` directory from indexing in your IDE (e.g. PhpStorm) before starting the tests.