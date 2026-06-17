# Story 2.1: Maintenance-Arten aus der kanonischen Konfiguration im Admin-UI darstellen

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Administrator,
I want alle vorhandenen Maintenance-Arten im Admin-UI aus einer einheitlichen Konfigurationsquelle sehen,
so that ich native und benutzerdefinierte Eintraege zentral verwalten kann.

## Acceptance Criteria

1. **Given** eine geladene Bundle-Konfiguration  
   **When** das Admin-UI geoeffnet wird  
   **Then** werden native Pimcore-Maintenance und vorhandene Custom-Maintenance-Arten aus derselben kanonischen Konfigurationsquelle dargestellt  
   **And** die Darstellung basiert nicht auf einem separaten, UI-spezifischen Datenmodell.
2. **Given** Maintenance-Arten mit bestehenden Werten  
   **When** das UI die Daten laedt  
   **Then** sind relevante Felder fuer Verwaltung und Bearbeitung im UI verfuegbar  
   **And** die Werte entsprechen dem kanonischen Konfigurationsstand.

## Tasks / Subtasks

- [x] Admin-Ladepfad auf die kanonische Konfigurationssicht ausrichten (AC: 1, 2)
  - [x] Verifizieren, dass `AdminpanelController::loadAction()` ausschliesslich ueber `MaintenanceConfigManager` laedt und keine Rohpersistenz oder Parallelstruktur einzieht.
  - [x] Die von `MaintenanceConfigManager::getAdminData()` gelieferte Struktur gegen die Erwartungen des bestehenden Admin-UIs abgleichen.
  - [x] Sicherstellen, dass nativer `pimcore`-Eintrag und alle vorhandenen `custom`-Eintraege aus derselben fachlichen Quelle stammen.
- [x] Bestehendes Admin-UI fuer die Darstellung vorhandener Maintenance-Arten korrekt nutzen oder eng anpassen (AC: 1, 2)
  - [x] Das ExtJS-Panel fuer `frontend`, `pimcore` und dynamische `custom`-Tokens gegen den aktuellen Datenvertrag pruefen.
  - [x] Nur die fuer die Darstellung noetigen UI-Aenderungen vornehmen; keine Create/Edit/Delete-Logik vorziehen.
  - [x] Relevante Felder sichtbar halten: Beschreibung, Aktiv-/Fixed-Status, Zeitfenster, Hinweissteuerung, Dokument.
- [x] Sonderfaelle und Brownfield-Vertraege bewahren (AC: 1, 2)
  - [x] Den Sonderfall `pimcore` weiterhin als festen Eintrag darstellen.
  - [x] Keine stillen Strukturverluste oder Default-Uminterpretationen im Ladepfad einfuehren.
  - [x] Keine neue UI-spezifische Datenprojektion aufbauen, die von Laufzeitlogik und Persistenzmodell abweicht.
- [x] Verifikation fuer den Lade- und Darstellungsvertrag ergaenzen (AC: 1, 2)
  - [x] Backend-seitige PHPUnit-Absicherung fuer den Admin-Ladepfad oder die zugrunde liegende Datenbereitstellung ergaenzen.
  - [x] Falls UI-seitige Automatisierung unverhaeltnismaessig ist, die testbare Backend-Vertragsflaeche eng absichern und Restluecken explizit benennen.

### Review Findings

- [x] [Review][Patch] `loadAction()`-Test verifiziert nur warmen Service-Pfad und ist dadurch teilweise tautologisch [tests/Unit/Controller/AdminpanelControllerTest.php:25]
- [x] [Review][Patch] `getAdminData()`-Tests decken Legacy-Fallback, Default-Fallback und leere `custom`-Mengen noch nicht ab [tests/Unit/Service/MaintenanceConfigManagerTest.php:124]
- [x] [Review][Patch] Token-Assertions sind unnötig fragil bzw. zu stark an interne Reihenfolge und dieselbe Datenquelle gekoppelt [tests/Unit/Service/MaintenanceConfigManagerTest.php:182]
- [x] [Review][Patch] Admin-Payload-Test lässt UI-kritische Felder und Datums-/Zeitnester unvalidiert [tests/Unit/Controller/AdminpanelControllerTest.php:31]
- [x] [Review][Defer] Malformierte persistierte `custom`-Eintraege koennen `getAdminData()` bzw. den Admin-Load weiter hart abstuerzen lassen [src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php:83] — deferred, pre-existing
- [x] [Review][Defer] Reservierter Token `pimcore` wird in `custom` weiterhin nicht explizit abgewehrt [src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php:83] — deferred, pre-existing
- [x] [Review][Defer] Invalides UTF-8 in persistierten Textwerten kann den JSON-Admin-Load weiter mit 500 beenden [src/CustomMaintenanceBundle/Controller/AdminpanelController.php:26] — deferred, pre-existing

