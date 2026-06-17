# Story 3.3: CLI-Steuerung auf die gemeinsame Status- und Konfigurationslogik ausrichten

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Operator,
I want Maintenance-Arten weiterhin ueber CLI steuern und abfragen koennen,
so that betriebliche Prozesse und Watchdogs denselben fachlichen Kern nutzen wie UI und Laufzeitlogik.

## Acceptance Criteria

1. **Given** das Bundle in einer Pimcore-`10.6.9`-Installation  
   **When** ich Tokens ueber CLI aufliste  
   **Then** werden die verfuegbaren Maintenance-Typen aus der gemeinsamen Konfigurationsquelle ermittelt.
2. **Given** ein vorhandener Token  
   **When** ich seinen Status ueber CLI abfrage oder ihn aktiviere bzw. deaktiviere  
   **Then** laufen diese Vorgaenge ueber dieselbe fachliche Status- und Konfigurationslogik wie im restlichen Bundle  
   **And** es wird kein separater CLI-spezifischer Logikpfad eingefuehrt.

## Tasks / Subtasks

- [x] Bestehenden CLI-Pfad gegen die Story-ACs pruefen (AC: 1, 2)
  - [x] `ControlCommand` auf direkte Delegation an `StatusService` pruefen.
  - [x] Nur dann Produktivcode anpassen, wenn die CLI nicht bereits am gemeinsamen Kern haengt.
- [x] CLI-Paritaet zur kanonischen Konfigurationsquelle explizit absichern (AC: 1, 2)
  - [x] Test fuer `list-tokens` mit realem `StatusService -> MaintenanceConfigManager`-Pfad ergaenzen.
  - [x] Test fuer `show-status` sowie `activate`/`deactivate` ueber denselben Persistenz- und Statuskern ergaenzen.
- [x] Brownfield-Grenzen wahren (AC: 1, 2)
  - [x] Keine neue CLI-spezifische Persistenz- oder Tokenlogik einfuehren.
  - [x] Rueckschalt-Schutzlogik aus `3.4` nicht vorziehen.

## Dev Notes

### Story Intent

Story `3.3` zieht den bestehenden CLI-Einstiegspunkt explizit unter denselben kanonischen Kern wie Admin und Laufzeit. Fokus ist Parity und Vertragsabsicherung, nicht ein neuer CLI-Featureblock.

### Epic Context

- `3.1` hat manuellen Status ueber den gemeinsamen Kern abgesichert.
- `3.2` hat geplante Zeitfenster als Laufzeit-Parity abgesichert.
- `3.3` bestaetigt nun, dass auch die CLI keinen Nebenpfad benutzt.

### Previous Story Intelligence

- `ControlCommand` delegiert bereits an `StatusService`.
- `StatusService` delegiert an `MaintenanceConfigManager` und damit an dieselbe kanonische Konfigurationsbasis wie UI und Laufzeit.
- Bisherige `ControlCommandTest`s pruefen zwar die Delegation ueber Mocks, aber nicht explizit den echten fachlichen Pfad.

### Current State Analysis

- `src/CustomMaintenanceBundle/Command/ControlCommand.php`
  - `list-tokens` ruft `StatusService::getValidTokens()` auf.
  - `show-status` ruft `StatusService::getStatus()` auf.
  - `activate`/`deactivate` rufen `isFixedMode()` und `setStatus()` auf.
- `src/CustomMaintenanceBundle/Service/StatusService.php`
  - nutzt fuer Token, Status und Persistenz den `MaintenanceConfigManager`.
- `tests/Unit/Command/ControlCommandTest.php`
  - deckt oeffentlichen CLI-Vertrag bereits ueber Mocks ab, aber noch nicht die Paritaet zum realen Config-/Statuskern.

### Must Preserve

- Command-Name `weblizards:custommaintenance:control`
- CLI-Ausgabeformate fuer Human-/Porcelain-Pfade
- Kanonische Status-/Konfigurationsbasis
- Bestehende Fixed-Guard-Mechanik unveraendert

### Must Change in Story 3.3

