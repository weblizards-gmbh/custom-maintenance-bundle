# Story 1.1: Bundle-Basis fuer Pimcore 10.6.9 konsolidieren

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Integrator,
I want das Bundle auf Pimcore `10.6.9` lauffaehig und sauber verdrahtet einsetzen koennen,
so that die erste oeffentliche Version auf einer stabilen technischen Basis startet.

## Acceptance Criteria

1. **Given** eine Pimcore-`10.6.9`-Installation mit eingebundenem Bundle  
   **When** das Bundle geladen und initialisiert wird  
   **Then** sind Bundle-Klasse, Service-Wiring, Routing und Assets kompatibel mit Pimcore `10.6.9`  
   **And** offensichtliche technische Altlasten, die die Grundfunktionsfaehigkeit auf Pimcore `10.6.9` verhindern, sind bereinigt.
2. **Given** bestehende oeffentliche Integrationspunkte des Bundles  
   **When** die technische Basis modernisiert wird  
   **Then** bleiben CLI, Twig-Funktionen und Laufzeit-API soweit moeglich stabil  
   **And** es wird keine neue externe API oder neue Persistenzachse eingefuehrt.

## Tasks / Subtasks

- [x] Bundle-Foundation gegen Pimcore-10.6.9-Bundle-Konventionen und den aktuellen Repo-Bestand pruefen und konsolidieren (AC: 1, 2)
  - [x] Bundle-Klasse, Installer-Anbindung und Admin-Asset-Registrierung gegen den Ist-Zustand absichern, statt neue Bundle-Architektur einzufuehren.
  - [x] `DependencyInjection/Configuration.php`, `DependencyInjection/WeblizardsCustomMaintenanceExtension.php`, `Resources/config/services.yml` und `Resources/config/pimcore/routing.yml` auf offensichtliche Pimcore-10-Kompatibilitaets- oder Namensfehler pruefen und korrigieren.
  - [x] Bestehende Admin-Classic-Integration unter `Resources/public/js/pimcore/` und Asset-Pfade unter `/bundles/weblizardscustommaintenance/...` erhalten.
- [x] Offensichtliche technische Altlasten entfernen, die die Basiskompatibilitaet oder die Glaubwuerdigkeit eines oeffentlichen Releases direkt untergraben (AC: 1, 2)
  - [x] Debug-Artefakte in Produktionspfaden entfernen oder korrigieren, insbesondere in PHP- und Admin-JS-Dateien.
  - [x] Offensichtliche Doku-/CLI-Inkonsistenzen nur dort korrigieren, wo sie direkt mit der Basis-Konsolidierung zusammenhaengen.
  - [x] Keine semantische Neuerfindung der Fachlogik unter dem Label "Cleanup" mitziehen.
- [x] Oeffentliche Integrationspunkte stabil halten und Regressionen vermeiden (AC: 2)
  - [x] Command-Name `weblizards:custommaintenance:control`, Twig-Funktionen, Admin-Route-Prefix und Runtime-API nicht unbegruendet aendern.
  - [x] Bestehende Legacy-PHP-Konfigurationsnutzung fuer diese Story nicht auf Settings Store umbauen; das gehoert erst in Story `1.3`.
  - [x] Keine neue REST-, GraphQL- oder sonstige externe API einfuehren.
- [x] Verifikationsbasis fuer die Story festlegen und dokumentieren (AC: 1, 2)
  - [x] Reale Verifikationsschritte am aktuellen Repo-Zustand ausrichten; Dokuannahmen nicht blind uebernehmen.
  - [x] Wenn Tests hinzugefuegt werden, unter `tests/` und passend zu PHPUnit `^10.5`; wenn keine Tests sinnvoll moeglich sind, die Luecke klar benennen.

### Review Findings

- [x] [Review][Patch] Story-Claim zu Service-Wiring und Routing ist nur teilweise verifiziert [`tests/Unit/DependencyInjection/ConfigurationTest.php`]
- [x] [Review][Patch] Oeffentliche CLI-Oberflaeche ist nur fuer `list-tokens` abgesichert [`tests/Unit/Command/ControlCommandTest.php`]

## Dev Notes

### Story Intent

