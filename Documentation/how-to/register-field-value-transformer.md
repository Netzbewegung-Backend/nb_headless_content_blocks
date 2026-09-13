# Register a field value transformer

This guide shows how to change how **string** field values are shaped in
the JSON output — the same mechanism that blanks passwords and parses
richtext.

## When you need one

Field value transformers shape plain string values based on the field's
**schema type** (`FieldTypeInterface` — TCA/Schema API knowledge: field
type, configuration, richtext flag, ...). Use one when:

- a specific field type needs a different string representation (e.g.
  trim, mask, map color names),
- you want to blank or normalize certain inputs the way
  `PasswordBlanker` blanks password fields.

For whole objects (files, links, relations) use a
[custom normalizer](register-custom-normalizer.md) instead — transformers
only ever see strings.

## Implement the interface

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\FieldTransformer;

use Netzbewegung\NbHeadlessContentBlocks\FieldTransformer\FieldValueTransformerInterface;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use TYPO3\CMS\Core\DataHandling\TableColumnType;
use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;

final class ColorNameTransformer implements FieldValueTransformerInterface
{
    private const COLOR_NAMES = [
        '#ff0000' => 'red',
        '#00ff00' => 'green',
    ];

    public function supports(FieldTypeInterface $field): bool
    {
        // claim Color fields (Content Block type "Color")
        return $field->getType() === TableColumnType::COLOR->value;
    }

    public function transform(string $value, FieldTypeInterface $field, Context $context): string
    {
        return self::COLOR_NAMES[strtolower($value)] ?? $value;
    }
}
```

The `Context` carries the state of the current normalization run: the
frontend request (with its TypoScript) and the `ContentObjectRenderer` of
the originating DataProcessor, plus the processor options — the built-in
`RichtextParser` uses the `ContentObjectRenderer` to parse values through
`lib.parseFunc_RTE`.

## Register the service

In your extension's `Configuration/Services.yaml`:

```yaml
services:
  MyVendor\MyExtension\FieldTransformer\ColorNameTransformer:
    tags: ['nb_headless.field_value_transformer']
```

The chain asks every tagged transformer; the first `supports() === true`
wins, and only strings pass through it (arrays and objects go to the
[normalizer chain](../reference/normalizers.md) instead).

## Built-in transformers

| Transformer | Applies to | Output |
|---|---|---|
| `PasswordBlanker` | Password fields | `""` — hashes never reach the client |
| `RichtextParser` | Text fields with richtext enabled | HTML via `lib.parseFunc_RTE` |
