# Story 3.1: Manuellen Aktiv-/Inaktiv-Status ueber die neue Konfigurationsbasis auswerten

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Betreiber,
I want den manuellen Status einer Maintenance-Art verlaesslich setzen und auswerten koennen,
so that fachliche Teilstoerungen gezielt aktiviert und beendet werden koennen.

## Acceptance Criteria

1. **Given** eine vorhandene Maintenance-Art mit gespeichertem Status  
   **When** der Status manuell auf aktiv oder inaktiv gesetzt wird  
   **Then** wird der Zustand ueber die kanonische Konfigurationsbasis gespeichert und ausgewertet  
   **And** der Status ist konsistent fuer Admin-UI und Laufzeitpruefung verfuegbar.
2. **Given** eine inaktive oder aktive Maintenance-Art  
   **When** ihr Zustand zur Laufzeit abgefragt wird  
   **Then** liefert das Bundle das fachlich erwartete Aktiv-/Inaktiv-Ergebnis  
   **And** die Statusauswertung basiert nicht auf einem separaten Nebenpfad.

## Tasks / Subtasks

- [x] Manuellen Statuspfad ueber die kanonische Konfigurationsbasis absichern (AC: 1, 2)
  - [x] Bestehenden Statuspfad in `StatusService` und `MaintenanceConfigManager` gegen Story-ACs pruefen.
  - [x] Falls noetig minimal korrigieren, damit manuelle Statuswerte nicht ueber einen Nebenpfad ausgewertet werden.
- [x] Laufzeitverhalten fuer aktiv/inaktiv explizit verifizieren (AC: 1, 2)
  - [x] Test fuer manuell aktiv gesetzten Token ausserhalb eines Zeitfensters ergaenzen.
  - [x] Test fuer manuell inaktiv gesetzten Token ausserhalb eines Zeitfensters ergaenzen.
  - [x] Test fuer Setzen und anschliessendes Auswerten desselben Tokens ueber die gemeinsame Persistenzbasis ergaenzen.
- [x] Bestehende Brownfield-Vertraege wahren (AC: 1, 2)
  - [x] Keine neue Status-Persistenzachse einfuehren.
  - [x] Keine CLI-Sonderlogik vorziehen; Story `3.3` bleibt getrennt.

## Dev Notes

### Story Intent

Story `3.1` startet Epic 3 mit dem kleinsten fachlichen Kern: manuelle Aktiv-/Inaktiv-Status muessen ueber die neue kanonische Konfigurationsbasis gespeichert und zur Laufzeit ausgewertet werden. Fokus ist nicht neue UI, sondern explizite Absicherung des bestehenden Pfads.

### Epic Context

- Epic 2 hat Admin-Konfiguration, Create/Edit/Rename/Delete und den Pimcore-Sonderfall auf die kanonische Konfigurationsbasis gezogen.
- Epic 3 baut darauf auf und richtet Laufzeit- und Betriebslogik auf denselben Kern aus.
- `3.1` behandelt nur manuelle Aktiv-/Inaktiv-Auswertung; geplante Zeitfenster folgen erst in `3.2`, CLI-Ausrichtung erst in `3.3`.

### Previous Story Intelligence

- `MaintenanceConfigManager` ist inzwischen die zentrale Quelle fuer Admin-Payload und Persistenz.
- `StatusService` arbeitet bereits gegen `MaintenanceConfigManager` und dessen `MaintenanceConfigSet`.
- `setCustomStatus()` schreibt den manuellen Status in die kanonische `custom`-Konfiguration und invalidiert den Cache.
- Review-/Delete-Arbeit aus Epic 2 hat den Worktree in Admin-/Manager-Tests verbreitert; bei `3.1` keine unnötigen UI-Aenderungen mitziehen.

### Current State Analysis

- `src/CustomMaintenanceBundle/Service/StatusService.php`
  - `setStatus()` delegiert bereits an `MaintenanceConfigManager::setCustomStatus()`.
  - `getStatus()` und `isActive()` lesen ueber `getMaintenanceEntry()` aus dem kanonischen `ConfigSet`.
  - Bisherige Tests mischen manuellen Status und Zeitfenster teilweise zusammen; fuer `3.1` braucht es entkoppelte manuelle Statusfaelle.
- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - `setCustomStatus()` schreibt direkt nach `custom[token].active`.
  - Cache-Invalidierung ist bereits vorhanden.
- `tests/Unit/Service/StatusServiceTest.php`
  - Deckt aktive/inaktive Faelle schon teilweise ab, aber nicht explizit als reine manuelle Statusverifikation ausserhalb des Zeitfensters.
- `tests/Unit/Service/MaintenanceConfigManagerTest.php`
  - Hat bereits einen Basistest fuer `setCustomStatus()`.

### Must Preserve

