# Story 3.2: Geplante Zeitfenster in die Statusbewertung integrieren

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Betreiber,
I want geplante Start- und Endzeitpunkte fuer Maintenances automatisch auswerten lassen,
so that Wartungsfenster ohne manuellen Eingriff wirksam werden.

## Acceptance Criteria

1. **Given** eine Maintenance-Art mit hinterlegtem Start- und Endzeitpunkt  
   **When** sich die aktuelle Zeit innerhalb des geplanten Zeitfensters befindet  
   **Then** wird die Maintenance als aktiv behandelt.
2. **Given** ein geplantes Zeitfenster, das noch nicht begonnen hat oder bereits abgelaufen ist  
   **When** der Status ausgewertet wird  
   **Then** wird die Maintenance entsprechend ausserhalb des Zeitfensters nicht ueber die Planungslogik aktiviert.
3. **Given** Datums- und Zeitwerte im bestehenden Bundle-Format  
   **When** sie verarbeitet werden  
   **Then** bleiben die fachlich erwarteten Formate `d.m.Y` und `H:i` kompatibel.

## Tasks / Subtasks

- [x] Bestehenden Zeitfensterpfad gegen die Story-ACs pruefen und minimal halten (AC: 1, 2, 3)
  - [x] `StatusService::isActive()` und `isTimeslotEntered()` auf bereits vorhandene Planungslogik pruefen.
  - [x] Nur dann Produktivcode anpassen, wenn die neuen Zeitfenster-ACs eine reale Luecke zeigen.
- [x] Laufzeitverhalten fuer geplante Fenster explizit absichern (AC: 1, 2)
  - [x] Test fuer aktives Zeitfenster bei manuell inaktivem Token ergaenzen.
  - [x] Test fuer Zeitpunkte vor und nach dem Fenster ohne Planungsaktivierung ergaenzen.
- [x] Bundle-Formate fuer Datum und Uhrzeit explizit absichern (AC: 3)
  - [x] Test fuer Verarbeitung und Rueckgabe im Format `d.m.Y` und `H:i` ergaenzen.
- [x] Brownfield-Grenzen wahren (AC: 1, 2, 3)
  - [x] Keine CLI- oder Admin-Logik aus `3.3` bzw. spaeteren Stories vorziehen.
  - [x] Keine neue Statusquelle neben `MaintenanceConfigManager` einfuehren.

### Review Findings

- [x] [Review][Patch] Exakte Fenstergrenzen sind im Zeitfenster-Regressionstest noch nicht abgesichert [tests/Unit/Service/StatusServiceTest.php:38]

## Dev Notes

### Story Intent

Story `3.2` zieht den bereits vorhandenen Planungszweig der Statusauswertung explizit unter Test. Ziel ist nicht neue Scheduling-Infrastruktur, sondern die fachliche Absicherung, dass gespeicherte Zeitfenster in der Laufzeitbewertung korrekt wirken.

### Epic Context

- `3.1` hat den manuellen Statuspfad ueber die kanonische Konfigurationsbasis abgesichert.
- `3.2` erweitert diese Laufzeitsicht um geplante Fenster, ohne neue Persistenz oder UI einzufuehren.
- `3.3` behandelt erst die CLI-Ausrichtung auf denselben Kern.

### Previous Story Intelligence

- `StatusService::isActive()` wertet bereits `isMarkedActive()` oder `isTimeslotEntered()` aus.
- `isHandsOff()` nutzt denselben Zeitfensterpfad bereits fuer nicht fixe Maintenances.
- `MaintenanceDateTime` und `MaintenanceSchedule` halten das Legacy-Format `d.m.Y` / `H:i` weiter aufrecht.
- `3.1` hat Carbon-Testzeiten bereits bewusst vom Zeitfenster entkoppelt; `3.2` darf diese Trennung nicht wieder verwischen.

### Current State Analysis

- `src/CustomMaintenanceBundle/Service/StatusService.php`
  - `isActive()` aktiviert einen Token bereits innerhalb des geplanten Fensters.
  - `isTimeslotEntered()` vergleicht `Carbon::now()` mit `planned.from`/`planned.to`.
- `src/CustomMaintenanceBundle/Domain/Model/MaintenanceDateTime.php`
  - verarbeitet leere Werte defensiv und parst das Bundle-Format ueber `Carbon::createFromFormat('d.m.Y H:i', ...)`.
- `tests/Unit/Service/StatusServiceTest.php`
  - deckt bisher manuellen Status, Hands-off und leere Schedule-Werte ab, aber nicht explizit die Story-ACs fuer Planungsfenster.

### Must Preserve

- Kanonische Konfigurationsbasis ueber `MaintenanceConfigManager`
- Laufzeitfassade `StatusService`
- Bundle-Datumsformat `d.m.Y` und Zeitformat `H:i`
- Carbon als bestehende Zeitvergleichsbasis

