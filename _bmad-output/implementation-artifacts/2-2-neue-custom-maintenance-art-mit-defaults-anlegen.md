# Story 2.2: Neue Custom-Maintenance-Art mit Defaults anlegen

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Administrator,
I want im Admin-UI eine neue Custom-Maintenance-Art mit sinnvollen Defaults anlegen,
so that ich neue fachliche Stoerungs- oder Wartungstypen ohne Dateiedits verwalten kann.

## Acceptance Criteria

1. **Given** das Admin-UI fuer Maintenance-Verwaltung  
   **When** ich eine neue Custom-Maintenance-Art anlege  
   **Then** steht mir ein Pflichtfeld fuer den technischen Token zur Verfuegung  
   **And** der Token akzeptiert nur alphanumerische Zeichen.
2. **Given** eine neu angelegte Custom-Maintenance-Art  
   **When** sie initial erzeugt wird  
   **Then** werden sinnvolle Defaults gesetzt  
   **And** `active` ist standardmaessig deaktiviert  
   **And** `show_info` ist standardmaessig deaktiviert  
   **And** Datums- und Zeitfelder sind initial leer bzw. `null`.

## Tasks / Subtasks

- [x] UI-Einstieg fuer neue Custom-Maintenance-Art minimalinvasiv ergaenzen (AC: 1, 2)
  - [x] Im bestehenden ExtJS-Admin-Panel eine klar erkennbare Aktion zum Anlegen einer neuen Custom-Maintenance-Art vorsehen.
  - [x] Ein Pflichtfeld fuer den technischen Token vorsehen; keine Create/Edit/Delete-Gesamtneugestaltung des Panels starten.
  - [x] Fuer neu angelegte Eintraege die relevanten Felder im bestehenden Panel-Schema sichtbar machen: Beschreibung, Aktiv-/Fixed-Status, Zeitfenster, Hinweissteuerung, Dokument.
- [x] Token-Vertrag zentral und Brownfield-tauglich absichern (AC: 1)
  - [x] Token-Validierung nicht nur im UI, sondern in der zentralen fachlichen Schicht absichern.
  - [x] Nur alphanumerische Tokens akzeptieren; leere oder ungueltige Werte mit nachvollziehbarer Fehlerrueckmeldung ablehnen.
  - [x] Kollisionen mit vorhandenen Tokens und dem reservierten Sonderfall `pimcore` kontrolliert behandeln.
- [x] Erzeugung und Persistenz neuer Custom-Eintraege ueber die kanonische Konfigurationsbasis einfuehren (AC: 2)
  - [x] Den bestehenden Admin-Schreibpfad so erweitern, dass neue `custom`-Eintraege ueber `MaintenanceConfigManager` entstehen und in den Settings Store persistieren.
  - [x] Fuer neue Eintraege sinnvolle Defaults setzen: `active = false`, `fixed = false`, `show_info = never`, Beschreibung initial sinnvoll/leer gemaess UI-Fluss.
  - [x] Datums- und Zeitfelder fuer neue Eintraege initial leer halten; keine stillen 1970-/Installer-Placeholders als Neu-Anlage-Default verwenden.
- [x] Brownfield-Vertraege und Folgestories vorbereiten, ohne sie vorwegzunehmen (AC: 1, 2)
  - [x] Bestehende Darstellung und Bearbeitung vorhandener Eintraege aus Story `2.1` nicht brechen.
  - [x] Keine Loeschlogik aus `2.5` und keine Sonderfall-UI fuer `pimcore` aus `2.4` vorziehen.
  - [x] Keine neue UI-spezifische Parallelstruktur oder separate Persistenzachse fuer Create-Vorgaenge einfuehren.
