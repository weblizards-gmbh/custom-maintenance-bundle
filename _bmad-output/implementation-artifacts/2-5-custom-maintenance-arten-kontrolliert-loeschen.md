# Story 2.5: Custom-Maintenance-Arten kontrolliert loeschen

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Administrator,
I want benutzerdefinierte Maintenance-Arten nach Bestaetigung loeschen koennen,
so that nicht mehr benoetigte Konfigurationseintraege aus dem System entfernt werden koennen.

## Acceptance Criteria

1. **Given** eine bestehende Custom-Maintenance-Art im Admin-UI  
   **When** ich den Loeschvorgang ausloese  
   **Then** wird eine explizite Sicherheitsabfrage verlangt.
2. **Given** eine bestaetigte Loeschaktion fuer eine Custom-Maintenance-Art  
   **When** der Vorgang abgeschlossen wird  
   **Then** ist der Eintrag nicht mehr in der Verwaltungsoberflaeche vorhanden  
   **And** der Token gilt fuer kuenftige Pruefungen als unbekannt.
3. **Given** eine aktuell aktive Custom-Maintenance-Art  
   **When** ich ihre Loeschung ausdruecklich bestaetige  
   **Then** darf sie geloescht werden  
   **And** es erfolgt keine zusaetzliche Aktiv-Blockade fuer den Loeschpfad.

## Tasks / Subtasks

- [x] Kontrollierten Delete-Flow im bestehenden Admin-UI einfuehren (AC: 1, 2, 3)
  - [x] Pro `custom`-Fieldset eine Loeschaktion im bestehenden ExtJS-Schema anbieten.
  - [x] Vor dem Entfernen eines `custom`-Eintrags eine explizite Bestaetigungsabfrage verlangen.
  - [x] Nach Bestaetigung den Eintrag lokal aus UI und `custom_tokens` entfernen, ohne den Pimcore-Sonderfall zu beruehren.
- [x] Kanonischen Save-/Persistenzpfad fuer geloeschte `custom`-Eintraege nutzen (AC: 2, 3)
  - [x] Loeschung ueber dieselbe `saveFromAdminPayload()`-/`custom_tokens`-Logik abwickeln statt ueber Nebenpfade.
  - [x] Keine Aktiv-Blockade fuer aktuell aktive `custom`-Eintraege einfuehren.
  - [x] Verhalten fuer kuenftige Token-Pruefungen durch das Entfernen aus der kanonischen Konfiguration absichern.
- [x] PHPUnit-Absicherung fuer Delete-Pfaede erweitern (AC: 1, 2, 3)
  - [x] Service-Test fuer bestaetigt geloeschten `custom`-Eintrag ergaenzen.
  - [x] Controller-Test fuer erfolgreichen Delete-Save-Pfad ergaenzen.
  - [x] Test fuer Loeschung eines aktiven `custom`-Eintrags ohne Zusatzblockade ergaenzen.

### Review Findings

- [x] [Review][Patch] UI entfernt `custom`-Fieldset schon vor erfolgreichem Save und laesst bei spaeterem Save-Fehler einen falschen lokalen Zustand stehen [src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js:361]

## Dev Notes

### Story Intent

Story `2.5` schliesst Epic 2 mit einem kontrollierten Delete-Flow fuer `custom`-Maintenances ab. Der Pfad soll bewusst minimal bleiben: UI-seitige Bestaetigung, danach Persistenz ueber dieselbe kanonische Save-Logik wie Create, Edit und Rename.

### Epic Context

- `2.1` hat den Admin-Ladevertrag abgesichert.
- `2.2` hat Neuanlage und Defaulting fuer `custom` eingefuehrt.
- `2.3` hat Bearbeiten und Rename fuer `custom` ergaenzt.
- `2.4` hat den nativen Pimcore-Sonderfall sichtbar und serverseitig geschuetzt.
- `2.5` betrifft ausschliesslich kontrolliertes Loeschen von `custom`; `pimcore` bleibt ausdruecklich unloeschbar.