## Dev Notes

### Story Intent

Story `2.1` ist die erste Epic-2-Story und bewusst eine Darstellungsstory. Sie soll das bereits etablierte kanonische Konfigurationsmodell im bestehenden Admin-UI sichtbar machen, ohne schon die nachfolgenden Schreib- und Mutationsfaelle aus `2.2` bis `2.5` vorwegzunehmen.

### Epic Context

- Epic 1 hat die technische Basis fuer Pimcore `10.6.9`, das kanonische Domain-Modell und den zentralen Persistenzpfad geschaffen.
- Epic 2 nutzt diese Basis nun erstmals direkt im Admin-UI.
- Story `2.1` ist der Startpunkt fuer alle spaeteren UI-Operationen. Wenn hier ein Parallelmodell entsteht, wird der Rest des Epics fragil.

### Previous Story Intelligence

- `1.5` hat `AdminpanelController` auf `UserAwareController` gehoben und den Erfolgsfall von `saveAction()` explizit per Unit-Test abgesichert.
- Die Epic-1-Retrospektive zeigt ein wiederkehrendes Muster: Aenderungen waren fachlich meist richtig, aber Brownfield-Vertraege wurden erst im Review voll abgesichert.
- Wichtige Epic-1-Learnings fuer `2.1`:
  - Admin-Vertraege explizit verifizieren, nicht implizit annehmen.
  - Keine UI-spezifische Parallelstruktur neben dem kanonischen Modell aufbauen.
  - Doku-/Code- und Installer-/Runtime-Drift frueh vermeiden.

### Current State Analysis

- `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
  - `loadAction()` liefert derzeit direkt `MaintenanceConfigManager::getAdminData()` als JSON aus.
  - Das ist fuer `2.1` der zentrale Backend-Vertrag und soll nicht wieder aufgeweicht werden.
- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - `getConfigSet()` baut die fachliche Sicht aus Settings Store, Legacy-Fallback oder Default.
  - `getAdminData()` serialisiert diese Sicht aktuell wieder in die bestehende Legacy-aehnliche Array-Struktur und liefert `tokens` fuer dynamische Custom-Eintraege.
  - Damit existiert bereits der bevorzugte Ladepfad fuer `2.1`.
- `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
  - Das bestehende ExtJS-Panel erwartet:
    - `data["frontend"]`
    - `data["pimcore"]`
    - `data["custom"][token]`
    - `data["tokens"]` fuer die dynamische Iteration
  - Das Panel rendert bereits feste Felder fuer `frontend` und `pimcore` sowie ein dynamisches Fieldset je Custom-Token.
  - Fuer `2.1` ist entscheidend, dass diese Struktur aus der kanonischen Konfigurationsquelle kommt und inhaltlich korrekt bleibt.
- `tests/Unit/Controller/AdminpanelControllerTest.php`
  - Deckt aktuell Vererbung und `saveAction()`-Erfolgsfall ab.
  - Der Ladepfad ist noch nicht explizit getestet.
- `tests/Unit/Service/MaintenanceConfigManagerTest.php`
  - Deckt bereits den Kern von Read-Prioritaet, Default-Fallback und Persistenzverhalten ab.
  - Diese Basis ist der naheliegende Ort fuer weitere Absicherung des Admin-Datenvertrags.

### Must Preserve

