# Story 2.4: Native Pimcore-Maintenance im UI als geschuetzten Sonderfall behandeln

Status: review

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Administrator,
I want die native Pimcore-Maintenance im UI verwalten, aber nicht loeschen koennen,
so that der zentrale Sonderfall des Bundles technisch geschuetzt bleibt.

## Acceptance Criteria

1. **Given** die native Pimcore-Maintenance im Admin-UI  
   **When** ich ihre Darstellung oder verfuegbare Aktionen sehe  
   **Then** ist sie als besonderer, permanenter Eintrag erkennbar  
   **And** es wird keine Loeschaktion angeboten oder serverseitig zugelassen.
2. **Given** ein Loeschversuch gegen die native Pimcore-Maintenance  
   **When** dieser technisch ausgeloest wird  
   **Then** wird er geblockt  
   **And** der Sonderfall bleibt auch ausserhalb der UI geschuetzt.

## Tasks / Subtasks

- [x] Pimcore-Sonderfall im bestehenden Admin-UI explizit markieren (AC: 1)
  - [x] Bestehenden Pimcore-Fieldset-Titel oder Hinweistext so erweitern, dass Permanenz/Schutz sichtbar wird.
  - [x] Keine Loeschaktion fuer den Pimcore-Eintrag einfuehren oder anzeigen.
- [x] Serverseitigen Schutz fuer den nativen Pimcore-Eintrag explizit haerten (AC: 1, 2)
  - [x] Zentralen Save-Pfad gegen manipulierte Delete-/Entfernungsversuche fuer `pimcore` absichern.
  - [x] Bestehenden Sonderfall `pimcore` weiterhin getrennt von `custom` behandeln.
- [x] Regressionen mit PHPUnit absichern (AC: 1, 2)
  - [x] Admin-Payload-/Controller-Tests fuer den sichtbaren Pimcore-Sonderfall ergaenzen.
  - [x] Service-Tests fuer serverseitig geblockten Pimcore-Delete-Versuch ergaenzen.

## Dev Notes

### Story Intent

Story `2.4` zieht den in PRD und Epic beschriebenen Sonderfall der nativen Pimcore-Maintenance explizit nach. Der Eintrag soll im UI klar als permanent geschuetzt erkennbar sein, ohne bereits die allgemeine Delete-Story `2.5` vorwegzunehmen.

### Epic Context

- `2.1` hat den kanonischen Admin-Ladevertrag abgesichert.
- `2.2` hat Create-Flow und zentrale Token-Validierung fuer `custom` eingefuehrt.
- `2.3` hat Edit-/Rename-Faelle fuer `custom` ueber denselben Save-Pfad erweitert.
- `2.4` betrifft ausschliesslich den nativen Sonderfall `pimcore`; kontrolliertes Loeschen von `custom` folgt erst in `2.5`.

### Previous Story Intelligence

- `custom_tokens` beschreibt weiterhin nur `custom`-Eintraege; `pimcore` lebt separat im Payload.
- Der zentrale Save-Pfad in `MaintenanceConfigManager` persistiert `frontend`, `pimcore` und `custom` gemeinsam, aber strukturell getrennt.
- Reserviertes `pimcore` fuer neue oder umbenannte `custom`-Tokens wird bereits serverseitig abgewehrt.

### Current State Analysis

