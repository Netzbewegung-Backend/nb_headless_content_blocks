# AGENTS.md - nb_headless_content_blocks

## Projektübersicht

TYPO3 Extension für headless Content Blocks. Konvertiert Content-Block-Daten in JSON-kompatible Arrays für API-Ausgaben.

## Technische Details

- **Namespace**: `Netzbewegung\NbHeadlessContentBlocks\`
- **PHP-Version**: 8.2+ (erforderlich durch TYPO3 v13/v14)
- **TYPO3**: ^13.4 || ^14.3
- **Dependencies**: `friendsoftypo3/content-blocks`, `friendsoftypo3/headless`

## Verzeichnisstruktur

```
Classes/
├── DataProcessing/
│   ├── ContentBlocksJsonDataProcessor.php    # Hauptprocessor für Content Blocks
│   └── ContainerJsonDataProcessor.php        # Processor für EXT:container
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

## Kernkomponenten

### DataProcessor

| Klasse | Service-ID | Zweck |
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

`ModifyArrayRecursiveToArrayEvent` - wird beim Konvertieren von Arrays gefeuert.

## Wichtige Hinweise

### Testing

- **Keine Tests vorhanden** - Testing Framework muss eingerichtet werden
- Siehe `TESTING-FRAMEWORK.md` für Details
- `TESTING-FRAMEWORK-PROGRESS.md` für Fortschritt

### Code-Änderungen

- `readonly` Klassendeklarationen verwenden (PHP 8.2+)
- `GeneralUtility::makeInstance()` in Utility-Klassen (kein DI)
- `autoconfigure: false` in `Services.yaml`

### Externe Abhängigkeiten

- `B13\Container\DataProcessing\ContainerProcessor` (nur in `ContainerJsonDataProcessor`)
- `TYPO3\CMS\ContentBlocks\*` (Core Content Blocks)

## Testing Framework

```bash
# Tests ausführen
Build/Scripts/runTests.sh -s unit
Build/Scripts/runTests.sh -s functional
Build/Scripts/runTests.sh -s cgl
Build/Scripts/runTests.sh -s phpstan
```

## Entwicklung

1. `composer install` ausführen
2. Tests mit `Build/Scripts/runTests.sh` ausführen
3. Vor Commits: CGL und PHPStan prüfen