- [x] Verifikation fuer Create- und Default-Vertrag ergaenzen (AC: 1, 2)
  - [x] Backend-seitige PHPUnit-Absicherung fuer Token-Validierung, Default-Setzung und Persistenz neuer `custom`-Eintraege ergaenzen.
  - [x] Controller-/Admin-Pfad gezielt absichern, falls die Create-Interaktion ueber bestehenden Save- oder neuen Endpunkt laeuft.
  - [x] Falls UI-seitige Automatisierung unwirtschaftlich bleibt, testbare Backend-Vertragsflaeche inklusive Fehlerfaellen eng absichern und Restluecken explizit benennen.

### Review Findings

- [x] [Review][Decision] Brownfield-Policy fuer Legacy-Tokens ausserhalb des neuen Vertrags festlegen — entschieden: bestehende Legacy-Tokens werden grandfathered; strikte Token-Regeln gelten nur fuer neue Eintraege.
- [x] [Review][Patch] Leere Datums-/Zeit-Defaults brechen Runtime-Consumer [src/CustomMaintenanceBundle/Domain/Model/MaintenanceDateTime.php:29]
- [x] [Review][Patch] Neue Admin-Translations haben keinen abgesicherten Upgrade-Importpfad [src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js:36]
- [x] [Review][Patch] Create-Fehlerpfade sind nicht ausreichend per PHPUnit abgesichert [tests/Unit/Controller/AdminpanelControllerTest.php:237]

## Dev Notes

### Story Intent

Story `2.2` fuehrt erstmals echten Schreibumfang in Epic 2 ein: neue Custom-Maintenance-Arten sollen aus dem bestehenden Admin-UI heraus entstehen koennen. Ziel ist kein neues CRUD-System, sondern ein kontrollierter Erweiterungsschritt auf Basis des bereits in `2.1` abgesicherten Lade- und Persistenzvertrags.

### Epic Context

- Epic 2 baut auf dem in Epic 1 eingefuehrten kanonischen Konfigurationsmodell und dem zentralen Settings-Store-Pfad auf.
- `2.1` hat Darstellung und Ladevertrag fuer vorhandene Eintraege abgesichert.
- `2.2` ist damit die erste Story, die neue `custom`-Eintraege in denselben fachlichen Datenpfad hineinschreibt.
- Fehler hier propagieren direkt in die Folgestories `2.3` bis `2.5`, weil Bearbeiten, Sonderfallbehandlung und Loeschen denselben Datenvertrag weiterverwenden.

### Previous Story Intelligence

- `2.1` hat den Ladevertrag `AdminpanelController::loadAction()` -> `MaintenanceConfigManager::getAdminData()` explizit verifiziert.
- Die Code-Review-Follow-ups aus `2.1` zeigen erneut das Brownfield-Muster:
  - Vertraege explizit testen, nicht implizit annehmen.
  - Token-/Datums-/Zeitfelder gezielt absichern, weil dort Legacy-Shape und UI eng gekoppelt sind.
  - Fragile oder rein selbstreferenzielle Tests vermeiden; Fehlerpfade und Fallbacks mitdenken.
- Fuer `2.2` folgt daraus:
  - Create-Logik muss ueber denselben zentralen Manager laufen wie bestehende Reads/Writes.
  - Default-Werte fuer neue Eintraege duerfen nicht mit globalen Fallback-Defaults verwechselt werden.
  - Token-Validierung braucht eine fachliche Quelle der Wahrheit, nicht nur UI-Regeln.

### Current State Analysis

