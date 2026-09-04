# Modify fields with the PSR-14 event

This guide shows the legacy way of overriding single field values — the
`ModifyArrayRecursiveToArrayEvent`. **It is deprecated** and kept for
backwards compatibility; prefer normalizers or field value transformers
for new code.

## Why it is deprecated

The event predates the normalizer registry. It runs per field on *every*
record conversion, carries ContentBlocks internals in its payload, and
cannot express value-type dispatch cleanly. The modern equivalents:

| Legacy (event) | Modern |
|---|---|
| Override by field name | [Field value transformer](register-field-value-transformer.md) (strings) |
| Override by value type | [Custom normalizer](register-custom-normalizer.md) |
| Whole-block post-processing | [headless.php](post-process-with-headless-php.md) |

The event keeps firing with its original payload until the next minor
release; listeners continue to work unchanged until then.

## The listener

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\EventListener;

use Netzbewegung\NbHeadlessContentBlocks\Event\ModifyArrayRecursiveToArrayEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;

#[AsEventListener]
final class MyCustomListener
{
    public function __invoke(ModifyArrayRecursiveToArrayEvent $event): void
    {
        // Custom handling by field name (column!)
        if ($event->getKey() === 'tx_my_vendor_field_name') {
            $processedValue = strtoupper((string)$event->getValue());
            $event->setProcessedValue($processedValue);
        }

        // Custom handling for all text fields
        if (
            $event->getTcaFieldDefinition() !== null
            && $event->getTcaFieldDefinition()->fieldType instanceof \TYPO3\CMS\ContentBlocks\FieldType\TextFieldType
        ) {
            $event->setProcessedValue(strtoupper((string)$event->getValue()));
        }
    }
}
```

Semantics:

- `getKey()` is the **database column** (pre identifier mapping) —
  `tx_my_vendor_field_name`, not `my_field`.
- `setProcessedValue()` marks the field as handled; the value lands in
  the JSON under the mapped field identifier.
- Unhandled fields fall through to the normal conversion.
- The event fires for **every field of every record** — return fast.

## Migration

Replace `setProcessedValue($value)` calls with a transformer (string
values) or normalizer (everything else). The migrated code is smaller,
testable in isolation, and independent of ContentBlocks internals.