Diese Story ist eine Basiskonsolidierung, kein Vollumbau. Ziel ist, das bestehende Bundle fuer Pimcore `10.6.9` technisch tragfaehig zu machen und offensichtliche Blocker zu beseitigen, ohne bereits die grossen Architekturumbauten aus den Folge-Stories vorwegzunehmen.

### Epic Context

- Epic 1 stellt zuerst die belastbare Pimcore-10-Basis und den Migrationspfad bereit.
- Story `1.2` fuehrt das kanonische Domain-Modell ein.
- Story `1.3` bringt Settings-Store-Persistenz mit Legacy-Read-Fallback.
- Story `1.1` darf deshalb die Basis vorbereiten, aber nicht schon heimlich Domain-, Persistenz- oder API-Achsen neu erfinden.

### Current State Analysis

- `src/CustomMaintenanceBundle/WeblizardsCustomMaintenanceBundle.php`
  - Bereits auf `AbstractPimcoreBundle` und `PimcoreBundleAdminClassicInterface` aufgebaut.
  - Registriert Admin-JS/CSS ueber feste Pfade und holt den Installer aus dem Container.
  - Muss als bestehender Bundle-Einstiegspunkt erhalten bleiben; hier keine neue Bundle-Architektur aufmachen.
- `src/CustomMaintenanceBundle/DependencyInjection/Configuration.php`
  - Enthält nur ein Platzhalter-`TreeBuilder` mit Root `weblizards_commerce_xml`.
  - Das ist fachlich inkonsistent zum Bundle und ein plausibler Basiskandidat fuer Pimcore-10-Bereinigung.
- `src/CustomMaintenanceBundle/Resources/config/pimcore/routing.yml`
  - Nutzt ebenfalls den inkonsistenten Key `weblizards_commerce_xml`.
  - Routing laeuft ueber Controller-Import mit Prefix `/admin/weblizards_custom_maintenance`; dieser Integrationspunkt soll stabil bleiben.
- `src/CustomMaintenanceBundle/Config.php`
  - Liest und schreibt direkt `PIMCORE_PRIVATE_VAR . '/config/custommaintenance.php'`.
  - Diese Legacy-Persistenz bleibt fuer Story `1.1` bewusst bestehen; Settings-Store-Migration startet erst in Story `1.3`.
- `src/CustomMaintenanceBundle/Service/StatusService.php`
  - Ist derzeit Mischklasse fuer Statuslogik, Hinweis-Rendering und Frontend-CSS-Injektion.
  - Enthält einen harten Debug-Rest `dump($cm);` in `isActive()`.
  - Nutzt Exceptions fuer unbekannte Tokens; Logging-Verhalten fuer unbekannte Tokens ist noch nicht in Story `1.1` zu loesen, aber Debug-Ausgaben muessen weg.
- `src/CustomMaintenanceBundle/Twig/Extensions.php`
  - Exponiert die oeffentlichen Twig-Funktionen `indicateCustomMaintenance`, `indicateUpcomingMaintenance`, `indicateCurrentMaintenance`, `isMaintenanceActive`.
  - Diese Namen sind oeffentliche Integrationsflaechen und muessen stabil bleiben.
- `src/CustomMaintenanceBundle/Command/ControlCommand.php`
  - Tatsächlicher Command-Name ist `weblizards:custommaintenance:control`.
  - README-Beispiele nennen noch alte CLI-Namen; Code-Signatur ist hier die Quelle der Wahrheit.
- `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
  - Nutzt Annotation-Routing und schreibt Rohdaten direkt in `Config`.
  - Fuer Story `1.1` nur soweit anfassen, wie es fuer Pimcore-10-Basis oder offensichtliche Kompatibilitaetsfehler noetig ist.
- `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
  - Legacy-ExtJS-Adminpanel, aktuell mit `console.log(token)` und `console.log(config)` im Produktionspfad.
  - Diese Debug-Reste sind direkte Basiskandidaten fuer Bereinigung.
- `README.md`
  - Falscher Install-Befehl `composer require weblizards/tag-management-bundle`.
  - Alte PHP-Template-/CLI-Annahmen; nur direkt storyrelevante Falschaussagen korrigieren.
