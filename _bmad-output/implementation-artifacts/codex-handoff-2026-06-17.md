# Codex Handoff 2026-06-17

Status: active

## BMAD-Stand

- Epic 1 ist abgeschlossen.
- Die Epic-1-Retrospektive existiert unter `_bmad-output/implementation-artifacts/epic-1-retro-2026-06-15.md`.
- Epic 2 ist gestartet.
- Story `2.1` ist erstellt und steht auf `ready-for-dev`.

## Relevante Dateien

- Sprint-Tracking:
  - `_bmad-output/implementation-artifacts/sprint-status.yaml`
- Naechste Story:
  - `_bmad-output/implementation-artifacts/2-1-maintenance-arten-aus-der-kanonischen-konfiguration-im-admin-ui-darstellen.md`
- Epic-1-Retro:
  - `_bmad-output/implementation-artifacts/epic-1-retro-2026-06-15.md`
- Architektur-/Planungsquellen:
  - `_bmad-output/planning-artifacts/epics.md`
  - `_bmad-output/planning-artifacts/architecture.md`
  - `_bmad-output/project-context.md`

## Inhaltlicher Stand

- `2.1` ist bewusst als Darstellungsstory geschnitten:
  - Admin-UI soll vorhandene Maintenance-Arten aus der kanonischen Konfigurationsquelle anzeigen.
  - Kein UI-Parallelmodell neben `MaintenanceConfigManager`.
  - Sonderfall `pimcore` muss erhalten bleiben.
- Die Story-Datei verweist als zentrale Vertragsflaechen auf:
  - `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
  - `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`

## Workspace-Zustand beim Neustart

- Nicht committeter Stand im Repo:
  - `M _bmad-output/implementation-artifacts/sprint-status.yaml`
  - `?? _bmad-output/implementation-artifacts/2-1-maintenance-arten-aus-der-kanonischen-konfiguration-im-admin-ui-darstellen.md`
- Dieser Zustand ist gewollt und repraesentiert die Vorbereitung von Epic 2 / Story `2.1`.

## Naechster sinnvoller Schritt

- `dev-story` fuer `2.1` ausfuehren.
- Implementierungsfokus:
  - Ladevertrag zwischen `loadAction()` und `getAdminData()` explizit absichern
  - bestehendes ExtJS-Panel nur minimalinvasiv anpassen
  - Backend-Tests fuer den Admin-Ladepfad bzw. die Admin-Datenstruktur ergaenzen

## Kurzprompt fuer neuen Codex-Start

Arbeite im BMAD-Prozess weiter. Lies zuerst:
1. `_bmad-output/implementation-artifacts/codex-handoff-2026-06-17.md`
2. `_bmad-output/implementation-artifacts/2-1-maintenance-arten-aus-der-kanonischen-konfiguration-im-admin-ui-darstellen.md`
3. `_bmad-output/implementation-artifacts/sprint-status.yaml`

Dann implementiere Story `2.1`.
