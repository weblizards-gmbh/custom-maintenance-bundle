# Story 2.3: Bestehende Maintenance-Art bearbeiten und Token speichern

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Administrator,
I want bestehende Maintenance-Arten inklusive Token aendern und speichern koennen,
so that ich meine Konfiguration im laufenden Betrieb anpassen kann.

## Acceptance Criteria

1. **Given** eine bestehende Maintenance-Art im Admin-UI  
   **When** ich ihre bearbeitbaren Felder aendere und speichere  
   **Then** werden die Aenderungen ueber die zentrale Konfigurationslogik persistiert  
   **And** die Aktualisierung landet im Settings Store.
2. **Given** eine bestehende Custom-Maintenance-Art  
   **When** ich den Token aendere und speichere  
   **Then** wird die Aenderung nicht durch zusaetzliche Warn- oder Blockadelogik verhindert  
   **And** die Verantwortung fuer moegliche Folgewirkungen bleibt beim Betreiber oder Integrator.

## Tasks / Subtasks

- [x] Bestehende Custom-Eintraege im Admin-UI bearbeitbar machen (AC: 1, 2)
  - [x] Bestehende `custom`-Fieldsets um editierbare Felder fuer Token und Beschreibung erweitern.
  - [x] Titel-/Feldanzeige im bestehenden ExtJS-Schema konsistent halten, wenn Token oder Beschreibung geaendert werden.
  - [x] Keine zusaetzliche Warn- oder Bestaetigungslogik fuer Token-Aenderungen einfuehren.
- [x] Save-Pfad fuer Updates und Token-Renames ueber die kanonische Konfiguration erweitern (AC: 1, 2)
  - [x] Bestehenden `saveFromAdminPayload()`-Pfad so erweitern, dass Feldwerte bestehender Eintraege weiter aktualisiert werden.
  - [x] Token-Rename ueber denselben zentralen Manager-Pfad persistieren statt ueber UI-Sonderlogik.
  - [x] Write-Pfad auf Settings Store / Legacy-Shape kompatibel halten.
- [x] Brownfield-Regeln und Guardrails aus Epic 2 wahren (AC: 1, 2)
  - [x] Keine Referenzwarnung fuer Anwendungscode oder Integrationen bei Token-Aenderungen einfuehren.
  - [x] Validierung fuer echte Kollisionen und reservierten Sonderfall `pimcore` beibehalten.
  - [x] Legacy-Tokens ausserhalb des neuen Vertrags weiter grandfathered behandeln, solange sie nicht aktiv umbenannt werden.
- [x] Verifikation fuer Bearbeiten und Rename-Faelle erweitern (AC: 1, 2)
  - [x] PHPUnit-Absicherung fuer Persistenz geaenderter Feldwerte und Token-Renames ergaenzen.
  - [x] Controller-Save-Pfad fuer erfolgreichen Rename absichern.
  - [x] Kollisionen beim Rename serverseitig absichern.

### Review Findings

- [x] [Review][Patch] Rename-Loop kann bestehende Eintraege bei Multi-Rename oder unvollstaendigem Payload ueberschreiben [src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php:125]
- [x] [Review][Patch] Fieldset-Titel kann waehrend Bearbeitung leer werden und Eintrag unkenntlich machen [src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js:386]

## Dev Notes

### Story Intent

Story `2.3` erweitert Epic 2 von der reinen Neuanlage (`2.2`) auf echte Bearbeitung bestehender `custom`-Maintenances. Kern ist kein neuer CRUD-Pfad, sondern das Nachziehen des bestehenden Save-Vertrags fuer Rename- und Edit-Faelle ohne zusaetzliche Schutzwarnungen fuer Referenzen im Anwendungscode.

### Epic Context

- Epic 2 nutzt den in Epic 1 eingefuehrten zentralen Settings-Store-/Legacy-Write-Pfad.
- `2.1` hat den Ladevertrag fuer Admin-Daten abgesichert.
- `2.2` hat Neuanlage, Defaults und zentrale Token-Validierung fuer neue Eintraege eingefuehrt.
- `2.3` muss denselben Datenpfad nun fuer Updates und Token-Renames weiterverwenden.

### Previous Story Intelligence