- Kanonische Konfigurationsbasis ueber `MaintenanceConfigManager`
- `StatusService` als Laufzeitfassade
- Pimcore-10-kompatible Bundle-Struktur
- Oeffentliche Laufzeit- und CLI-Integrationspunkte

### Must Change in Story 3.1

- Manueller Status muss explizit gegen die kanonische Persistenz und die Laufzeitauswertung abgesichert werden.
- Tests muessen manuellen Status von spaeterer Zeitfensterlogik trennen.

### Explicitly Out of Scope

- Keine neue Admin-UI
- Keine CLI-Refactorings
- Keine neue Zeitfensterlogik
- Kein Logging unbekannter Tokens

### Architecture Compliance

- Keine neue Statusquelle oder Spiegelstruktur einfuehren.
- Laufzeitpruefung muss weiter ueber `StatusService -> MaintenanceConfigManager -> MaintenanceConfigSet` laufen.

### Technical Requirements

- PHP `>=8.1`, Pimcore `10.6.9`
- Tests sollen beweisen, dass ein manuell gesetzter Status auch ausserhalb des Zeitfensters korrekt wirkt.
- Carbon-Testzeit gezielt setzen, damit `3.1` nicht versehentlich `3.2` vorwegnimmt.

### Library / Framework Requirements

- PHPUnit `^10.5`
- Carbon-Testclock fuer Zeitkontrolle

### File Structure Requirements

- Wahrscheinlich betroffene UPDATE-Dateien:
  - `src/CustomMaintenanceBundle/Service/StatusService.php`
  - `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - `tests/Unit/Service/StatusServiceTest.php`
  - `tests/Unit/Service/MaintenanceConfigManagerTest.php`
- Keine neue Verzeichnisachse.

### Testing Requirements

- Erwartete Absicherung:
  - manuell aktiv gesetzter Token ist ausserhalb des Zeitfensters aktiv
  - manuell inaktiv gesetzter Token ist ausserhalb des Zeitfensters inaktiv
  - `setStatus()` und anschliessendes `getStatus()`/`isActive()` laufen ueber dieselbe Persistenzbasis
- Vollsuite muss gruen bleiben.

### Risks To Watch

- Tests bestaetigen versehentlich wieder Zeitfenster statt manuellen Status
- Story zieht `3.2`-Logik vor
- Status wird aus anderem Pfad gelesen als geschrieben

### Git Intelligence Summary

- Bisher wurden Stories in kleinen, testgetriebenen Brownfield-Schritten umgesetzt.
- `3.1` sollte diesem Muster folgen: moeglichst kein Produktivumbau, aber harte Vertrags- und Laufzeittests.

### Project Context Reference

- `_bmad-output/project-context.md`
- `_bmad-output/planning-artifacts/epics.md`
- `_bmad-output/planning-artifacts/architecture.md`
- `_bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md`
- `_bmad-output/implementation-artifacts/2-5-custom-maintenance-arten-kontrolliert-loeschen.md`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `sed -n '1,180p' _bmad-output/implementation-artifacts/sprint-status.yaml`
- `sed -n '300,360p' _bmad-output/planning-artifacts/epics.md`
- `sed -n '1,260p' tests/Unit/Service/StatusServiceTest.php`
- `sed -n '1,260p' src/CustomMaintenanceBundle/Service/StatusService.php`
- `sed -n '260,520p' src/CustomMaintenanceBundle/Service/StatusService.php`
- `sed -n '1000,1090p' tests/Unit/Service/MaintenanceConfigManagerTest.php`

### Completion Notes List

- Story `3.1` bestaetigt, dass manueller Aktiv-/Inaktiv-Status bereits ueber den bestehenden kanonischen Kern laeuft.
- Kein Produktivcode-Umbau noetig; `StatusService` und `MaintenanceConfigManager` erfuellen den Story-Vertrag bereits.
- `StatusServiceTest` um drei gezielte Faelle erweitert:
  - manuell aktiv ausserhalb des Zeitfensters
  - manuell inaktiv ausserhalb des Zeitfensters
  - Setzen und anschliessendes Auswerten ueber dieselbe Persistenzbasis
- Vollsuite erfolgreich ausgefuehrt.

### File List

- `_bmad-output/implementation-artifacts/3-1-manuellen-aktiv-inaktiv-status-ueber-die-neue-konfigurationsbasis-auswerten.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `tests/Unit/Service/StatusServiceTest.php`

### Change Log

- 2026-06-17: Story 3.1 erstellt und auf `ready-for-dev` gesetzt.
- 2026-06-17: Manuellen Statuspfad ueber gezielte Runtime-Tests abgesichert und Story auf `review` gesetzt.
- 2026-06-17: Code-Review ohne Findings abgeschlossen und Story auf `done` gesetzt.
