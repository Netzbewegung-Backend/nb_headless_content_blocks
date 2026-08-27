# Register a custom normalizer

This guide shows how to add your own value types to the JSON output — for
domain objects or field types the built-in normalizers do not cover.

## When you need one

A normalizer claims a *value type*. You need one when a field value
arrives at the end of the chain and would become `null` (see
[Troubleshooting](../troubleshooting.md#a-field-is-null-in-the-json-and-the-log-mentions-an-unknown-type)) —
typically because your extension introduced a custom relation type, or you
want to change the shape of an existing one (e.g. categories with more
fields than the frozen `uid/pid/title` shape).

Normalizers are consulted in registration order; the first one whose
`supports()` returns `true` wins. To *replace* built-in behavior, claim
the same type with a higher `priority`.

## Implement the interface

```php
<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Normalization;

use Netzbewegung\NbHeadlessContentBlocks\Normalization\Context;
use Netzbewegung\NbHeadlessContentBlocks\Normalization\NormalizerInterface;
use TYPO3\CMS\Core\Resource\FileReference;

final class SquareThumbnailNormalizer implements NormalizerInterface
{
    public function supports(mixed $value, Context $context): bool
    {
        // claim only what you really want to shape
        return $value instanceof FileReference && ($context->getOption('square') === true);
    }

    public function normalize(mixed $value, Context $context): mixed
    {
        // must return a JSON-compatible value (array|string|int|float|null)
        return [
            'publicUrl' => $value->getPublicUrl(),
        ];
    }
}
```

The `Context` gives you the current `TcaSchema`, the request, and the
processor `options.` (TypoScript). Normalizers may recurse: call
`$context->getChain()->normalize($nestedValue, $context)` for nested
rich values, and `$context->buildRecord($record)` to run a related record
through the full conversion (identifier mapping, transformers, event).

## Register the service

In your extension's `Configuration/Services.yaml`:

```yaml
services:
  MyVendor\MyExtension\Normalization\SquareThumbnailNormalizer:
    tags:
      - name: 'nb_headless.normalizer'
        priority: 100        # higher = earlier in the chain (default 0)
```

No other wiring needed — the `NormalizerChain` receives all tagged
services via a tagged iterator.

## Built-in normalizers

See [Normalizers and transformers](../reference/normalizers.md) for the
built-in chain and its frozen output shapes — do not change them
accidentally: the shapes are part of the
[JSON contract](../reference/json-contract.md).
