# JSON contract

This page documents the exact output shape of the conversion per field
type. The contract is **frozen** by characterization tests
(`ContentBlocksJsonDataProcessorCharTest` — they assert the complete JSON
per fixture record); changes are deliberate decisions, not accidents.

## Global rules

- **Keys** are the Content Block field identifiers (`my_field`), mapped
  from the database columns (`tx_myext_my_field`). Columns without a
  Content Block field definition pass through with their column name.
- **Order**: keys are alphabetically sorted (`ksort`) — inside `data`,
  inside nested collections, everywhere.
- **System fields** are stripped: `uid`, `pid`, `colPos`, `CType`,
  `foreign_table_parent_uid`, `tx_container_parent`.
- **Never break the response**: unknown value types become `null` with a
  debug log entry; missing files produce `__errorMessage` entries
  (see below).
- The `id`/`type`/`colPos` wrapper around `data` is built by
  EXT:headless, not by this extension.

## Field types

| Content Block type | JSON shape |
|---|---|
| Text | string, unchanged |
| Textarea | string, unchanged |
| Richtext (Text with `enableRichtext`) | HTML string via `lib.parseFunc_RTE` |
| Number | int (or float), `0`/`null` when empty |
| DateTime | W3C string `2023-10-20T14:08:34+00:00` (configurable via `options.dateTimeFormat`), `null` when empty |
| Select | selected value(s): string or array of strings, `""` when empty |
| Password | `""` — always blanked, hashes never leave the system |
| Email / Color / Slug | string |
| Json | parsed array (`null` when the column is empty) |
| Link | link object, see below (`null` when empty) |
| File (`oneToOne`) | file object, see below |
| File (`oneToMany`) | array of file objects |
| Folder | array of storage-absolute folder paths, e.g. `["/fileadmin/my-folder/"]` |
| Category | array of `{uid, pid, title}` (reduced shape — deliberate, see design record) |
| Collection | array of item objects, each fully converted (identifier keys, sorted) |
| Relation | resolved records, fully converted per target table |
| FlexForm | parsed FlexForm values as array |
| Checkbox | `null` + debug log — the Core Record API resolves checkboxes to booleans, and booleans are not part of the frozen contract. Register a [custom normalizer](../how-to/register-custom-normalizer.md) if you need checkbox values. |
| unknown types | `null` + debug log entry |

## Link object

```json
{
    "url": "https://example.com",
    "target": "",
    "type": "url",
    "title": "https://example.com",
    "config": { "parameter": "https://example.com" },
    "attr": { "href": "https://example.com" }
}
```

- `title` is the typolink *text*, which falls back to the URL when no
  link text is set.
- Empty links are `null`.
- Unresolvable targets produce the same object with empty strings plus
  `__errorMessage`.

## File object

```json
{
    "id": 1,
    "alt": "",
    "title": "",
    "publicUrl": "https://example.com/fileadmin/image.jpg",
    "thumbnails": {
        "mobile": "https://example.com/fileadmin/_processed_/…jpg",
        "desktop": "https://example.com/fileadmin/_processed_/…jpg"
    }
}
```

- `id` is the `sys_file_reference` uid.
- `publicUrl` is absolute and respects manual Backend crops.
- `thumbnails` exists only when image variants are defined for the field
  — see [Define image variants](../how-to/define-image-variants.md).
- A missing/deleted file turns the whole field into
  `{"__errorMessage": "…"}`.

## Category shape rationale

Categories use the reduced `{uid, pid, title}` shape for historical
contract reasons (the production consumer depends on it). If you need
more fields, register a [custom normalizer](../how-to/register-custom-normalizer.md)
that claims `LazyRecordCollection` of `sys_category` records with a
higher priority.

## Collections and relations

Each collection item is converted with the full pipeline of its own table:
field identifiers as keys, system fields stripped, values normalized.
Relation targets are resolved via the Core Schema API
(`ActiveRelation`), so the correct sub-schema (per record type) applies.
