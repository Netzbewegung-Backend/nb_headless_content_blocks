# Testing Framework Dokumentation

## Referenz-Extension Analyse

Die Extension `content-blocks` (`/var/www/vhosts/content-blocks`) dient als Vorlage für das Testing Framework.

### Verwendete Tools

| Tool | Version | Zweck |
|---|---|---|
| PHPUnit | 10.1 (Schema) | Test-Ausführung |
| TYPO3 Testing Framework | ^9.5 | Bootstrap, Test-Basisklassen |
| PHPStan | ^2.1 (Level 5) | Statische Analyse |
| PHP-CS-Fixer | ^3.22 | Coding Standards |
| runTests.sh | Docker/Podman | Test-Runner |
| GitHub Actions | CI/CD | Automatisierte Tests |

### Verzeichnisstruktur (Referenz)

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
├── Fixtures/
├── Unit/
└── Functional/
```

---

## Ist-Zustand: nb_headless_content_blocks

### Fehlende Komponenten

| Komponente | Status | Priorität |
|---|---|---|
| `Tests/` Verzeichnis | Fehlt komplett | Hoch |
| `Build/phpunit/` Konfiguration | Fehlt komplett | Hoch |
| `require-dev` in composer.json | Fehlt komplett | Hoch |
| `autoload-dev` in composer.json | Fehlt komplett | Hoch |
| CI/CD Pipeline | Fehlt komplett | Mittel |
| PHPStan | Fehlt komplett | Mittel |
| PHP-CS-Fixer | Fehlt komplett | Niedrig |

### Vorhandene PHP-Klassen

| Klasse | Typ | Test-Priorität |
|---|---|---|
| `ContentBlocksJsonDataProcessor` | DataProcessor | Hoch |
| `ContainerJsonDataProcessor` | DataProcessor | Hoch |
| `RecordToArray` | Utility | Mittel |
| `ArrayRecursiveToArray` | Utility | Mittel |
| `FileReferenceToArray` | Utility | Niedrig |
| `LazyFileReferenceCollectionToArray` | Utility | Niedrig |
| `LazyRecordCollectionToArray` | Utility | Niedrig |
| `LazyRecordCollectionSysCategoryToArray` | Utility | Niedrig |
| `TypolinkParameterToArray` | Utility | Niedrig |
| `LazyFolderCollectionToArray` | Utility | Niedrig |
| `ModifyArrayRecursiveToArrayEvent` | Event | Hoch |

### Technische Herausforderungen

1. **Starke TYPO3-Core-Abhängigkeiten**: `RecordFactory`, `ContentTypeResolver`, `ContentBlockRegistry`, `TableDefinitionCollection` benötigen laufenden TYPO3-Kontext
2. **`GeneralUtility::makeInstance()`**: ToArray-Klassen nutzen statische Instantiierung (schwer zu mocken)
3. **`readonly` Klassen**: PHP 8.2+ erforderlich
4. **Externe Dependency**: `ContainerJsonDataProcessor` nutzt EXT:container
5. **File-I/O**: `ContentBlocksJsonDataProcessor` nutzt `file_exists()` und `require`

### Empfohlene Teststrategie

#### Unit Tests (kein TYPO3-Kontext nötig)
- `ModifyArrayRecursiveToArrayEvent` - reines Event-Objekt
- Einfache Utility-Klassen mit minimalen Abhängigkeiten

#### Functional Tests (mit TYPO3-Kontext)
- `ContentBlocksJsonDataProcessor` - voller TYPO3-Kontext mit InMemory-PDO
- `ContainerJsonDataProcessor` - voller TYPO3-Kontext
- `ArrayRecursiveToArray` / `RecordToArray` mit gemockten Record-Objekten

---

## açıs Umsetzungsplan

### Schritt 1: composer.json erweitern

Folgende `require-dev` Dependencies hinzufügen:

```json
{
    "require-dev": {
        "typo3/testing-framework": "^9.5",
        "phpstan/phpstan": "^2.1",
        "phpstan/phpstan-phpunit": "^2.0",
        "bnf/phpstan-psr-container": "^1.1",
        "friendsofphp/php-cs-fixer": "^3.22",
        "typo3/coding-standards": "0.8.x-dev"
    }
}
```

Folgende `autoload-dev` hinzufügen:

```json
{
    "autoload-dev": {
        "psr-4": {
            "Netzbewegung\\NbHeadlessContentBlocks\\Tests\\": "Tests"
        }
    }
}
```

Folgende `scripts` hinzufügen:

```json
{
    "scripts": {
        "tests": [
            "Build/Scripts/runTests.sh -s cgl",
            "Build/Scripts/runTests.sh -s phpstan",
            "Build/Scripts/runTests.sh -s unit",
            "Build/Scripts/runTests.sh -s functional"
        ]
    }
}
```

### Schritt 2: PHPUnit Konfiguration

Dateien erstellen:
- `Build/phpunit/UnitTests.xml`
- `Build/phpunit/UnitTestsBootstrap.php`
- `Build/phpunit/FunctionalTests.xml`
- `Build/phpunit/FunctionalTestsBootstrap.php`

### Schritt 3: PHPStan Konfiguration

Dateien erstellen:
- `Build/phpstan/phpstan.neon`
- `Build/phpstan/phpstan-constants.php`

### Schritt 4: PHP-CS-Fixer Konfiguration

Dateien erstellen:
- `Build/php-cs-fixer/config.php`

### Schritt 5: runTests.sh

Skript aus Referenz-Extension kopieren und anpassen.

### Schritt 6: GitHub Actions

Datei erstellen:
- `.github/workflows/tests.yaml`

### Schritt 7: Tests schreiben

Beginnend mit den einfachsten Tests:
1. `ModifyArrayRecursiveToArrayEvent` Unit Test
2. Utility-Klassen Unit Tests
3. DataProcessor Functional Tests

---

## Befehle zum Ausführen

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