- `docs/development_testing.md`
  - Beschreibt fremde Tag-/Snippet-Funktionalitaet und Tests, die im Repo nicht existieren.
  - Nur korrigieren, wenn die Story daran direkt haengt; sonst als bekannte Bestandsabweichung benennen.

### Must Preserve

- Bundle bleibt ein Pimcore-Bundle auf Basis der vorhandenen Struktur unter `src/CustomMaintenanceBundle/`.
- Oeffentliche Integrationspunkte bleiben soweit moeglich stabil:
  - Command `weblizards:custommaintenance:control`
  - Admin-Routen unter `/admin/weblizards_custom_maintenance/...`
  - Twig-Funktionen `indicateCustomMaintenance`, `indicateUpcomingMaintenance`, `indicateCurrentMaintenance`, `isMaintenanceActive`
  - Asset-Pfade unter `/bundles/weblizardscustommaintenance/...`
- Legacy-Admin-UI bleibt ExtJS-/Classic-basiert.
- Datums- und Zeitformate bleiben `d.m.Y` und `H:i`.
- Konfigurationsvertrag der Legacy-PHP-Datei bleibt in dieser Story unangetastet.

### Must Change in Story 1.1

- Offensichtliche Pimcore-10-Basisfehler und Namensinkonsistenzen in Bundle-Wiring, Routing oder Bundle-Metadaten beseitigen.
- Debug-Artefakte aus Produktionscode entfernen.
- Repo-Realitaet vor Doku-Altlasten stellen.
- Basiskompatibilitaet herstellen, ohne bereits den Settings-Store-Umbau oder das Domain-Modell vorwegzunehmen.

### Explicitly Out of Scope

- Kein Settings-Store-Write-Path.
- Kein kanonisches Domain-Modell.
- Keine neue externe API.
- Keine funktionale Erweiterung fuer Maintenance-Arten, Template-Konfiguration oder Logging unbekannter Tokens.
- Keine tiefere Neuordnung von `StatusService`/`Controller`/`Twig`, wenn sie nicht fuer die Basisfaehigkeit zwingend ist.

### Technical Requirements

- PHP `>=8.1`, Pimcore `^10.0`, Zielplattform konkret `10.6.9`.
- `declare(strict_types=1);` in PHP-Dateien beibehalten.
- Services weiter ueber `src/CustomMaintenanceBundle/Resources/config/services.yml`.
- Routing weiter ueber `src/CustomMaintenanceBundle/Resources/config/pimcore/routing.yml`.
- Admin-Classic-Assets ueber Bundle-Klasse registrieren, nicht ueber neue Build- oder Runtime-Pfade.
- Keine neue bundle-spezifische Datenbanktabelle.
- Keine Umstellung der Persistenz in dieser Story.

### Architecture Compliance

- Brownfield-first: vorhandenes Bundle modernisieren, keinen Starter oder Parallel-Stack einfuehren.
- Controller, Twig und Command bleiben Adapter; keine neue Fachlogik in diese Schichten schieben.
- Dieselben Konfigurations- und Integrationspfade weiterverwenden; keine Nebenpfade aufbauen.
- Folge-Stories respektieren:
  - Story `1.2` = Domain-Modell
  - Story `1.3` = Persistence Adapter / Settings Store
- Wenn waehrend Story `1.1` konkrete Pimcore-10-Deprecation-Hinweise mit klarer Handlungsanweisung auftauchen, duerfen sie opportunistisch mitbehandelt werden; unscharfe Zukunftshaertung nicht.

### Library / Framework Requirements

- Pimcore-Bundle-Konvention fuer diese Story:
  - `AbstractPimcoreBundle` als Bundle-Basis beibehalten.
  - Admin-Classic-Assets ueber `PimcoreBundleAdminClassicInterface` + Trait/Pfade weiter anbinden.
  - Bundle-interne Config-/Routing-Definitionen weiter unter `Resources/config/pimcore/` halten.
- Annotation-basierte Admin-Controller-Routen nicht ohne Not auf einen anderen Routing-Stil umziehen.
- Twig bleibt die bestehende Template-Technologie; PHP-Templates werden hier aber noch nicht umgebaut, das kommt erst in Epic 4.