- `2.2` hat bereits Hidden-Tokenliste, Add-Flow und zentrale Create-Persistenz ueber `MaintenanceConfigManager` eingefuehrt.
- Review-Entscheid aus `2.2`: Legacy-Tokens ausserhalb des neuen Vertrags werden grandfathered; strikte Token-Regeln gelten nur fuer neue oder aktiv umbenannte Tokens.
- Leerwert-Pfad fuer Datums-/Zeitfelder ist seit `2.2` runtime-safe; dieser Vertrag darf durch Rename-Logik nicht gebrochen werden.

### Current State Analysis

- `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
  - Rendert bestehende `custom`-Fieldsets dynamisch pro Token.
  - Nach `2.2` sind neue Eintraege erzeugbar, aber bestehende Eintraege hatten noch keinen editierbaren Token und keine editierbare Beschreibung.
  - Feldnamen haengen bisher direkt am Token; fuer Rename braucht der Save-Pfad daher eine stabile Form-ID pro vorhandenen Fieldset-Key.
- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - Schreibt bereits `frontend`, `pimcore` und `custom` ueber einen zentralen Save-Pfad in die Legacy-Shape.
  - Nach `2.2` werden neue Tokens validiert, reserviertes `pimcore` abgewehrt und Defaults gesetzt.
  - Fuer `2.3` fehlte noch eine Abbildung `Form-Key -> neuer Ziel-Token`, damit bestehende Keys kontrolliert umbenannt werden koennen.
- `tests/Unit/Controller/AdminpanelControllerTest.php`
  - Deckt erfolgreiche Saves und Create-Faelle bereits ab.
  - Fuer `2.3` braucht es einen Erfolgsfall fuer Rename ueber denselben Controller-Pfad.
- `tests/Unit/Service/MaintenanceConfigManagerTest.php`
  - Deckt Create-Flow, Reserved-Token und Duplicate-Create bereits ab.
  - Bester Ankerpunkt fuer Rename-Persistenz und Rename-Kollisionen.

### Must Preserve

- Admin-Routen unter `/admin/weblizards_custom_maintenance/...`
- ExtJS-/Classic-Admin-UI und Bundle-Asset-Struktur
- Zentrale Konfigurationsquelle und Persistenz ueber `MaintenanceConfigManager`
- Legacy-Shape unter `custom`, `pimcore`, `frontend`
- Sonderfall `pimcore` als reservierter nativer Token
- Datums-/Zeitformate `d.m.Y` und `H:i`

### Must Change in Story 2.3

- Bestehende `custom`-Maintenances muessen editierbare Felder fuer Token und Beschreibung bekommen.
- Save muss Updates und Token-Rename ueber denselben Payload/Manager-Pfad persistieren.
- Token-Renames duerfen nicht durch zusaetzliche Schutzwarnungen fuer Anwendungscode-Referenzen gebremst werden.

### Explicitly Out of Scope

- Keine Delete-Logik fuer `custom`-Eintraege
- Keine Schutz-/Delete-Sonderfall-UI fuer `pimcore`
- Keine neue API oder Persistenzachse
- Keine automatische Migration oder Normalisierung grandfathered Legacy-Tokens ausserhalb des konkret geaenderten Eintrags

### Architecture Compliance

- Controller bleiben duenn; fachliche Rename-/Persistenzlogik gehoert in `MaintenanceConfigManager`.
- UI darf keine zweite Datenstruktur fuer Rename-Faelle aufbauen.
- Save-, Load- und spaetere Delete-/Sonderfall-Faelle muessen auf derselben Konfigurationsquelle bleiben.

### Technical Requirements

- PHP `>=8.1`, Pimcore `10.6.9`, klassische Pimcore-Admin-Integration.
- Keine neue Frontend-Library oder Build-Pipeline.
- Token-Aenderung ohne Referenzwarnung, aber weiterhin mit zentraler Validierung fuer reserviertes `pimcore` und echte Duplicate-Kollisionen.
- Grandfathered Legacy-Tokens bleiben bearbeitbar; erst aktive Rename-Ziele muessen dem neuen Vertrag entsprechen.

### Library / Framework Requirements

- ExtJS-/Pimcore-Classic-Admin-Struktur beibehalten.
- PHPUnit `^10.5` fuer neue Tests.

### File Structure Requirements

- Wahrscheinlich betroffene UPDATE-Dateien:
  - `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
  - `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - `tests/Unit/Controller/AdminpanelControllerTest.php`
  - `tests/Unit/Service/MaintenanceConfigManagerTest.php`
- Keine neue Verzeichnisachse oder UI-spezifische Rename-Adapter einfuehren.

### Testing Requirements

- Erwartete Absicherung fuer `2.3`:
  - Rename eines bestehenden `custom`-Tokens ueber den zentralen Save-Pfad
  - Persistenz geaenderter Beschreibung/Feldwerte beim Rename
  - Controller-Erfolgsfall fuer Rename
  - Duplicate-Kollision bei Rename als Fehler
- Vollsuite muss gruen bleiben.

### Risks To Watch

- Feldnamen haengen am alten Token und koennen Rename-Persistenz falsch zuordnen
- UI fuehrt versehentlich Referenzwarnungen oder Bestaetigungen ein
- Rename loescht unbeabsichtigt andere `custom`-Eintraege
- Grandfathered Legacy-Tokens werden still global normalisiert statt nur bei echter Aenderung validiert

### Git Intelligence Summary

- Juengste Commits zeigen kleine, testgetriebene Brownfield-Schritte im Admin-/Manager-Pfad statt grosser Architekturumbauten.
- `2.3` soll diesem Muster folgen: minimale UI-Aenderung, zentrale Persistenzlogik, enge PHPUnit-Absicherung.

### Project Context Reference

- `_bmad-output/project-context.md`
- `_bmad-output/planning-artifacts/epics.md`
- `_bmad-output/planning-artifacts/architecture.md`
- `_bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md`
- `_bmad-output/implementation-artifacts/2-2-neue-custom-maintenance-art-mit-defaults-anlegen.md`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `sed -n '1,220p' .agents/skills/bmad-dev-story/SKILL.md`
- `sed -n '221,520p' .agents/skills/bmad-dev-story/SKILL.md`
- `sed -n '1,220p' .agents/skills/bmad-create-story/SKILL.md`
- `sed -n '221,520p' .agents/skills/bmad-create-story/SKILL.md`
- `sed -n '1,140p' _bmad-output/implementation-artifacts/sprint-status.yaml`
- `sed -n '220,300p' _bmad-output/planning-artifacts/epics.md`
- `sed -n '220,236p' _bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md`
- `sed -n '1,380p' src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `sed -n '1,620p' src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
- `sed -n '1,340p' tests/Unit/Controller/AdminpanelControllerTest.php`
- `sed -n '1,680p' tests/Unit/Service/MaintenanceConfigManagerTest.php`
- `vendor/bin/phpunit tests/Unit/Controller/AdminpanelControllerTest.php tests/Unit/Service/MaintenanceConfigManagerTest.php`
- `vendor/bin/phpunit tests/Unit/Service/StatusServiceTest.php tests/Unit/Domain/Model/MaintenanceTokenTest.php tests/Unit/Domain/Model/MaintenanceEntryTest.php`
- `vendor/bin/phpunit`

### Completion Notes List

- Story `2.3` aus Epic-/PRD-/Projektkontext abgeleitet und direkt im bestehenden Admin-/Manager-Pfad umgesetzt.
- Bestehende `custom`-Fieldsets im Admin-UI um editierbare Token- und Beschreibungsfelder erweitert.
- Save-Pfad im `MaintenanceConfigManager` auf `Form-Key -> Ziel-Token` erweitert, damit bestehende Eintraege kontrolliert umbenannt werden koennen.
- Kein Referenzwarnungs- oder Schutzdialog fuer Token-Aenderungen eingefuehrt; zentrale Validierung fuer Duplicate-Kollisionen und reserviertes `pimcore` bleibt aktiv.
- PHPUnit fuer Rename-Erfolg, Rename-Kollision und Controller-Save-Pfad erweitert.
- Vollstaendige PHPUnit-Suite nach den Aenderungen erfolgreich ausgefuehrt.
- Code-Review-Follow-ups umgesetzt: Rename-Rebuild gegen Ueberschreiben gehaertet und UI-Titel-Fallback fuer leere Edit-Zustaende verbessert.

### File List

- `_bmad-output/implementation-artifacts/2-3-bestehende-maintenance-art-bearbeiten-und-token-speichern.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `tests/Unit/Controller/AdminpanelControllerTest.php`
- `tests/Unit/Service/MaintenanceConfigManagerTest.php`

### Change Log

- 2026-06-17: Story 2.3 erstellt, bestehende Custom-Maintenances fuer Rename/Edit erweitert und auf `review` gesetzt.
- 2026-06-17: Code-Review-Follow-ups umgesetzt und Story auf `done` gesetzt.
