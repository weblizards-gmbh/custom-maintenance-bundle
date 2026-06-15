# Story 1.5: Opportunistische Pimcore-10-Deprecations im Basisschnitt mitbehandeln

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Maintainer,
I want konkrete Pimcore-10-Deprecation-Hinweise mit Handlungsanweisung im Rahmen der Basismodernisierung mitbehandeln,
so that vermeidbare spaetere Reibung Richtung Pimcore `11` reduziert wird, ohne den Scope aufzublasen.

## Acceptance Criteria

1. **Given** waehrend der Pimcore-`10.6.9`-Konsolidierung auftretende Deprecation-Hinweise  
   **When** diese konkrete Handlungsanweisungen fuer betroffene Stellen enthalten  
   **Then** duerfen sie im Rahmen derselben Basisarbeiten mit behoben werden.
2. **Given** unscharfe oder nur allgemein zukunftsbezogene Deprecation-Themen  
   **When** sie keinen klaren unmittelbaren Handlungsbedarf fuer die Pimcore-`10.6.9`-Faehigkeit ausloesen  
   **Then** werden sie nicht zum eigenen Umsetzungsblock in Epic 1 gemacht.

## Tasks / Subtasks

- [x] Verwendete Pimcore-APIs gegen den installierten Vendor-Stand auf konkrete Deprecation-Hinweise pruefen (AC: 1, 2)
  - [x] Nur Stellen mit expliziter Handlungsanweisung oder eindeutiger Pimcore-10-Deprecation fuer Story `1.5` zulassen.
  - [x] Unscharfe Legacy-Themen ohne konkreten Pimcore-10-Hinweis bewusst ausserhalb des Scopes lassen.
- [x] Belastbare Deprecation-Kandidaten minimalinvasiv beheben (AC: 1)
  - [x] `AdminpanelController` vom deprecated `Pimcore\Bundle\AdminBundle\Controller\AdminController` auf `Pimcore\Controller\UserAwareController` umstellen.
  - [x] Oeffentliche Routen und JSON-Verhalten des Controllers unveraendert lassen.
- [x] Die opportunistische Natur der Story wahren (AC: 2)
  - [x] Keine weiteren Brownfield-Umbauten nur wegen vermuteter kuenftiger Inkompatibilitaeten.
  - [x] Keine unscharfen Symfony-/Pimcore-Modernisierungen ohne belastbaren Vendor-Hinweis aufnehmen.
- [x] Regressionstest fuer die konkrete Deprecation-Korrektur ergaenzen (AC: 1)
  - [x] Absichern, dass `AdminpanelController` nicht mehr von der deprecated Basisklasse erbt.

### Review Findings

- [x] [Review][Patch] Die Umstellung auf `UserAwareController` bricht die Erfolgsmeldung in `saveAction()`, weil `trans()` nur auf `AdminController` existiert [src/CustomMaintenanceBundle/Controller/AdminpanelController.php:43]

## Dev Notes

### Story Intent

Story `1.5` ist bewusst opportunistisch. Sie soll keine allgemeine Zukunftshaertung erzwingen, sondern nur konkrete Pimcore-10-Deprecations mit klarer Handlungsanweisung mitnehmen, solange sie in die laufende Basismodernisierung passen.

### Current Deprecation Finding

- Im installierten Pimcore-`10.6`-Vendor-Stand ist `Pimcore\Bundle\AdminBundle\Controller\AdminController` explizit deprecated und mit einer klaren Migrationsanweisung versehen:
  - "Use `Pimcore\Controller\UserAwareController` instead."
- `src/CustomMaintenanceBundle/Controller/AdminpanelController.php` erbte bisher von genau dieser deprecated Klasse.

### Scope Decision

- Diese Story behebt nur den klar belegten `AdminController`-Fall.
- Keine weiteren Admin-UI-, Routing- oder Bundle-Modernisierungen werden ohne expliziten Vendor-Hinweis mitgezogen.
- Dinge wie ExtJS-Altstil, Annotation-Routing oder sonstige Legacy-Muster bleiben in Story `1.5` unberuehrt, solange kein konkreter Pimcore-10-Deprecation-Hinweis vorliegt.

### Must Preserve

- Admin-Routen unter `/admin/weblizards_custom_maintenance/...`
- JSON-Antwortverhalten des Controllers
- Bestehende Abhaengigkeit auf `MaintenanceConfigManager`
- Keine Aenderung an fachlicher Save-/Load-Logik

### References

- `_bmad-output/planning-artifacts/epics.md`
- `_bmad-output/planning-artifacts/architecture.md`
- `_bmad-output/project-context.md`
- `vendor/pimcore/pimcore/bundles/AdminBundle/Controller/AdminController.php`
- `vendor/pimcore/pimcore/lib/Controller/UserAwareController.php`
- `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `rg -n "@Route|Annotation|Controller|AbstractPimcoreBundle|BundleAdminClassicInterface|Tool\\\\Admin|getLanguages|deprecated" src tests`
- `sed -n '1,260p' vendor/pimcore/pimcore/bundles/AdminBundle/Controller/AdminController.php`
- `sed -n '1,240p' vendor/pimcore/pimcore/lib/Controller/UserAwareController.php`
- `php -l src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
- `php -l tests/Unit/Controller/AdminpanelControllerTest.php`
- `composer test`

### Completion Notes List

- Story-Datei fuer `1.5` aus Epic-, Architektur- und Vendor-Kontext erstellt.
- Konkrete Pimcore-10-Deprecations im verwendeten Bundle-Code gegen den installierten Vendor-Stand geprueft.
- Nur einen belastbaren Treffer mit klarer Handlungsanweisung aufgenommen: `AdminController` -> `UserAwareController`.
- `AdminpanelController` auf `Pimcore\Controller\UserAwareController` umgestellt, ohne Routing oder Response-Verhalten zu aendern.
- Regressionstest ergaenzt, der die Abkehr von der deprecated Basisklasse absichert.

### File List

- `_bmad-output/implementation-artifacts/1-5-opportunistische-pimcore-10-deprecations-im-basisschnitt-mitbehandeln.md`
- `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
- `tests/Unit/Controller/AdminpanelControllerTest.php`

### Change Log

- 2026-06-10: Story 1.5 umgesetzt. Konkrete Pimcore-10-Deprecation `AdminController` opportunistisch auf `UserAwareController` migriert.
- 2026-06-15: Re-Review ohne weitere Findings. Erfolgsfall von `saveAction()` per Unit-Test abgesichert.