### Must Change in Story 3.2

- Zeitfenster muessen explizit als Aktivierungsfaktor unter Test stehen.
- Vorher-/Nachher-Faelle muessen klar von manueller Aktivierung getrennt abgesichert werden.
- Formatkompatibilitaet darf nicht nur implizit bleiben.

### Explicitly Out of Scope

- Keine neue Admin-UI
- Keine neue Persistenzlogik
- Keine CLI-Aenderungen
- Kein Logging unbekannter Tokens

### Architecture Compliance

- Statusauswertung bleibt auf `StatusService -> MaintenanceConfigManager -> MaintenanceConfigSet`.
- Keine zweite Scheduling-Logik oder Formatnormalisierung ausserhalb der bestehenden Domain-Objekte einfuehren.

### Technical Requirements

- PHP `>=8.1`, Pimcore `10.6.9`
- Carbon-Testzeit gezielt innerhalb und ausserhalb des konfigurierten Fensters setzen.
- Geplantes Fenster weiterhin ueber `planned.from` und `planned.to` im Legacy-Format auswerten.

### Library / Framework Requirements

- PHPUnit `^10.5`
- Carbon fuer kontrollierte Zeitfenster-Tests

### File Structure Requirements

- Erwartete UPDATE-Dateien:
  - `tests/Unit/Service/StatusServiceTest.php`
  - `_bmad-output/implementation-artifacts/3-2-geplante-zeitfenster-in-die-statusbewertung-integrieren.md`
  - `_bmad-output/implementation-artifacts/sprint-status.yaml`
- Keine neue Verzeichnisachse.

### Testing Requirements

- Erwartete Absicherung:
  - innerhalb des Fensters aktiv trotz manuell inaktivem Status
  - vor und nach dem Fenster keine Planungsaktivierung
  - `getMaintenanceFrom()`/`getMaintenanceTo()` liefern weiterhin Bundle-kompatible Formate
- Vollsuite muss gruen bleiben.

### Risks To Watch

- Tests bestaetigen versehentlich wieder nur manuellen Status statt Zeitfensterlogik
- Story zieht CLI- oder Notice-Verhalten aus spaeteren Stories vor
- Formatkompatibilitaet bleibt unbewiesen und bricht spaeter still

### Git Intelligence Summary

- Epic 3 wird bisher als testgetriebene Härtung des vorhandenen Statuskerns umgesetzt.
- `3.2` folgt demselben Muster: explizit absichern, aber keinen unnötigen Produktivumbau erzeugen.

### Project Context Reference

- `_bmad-output/project-context.md`
- `_bmad-output/planning-artifacts/epics.md`
- `_bmad-output/implementation-artifacts/3-1-manuellen-aktiv-inaktiv-status-ueber-die-neue-konfigurationsbasis-auswerten.md`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `sed -n '1,220p' _bmad-output/implementation-artifacts/sprint-status.yaml`
- `sed -n '300,380p' _bmad-output/planning-artifacts/epics.md`
- `sed -n '1,260p' src/CustomMaintenanceBundle/Service/StatusService.php`
- `sed -n '320,390p' src/CustomMaintenanceBundle/Service/StatusService.php`
- `sed -n '1,260p' tests/Unit/Service/StatusServiceTest.php`
- `sed -n '260,420p' tests/Unit/Service/StatusServiceTest.php`
- `sed -n '1,220p' src/CustomMaintenanceBundle/Domain/Model/MaintenanceDateTime.php`
- `sed -n '1,220p' src/CustomMaintenanceBundle/Domain/Model/MaintenanceSchedule.php`

### Completion Notes List

- Story `3.2` bestaetigt, dass die geplante Zeitfensterlogik bereits ueber den bestehenden kanonischen Statuspfad laeuft.
- Kein Produktivcode-Umbau noetig; `StatusService` erfuellt den Story-Vertrag bereits.
- `StatusServiceTest` um drei gezielte Faelle erweitert:
  - aktiv innerhalb des Zeitfensters trotz manuell inaktivem Status
  - keine Planungsaktivierung vor oder nach dem Zeitfenster
  - kompatible Rueckgabe der konfigurierten Fensterwerte im Bundle-Format
- Fokus blieb bewusst auf Runtime-Logik; CLI- und spaetere Story-Themen wurden nicht vorgezogen.

### File List

- `_bmad-output/implementation-artifacts/3-2-geplante-zeitfenster-in-die-statusbewertung-integrieren.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `tests/Unit/Service/StatusServiceTest.php`

### Change Log

- 2026-06-17: Story 3.2 erstellt und auf `ready-for-dev` gesetzt.
- 2026-06-17: Zeitfenster-Auswertung ueber gezielte Runtime-Tests abgesichert und Story auf `review` gesetzt.
- 2026-06-17: Review-Patch fuer inklusive Start-/Endgrenzen des Zeitfensters in den Regressionstests umgesetzt.
