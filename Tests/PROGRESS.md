# Testing Framework - Fortschritt

## Aktueller Stand

### Abgeschlossene Aufgaben

| Aufgabe | Status | Datum |
|---|---|---|
| Referenz-Extension analysiert | ✅ Abgeschlossen | Heute |
| Aktuelle Extension analysiert | ✅ Abgeschlossen | Heute |
| TESTING-FRAMEWORK.md erstellt | ✅ Abgeschlossen | Heute |
| AGENTS.md erstellt | ✅ Abgeschlossen | Heute |
| composer.json erweitert | ✅ Abgeschlossen | Heute |
| PHPUnit Konfiguration erstellt | ✅ Abgeschlossen | Heute |
| Bootstrap Dateien erstellt | ✅ Abgeschlossen | Heute |
| PHPStan Konfiguration erstellt | ✅ Abgeschlossen | Heute |
| PHP-CS-Fixer Konfiguration erstellt | ✅ Abgeschlossen | Heute |
| runTests.sh kopiert | ✅ Abgeschlossen | Heute |
| GitHub Actions Workflow erstellt | ✅ Abgeschlossen | Heute |
| Erster Unit Test erstellt | ✅ Abgeschlossen | Heute |

### Validiert (22.07.2026)

| Prüfung | Ergebnis |
|---|---|
| Unit Tests | ✅ 22 Tests OK (Event, TypolinkParameterToArray, ArrayRecursiveToArray) |
| Functional Tests | ✅ 15 Tests OK (ContentBlocksJsonDataProcessor + ContainerJsonDataProcessor, sqlite) |
| CGL (php-cs-fixer) | ✅ Sauber |
| PHPStan (Level 5) | ✅ Keine Fehler |
| DDEV-Ausführung | ✅ Unit Tests + PHPStan via `ddev exec` |

### Abgedeckte Feldtypen (Functional Tests)

Text, Number, DateTime, Select, Password (Wert wird geleert), Json, Link, Category, Collection, useExistingField (header/bodytext), headless.php Verarbeitung, `as`-Konfiguration, unbekannte Tabellen, Container (colPos-Auswahl, skipRenderingChildContent, childRendering via dataProcessing stubs).

### Erkenntnisse

- Functional Tests brauchen DB-Credentials als Env-Variablen → immer via `CI=true Build/Scripts/runTests.sh -s functional` oder `ddev exec env typo3DatabaseDriver=pdo_sqlite .Build/bin/phpunit -c Build/phpunit/FunctionalTests.xml`
- `content_blocks` muss in `testExtensionsToLoad` explizit geladen werden, sonst kein ContentBlocks-Schema (SqlGenerator)
- Link-Felder brauchen `$GLOBALS['TYPO3_REQUEST']` im Test (LinkFactory)
- PHPStan-Cache (`.cache/phpstan`) kann root-gehörig sein → Container-Runs als User 1000 schlagen fehl
- ContainerJsonDataProcessor braucht `PageInformation` Request-Attribut (`frontend.page.information`) für `FrontendContainerFactory::buildContainer()` → `b13/container` nutzt `ContentObjectRenderer::getRecords()` die `$request->getAttribute('frontend.page.information')->getContentFromPid()` aufruft
- Container-Child-Rendering via `skipRenderingChildContent=1` + Stub-DataProcessor (`test.set-rendered-content`) um Full-TSFE-Bootstrap zu vermeiden

### Nächste Schritte (optional)

1. **File/Feldtyp File Test** - braucht FAL-Fixtures (sys_file, sys_file_reference)
2. **GitHub Actions Workflow validieren** - `.github/workflows/tests.yaml` auf CI-Erfolg prüfen

## Dateien die erstellt wurden

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

.github/
└── workflows/
    └── tests.yaml

Tests/
├── Unit/
│   ├── Event/
│   │   └── ModifyArrayRecursiveToArrayEventTest.php
│   └── DataProcessing/
│       └── ToArray/
│           ├── TypolinkParameterToArrayTest.php
│           └── ArrayRecursiveToArrayTest.php
└── Functional/
    └── DataProcessing/
        ├── ContentBlocksJsonDataProcessorTest.php
        ├── ContainerJsonDataProcessorTest.php
        └── Fixtures/
            └── DataSet/
                └── container_content_element.csv

Tests/Fixtures/Extensions/test_nb_headless_content_blocks/
├── composer.json
├── Configuration/
│   ├── Services.yaml
│   └── TCA/Overrides/tt_content.php
├── Classes/
│   └── SetRenderedContentProcessor.php
└── ContentElements/
    ├── simple/config.yaml + EditorInterface.yaml
    └── headless/config.yaml + EditorInterface.yaml + headless.php

AGENTS.md
TESTING-FRAMEWORK.md
TESTING-FRAMEWORK-PROGRESS.md
```

## Geänderte Dateien

- `composer.json` - require-dev, autoload-dev, scripts hinzugefügt

## Offene Punkte

- [x] composer install ausführen
- [x] Tests ausführen und validieren
- [x] PHPStan ausführen (keine Fehler, keine Baseline nötig)
- [x] CGL ausführen und Fixes anwenden
- [x] Weitere Unit Tests für Utility-Klassen
- [x] Functional Tests für DataProcessor
- [x] README mit Testing-Hinweisen ergänzen
- [x] Functional Test für ContainerJsonDataProcessor (b13/container)
- [ ] Functional Test für File-Feldtyp (FAL)
- [ ] GitHub Actions Workflow auf CI validieren