- `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
  - Es existieren aktuell nur `/load` und `/save`.
  - `saveAction()` delegiert bereits an `MaintenanceConfigManager::saveFromAdminPayload()` und ist damit der naheliegende bestehende Schreibpfad.
  - Fuer `2.2` muss entschieden werden, ob die Neuanlage in denselben Payload integriert wird oder ob ein minimaler Zusatz-Endpunkt noetig ist. Ohne Not sollte kein neuer API-Stil aufgemacht werden.
- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - `saveFromAdminPayload()` aktualisiert heute ausschliesslich bereits vorhandene `custom`-Tokens via `foreach (array_keys($data['custom']))`.
  - Es gibt noch keine fachliche Operation fuer das Anlegen eines neuen `custom`-Eintrags.
  - `getCurrentRawData()` und `persistLegacyData()` sind bereits der zentrale Persistenzpfad.
  - `DEFAULT_PIMCORE`/`DEFAULT_FRONTEND` sowie `normalizeData()` beschreiben globale Fallbacks fuer fehlende Konfiguration, nicht notwendigerweise sinnvolle Defaults fuer neu erzeugte `custom`-Eintraege.
  - `convertJsDateTime()` setzt parsebare Datums-/Zeitstrings voraus; leere Eingaben fuer neue Eintraege muessen deshalb bewusst behandelt werden.
- `src/CustomMaintenanceBundle/Domain/Model/MaintenanceToken.php`
  - Validiert aktuell nur `trim($value) !== ''`.
  - Die Story fordert zusaetzlich alphanumerische Tokens; diese Regel ist hier oder in einer fachlich gleich zentralen Stelle naheliegend.
  - Der reservierte Sonderfall `pimcore` ist fachlich bereits relevant und darf fuer neue `custom`-Tokens nicht kollidieren.
- `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
  - Rendert derzeit nur vorhandene Tokens aus `data["tokens"]`.
  - Besitzt keine Add-Aktion, kein Token-Eingabefeld und keinen Neuanlage-Flow.
  - Erwartet fuer bestehende `custom`-Eintraege weiterhin Felder wie `description`, `active`, `fixed`, `show_info`, `show_info_from`, `planned`, `document`.
  - Das Panel bleibt Brownfield-Ausgangspunkt; die Story soll es erweitern, nicht ersetzen.
- `src/CustomMaintenanceBundle/Resources/install/custommaintenance.php`
  - Zeigt die bestehende Legacy-Shape fuer `custom`-Eintraege.
  - Enthaltene Datumswerte sind Beispiel-/Installer-Werte und duerfen nicht automatisch zu Neu-Anlage-Defaults im Admin-UI werden.
- `src/CustomMaintenanceBundle/Resources/install/admin_translations.csv`
  - Enthaelt bestehende Texte fuer Anzeige-/Save-Felder.
  - Fuer Add-Button, Token-Feld und moegliche Fehlermeldungen koennen neue Uebersetzungseintraege noetig werden.
- `tests/Unit/Controller/AdminpanelControllerTest.php`
  - Deckt derzeit `loadAction()` und Save-Erfolgsfall ab.
  - Noch keine Create-bezogene Controller-Absicherung.
- `tests/Unit/Service/MaintenanceConfigManagerTest.php`
  - Deckt Lesen, Fallbacks, Persistenz bestehender Eintraege und Statuswechsel ab.
  - Bester Ankerpunkt fuer neue Tests zu Token-Validierung, Default-Erzeugung und Persistenz neuer `custom`-Eintraege.

### Must Preserve

- Admin-Routen unter `/admin/weblizards_custom_maintenance/...`
- ExtJS-/Classic-Admin-UI als bestehendes Shell-Modell
- Zentrale Konfigurationsquelle ueber `MaintenanceConfigManager`
- Rolling-Migration und schreibender Settings-Store-Pfad aus Epic 1
- Sonderfall `pimcore` als nativer, reservierter Eintrag
- Daten- und Feldformate im Bundle:
  - Datum `d.m.Y`
  - Zeit `H:i`
- Bestehende Struktur vorhandener `custom`-Eintraege, damit `2.1` und Folgestories nicht brechen

### Must Change in Story 2.2