### Previous Story Intelligence

- `custom_tokens` ist die kanonische Liste aller `custom`-Eintraege im Admin-Payload.
- Der Save-Pfad in `MaintenanceConfigManager` entfernt bereits implizit `custom`-Eintraege, die nicht mehr in `custom_tokens` enthalten sind.
- `pimcore_delete` wird seit `2.4` explizit serverseitig geblockt.
- `custom`-Token duerfen renamebar bleiben; Delete darf diese bestehende Logik nicht aufbrechen.

### Current State Analysis

- `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
  - Rendert pro `custom`-Token ein eigenes Fieldset.
  - Fuehrt bereits Add-Flow, Token-/Beschreibungsaenderung und Hidden-Feld `custom_tokens`.
  - Hat noch keine Delete-Aktion fuer `custom`; das ist der zentrale UI-Einstiegspunkt fuer `2.5`.
- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - Persistiert `custom` anhand von `custom_tokens`.
  - Wenn ein bestehender Token aus `custom_tokens` verschwindet, faellt er beim Rebuild von `custom` bereits aus dem Ergebnis.
  - Loeschung braucht daher keinen neuen Persistenzpfad, nur gute Absicherung ueber Tests und UI.
- `tests/Unit/Controller/AdminpanelControllerTest.php`
  - Deckt erfolgreiche Saves, Create, Rename und Fehlerpfade ab.
  - Bester Ort fuer einen Save-Pfad, der nach Entfernen eines Tokens keinen Eintrag mehr persistiert.
- `tests/Unit/Service/MaintenanceConfigManagerTest.php`
  - Deckt Duplicate-, Rename- und Reserved-Token-Pfaede ab.
  - Bester Ort fuer Delete eines normalen und eines aktiven `custom`-Eintrags.

### Must Preserve

- Klassisches Pimcore-/ExtJS-Admin-UI
- Admin-Routen unter `/admin/weblizards_custom_maintenance/...`
- Zentrale Persistenz ueber `MaintenanceConfigManager`
- Struktur `frontend`, `pimcore`, `custom`, `tokens`
- Pimcore als geschuetzter Sonderfall ohne Delete-UI oder Delete-Persistenz

### Must Change in Story 2.5

- `custom`-Eintraege muessen im UI kontrolliert loeschbar werden.
- Eine explizite Confirm-Interaktion ist Pflicht.
- Nach Bestaetigung muss der Eintrag aus UI und kanonischer Konfiguration verschwinden.
- Aktive `custom`-Eintraege duerfen ebenfalls loeschbar sein, ohne Zusatzwarnung oder Blockade.

### Explicitly Out of Scope

- Keine Delete-Moeglichkeit fuer `pimcore`
- Keine neue API oder separater Delete-Endpoint
- Keine zusaetzliche Aktiv-Schutzlogik
- Kein Logging unbekannter Tokens; das folgt erst in Epic 3

### Architecture Compliance

- UI darf nur `custom_tokens` und lokale Fieldsets manipulieren; die fachliche Quelle bleibt der Manager.
- Kein zweites Delete-Modell oder Client-only Persistenzabkuerzung einfuehren.

### Technical Requirements

- PHP `>=8.1`, Pimcore `10.6.9`, klassische ExtJS-Admin-Integration
- Confirm-Dialog im bestehenden ExtJS-/Pimcore-Stil
- Delete ueber Weglassen des Tokens aus `custom_tokens`, nicht ueber neue Payload-Sonderstruktur

### Library / Framework Requirements

- ExtJS-/Classic-Pimcore-Admin-Schema beibehalten
- PHPUnit `^10.5` fuer neue Tests

### File Structure Requirements

- Wahrscheinlich betroffene UPDATE-Dateien:
  - `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
  - `src/CustomMaintenanceBundle/Resources/install/admin_translations.csv`
  - `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - `tests/Unit/Controller/AdminpanelControllerTest.php`
  - `tests/Unit/Service/MaintenanceConfigManagerTest.php`
- Keine neue Verzeichnisachse oder neuer Controller-Endpunkt.

### Testing Requirements

- Erwartete Absicherung:
  - bestaetigtes Loeschen eines bestehenden `custom`-Eintrags
  - Loeschen eines aktiven `custom`-Eintrags ohne Blockade
  - Controller-Save-Pfad nach Delete persistiert den Eintrag nicht mehr
  - `pimcore` bleibt unberuehrt
- Vollsuite muss gruen bleiben.

### Risks To Watch

- Delete fuehrt versehentlich auch fuer `pimcore` sichtbare Controls ein
- UI entfernt Fieldset, aktualisiert aber `custom_tokens` nicht
- Delete-Pfad blockiert aktive `custom`-Eintraege trotz Story-Vorgabe
- Rename/Delete-Kombinationen zerstoeren bestehende `custom`-Eintraege

### Git Intelligence Summary

- Epic 2 wurde bisher in kleinen, testgetriebenen Schritten ueber denselben Admin-/Manager-Pfad erweitert.
- `2.5` sollte diesem Muster folgen: minimale UI-Aktion, kein neuer Persistenzpfad, enge PHPUnit-Absicherung.

### Project Context Reference

- `_bmad-output/project-context.md`
- `_bmad-output/planning-artifacts/epics.md`
- `_bmad-output/planning-artifacts/architecture.md`
- `_bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md`
- `_bmad-output/implementation-artifacts/2-4-native-pimcore-maintenance-im-ui-als-geschuetzten-sonderfall-behandeln.md`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `sed -n '1,160p' _bmad-output/implementation-artifacts/sprint-status.yaml`
- `sed -n '260,340p' _bmad-output/planning-artifacts/epics.md`
- `sed -n '1,260p' _bmad-output/implementation-artifacts/2-4-native-pimcore-maintenance-im-ui-als-geschuetzten-sonderfall-behandeln.md`
- `sed -n '240,760p' src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
- `rg -n "delete|remove|pimcore_delete|custom_tokens" src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php tests/Unit/Service/MaintenanceConfigManagerTest.php tests/Unit/Controller/AdminpanelControllerTest.php`