### File Structure Requirements

- Wahrscheinlich zu pruefende oder zu aendernde Dateien in Story `1.1`:
  - `composer.json`
  - `src/CustomMaintenanceBundle/WeblizardsCustomMaintenanceBundle.php`
  - `src/CustomMaintenanceBundle/DependencyInjection/WeblizardsCustomMaintenanceExtension.php`
  - `src/CustomMaintenanceBundle/DependencyInjection/Configuration.php`
  - `src/CustomMaintenanceBundle/Resources/config/services.yml`
  - `src/CustomMaintenanceBundle/Resources/config/pimcore/routing.yml`
  - `src/CustomMaintenanceBundle/Service/StatusService.php`
  - `src/CustomMaintenanceBundle/Twig/Extensions.php`
  - `src/CustomMaintenanceBundle/Command/ControlCommand.php`
  - `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
  - `src/CustomMaintenanceBundle/Resources/public/js/pimcore/startup.js`
  - `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
  - `README.md`
  - `docs/development_testing.md`
- Vor jedem Edit UPDATE-Dateien komplett lesen und bestehendes Verhalten gezielt erhalten.
- Neue Dateien nur dann einfuehren, wenn sie fuer Basiskompatibilitaet oder Verifikation wirklich noetig sind.

### Testing Requirements

- Reales Repo hat aktuell kein `tests/`-Verzeichnis, obwohl `composer.json` PHPUnit `^10.5` und `composer test` vorsieht.
- Wenn in Story `1.1` Tests eingefuehrt werden:
  - unter `tests/`
  - passend zum Namespace `Weblizards\\CustomMaintenanceBundle\\Test\\`
  - bevorzugt kleine Smoke-/Integrationstests fuer Bundle-Wiring oder isolierte Unit-Tests fuer Basisklassen
- Wenn keine automatisierten Tests sinnvoll oder wirtschaftlich sind, mindestens dokumentieren:
  - was lokal geprueft wurde
  - welche Basispfade manuell verifiziert wurden
  - welche Testluecken bleiben

### Previous Story Intelligence

- Nicht vorhanden. Dies ist die erste Story im Sprintplan.

### Git Intelligence Summary

- Junger Git-Verlauf mit nur zwei sichtbaren Commits:
  - `5fd66ca` Erstes Standalone-Bundle-Commit
  - `b1dae2e` Nachtraegliche Kompatibilitaetsnotiz
- Daraus folgt:
  - Es gibt keine belastbare Serie moderner Refactorings, auf die man sich lehnen kann.
  - Der aktuelle Repo-Zustand selbst ist die wichtigste Quelle fuer Muster und Stolperstellen.

### Latest Technical Information

- Fuer die Pimcore-10-Basis ist der relevante Standard weiterhin das klassische Pimcore-Bundle-Modell mit `AbstractPimcoreBundle`, Classic-Admin-Assets und auto-geladenen Bundle-Konfigurationsdateien unter `Resources/config/pimcore/`.
- Fuer Story `1.1` ist daraus kein Redesign abzuleiten, sondern die Pflicht, die bestehende Bundle-Integration an genau diesem Modell sauber auszurichten.

### Project Structure Notes

- Architektur-Zielstruktur fuehrt `Domain/` und `Infrastructure/Persistence/` spaeter explizit ein.
- Story `1.1` darf diese Zielstruktur vorbereiten, aber nicht halb implementieren und dann alte sowie neue Architektur gleichzeitig offenlassen.
- Das groesste Baseline-Risiko ist momentan nicht fehlende neue Architektur, sondern inkonsistenter Bestand:
  - falsche oder veraltete Namen
  - Debug-Reste
  - widerspruechliche Doku
  - alte Beispiele, die nicht mehr zur Codebasis passen

### References

- Story-/Epic-Quelle:
  - `_bmad-output/planning-artifacts/epics.md`
- Produktkontext:
  - `_bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md`
- Architektur und Guardrails:
  - `_bmad-output/planning-artifacts/architecture.md`
  - `_bmad-output/project-context.md`