- Admin-Routen unter `/admin/weblizards_custom_maintenance/...`
- Bestehendes ExtJS-/Classic-Admin-UI-Modell
- Sonderfall `pimcore` als permanenter, nativer Eintrag
- Daten- und Feldformate:
  - Datum `d.m.Y`
  - Zeit `H:i`
- Zentrale Konfigurationsquelle ueber `MaintenanceConfigManager`
- Dynamischer UI-Aufbau ueber `tokens` fuer Custom-Maintenance-Arten

### Must Change in Story 2.1

- Der Story-Fokus muss explizit auf die konsistente Darstellung aus der kanonischen Konfigurationssicht gezogen werden.
- Der Ladevertrag zwischen `MaintenanceConfigManager` und `AdminPanel.js` muss so eng abgesichert werden, dass spaetere UI-Stories nicht auf impliziten Annahmen aufbauen.
- Falls das aktuelle Admin-UI noch Datenannahmen aus einem Altpfad transportiert, muessen diese in `2.1` minimalinvasiv an die kanonische Sicht angeglichen werden.

### Explicitly Out of Scope

- Keine neue Custom-Maintenance-Art anlegen
- Keine Bearbeitungs- oder Speichererweiterungen ueber den bestehenden Vertrag hinaus
- Keine Delete-Logik
- Keine Schutzlogik fuer `pimcore` beim Loeschen; das gehoert in `2.4`
- Keine neue REST- oder sonstige externe API
- Kein Redesign des Admin-UIs oder Wechsel weg von ExtJS/Classic

### Architecture Compliance

- Story `2.1` muss die Architekturentscheidung aus Epic 1 respektieren:
  - ein gemeinsames kanonisches Modell
  - duenne Controller
  - keine fachliche Logik in UI oder Controller
- Das UI darf Daten lesen, aber kein eigenes zweites Fachmodell erfinden.
- `MaintenanceConfigManager` bleibt die fachliche Zugriffsstelle fuer Admin-Loads.
- Persistenzdetails bleiben ausserhalb von Controller und JS.

### Technical Requirements

- PHP `>=8.1`, Pimcore `10.6.9`, Symfony Service Container ueber YAML.
- Controller bleiben Annotation-basiert und via `controller.service_arguments` verdrahtet.
- `AdminpanelController::loadAction()` soll weiter einen leichten JSON-Vertrag liefern.
- `MaintenanceConfigManager::getAdminData()` ist die bevorzugte Vertragsflaeche fuer die Story.
- Legacy-Felder duerfen im Ladepfad nicht verloren gehen, wenn sie fuer spaetere Storys oder bestehende Installationen relevant sind.
- Keine Aenderung an Command-, Twig- oder Laufzeit-API-Signaturen.

### Library / Framework Requirements

- ExtJS-/Pimcore-Classic-Admin-Struktur beibehalten.
- Keine neuen Frontend-Frameworks oder Build-Schritte einfuehren.
- Tests weiterhin ueber PHPUnit `^10.5`.
- Der Story-Scope braucht keine externe Web-Recherche; der relevante Stack ist durch Repo, Vendor-Stand und Architektur bereits festgelegt.

### File Structure Requirements

- Wahrscheinlich zu lesende oder anzufassende UPDATE-Dateien:
  - `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
  - `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
  - `tests/Unit/Controller/AdminpanelControllerTest.php`
  - `tests/Unit/Service/MaintenanceConfigManagerTest.php`
- Moegliche weitere betroffene Dateien nur bei echtem Bedarf:
  - `src/CustomMaintenanceBundle/Resources/config/services.yml`
  - Admin-bezogene Resource-Dateien fuer Uebersetzungen oder Panel-Registrierung
- Keine neue Verzeichnisachse fuer UI-Datenmodelle oder Admin-Adapter einziehen, solange der bestehende Vertrag tragfaehig bleibt.

### Testing Requirements

- Erwartete Absicherung fuer `2.1`:
  - Test fuer den Admin-Ladepfad oder fuer die von `getAdminData()` gelieferte Datenstruktur
  - Verifikation, dass `tokens` mit den `custom`-Eintraegen konsistent sind
  - Verifikation, dass `pimcore` und vorhandene Custom-Eintraege aus derselben Quelle stammen