### Completion Notes List

- Story `2.5` als minimaler Delete-Schritt auf dem bestehenden `custom_tokens`-/Save-Vertrag umgesetzt.
- Pro `custom`-Fieldset eine Loeschaktion mit expliziter Confirm-Abfrage im ExtJS-Admin-UI eingefuehrt.
- Nach Bestaetigung wird der Eintrag lokal aus `custom_tokens`, `custom`-Cache und Layout entfernt; `pimcore` bleibt unberuehrt.
- `MaintenanceConfigManager` so korrigiert, dass ein leeres `custom_tokens` auch wirklich alle `custom`-Eintraege entfernt.
- PHPUnit fuer Delete eines normalen und eines aktiven `custom`-Eintrags sowie den Controller-Save-Pfad erweitert.
- Betroffene Test-Suites und Vollsuite erfolgreich ausgefuehrt.
- Review-Patch umgesetzt: lokaler UI-Delete erfolgt jetzt erst nach erfolgreichem Save-Response statt schon direkt bei Confirm.

### File List

- `_bmad-output/implementation-artifacts/2-5-custom-maintenance-arten-kontrolliert-loeschen.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `src/CustomMaintenanceBundle/Resources/install/admin_translations.csv`
- `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `tests/Unit/Controller/AdminpanelControllerTest.php`
- `tests/Unit/Service/MaintenanceConfigManagerTest.php`

### Change Log

- 2026-06-17: Story 2.5 erstellt und auf `ready-for-dev` gesetzt.
- 2026-06-17: Confirm-Delete fuer `custom` umgesetzt, Delete-all-Bug im Manager behoben und Story auf `review` gesetzt.
- 2026-06-17: Review-Patch fuer save-sicheren UI-Delete umgesetzt und Story auf `done` gesetzt.
