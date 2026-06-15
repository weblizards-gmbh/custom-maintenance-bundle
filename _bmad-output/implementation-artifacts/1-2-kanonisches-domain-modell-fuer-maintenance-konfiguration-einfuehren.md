# Story 1.2: Kanonisches Domain-Modell fuer Maintenance-Konfiguration einfuehren

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Entwickler,
I want ein kanonisches Domain-Modell fuer Maintenance-Konfigurationen nutzen,
so that Konfigurationslogik nicht mehr verteilt ueber Roharrays in mehreren Schichten lebt.

## Acceptance Criteria

1. **Given** die bestehende Bundle-Konfiguration  
   **When** Konfigurationsdaten im Bundle verarbeitet werden  
   **Then** werden sie in ein kanonisches Domain-Modell normalisiert  
   **And** dieses Modell deckt mindestens Maintenance-Eintraege, Token, Zeitfenster und Hinweis-Konfiguration ab.
2. **Given** Controller, Services und Twig-nahe Logik  
   **When** sie mit Bundle-Konfiguration arbeiten  
   **Then** greifen sie nicht direkt auf unstrukturierte Persistenzrohdaten zu  
   **And** Persistenzdetails bleiben ausserhalb des Domain-Modells gekapselt.

## Tasks / Subtasks

- [x] Kanonisches Domain-Modell fuer die bestehende Maintenance-Konfiguration einfuehren (AC: 1)
  - [x] Value-/Model-Klassen fuer mindestens Token, Zeitfenster, Notice-Konfiguration, Maintenance-Eintrag und Konfigurationswurzel unter `src/CustomMaintenanceBundle/Domain/Model/` anlegen.
  - [x] Modell so schneiden, dass nativer `pimcore`-Eintrag und Custom-Eintraege ueber dieselbe Kanonisierung beschrieben werden koennen, ohne den faktischen Konfigurationsvertrag zu verlieren.
  - [x] Datums-/Zeitformate `d.m.Y` und `H:i` sowie die bestehenden Konfigurationsschluessel fachlich erhalten.
- [x] Normalisierungsschicht zwischen Legacy-Persistenz und Laufzeitlogik einfuehren (AC: 1, 2)
  - [x] Eine zentrale Klasse einfuehren, die aus `Config`-Rohdaten ein `MaintenanceConfigSet` baut.
  - [x] Persistenzrohdaten weiterhin nur an einer klaren Stelle lesen; keine neue Persistenzachse bauen.
  - [x] Ungueltige oder unvollstaendige Konfigurationsdaten frueh und eng behandeln, statt sie quer durch Services zu verteilen.
- [x] Bestehende Laufzeitlogik auf das Domain-Modell umstellen (AC: 2)
  - [x] `StatusService` nicht mehr direkt gegen rohe Array-Strukturen arbeiten lassen.
  - [x] Twig-nahe Aufrufe ueber `Extensions` weiterhin stabil halten, aber intern auf das kanonische Modell fuehren.
  - [x] Wenn `AdminpanelController` Konfigurationsdaten fuer das UI laedt, die fachliche Sicht aus dem Domain-Modell oder einer klar gekapselten Anwendungslogik beziehen statt Rohpersistenz in weitere Schichten durchzureichen.
- [x] PHPUnit-Absicherung fuer Modell und Normalisierung ergaenzen (AC: 1, 2)
  - [x] Unit-Tests fuer die Domain-Modell-Klassen und die Normalisierung aus Legacy-Rohdaten anlegen.
  - [x] Bestehende Story-1.1-Tests grün halten und bei Bedarf Laufzeit-Tests auf das neue Modell anpassen.

### Review Findings

- [x] [Review][Patch] `isHandsOff()` crasht fuer nicht-fixe Maintenances wegen falschem Methodentyp [src/CustomMaintenanceBundle/Service/StatusService.php:133]
- [x] [Review][Patch] `saveFromAdminPayload()` verwirft unbekannte Legacy-Konfigurationsfelder beim Speichern [src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php:100]

## Dev Notes

### Story Intent