- Wenn nur Backend-Tests wirtschaftlich sind, muss das explizit als bewusste Testgrenze dokumentiert werden.
- Bestehende Tests aus Epic 1 muessen grün bleiben.

### Risks To Watch

- Ein versehentliches UI-Parallelmodell, das spaeter von Laufzeit- oder Persistenzlogik abweicht
- Verdeckte Annahmen im alten ExtJS-Panel ueber Feldnamen oder Strukturen
- Verlust des Sonderfalls `pimcore` im Versuch, alles gleichfoermig zu rendern
- Spae tere Epic-2-Stories werden erschwert, wenn `2.1` die Darstellung nicht sauber an die zentrale Quelle bindet

### Git Intelligence Summary

- Die juengsten Commits zeigen eine klare Linie: Pimcore-10-Basis konsolidieren, Migration und Tests erweitern, Story-Review-Findings sauber nachziehen.
- Fuer `2.1` ist deshalb kein grosser Architekturwurf angemessen, sondern ein enger, verifizierter UI-Anschluss an die bereits vorhandene Konfigurationsbasis.

### Project Context Reference

- `_bmad-output/project-context.md`
- `_bmad-output/planning-artifacts/epics.md`
- `_bmad-output/planning-artifacts/architecture.md`
- `_bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md`
- `_bmad-output/implementation-artifacts/epic-1-retro-2026-06-15.md`
- `_bmad-output/implementation-artifacts/1-5-opportunistische-pimcore-10-deprecations-im-basisschnitt-mitbehandeln.md`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `sed -n '1,260p' src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
- `sed -n '1,360p' src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `sed -n '1,520p' src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
- `sed -n '1,220p' tests/Unit/Controller/AdminpanelControllerTest.php`
- `sed -n '1,260p' tests/Unit/Service/MaintenanceConfigManagerTest.php`
- `sed -n '206,280p' _bmad-output/planning-artifacts/epics.md`
- `sed -n '1,220p' _bmad-output/implementation-artifacts/sprint-status.yaml`
- `git log --oneline -5`
- `vendor/bin/phpunit tests/Unit/Controller/AdminpanelControllerTest.php tests/Unit/Service/MaintenanceConfigManagerTest.php`
- `vendor/bin/phpunit`

### Completion Notes List

- Story-Datei fuer `2.1` aus Epic-, Architektur-, Projektkontext- und aktuellem Codepfad erstellt.
- Den Fokus explizit auf die Darstellung aus einer gemeinsamen kanonischen Konfigurationsquelle gelegt.
- Das bestehende ExtJS-Admin-Panel als Brownfield-Ausgangspunkt analysiert und gegen den neuen Konfigurationspfad eingeordnet.
- Review- und Retrospektiv-Learnings aus Epic 1 als Guardrails fuer die Implementierung aufgenommen.
- Ultimate context engine analysis completed - comprehensive developer guide created.
- `AdminpanelController::loadAction()` per PHPUnit gegen den von `MaintenanceConfigManager::getAdminData()` gelieferten JSON-Vertrag abgesichert.
- `MaintenanceConfigManager::getAdminData()` fuer `pimcore`, `custom` und `tokens` als gemeinsame kanonische Admin-Datenquelle explizit verifiziert.
- Bewusst keine UI-Automatisierung hinzugefuegt, weil der Story-Scope mit eng abgesicherten Backend-Vertrags-Tests wirtschaftlich und stabiler abgedeckt ist.
- Vollstaendige PHPUnit-Suite nach den Aenderungen erfolgreich ausgefuehrt.

### File List

- `_bmad-output/implementation-artifacts/2-1-maintenance-arten-aus-der-kanonischen-konfiguration-im-admin-ui-darstellen.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `tests/Unit/Controller/AdminpanelControllerTest.php`
- `tests/Unit/Service/MaintenanceConfigManagerTest.php`

### Change Log

- 2026-06-15: Story 2.1 erstellt und auf `ready-for-dev` gesetzt.
- 2026-06-17: Backend-Vertrags-Tests fuer Admin-Ladepfad und kanonische Admin-Datenstruktur ergaenzt; Story auf `review` gesetzt.