- Ein neuer `custom`-Eintrag muss ueber das Admin-UI anstossbar sein.
- Token-Validierung fuer Neuanlagen muss fachlich auf alphanumerische Werte verengt werden.
- Neue `custom`-Eintraege muessen mit klaren Defaults in dieselbe kanonische Konfigurationsstruktur geschrieben werden wie bestehende Eintraege.
- Leere Datums-/Zeitfelder fuer neue Eintraege muessen bewusst modelliert werden; bestehende globale Fallback-Placeholders duerfen nicht ungeprueft hineinlaufen.

### Explicitly Out of Scope

- Kein vollstaendiges Bearbeiten aller spaeteren Feldmutationen ueber den Neuanlage-Flow hinaus; das ist Fokus von `2.3`
- Keine Delete-Logik
- Keine Schutz- oder Delete-Sonderfall-UI fuer `pimcore`; das ist Fokus von `2.4`
- Kein Refactor des gesamten Admin-Panels
- Keine neue externe REST-/GraphQL-API
- Kein Wechsel weg von ExtJS/Classic

### Architecture Compliance

- Ein gemeinsames kanonisches Modell bleibt Pflicht; keine zweite Create-spezifische Datenstruktur nur fuer UI oder Controller.
- Controller bleiben duenn; fachliche Create-/Validierungslogik gehoert in `MaintenanceConfigManager` oder gleich zentrale Domain-/Service-Pfade.
- Persistenzdetails bleiben ausserhalb von Controller und JS.
- Create-, Read- und spaetere Update-/Delete-Faelle muessen auf dieselbe Konfigurationsquelle und dasselbe Persistenzmodell zeigen.

### Technical Requirements

- PHP `>=8.1`, Pimcore `10.6.9`, Symfony Service Container via YAML.
- Annotation-basierte Controller und `controller.service_arguments` beibehalten.
- Bevorzuge Erweiterung des bestehenden Admin-Schreibpfads statt Einfuehrung einer neuen Architekturachse.
- Neue Token duerfen nicht leer sein; Story verlangt alphanumerische Einschränkung.
- Kollisionen mit bestehenden `custom`-Tokens und dem reservierten Token `pimcore` muessen fachlich abgefangen werden, auch wenn das UI die erste Schranke bildet.
- Neue Eintraege muessen in die Legacy-aehnliche Array-Shape geschrieben werden, die das aktuelle Panel und die Laufzeitlogik erwarten.
- Datums-/Zeitfelder fuer neue Eintraege brauchen einen klaren Leerwert-Pfad; bestehendes `convertJsDateTime()` ist dafuer heute nicht ausreichend robust.
- Keine Aenderung an CLI-, Twig- oder Laufzeit-API-Signaturen ohne echten Zwang.

### Library / Framework Requirements

- ExtJS-/Pimcore-Classic-Admin-Struktur beibehalten.
- Keine neuen Frontend-Frameworks, Build-Schritte oder Validierungsbibliotheken einfuehren.
- PHPUnit `^10.5` fuer neue Tests nutzen.
- Externe Web-Recherche ist fuer diese Story nicht notwendig; relevanter Stack und Brownfield-Randbedingungen liegen im Repo vor.

### File Structure Requirements

