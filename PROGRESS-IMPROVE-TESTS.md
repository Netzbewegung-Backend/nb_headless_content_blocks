# Test Improvement Progress

## High Priority (Unit Tests)

- [x] `ModifyArrayRecursiveToArrayEvent` — non-null `TcaFieldDefinition` test
- [x] `RecordToArray` — system field removal + error handling tests
- [x] `LazyRecordCollectionSysCategoryToArray` — field extraction tests
- [x] `LazyFolderCollectionToArray` — path construction tests
- [x] `ArrayRecursiveToArray::processStringField` — password emptying, passthrough tests

## Medium Priority (Unit Tests)

- [x] `TypolinkParameterToArray` — error path test (via subclass override)
- [x] `LazyFileReferenceCollectionToArray` — empty collection test
- [x] `LazyRecordCollectionToArray` — exception test for unknown table

## High Priority (Functional Tests)

- [x] Textarea field type (plain) — RTE omitted, requires TypoScript setup
- [x] Additional DataProcessors via `dataProcessing.` config
- [x] Empty container (no children)

## Medium Priority (Functional Tests)

- [x] Color field type pass-through
- [x] Email field type pass-through
- [x] Slug field type pass-through
- [x] Empty link value returning null
- [x] Missing file reference — graceful handling for non-existent file (empty publicUrl)
- [x] Default `as` key for container

## Low Priority (Functional Tests)

- [x] Multiple categories
- [x] Richer collection sub-fields
- [x] Link with target and title attributes
- [x] DateTime null value