- `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
  - Hat bereits ein separates Pimcore-Fieldset.
  - Bietet aktuell noch keine Delete-Buttons fuer irgend einen Eintrag.
  - Muss fuer `2.4` nur kenntlich machen, dass der Pimcore-Eintrag ein permanenter Sonderfall ist.
- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - Verwaltet `pimcore` bereits separat von `custom`.
  - Hat noch keine explizite Guard gegen manipulierte Delete-Flags fuer `pimcore`.
- `tests/Unit/Controller/AdminpanelControllerTest.php`
  - Deckt Laden/Speichern des zentralen Payloads ab.
- `tests/Unit/Service/MaintenanceConfigManagerTest.php`
  - Deckt bereits Reserved-Token- und Persistenzverhalten ab und ist der richtige Ort fuer harte Sonderfall-Guards.

### Must Preserve

- Klassisches Pimcore-Admin-UI auf ExtJS-Basis
- Admin-Routen unter `/admin/weblizards_custom_maintenance/...`
- Zentrale Persistenz ueber `MaintenanceConfigManager`
- Legacy-Shape fuer `frontend`, `pimcore`, `custom`
- Keine Delete-UI fuer `pimcore`

### Must Change in Story 2.4

- Pimcore-Maintenance muss im UI explizit als geschuetzter/permanenter Eintrag erkennbar sein.
- Serverseitig muss ein technischer Delete-Versuch gegen `pimcore` explizit geblockt werden.

### Explicitly Out of Scope

- Kein allgemeiner Delete-Flow fuer `custom`
- Keine neue API oder neue Persistenzachse
- Keine Veraenderung des Token-Vertrags fuer `custom` ueber den bereits eingefuehrten Stand hinaus

### Architecture Compliance

- UI-Hinweise bleiben rein darstellerisch; Schutzlogik gehoert in den zentralen Manager.
- Kein zweites Modell oder UI-Spezialpfad fuer den Pimcore-Sonderfall.

### Technical Requirements

- PHP `>=8.1`, Pimcore `10.6.9`, ExtJS-basierte Admin-Integration
- Wenn ein technischer Delete-Versuch fuer `pimcore` ueber den bestehenden Save-Pfad injiziert wird, muss dieser mit Fehler abbrechen.

### Testing Requirements

- Erwartete Tests:
  - Admin-Daten/Controller enthalten weiterhin separaten `pimcore`-Block und nur `custom` in `tokens`
  - Manipulierter Save-Payload mit Pimcore-Delete-Versuch wird serverseitig abgewiesen
  - UI-relevanter Schutzhinweis ist im gelieferten Panel/Vertrag nachvollziehbar abgesichert, ohne Delete-Action einzufuehren

### Risks To Watch

- Versehentlich schon Delete-Mechanik fuer `custom` oder `pimcore` vorziehen
- Schutz nur im UI markieren, aber nicht im zentralen Save-Pfad haerten
- `pimcore` indirekt als `custom` behandelbar machen

### Git Intelligence Summary

- Epic 2 wurde bisher in kleinen, testgetriebenen Brownfield-Schritten umgesetzt.
- `2.4` soll diesem Muster folgen: minimale UI-Aenderung, explizite Guard im Manager, enge PHPUnit-Absicherung.

### Project Context Reference

- `_bmad-output/project-context.md`
- `_bmad-output/planning-artifacts/epics.md`
- `_bmad-output/planning-artifacts/architecture.md`
- `_bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md`
- `_bmad-output/implementation-artifacts/2-3-bestehende-maintenance-art-bearbeiten-und-token-speichern.md`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `sed -n '1,140p' _bmad-output/implementation-artifacts/sprint-status.yaml`
- `sed -n '220,280p' _bmad-output/planning-artifacts/epics.md`
- `sed -n '220,250p' _bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md`
- `sed -n '1,460p' src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
- `sed -n '1,360p' src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `sed -n '1,220p' src/CustomMaintenanceBundle/Resources/install/admin_translations.csv`

### Completion Notes List

- Story `2.4` als kleiner Brownfield-Schritt zwischen Rename (`2.3`) und Delete (`2.5`) umgesetzt.
- Pimcore-Fieldset im ExtJS-Admin-UI explizit als geschuetzter, permanenter Sondereintrag markiert.
- Zentralen Save-Pfad in `MaintenanceConfigManager` um harte Blockade fuer manipuliertes `pimcore_delete` erweitert.
- Keine Delete-UI oder neue API vorgezogen; `custom_tokens` bleibt ausschliesslich fuer `custom` zustaendig.
- PHPUnit fuer technischen Pimcore-Delete-Versuch im Service und Controller erweitert.
- Betroffene Test-Suites und Vollsuite erfolgreich ausgefuehrt.

### File List

- `_bmad-output/implementation-artifacts/2-4-native-pimcore-maintenance-im-ui-als-geschuetzten-sonderfall-behandeln.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `src/CustomMaintenanceBundle/Resources/install/admin_translations.csv`
- `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `tests/Unit/Controller/AdminpanelControllerTest.php`
- `tests/Unit/Service/MaintenanceConfigManagerTest.php`

### Change Log

- 2026-06-17: Story 2.4 erstellt und auf `ready-for-dev` gesetzt.
- 2026-06-17: Pimcore-Sonderfall im UI markiert, serverseitige Delete-Blockade ergaenzt und Story auf `review` gesetzt.