Story `1.2` ist der erste echte Architekturzug in Richtung Zielbild aus `architecture.md`: weg von Arrays als implizitem Fachmodell, hin zu einem expliziten Domain-Modell. Das ist noch keine Settings-Store-Migration und noch keine komplette Service-Neuordnung, aber es muss die Laufzeitlogik so weit stabilisieren, dass spaetere Persistenz- und UI-Stories nicht mehr an roh gelesenen Config-Arrays haengen.

### Epic Context

- Story `1.1` hat die Basis fuer Pimcore `10.6.9` konsolidiert und direkte Altlasten entfernt.
- Story `1.2` zieht die interne Modellgrenze ein.
- Story `1.3` setzt darauf auf und fuehrt den zentralen Persistence Adapter mit Settings-Store-Fallback ein.
- Story `1.2` darf deshalb die jetzige `Config`-Klasse noch als Legacy-Quelle nutzen, aber nicht als dauerhaftes Fachmodell zementieren.

### Previous Story Intelligence

- `1.1` hat bereits die Bundle-Basis, Routing-Namen und Testbasis stabilisiert. Die wichtigsten oeffentlichen Integrationspunkte bleiben:
  - Command `weblizards:custommaintenance:control`
  - Admin-Routen unter `/admin/weblizards_custom_maintenance/...`
  - Twig-Funktionen `indicateCustomMaintenance`, `indicateUpcomingMaintenance`, `indicateCurrentMaintenance`, `isMaintenanceActive`
  - Asset-Pfade unter `/bundles/weblizardscustommaintenance/...`
- Es gibt jetzt eine funktionierende PHPUnit-Basis unter `tests/`.
- Review-Learnings aus `1.1`:
  - Basisaenderungen muessen explizit verifiziert werden, nicht nur indirekt.
  - Oeffentliche CLI-Oberflaeche sollte bei internen Umbauten weiter mit abgesichert werden.

### Current State Analysis

- `src/CustomMaintenanceBundle/Config.php`
  - Liest und schreibt Legacy-Rohdaten aus `PIMCORE_PRIVATE_VAR . '/config/custommaintenance.php'`.
  - Liefert ueber `getData()` weiterhin rohe Arrays und ist damit aktuell noch die implizite Fachmodellquelle.
- `src/CustomMaintenanceBundle/Service/StatusService.php`
  - Ist derzeit die zentrale Problemzone fuer Story `1.2`.
  - Liest an vielen Stellen direkt aus Array-Strukturen, etwa `custom`, `pimcore`, `planned`, `show_info`, `document`, `frontend`.
  - Mischt Statuslogik, Notice-Ermittlung und Datenzugriff.
- `src/CustomMaintenanceBundle/Twig/Extensions.php`
  - Ist duenn genug und soll oeffentlich stabil bleiben.
  - Muss nach Story `1.2` weiterhin nur gegen Service-Schnittstellen laufen, nicht gegen Persistenzdetails.
