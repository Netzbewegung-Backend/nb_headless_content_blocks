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

### Code-Änderungen

- `readonly` Klassendeklarationen verwenden (PHP 8.2+)
- `GeneralUtility::makeInstance()` in Utility-Klassen (kein DI)
- `autoconfigure: false` in `Services.yaml`

### Externe Abhängigkeiten

- `B13\Container\DataProcessing\ContainerProcessor` (nur in `ContainerJsonDataProcessor`)
- `TYPO3\CMS\ContentBlocks\*` (Core Content Blocks)

## Testing Framework

### Tools

| Tool | Version | Zweck |
|---|---|---|
| PHPUnit | 11.x | Test-Ausführung |
| TYPO3 Testing Framework | ^9.5 | Bootstrap, Test-Basisklassen |
| PHPStan | ^2.1 (Level 5) | Statische Analyse |
| PHP-CS-Fixer | ^3.22 | Coding Standards |
| runTests.sh | Docker | Test-Runner |

### Verzeichnisstruktur

```
Build/
├── phpunit/
│   ├── UnitTests.xml
│   ├── UnitTestsBootstrap.php
│   ├── FunctionalTests.xml
│   └── FunctionalTestsBootstrap.php
├── phpstan/
│   ├── phpstan.neon
│   └── phpstan-constants.php
├── php-cs-fixer/
│   └── config.php
└── Scripts/
    └── runTests.sh

Tests/
├── Unit/
│   └── Event/
│       └── ModifyArrayRecursiveToArrayEventTest.php
└── Functional/
```

### Befehle

```bash
# Alle Tests
Build/Scripts/runTests.sh -s all

# Nur Unit Tests
Build/Scripts/runTests.sh -s unit

# Nur Functional Tests
Build/Scripts/runTests.sh -s functional

# CGL prüfen
Build/Scripts/runTests.sh -s cgl

# PHPStan ausführen
Build/Scripts/runTests.sh -s phpstan

# PHP-Version angeben
Build/Scripts/runTests.sh -s unit -p 8.4
```

### Teststrategie

- **Unit Tests**: `ModifyArrayRecursiveToArrayEvent` - reines Event-Objekt
- **Functional Tests**: DataProcessor mit TYPO3-Kontext (InMemory-PDO)

## Entwicklung

### Setup

```bash
# Dependencies installieren
ddev composer install

# DDEV starten
ddev start
```

### Verzeichnisse

- `.Build/vendor` - Composer Vendor-Directory
- `.Build/bin` - Composer Binaries
- `.Build/public` - Web-Root (TYPO3)

### Workflow

1. `ddev composer install` ausführen
2. Tests mit `Build/Scripts/runTests.sh` ausführen
3. Vor Commits: CGL und PHPStan prüfen

## Offene Punkte

- [ ] Unit Tests ausführen und validieren
- [ ] Weitere Unit Tests für Utility-Klassen
- [ ] Functional Tests für DataProcessor
- [ ] PHPStan baseline erstellen
- [ ] README mit Testing-Hinweisen ergänzen
