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

- [ ] Textarea field type (plain + RTE)
- [ ] Additional DataProcessors via `dataProcessing.` config
- [ ] Empty container (no children)

## Medium Priority (Functional Tests)

- [ ] Color field type pass-through
- [ ] Email field type pass-through
- [ ] Slug field type pass-through
- [ ] Empty link value returning null
- [ ] Missing file reference (`FileDoesNotExistException`)
- [ ] Default `as` key for container

## Low Priority (Functional Tests)

- [ ] Multiple categories
- [ ] Richer collection sub-fields
- [ ] Link with target and title attributes
- [ ] DateTime null value