- `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
  - Liest beim Laden und Schreiben noch direkt gegen `Config`.
  - Fuer Story `1.2` ist relevant, dass fachliche Sicht und UI-nahe Daten nicht weiter unkontrolliert auf Rohpersistenz basieren.
  - Das komplette Schreibmodell muss hier noch nicht final neu gebaut werden, aber die Fachsicht fuer geladene Konfiguration sollte gekapselt werden.
- `src/CustomMaintenanceBundle/Resources/install/custommaintenance.php`
  - Ist die normative Legacy-Beispielstruktur, an der sich das Domain-Modell orientieren muss.

### Domain Model Scope

Das kanonische Modell muss fuer diese Story mindestens abbilden:

- `MaintenanceConfigSet`
  - Wurzelobjekt fuer die gesamte geladene Konfiguration.
- `MaintenanceEntry`
  - Eintrag fuer nativen `pimcore`-Fall oder einen Custom-Token.
- `MaintenanceToken`
  - Technischer Schluessel; `pimcore` bleibt ein fachlicher Sonderfall.
- `MaintenanceSchedule`
  - `from` / `to` fuer geplante Zeitfenster.
- `MaintenanceNoticeConfig`
  - Anzeige-/Hinweis-relevante Felder wie `show_info`, `show_info_from`, `document`.

Wenn fuer die Umsetzung ein kleines zusaetzliches Hilfsmodell noetig ist, soll es nahe an dieser Struktur bleiben und keine zweite Parallelwelt eroeffnen.

### Must Preserve

- Legacy-PHP-Datei bleibt in Story `1.2` die einzige reale Persistenzquelle.
- Bestehende Konfigurationsschluessel und Datums-/Zeitformate bleiben kompatibel.
- Oeffentliche Integrationspunkte aus Story `1.1` bleiben stabil.
- `StatusService` darf intern umgebaut werden, seine oeffentliche Rolle fuer CLI/Twig/API muss aber erhalten bleiben.

### Must Change in Story 1.2

- Konfigurationsrohdaten duerfen nicht mehr das implizite Fachmodell sein.
- Mindestens die Laufzeit- und Twig-nahe Logik muss gegen kanonisierte Domain-Objekte arbeiten.
- Die Normalisierung aus Legacy-Rohdaten in Domain-Objekte muss zentral sein.

### Explicitly Out of Scope

- Kein Settings-Store-Lesen oder -Schreiben.
- Kein vollstaendiger Persistence Adapter.
- Kein finales Admin-UI-Refactoring fuer Create/Delete/Edit von Maintenance-Arten.
- Keine neue externe API.
- Keine Aenderung am fachlichen Aktivierungsmodell ueber die benoetigte interne Modellierung hinaus.

### Architecture Compliance

- Architektur-Ziel laut `architecture.md`:
  - `Domain/Model/`
  - spaeter `Domain/Validation/`
  - Service-Grenzen bleiben bestehen
  - Persistenzdetails ausserhalb des Domain-Modells kapseln
- Story `1.2` soll diese Zielstruktur beginnen, ohne `Infrastructure/Persistence/` schon halb einzufuehren.
- Keine Roharray-Nutzung ausserhalb der zentralen Normalisierungsschicht.
- Keine duplizierte Token-, Zeitfenster- oder Notice-Logik in Controller/Twig/Command.

### File Structure Requirements

- Wahrscheinlich zu aendernde Dateien:
  - `src/CustomMaintenanceBundle/Config.php`
  - `src/CustomMaintenanceBundle/Service/StatusService.php`
  - `src/CustomMaintenanceBundle/Twig/Extensions.php`
  - `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
- Wahrscheinlich neue Dateien:
  - `src/CustomMaintenanceBundle/Domain/Model/*`
  - eine zentrale Normalisierungsklasse, wahrscheinlich unter `src/CustomMaintenanceBundle/Service/` oder `Domain/`
  - Tests unter `tests/Unit/Domain/` und/oder `tests/Unit/Service/`
- Bestehende `Resources/`, Command- und Twig-Dateien nur anfassen, wenn sie direkt vom internen Modellumbau betroffen sind.

### Testing Requirements

- PHPUnit `^10.5` ist vorhanden und laeuft.
- Erwartet fuer diese Story:
  - Unit-Tests fuer Domain-Modelle
  - Unit-Tests fuer die Normalisierung von Legacy-Config-Arrays
  - Falls `StatusService` intern umgebaut wird, gezielte Tests fuer bestehendes Verhalten
- Story `1.1`-Tests muessen weiterhin grün bleiben.

### Risks To Watch

- Domain-Modell zu frueh mit Persistenzannahmen verunreinigen.
- `AdminpanelController` komplett umzubauen, obwohl Story `1.2` primaer eine Modellgrenze einziehen soll.
- `StatusService` nur teilweise umzustellen und dann Roharrays und Domain-Objekte gleichzeitig offen zu lassen.
- `pimcore`-Sonderfall im Modell zu verlieren.

### References

- Story-/Epic-Quelle:
  - `_bmad-output/planning-artifacts/epics.md`
- Architektur:
  - `_bmad-output/planning-artifacts/architecture.md`
- Produktkontext:
  - `_bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md`
- Projektregeln:
  - `_bmad-output/project-context.md`
- Vorherige Story:
  - `_bmad-output/implementation-artifacts/1-1-bundle-basis-fuer-pimcore-10-6-9-konsolidieren.md`