- Relevanter aktueller Code:
  - `composer.json`
  - `src/CustomMaintenanceBundle/WeblizardsCustomMaintenanceBundle.php`
  - `src/CustomMaintenanceBundle/DependencyInjection/Configuration.php`
  - `src/CustomMaintenanceBundle/DependencyInjection/WeblizardsCustomMaintenanceExtension.php`
  - `src/CustomMaintenanceBundle/Resources/config/services.yml`
  - `src/CustomMaintenanceBundle/Resources/config/pimcore/routing.yml`
  - `src/CustomMaintenanceBundle/Config.php`
  - `src/CustomMaintenanceBundle/Service/StatusService.php`
  - `src/CustomMaintenanceBundle/Twig/Extensions.php`
  - `src/CustomMaintenanceBundle/Command/ControlCommand.php`
  - `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
  - `src/CustomMaintenanceBundle/Resources/public/js/pimcore/startup.js`
  - `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
  - `src/CustomMaintenanceBundle/Resources/install/custommaintenance.php`
  - `README.md`
  - `docs/development_testing.md`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `php -l src/CustomMaintenanceBundle/DependencyInjection/Configuration.php`
- `php -l src/CustomMaintenanceBundle/Service/StatusService.php`
- `php -l tests/Unit/WeblizardsCustomMaintenanceBundleTest.php`
- `php -l tests/Unit/DependencyInjection/ConfigurationTest.php`
- `php -l tests/Unit/Command/ControlCommandTest.php`
- `vendor/bin/phpunit`
- `composer update --lock --no-install`
- `composer validate --no-check-publish`
- `composer test`
- `php -l tests/Unit/DependencyInjection/ConfigurationTest.php`
- `php -l tests/Unit/Command/ControlCommandTest.php`
- `composer test`

### Completion Notes List

- Bundle-Basis auf Pimcore-10-Konventionen geschaerft: bundle-spezifischer Config-Root und Routing-Key auf `weblizards_custom_maintenance` konsolidiert.
- Composer-Metadaten um explizite Pimcore-Bundle-Registrierung ergaenzt und `composer.lock` auf die geaenderte `composer.json` synchronisiert.
- Produktions-Debug-Reste in `StatusService` und `Resources/public/js/pimcore/AdminPanel.js` entfernt.
- README und `docs/development_testing.md` auf die tatsaechlichen Bundle-, CLI- und Testpfade korrigiert.
- PHPUnit-Basis mit `phpunit.xml.dist` und drei Smoke-Testdateien fuer Bundle-Metadaten, DI-Konfigurationsroot und stabilen CLI-Einstieg hinzugefuegt.
- Verifiziert mit `composer validate --no-check-publish` und `composer test` erfolgreich.
- Während `composer update --lock --no-install` gemeldete abandoned Packages und Security Advisories stammen aus dem bestehenden Dependency-Set und wurden in Story `1.1` nicht adressiert.
- Code-Review-Follow-up eingearbeitet: Extension-Load und Routing-Key werden jetzt explizit getestet.
- Code-Review-Follow-up eingearbeitet: CLI-Flaeche fuer `show-status`, `activate`, `deactivate`, `--porcelain` und `--override-fixed` ist jetzt mitgetestet.

### File List

- composer.json
- composer.lock
- README.md
- docs/development_testing.md
- phpunit.xml.dist
- src/CustomMaintenanceBundle/DependencyInjection/Configuration.php
- src/CustomMaintenanceBundle/Resources/config/pimcore/routing.yml
- src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js
- src/CustomMaintenanceBundle/Service/StatusService.php
- tests/Unit/Command/ControlCommandTest.php
- tests/Unit/DependencyInjection/ConfigurationTest.php
- tests/Unit/WeblizardsCustomMaintenanceBundleTest.php
- _bmad-output/implementation-artifacts/1-1-bundle-basis-fuer-pimcore-10-6-9-konsolidieren.md

### Change Log

- 2026-05-27: Story 1.1 implementiert. Bundle-Wiring konsolidiert, Debug-Reste entfernt, Story-relevante Doku korrigiert und PHPUnit-Smoke-Tests eingefuehrt.
- 2026-05-28: Code-Review-Findings abgearbeitet. Verifikation fuer Extension-/Routing-Basis und bestehende CLI-Oberflaeche erweitert.