- Wahrscheinlich betroffene UPDATE-Dateien:
  - `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
  - `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - `src/CustomMaintenanceBundle/Domain/Model/MaintenanceToken.php`
  - `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
  - `src/CustomMaintenanceBundle/Resources/install/admin_translations.csv`
  - `tests/Unit/Controller/AdminpanelControllerTest.php`
  - `tests/Unit/Service/MaintenanceConfigManagerTest.php`
- Moegliche weitere betroffene Dateien nur bei echtem Bedarf:
  - `tests/Unit/Domain/Model/MaintenanceEntryTest.php`
  - `src/CustomMaintenanceBundle/Resources/config/services.yml`
- Keine neue Verzeichnisachse fuer Admin-Create-Adapter oder zweite Konfigurationsmodelle einfuehren.

### Testing Requirements

- Erwartete Absicherung fuer `2.2`:
  - Test fuer Token-Validierung inkl. leer/unzulaessig/reserviert/dupliziert
  - Test fuer Persistenz eines neuen `custom`-Eintrags ueber den kanonischen Manager-Pfad
  - Test fuer Default-Werte neuer Eintraege (`active`, `fixed`, `show_info`, leere Datums-/Zeitwerte)
  - Test dafuer, dass bestehende Eintraege und Root-/Legacy-Felder beim Anlegen nicht verloren gehen
  - Ggf. Controller-Test fuer den erfolgreichen Create-Schreibpfad und nachvollziehbare Fehlerreaktionen
- Admin-UI bleibt schwer automatisiert testbar; wenn kein belastbarer JS-Test wirtschaftlich ist, Backend-Vertragsflaeche enger absichern und UI-Restluecken benennen.
- Bestehende Tests aus Epic 1 und Story `2.1` muessen gruen bleiben.

### Risks To Watch

- Neuanlage wird nur im JS modelliert und landet nicht sauber in der kanonischen Persistenz
- Token-Validierung nur im UI -> Umgehung oder inkonsistente Regeln zwischen UI und Backend
- Leere Datums-/Zeitfelder werden ueber bestehende Konvertierung versehentlich zu aktuellen Zeiten oder Placeholdern umgedeutet
- Duplicate-/Reserved-Token-Faelle werden nicht frueh genug abgefangen
- `2.2` zieht bereits Bearbeiten/Loeschen/Sonderfall-Logik aus spaeteren Stories vor

### Git Intelligence Summary

- Juengste Commits zeigen eine Folge aus Test-Haertung und UI-nahen Brownfield-Verbesserungen statt grosser Architekturverschiebung.
- Fuer `2.2` spricht das fuer einen kleinen, klar getesteten Create-Eingriff auf den vorhandenen Admin- und Manager-Pfaden.

### Project Context Reference

- `_bmad-output/project-context.md`
- `_bmad-output/planning-artifacts/epics.md`
- `_bmad-output/planning-artifacts/architecture.md`
- `_bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md`
- `_bmad-output/implementation-artifacts/2-1-maintenance-arten-aus-der-kanonischen-konfiguration-im-admin-ui-darstellen.md`
- `src/CustomMaintenanceBundle/Resources/install/custommaintenance.php`
- `src/CustomMaintenanceBundle/Resources/install/admin_translations.csv`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `sed -n '1,520p' .agents/skills/bmad-create-story/SKILL.md`
- `sed -n '1,260p' .agents/skills/bmad-create-story/discover-inputs.md`
- `sed -n '1,260p' _bmad-output/implementation-artifacts/sprint-status.yaml`
- `sed -n '1,320p' _bmad-output/planning-artifacts/epics.md`
- `sed -n '1,360p' _bmad-output/planning-artifacts/architecture.md`
- `sed -n '1,320p' _bmad-output/project-context.md`
- `sed -n '1,320p' _bmad-output/implementation-artifacts/2-1-maintenance-arten-aus-der-kanonischen-konfiguration-im-admin-ui-darstellen.md`
- `sed -n '1,240p' _bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md`
- `git log --oneline -5`
- `sed -n '1,260p' src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
- `sed -n '1,420p' src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `sed -n '1,220p' src/CustomMaintenanceBundle/Domain/Model/MaintenanceToken.php`
- `sed -n '1,520p' src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
- `sed -n '1,220p' src/CustomMaintenanceBundle/Resources/public/js/pimcore/startup.js`
- `sed -n '1,260p' src/CustomMaintenanceBundle/Resources/install/custommaintenance.php`
- `sed -n '1,220p' src/CustomMaintenanceBundle/Resources/install/admin_translations.csv`
- `sed -n '1,260p' tests/Unit/Controller/AdminpanelControllerTest.php`
- `sed -n '1,360p' tests/Unit/Service/MaintenanceConfigManagerTest.php`
- `sed -n '1,240p' src/CustomMaintenanceBundle/Domain/Model/MaintenanceDateTime.php`
- `sed -n '1,240p' src/CustomMaintenanceBundle/Domain/Model/MaintenanceSchedule.php`
- `sed -n '1,240p' src/CustomMaintenanceBundle/Domain/Model/MaintenanceNoticeConfig.php`
- `rg -n "token|custom.*add|add.*custom|saveFromAdminPayload" src tests -g '*.php' -g '*.js' -g '*.yml'`
- `vendor/bin/phpunit tests/Unit/Domain/Model/MaintenanceTokenTest.php tests/Unit/Controller/AdminpanelControllerTest.php tests/Unit/Service/MaintenanceConfigManagerTest.php`
- `vendor/bin/phpunit`