- Die CLI muss explizit gegen den realen gemeinsamen Kern abgesichert werden.
- Tests muessen zeigen, dass Tokenliste und Statusaenderungen nicht aus einem separaten CLI-Modell stammen.

### Explicitly Out of Scope

- Keine neue CLI-Option
- Keine neue Rueckschaltlogik
- Kein Logging unbekannter Tokens
- Keine Admin- oder Twig-Aenderungen

### Architecture Compliance

- `ControlCommand -> StatusService -> MaintenanceConfigManager` bleibt die einzige fachliche Kette.
- Keine CLI-spezifische Direktmanipulation der Rohkonfiguration einfuehren.

### Technical Requirements

- PHP `>=8.1`, Pimcore `10.6.9`
- Tests sollen zeigen, dass dieselbe Persistenzbasis von CLI und Statusauswertung gelesen/geschrieben wird.

### Library / Framework Requirements

- Symfony Console / `CommandTester`
- PHPUnit `^10.5`

### File Structure Requirements

- Erwartete UPDATE-Dateien:
  - `tests/Unit/Command/ControlCommandTest.php`
  - `_bmad-output/implementation-artifacts/3-3-cli-steuerung-auf-die-gemeinsame-status-und-konfigurationslogik-ausrichten.md`
  - `_bmad-output/implementation-artifacts/sprint-status.yaml`

### Testing Requirements

- Erwartete Absicherung:
  - `list-tokens` liest echte Tokens aus der kanonischen Konfigurationsquelle
  - `show-status` gibt den ueber denselben Kern ausgewerteten Status aus
  - `activate`/`deactivate` persistieren ueber denselben Kern und beeinflussen spaetere CLI-Abfragen
- Vollsuite muss gruen bleiben.

### Risks To Watch

- Tests bleiben auf Mock-Ebene und beweisen die Paritaet nicht
- Story zieht Rueckschalt-/Fixed-Sonderfaelle aus `3.4` vor
- CLI wird versehentlich an ein eigenes Modell gekoppelt

### Git Intelligence Summary

- Epic 3 wird weiterhin als gezielte Parity-Haertung des vorhandenen Kerns umgesetzt.
- `3.3` folgt demselben Muster: minimal invasive, testgetriebene Absicherung eines schon vorhandenen Pfads.

### Project Context Reference

- `_bmad-output/project-context.md`
- `_bmad-output/planning-artifacts/epics.md`
- `_bmad-output/implementation-artifacts/3-1-manuellen-aktiv-inaktiv-status-ueber-die-neue-konfigurationsbasis-auswerten.md`
- `_bmad-output/implementation-artifacts/3-2-geplante-zeitfenster-in-die-statusbewertung-integrieren.md`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `sed -n '1,220p' _bmad-output/implementation-artifacts/sprint-status.yaml`
- `sed -n '328,380p' _bmad-output/planning-artifacts/epics.md`
- `sed -n '1,220p' src/CustomMaintenanceBundle/Command/ControlCommand.php`
- `sed -n '1,220p' src/CustomMaintenanceBundle/Service/StatusService.php`
- `sed -n '1,260p' src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `sed -n '1,220p' tests/Unit/Command/ControlCommandTest.php`

### Completion Notes List

- `ControlCommand` hing fachlich bereits korrekt am gemeinsamen `StatusService -> MaintenanceConfigManager`-Kern.
- Kein Produktivcode-Umbau noetig.
- `ControlCommandTest` um echte CLI-Paritaetstests gegen den kanonischen Status-/Config-Pfad erweitert.

### File List

- `_bmad-output/implementation-artifacts/3-3-cli-steuerung-auf-die-gemeinsame-status-und-konfigurationslogik-ausrichten.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `tests/Unit/Command/ControlCommandTest.php`

### Change Log

- 2026-06-17: Story 3.3 erstellt und auf `ready-for-dev` gesetzt.
- 2026-06-17: CLI-Paritaet ueber reale Command-/Status-/Config-Tests abgesichert und Story auf `review` gesetzt.
- 2026-06-17: Code-Review ohne Findings abgeschlossen und Story auf `done` gesetzt.