- Aktueller Code:
  - `src/CustomMaintenanceBundle/Config.php`
  - `src/CustomMaintenanceBundle/Service/StatusService.php`
  - `src/CustomMaintenanceBundle/Twig/Extensions.php`
  - `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
  - `src/CustomMaintenanceBundle/Resources/install/custommaintenance.php`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `php -l src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `php -l src/CustomMaintenanceBundle/Service/StatusService.php`
- `php -l src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
- `php -l tests/Unit/Service/MaintenanceConfigManagerTest.php`
- `php -l tests/Unit/Service/StatusServiceTest.php`
- `php -l tests/Unit/Domain/Model/MaintenanceEntryTest.php`
- `composer test`
- `php -l src/CustomMaintenanceBundle/Service/StatusService.php`
- `php -l src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `php -l tests/Unit/Service/StatusServiceTest.php`
- `php -l tests/Unit/Service/MaintenanceConfigManagerTest.php`
- `composer test`

### Completion Notes List

- Story-Datei fuer `1.2` aus Epic-, Architektur- und Projektkontext erstellt.
- Learnings und Review-Follow-ups aus Story `1.1` als Guardrails uebernommen.
- Kanonisches Domain-Modell unter `src/CustomMaintenanceBundle/Domain/Model/` eingefuehrt fuer Token, DateTime, Schedule, Notice, FrontendConfig, Entry und ConfigSet.
- `MaintenanceConfigManager` als zentrale Normalisierungsschicht zwischen `Config`-Legacy-Rohdaten und Laufzeit-/Admin-Logik eingefuehrt.
- `StatusService` auf das Domain-Modell umgestellt; rohe Persistenzarrays werden dort nicht mehr direkt verarbeitet.
- `AdminpanelController` laedt und speichert Konfigurationsdaten jetzt ueber den zentralen Manager statt direkt gegen `Config`.
- PHPUnit-Absicherung fuer Domain-Roundtrip, Legacy-Normalisierung und Domain-basierte Statusauswertung ergaenzt.
- Vollstaendige Suite erfolgreich: `13 tests, 62 assertions`.
- Review-Follow-up 1 behoben: `isHandsOff()` wertet Nicht-`fixed`-Maintenances wieder ueber den kanonischen Timeslot-Pfad aus.
- Review-Follow-up 2 behoben: `saveFromAdminPayload()` und `setCustomStatus()` erhalten unbekannte Legacy-Konfigurationsfelder beim Speichern.
- Zusaetzliche Regressionstests fuer den Nicht-`fixed`-Hands-off-Pfad und fuer das Erhalten unbekannter Legacy-Felder ergaenzt.
- Vollstaendige Suite nach den Review-Fixes erfolgreich: `14 tests, 70 assertions`.

### File List

- `_bmad-output/implementation-artifacts/1-2-kanonisches-domain-modell-fuer-maintenance-konfiguration-einfuehren.md`
- src/CustomMaintenanceBundle/Controller/AdminpanelController.php
- src/CustomMaintenanceBundle/Domain/Model/FrontendConfig.php
- src/CustomMaintenanceBundle/Domain/Model/MaintenanceConfigSet.php
- src/CustomMaintenanceBundle/Domain/Model/MaintenanceDateTime.php
- src/CustomMaintenanceBundle/Domain/Model/MaintenanceEntry.php
- src/CustomMaintenanceBundle/Domain/Model/MaintenanceNoticeConfig.php
- src/CustomMaintenanceBundle/Domain/Model/MaintenanceSchedule.php
- src/CustomMaintenanceBundle/Domain/Model/MaintenanceToken.php
- src/CustomMaintenanceBundle/Resources/config/services.yml
- src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php
- src/CustomMaintenanceBundle/Service/StatusService.php
- tests/Unit/Domain/Model/MaintenanceEntryTest.php
- tests/Unit/Service/MaintenanceConfigManagerTest.php
- tests/Unit/Service/StatusServiceTest.php

### Change Log

- 2026-05-28: Story 1.2 implementiert. Kanonisches Domain-Modell, zentrale Legacy-Normalisierung und Domain-basierte Laufzeitlogik eingefuehrt.
- 2026-06-08: Review-Findings aus Code Review behoben, Legacy-Feld-Erhalt abgesichert und Story abgeschlossen.
