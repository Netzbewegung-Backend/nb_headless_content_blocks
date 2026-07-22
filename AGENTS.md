# AGENTS.md - nb_headless_content_blocks

## Project Overview

TYPO3 Extension for headless Content Blocks. Converts Content Block data into JSON-compatible arrays for API output.

## Technical Details

- **Namespace**: `Netzbewegung\NbHeadlessContentBlocks\`
- **PHP-Version**: 8.2+ (required by TYPO3 v13/v14)
- **TYPO3**: ^13.4 || ^14.3
- **Dependencies**: `friendsoftypo3/content-blocks`, `friendsoftypo3/headless`

## Directory Structure

```
Classes/
├── DataProcessing/
│   ├── ContentBlocksJsonDataProcessor.php    # Main processor for Content Blocks
│   └── ContainerJsonDataProcessor.php        # Processor for EXT:container
├── DataProcessing/ToArray/
│   ├── RecordToArray.php
│   ├── ArrayRecursiveToArray.php
│   ├── FileReferenceToArray.php
│   ├── LazyFileReferenceCollectionToArray.php
│   ├── LazyRecordCollectionToArray.php
│   ├── LazyRecordCollectionSysCategoryToArray.php
│   ├── TypolinkParameterToArray.php
│   └── LazyFolderCollectionToArray.php
└── Event/
    └── ModifyArrayRecursiveToArrayEvent.php  # PSR-14 Event

Configuration/
└── Sets/HeadlessContentBlock/
    ├── setup.typoscript
    └── config.yaml
```

## Core Components

### DataProcessor

| Class | Service ID | Purpose |
|---|---|---|
| `ContentBlocksJsonDataProcessor` | `nb-content-blocks-json` | Content Blocks → JSON |
| `ContainerJsonDataProcessor` | `nb-container-json` | EXT:container → JSON |

### Dependencies (Constructor Injection)

**ContentBlocksJsonDataProcessor:**
- `TableDefinitionCollection`
- `RecordFactory`
- `ContentTypeResolver`
- `ContentBlockRegistry`
- `EventDispatcher`

**ContainerJsonDataProcessor:**
- `TableDefinitionCollection`
- `RecordFactory`
- `ContentBlockDataDecorator`
- `ContentTypeResolver`
- `ContentBlockRegistry`

### PSR-14 Event

`ModifyArrayRecursiveToArrayEvent` - fired when converting arrays.

## Important Notes

### Git Workflow

- Before every commit: Run CGL and PHPStan (`Build/Scripts/runTests.sh -s cgl` / `-s phpstan`)

### Code Changes

- Use `readonly` class declarations (PHP 8.2+)
- `GeneralUtility::makeInstance()` in Utility classes (no DI)
- `autoconfigure: false` in `Services.yaml`

### External Dependencies

- `B13\Container\DataProcessing\ContainerProcessor` (only in `ContainerJsonDataProcessor`)
- `TYPO3\CMS\ContentBlocks\*` (Core Content Blocks)

## Testing Framework

### Tools

| Tool | Version | Purpose |
|---|---|---|
| PHPUnit | 11.x | Test execution |
| TYPO3 Testing Framework | ^9.5 | Bootstrap, test base classes |
| PHPStan | ^2.1 (Level 5) | Static analysis |
| PHP-CS-Fixer | ^3.22 | Coding standards |
| runTests.sh | Docker | Test runner |

### Directory Structure

```
Build/
├── phpunit/
│   ├── UnitTests.xml
│   ├── UnitTestsBootstrap.php
│   ├── FunctionalTests.xml
│   └── FunctionalTestsBootstrap.php
├── phpstan/
│   ├── phpstan.neon
│   ├── phpstan.local.neon
│   ├── phpstan.ci.neon
│   └── phpstan-constants.php
├── php-cs-fixer/
│   └── config.php
└── Scripts/
    └── runTests.sh

Tests/
├── Unit/
│   ├── Event/
│   │   └── ModifyArrayRecursiveToArrayEventTest.php
│   └── DataProcessing/ToArray/
│       ├── ArrayRecursiveToArrayTest.php
│       └── TypolinkParameterToArrayTest.php
├── Functional/
│   └── DataProcessing/
│       ├── ContentBlocksJsonDataProcessorTest.php
│       ├── ContainerJsonDataProcessorTest.php
│       └── Fixtures/
│           ├── DataSet/ (CSV fixtures)
│           └── Files/ (test images)
└── Fixtures/Extensions/test_nb_headless_content_blocks/
    ├── ContentBlocks/ContentElements/
    │   ├── simple/       # Text, Number, DateTime, Select, Password, Json, Link, Category, Collection
    │   ├── headless/     # headless.php processing
    │   └── filetest/     # File/FAL (oneToOne, oneToMany)
    └── Classes/
        └── SetRenderedContentProcessor.php  # Stub for container child rendering
```

### Commands

```bash
# All tests
Build/Scripts/runTests.sh -s unit && Build/Scripts/runTests.sh -s functional

# Unit tests only
Build/Scripts/runTests.sh -s unit

# Functional tests only
Build/Scripts/runTests.sh -s functional

# CGL check
Build/Scripts/runTests.sh -s cgl

# PHPStan
Build/Scripts/runTests.sh -s phpstan

# Specify PHP version
Build/Scripts/runTests.sh -s unit -p 8.4
```

### Test Strategy

- **Unit Tests**: `ModifyArrayRecursiveToArrayEvent` — pure event object
- **Functional Tests**: DataProcessor with TYPO3 context (InMemory-PDO)

## Development

### Setup

```bash
# Install dependencies
ddev composer install

# Start DDEV
ddev start
```

### Directories

- `.Build/vendor` - Composer Vendor Directory
- `.Build/bin` - Composer Binaries
- `.Build/public` - Web Root (TYPO3)

### Workflow

1. Run `ddev composer install`
2. Run tests with `Build/Scripts/runTests.sh`
3. Before commits: Check CGL and PHPStan
