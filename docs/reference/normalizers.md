# Normalizers and transformers

This page lists the built-in conversion services, their interfaces and
their DI tags — the lookup companion to
[Architecture](../concepts/architecture.md).

## Normalizer chain

Normalizers implement `NormalizerInterface` and are registered with the DI
tag `nb_headless.normalizer`. The chain consults them in order (tag
`priority`, highest first); the first `supports() === true` wins. If none
matches, the `UnknownTypeNormalizer` emits `null` and logs the type.

```php
interface NormalizerInterface
{
    public function supports(mixed $value, Context $context): bool;
    public function normalize(mixed $value, Context $context): mixed;
}
```

Built-in normalizers (`Classes/Normalization/Normalizer/`):

| Normalizer | Claims | Output |
|---|---|---|
| `ScalarNormalizer` | `null`, int, string, plain arrays | passthrough (arrays recurse via the chain) |
| `DateTimeNormalizer` | `\DateTimeInterface` | formatted string, `options.dateTimeFormat` (default W3C) |
| `FlexFormNormalizer` | `FlexFormFieldValues` | parsed array |
| `TypolinkNormalizer` | `TypolinkParameter` | [link object](json-contract.md#link-object); `null` when empty; `__errorMessage` shape when unresolvable |
| `RecordNormalizer` | `Record` | full record conversion via `Context::buildRecord()` |
| `RecordCollectionNormalizer` | `LazyRecordCollection` | array; `sys_category` → reduced `uid/pid/title`, everything else per-record recursion |
| `FileReferenceNormalizer` | `FileReference`, `LazyFileReferenceCollection` | [file object](json-contract.md#file-object) incl. crop-aware `publicUrl` and declarative `thumbnails` |
| `FolderCollectionNormalizer` | `LazyFolderCollection` | array of storage-absolute paths |
| `UnknownTypeNormalizer` | everything (fallback, not tagged) | `null` + debug log |

## Field value transformer chain

Transformers implement `FieldValueTransformerInterface` and are registered
with the DI tag `nb_headless.field_value_transformer`. They shape **string**
values based on the field's Schema API type before the value reaches the
JSON. First `supports() === true` wins.

```php
interface FieldValueTransformerInterface
{
    public function supports(FieldTypeInterface $field): bool;
    public function transform(string $value, FieldTypeInterface $field, Context $context): string;
}
```

Built-in transformers (`Classes/FieldTransformer/String/`):

| Transformer | Applies to | Output |
|---|---|---|
| `PasswordBlanker` | Password fields | `""` |
| `RichtextParser` | Text fields with richtext enabled | HTML via `parseFunc($value, null, '< lib.parseFunc_RTE')` |

## Context

`Normalization/Context` carries the per-run state:

| Member | Purpose |
|---|---|
| `getTcaSchema()` | current table schema (sub-schema per record type) |
| `getRequest()` | PSR-7 request of the originating DataProcessor (may be `null` in CLI/unit contexts) |
| `getContentObjectRenderer()` | the processor's `ContentObjectRenderer` (may be `null` in CLI/unit contexts) |
| `getOptions()` / `getOption()` | TypoScript `options.` of the processor |
| `getFileProcessingForCurrentField()` | image variant definitions of the field being normalized |
| `getChain()` | the normalizer chain — for recursion without circular DI |
| `buildRecord()` | full record conversion — for nested records |
| `getEventDispatcher()` | PSR-14 dispatcher (deprecated event) |

## Registering your own

See the how-to guides:
[register a custom normalizer](../how-to/register-custom-normalizer.md) and
[register a field value transformer](../how-to/register-field-value-transformer.md).
