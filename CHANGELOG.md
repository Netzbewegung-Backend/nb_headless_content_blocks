# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

> Note: The tags 0.0.24 and 0.0.25 were deleted (unstable releases) and do
> not exist. The versions between 0.0.23 and 0.0.26 shipped together in
> 0.0.26.

## [Unreleased]

Rewrite of the ToArray conversion (see `docs/design/IMPROVE_TO_ARRAY.md`).
The JSON output contract is unchanged and frozen by characterization tests.

### Added

- Normalizer registry: value conversion is split into small, DI-tagged
  normalizers (`nb_headless.normalizer`). Site packages can register their
  own normalizers via `Services.yaml`.
- Field value transformer registry (`nb_headless.field_value_transformer`) for
  string field shaping (password blanking, richtext parsing).
- Declarative image variants: Content Blocks can define responsive thumbnails
  in an optional `headless.yaml` per field, overridable via TypoScript
  processor option `options.processing.<field>.<variant>`. Replaces the
  handwritten `ThumbnailUtility` calls inside `headless.php` files.
- `headless.php` may receive a second parameter with the `Context` (planned,
  see design doc decision 4 — not yet part of the stable API).
- Characterization tests freezing the complete JSON contract for all fixture
  records (`ContentBlocksJsonDataProcessorCharTest`).
- Unknown/unconvertible value types now become `null` with a debug log entry
  instead of being silently dropped.

### Changed

- Field metadata (field type, relation targets, richtext flag) is resolved via
  the TYPO3 Core Schema API (`TcaSchemaFactory`) instead of ContentBlocks
  internals. ContentBlocks definitions are now only used for the column →
  field identifier mapping (behind `IdentifierMapperInterface`).
- All conversion services are constructor-injected (DI); almost no
  `GeneralUtility::makeInstance()` calls remain.
- PSR-14 event `ModifyArrayRecursiveToArrayEvent` is now fired by the new
  `RecordArrayBuilder` with the same payload (deprecated, but fully
  functional).

### Removed

- Internal `DataProcessing/ToArray/*` classes (`ArrayRecursiveToArray`,
  `RecordToArray`, `FileReferenceToArray`, `TypolinkParameterToArray`,
  `LazyRecordCollectionToArray`, `LazyRecordCollectionSysCategoryToArray`,
  `LazyFileReferenceCollectionToArray`, `LazyFolderCollectionToArray`).

### Breaking (internal)

- Constructor signatures of `ContentBlocksJsonDataProcessor` and
  `ContainerJsonDataProcessor` changed (now receive `RecordArrayBuilder`,
  `ContentDataProcessor` / `ContainerProcessor` via DI). Only relevant if you
  instantiated or XCLASSed these classes manually — TypoScript usage via
  `nb-content-blocks-json` / `nb-container-json` is unaffected.
- The removed `ToArray` classes were not part of the documented API, but if
  you used them directly, migrate to `RecordArrayBuilder` / the normalizers.
- Boolean/float values (which never occur in real records) are now `null`
  with a debug log entry instead of being dropped silently.

## [0.0.26] - 2026-07-23

### Added

- Testing framework (unit + functional tests based on TYPO3 Testing Framework,
  `Build/Scripts/runTests.sh` docker runner).
- CI: CGL, PHPStan, TYPO3 version matrix (13.4 / 14.3), composer cache.
- `ext_emconf.php` re-added for TYPO3 13 classic mode compatibility.
- `setRequest()` handling for TYPO3 14 compatibility.
- Version tagging via composer.json `extra.typo3/cms.version`.

### Fixed

- CRLF line endings; `.gitattributes` enforces LF.

## [0.0.23] - 2026-05-09

### Fixed

- Re-added `ext_emconf.php` to stay compatible with TYPO3 13.

## [0.0.22] - 2026-05-09

### Changed

- Allow `friendsoftypo3/headless` ^5.0 and TYPO3 14 installation.

## [0.0.21] - 2025-11-02