### Completion Notes List

- Story `2.2` aus Epic-, Architektur-, PRD-, Projektkontext- und aktuellem Codepfad erstellt.
- Fokus auf minimalinvasive Neuanlage einer `custom`-Maintenance-Art ueber den bestehenden Admin- und Persistenzpfad gelegt.
- Token-Validierung, Default-Setzung und Leerwert-Behandlung fuer Datums-/Zeitfelder als Haupt-Guardrails herausgearbeitet.
- Vorarbeit aus `2.1` als Vertragsbasis und Folgestories `2.3` bis `2.5` als Scope-Grenzen explizit eingezogen.
- Ultimate context engine analysis completed - comprehensive developer guide created.
- ExtJS-Admin-Panel um Add-Aktion, Token-Prompt, Hidden-Tokenliste und dynamische Fieldsets fuer neu angelegte `custom`-Eintraege erweitert.
- Zentrale Token-Regel fuer neue `custom`-Tokens in `MaintenanceToken` eingefuehrt und im `MaintenanceConfigManager` fuer reservierte/duplizierte Tokens sowie Create-Persistenz verdrahtet.
- Bestehenden Save-Pfad erweitert, damit neue `custom`-Eintraege mit Defaults (`active=false`, `fixed=false`, `show_info=never`, leere Datums-/Zeitwerte) in den Settings Store geschrieben werden.
- Backend-Vertragsflaeche mit PHPUnit fuer Token-Validierung, Create-Persistenz, Default-Setzung und Controller-Save-Pfad abgesichert; keine separate UI-Automatisierung hinzugefuegt.
- Vollstaendige PHPUnit-Suite nach den Aenderungen erfolgreich ausgefuehrt.
- Code-Review-Follow-ups umgesetzt: Runtime-Fallback fuer leere Datums-/Zeitwerte, JS-Fallbacktexte fuer neue Translation-Keys und zusaetzliche Fehlerpfad-Tests.

### File List

- `_bmad-output/implementation-artifacts/2-2-neue-custom-maintenance-art-mit-defaults-anlegen.md`
- `_bmad-output/implementation-artifacts/sprint-status.yaml`
- `src/CustomMaintenanceBundle/Domain/Model/MaintenanceToken.php`
- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `src/CustomMaintenanceBundle/Resources/public/js/pimcore/AdminPanel.js`
- `src/CustomMaintenanceBundle/Resources/install/admin_translations.csv`
- `tests/Unit/Domain/Model/MaintenanceTokenTest.php`
- `tests/Unit/Controller/AdminpanelControllerTest.php`
- `tests/Unit/Service/MaintenanceConfigManagerTest.php`

### Change Log

- 2026-06-17: Story 2.2 erstellt und auf `ready-for-dev` gesetzt.
- 2026-06-17: Create-Flow fuer neue `custom`-Maintenances inkl. Token-Validierung, Defaults und Backend-Tests implementiert; Story auf `review` gesetzt.
- 2026-06-17: Code-Review-Follow-ups umgesetzt, Legacy-Token-Policy auf grandfathering festgelegt und Story auf `done` gesetzt.