### Fixed

- Documentation typo.

## [0.0.20] - 2025-11-01

### Added

- Basic support for custom field types: unknown types are returned as-is
  instead of failing.

## [0.0.19] - 2025-08-06

### Fixed

- Relation fields with multiple allowed tables (comma-separated) no longer
  throw an exception.

## [0.0.18] - 2025-05-19

### Changed

- Compatibility with EXT:content_blocks >= 1.2.3.

## [0.0.17] - 2025-05-19

### Changed

- Documentation updates.

## [0.0.16] - 2025-05-14

### Changed

- Documentation updates.

## [0.0.15] - 2025-05-13

### Changed

- Rector cleanups.

## [0.0.14] - 2025-05-07

### Changed

- Code cleanup, PHP CS Fixer.

## [0.0.13] - 2025-05-05

### Fixed

- Reuse existing ContentObjectRenderer so Content Blocks used as sub data
  processors work correctly.

## [0.0.12] - 2025-05-04

### Added

- Support for field types: `Category`, `Checkbox`, `DateTime`, `Email`,
  `Folder`, `Json`, `Pass`, `Password`, `Relation` (single allowed table),
  `Select` (selectMultipleSideBySide), `Slug`, `Uuid`.

## [0.0.11] - 2025-05-01

### Fixed

- Rich text processing.

## [0.0.10] - 2025-05-01

### Changed

- Replaced JsonSerializable approach with explicit `toArray()` conversion;
  `GeneralUtility::makeInstance` instead of `new` to allow XCLASSing.

## [0.0.9] - 2025-04-15

### Added

- Support for EXT:container (b13/container) via `nb-container-json`
  processor.
- Graceful error message in JSON response when a referenced file is missing.

## [0.0.8] - 2025-03-18

### Changed

- Documentation updates.

## [0.0.7] - 2025-03-15

### Fixed

- Prevent undefined variable error.

## [0.0.6] - 2025-03-12

### Added

- Per Content Block customization via `headless.php`.
- Support for sub DataProcessors (`dataProcessing.`).
- Image cropping support for manually cropped images (Backend crop tool).

## [0.0.5] - 2025-03-10

### Changed

- Use the field identifiers from the Content Blocks YAML files as JSON keys
  (instead of the prefixed database column names).

## [0.0.4] - 2024-12-10

### Added

- FlexForm support (`FlexFormFieldValues` conversion).

## [0.0.3] - 2024-12-10

### Changed

- Set `autoconfigure: false` in Services.yaml.

## [0.0.2] - 2024-12-10

### Changed

- EXT:content_blocks dependency raised to >= 1.0.

## [0.0.1] - 2024-12-06

### Added

- Initial release: connects EXT:headless with EXT:content_blocks, converts
  Content Block records to JSON-compatible arrays.

[Unreleased]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.26...HEAD
[0.0.26]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.23...0.0.26
[0.0.23]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.22...0.0.23
[0.0.22]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.21...0.0.22
[0.0.21]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.20...0.0.21
[0.0.20]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.19...0.0.20
[0.0.19]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.18...0.0.19
[0.0.18]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.17...0.0.18
[0.0.17]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.16...0.0.17
[0.0.16]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.15...0.0.16
[0.0.15]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.14...0.0.15
[0.0.14]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.13...0.0.14
[0.0.13]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.12...0.0.13
[0.0.12]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.11...0.0.12
[0.0.11]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.10...0.0.11
[0.0.10]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.9...0.0.10
[0.0.9]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.8...0.0.9
[0.0.8]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.7...0.0.8
[0.0.7]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.6...0.0.7
[0.0.6]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.5...0.0.6
[0.0.5]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.4...0.0.5
[0.0.4]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.3...0.0.4
[0.0.3]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.2...0.0.3
[0.0.2]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/compare/0.0.1...0.0.2
[0.0.1]: https://github.com/Netzbewegung-Backend/nb_headless_content_blocks/releases/tag/0.0.1
