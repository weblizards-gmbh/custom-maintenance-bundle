# Story 1.3: Settings-Store-Persistenz mit Legacy-Read-Fallback einfuehren

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Integrator,
I want dass das Bundle Konfigurationen aus dem neuen Persistenzpfad lesen und dorthin schreiben kann,
so that die Migration von der Legacy-PHP-Datei kontrolliert und ohne Bruch erfolgt.

## Acceptance Criteria

1. **Given** vorhandene Konfiguration im Pimcore Settings Store  
   **When** das Bundle Konfiguration liest  
   **Then** wird der Settings Store als primaere Quelle verwendet.
2. **Given** keine Konfiguration im Settings Store, aber eine vorhandene Legacy-PHP-Datei  
   **When** das Bundle Konfiguration liest  
   **Then** wird die Legacy-PHP-Datei als Read-Fallback verwendet.
3. **Given** weder Settings Store noch Legacy-PHP-Datei enthalten Bundle-Konfiguration  
   **When** das Bundle initial Konfiguration aufloest  
   **Then** wird ein sinnvoller Default mit nativer Pimcore-Maintenance erzeugt  
   **And** es werden keine weiteren Custom-Maintenance-Arten implizit erzeugt.
4. **Given** ein gueltiger Schreibvorgang auf die Konfiguration  
   **When** das Bundle persistiert  
   **Then** wird immer in den Settings Store geschrieben  
   **And** es erfolgt keine Rueckschreibung in die Legacy-PHP-Datei.

## Tasks / Subtasks

- [x] Persistenzschicht fuer Settings Store und Legacy-Fallback einfuehren (AC: 1, 2, 3, 4)
  - [x] Einen dedizierten Settings-Store-Adapter unter `src/CustomMaintenanceBundle/Infrastructure/Persistence/` anlegen.
  - [x] Einen dedizierten Legacy-Adapter fuer `custommaintenance.php` als Read-Fallback kapseln.
  - [x] Einen stabilen Scope/Key fuer die Bundle-Konfiguration im Pimcore Settings Store definieren.
- [x] `MaintenanceConfigManager` auf die neue Read-Chain und das neue Write-Target umstellen (AC: 1, 2, 3, 4)
  - [x] Lesereihenfolge `Settings Store -> Legacy-PHP-Datei -> Default` zentral im Manager oder Persistence-Layer abbilden.
  - [x] Schreibvorgaenge ausschliesslich ueber den Settings Store laufen lassen.
  - [x] Bestehende Domain-Modell- und Service-Aufrufer unveraendert ueber den Manager weiter bedienen.
- [x] Brownfield-Verhalten und Defaults konservativ halten (AC: 2, 3, 4)
  - [x] Default-Konfiguration nur mit nativer `pimcore`-Maintenance und leerem `custom`-Block erzeugen.
  - [x] Vorhandene unbekannte Legacy-Felder beim Schreiben nicht verlieren.
  - [x] Legacy-Datei in Story `1.3` nicht mehr beschreiben.
- [x] Persistenzverhalten mit PHPUnit absichern (AC: 1, 2, 3, 4)
  - [x] Tests fuer Read-Prioritaet, Legacy-Fallback und Default-Fallback anlegen.
  - [x] Tests fuer schreibenden Settings-Store-Pfad und das Ausbleiben von Legacy-Writebacks anlegen.
  - [x] Story-1.1-/1.2-Tests gruen halten und bei Bedarf an neue Persistenzgrenzen anpassen.

### Review Findings

- [x] [Review][Patch] Strukturell defekte Settings-Store-Payloads werden still normalisiert statt eng als Fehler behandelt [src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php:157]
- [x] [Review][Patch] Der reale Settings-Store-Adaptervertrag ist durch die Tests nicht abgesichert [tests/Unit/Service/MaintenanceConfigManagerTest.php:14]

## Dev Notes

### Story Intent

Story `1.3` zieht die erste echte Persistenzachse des Zielbilds ein. Nach Story `1.2` existiert bereits das kanonische Domain-Modell; jetzt muss das Bundle die Konfiguration ueber eine gekapselte Read-Chain laden und ausschliesslich in den Pimcore Settings Store schreiben, ohne das Brownfield-Verhalten fuer bestehende Installationen zu brechen.

### Epic Context

- Story `1.1` hat die Pimcore-10-Basis stabilisiert.
- Story `1.2` hat das kanonische Domain-Modell und den `MaintenanceConfigManager` eingefuehrt.
- Story `1.3` ersetzt die direkte Legacy-`Config`-Abhaengigkeit im fachlichen Pfad durch eine gekapselte Persistenzschicht.
- Story `1.4` dokumentiert und haertet anschliessend die Rolling-Migration; Story `1.3` muss dafuer schon den tatsaechlichen Migrationspfad herstellen.

### Previous Story Intelligence

- `1.2` hat zwei wichtige Guardrails bereits festgezogen:
  - `StatusService` und `AdminpanelController` sprechen ueber `MaintenanceConfigManager` mit dem Domain-Modell.
  - Beim Speichern muessen unbekannte Legacy-Felder erhalten bleiben.
- Review-Learnings aus `1.2`:
  - Kritische Pfade brauchen gezielte Regressionstests, nicht nur Happy-Path-Abdeckung.
  - Schreibpfade duerfen bei Brownfield-Konfigurationen keine unbekannten Felder verwerfen.

### Current State Analysis

- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - Laedt aktuell immer ueber `Config::getData()`.
  - Schreibt aktuell ueber `Config::setData()` und `Config::save()` weiterhin direkt in die Legacy-PHP-Datei.
  - Ist der richtige Integrationspunkt, um die neue Read-Chain einzuziehen, weil Controller, Statuslogik und Twig bereits darueber laufen.
- `src/CustomMaintenanceBundle/Config.php`
  - Liest und schreibt `PIMCORE_PRIVATE_VAR . '/config/custommaintenance.php'`.
  - Soll in Story `1.3` nicht entfernt werden, sondern als Legacy-Read-Fallback gekapselt weiterleben.
- `src/CustomMaintenanceBundle/Tools/Installer.php`
  - Kopiert aktuell die Legacy-Datei als Installationsdefault nach `var/config/custommaintenance.php`.
  - Diese Story muss den Installer nicht zwangslaeufig umbauen, aber das Laufzeitverhalten darf nicht mehr vom Vorhandensein dieser Datei abhaengen.
- Pimcore `10.6.9` im installierten Vendor-Stand:
  - `Pimcore\Model\Tool\SettingsStore::get(string $id, ?string $scope = null): ?SettingsStore`
  - `Pimcore\Model\Tool\SettingsStore::set(string $id, $data, string $type = 'string', ?string $scope = null): bool`
  - Erlaubte Typen sind nur skalare Typen; strukturierte Konfiguration muss daher als String serialisiert werden.

### Must Preserve

- Oeffentliche Integrationspunkte aus Story `1.1` und `1.2` bleiben stabil:
  - Command `weblizards:custommaintenance:control`
  - Admin-Routen unter `/admin/weblizards_custom_maintenance/...`
  - Twig-Funktionen `indicateCustomMaintenance`, `indicateUpcomingMaintenance`, `indicateCurrentMaintenance`, `isMaintenanceActive`
  - Asset-Pfade unter `/bundles/weblizardscustommaintenance/...`
- `MaintenanceConfigManager` bleibt die zentrale fachliche Zugriffsstelle fuer Controller und Laufzeitlogik.
- Legacy-Datums- und Zeitformate bleiben `d.m.Y` und `H:i`.
- Keine Rueckschreibung in `custommaintenance.php`, sobald Story `1.3` umgesetzt ist.

### Must Change in Story 1.3

- Die fachliche Konfiguration darf nicht mehr direkt an `Config` gebunden sein.
- Es braucht eine gekapselte Persistenzschicht fuer Settings Store und Legacy-Fallback.
- Schreiben muss immer in den Settings Store gehen.
- Wenn weder Store noch Legacy-Datei existieren, muss der Manager einen nativen `pimcore`-Default mit leerem `custom` liefern.

### Explicitly Out of Scope

- Keine Dokumentationsarbeit fuer Rolling-Migration; das gehoert in Story `1.4`.
- Kein finales Admin-UI-Refactoring fuer Create/Delete/Edit von Maintenance-Arten.
- Keine neue externe API.
- Kein Logging unbekannter Tokens; das gehoert spaeter in Epic 3.
- Keine tiefere Template- oder Notice-Logik.

### Architecture Compliance

- Zielstruktur laut `architecture.md`:
  - `Infrastructure/Persistence/` fuer Settings-Store-, Legacy- und Serialisierungsdetails
  - `Service/` bleibt fachlicher Orchestrator
  - `Domain/Model/` bleibt das kanonische Konfigurationsmodell
- Serialisierung fuer den Settings Store bleibt internes Infrastrukturdetail.
- Lesereihenfolge verbindlich:
  1. Settings Store
  2. Legacy-PHP-Datei
  3. Default-Konfiguration
- Schreibziel verbindlich: Settings Store
- Keine neue Datenbanktabelle und keine neue Persistenzachse ausser dem Pimcore Settings Store.

### Technical Requirements

- Pimcore Settings Store nur mit skalarem Typ `string` nutzen; strukturierte Daten serialisiert speichern.
- Stabilen Scope und Key fuer Bundle-Konfiguration verwenden.
- Lesefehler und fehlende Daten sauber trennen:
  - kein stilles Umschalten auf Legacy, wenn Store-Daten vorhanden aber strukturell defekt sind
  - Fallback nur, wenn Store-Eintrag fehlt
- Unknown Fields Preservation:
  - Beim Laden aus Legacy oder Store muessen unbekannte Felder fuer spaetere Schreibvorgaenge erhalten bleiben.

### File Structure Requirements

- Wahrscheinlich zu aendernde Dateien:
  - `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - `src/CustomMaintenanceBundle/Resources/config/services.yml`
  - `tests/Unit/Service/MaintenanceConfigManagerTest.php`
  - eventuell `tests/Unit/Service/StatusServiceTest.php` falls sich die Konstruktion aendert
- Wahrscheinlich neue Dateien:
  - `src/CustomMaintenanceBundle/Infrastructure/Persistence/SettingsStorePersistenceAdapter.php`
  - `src/CustomMaintenanceBundle/Infrastructure/Persistence/LegacyPhpConfigAdapter.php`
  - moegliche kleine Interfaces fuer Adapter, wenn sie die Testbarkeit verbessern
- `src/CustomMaintenanceBundle/Config.php` nur konservativ anfassen; Legacy-Leseverhalten nicht unbegruendet aendern.

### Testing Requirements

- PHPUnit `^10.5` ist vorhanden und laeuft.
- Erwartet fuer diese Story:
  - Tests fuer Read-Prioritaet `Store > Legacy > Default`
  - Tests fuer schreibenden Store-Pfad
  - Tests dafuer, dass Legacy beim Schreiben unberuehrt bleibt
  - Tests dafuer, dass unbekannte Felder erhalten bleiben
- Story-1.1-/1.2-Tests muessen grün bleiben.

### Risks To Watch

- Read-Chain versehentlich falsch herum implementieren.
- Store-Serialisierung ausserhalb der Infrastruktur verteilen.
- Default-Fallback zu breit machen und damit implizit Custom-Maintenances erzeugen.
- Legacy-Datei beim Schreiben versehentlich weiter aktualisieren.
- Unknown Fields beim Uebergang von Domain-Modell zu Persistenz wieder verlieren.

### References

- Story-/Epic-Quelle:
  - `_bmad-output/planning-artifacts/epics.md`
- Architektur:
  - `_bmad-output/planning-artifacts/architecture.md`
- Produktkontext:
  - `_bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md`
- Projektregeln:
  - `_bmad-output/project-context.md`
- Vorherige Stories:
  - `_bmad-output/implementation-artifacts/1-1-bundle-basis-fuer-pimcore-10-6-9-konsolidieren.md`
  - `_bmad-output/implementation-artifacts/1-2-kanonisches-domain-modell-fuer-maintenance-konfiguration-einfuehren.md`
- Aktueller Code:
  - `src/CustomMaintenanceBundle/Config.php`
  - `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
  - `src/CustomMaintenanceBundle/Service/StatusService.php`
  - `src/CustomMaintenanceBundle/Controller/AdminpanelController.php`
  - `src/CustomMaintenanceBundle/Tools/Installer.php`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `php -l src/CustomMaintenanceBundle/Infrastructure/Persistence/ConfigPersistenceInterface.php`
- `php -l src/CustomMaintenanceBundle/Infrastructure/Persistence/LegacyConfigLoaderInterface.php`
- `php -l src/CustomMaintenanceBundle/Infrastructure/Persistence/LegacyPhpConfigAdapter.php`
- `php -l src/CustomMaintenanceBundle/Infrastructure/Persistence/SettingsStorePersistenceAdapter.php`
- `php -l src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `php -l tests/Unit/Service/MaintenanceConfigManagerTest.php`
- `php -l tests/Unit/Service/StatusServiceTest.php`
- `composer test`
- `php -l src/CustomMaintenanceBundle/Infrastructure/Persistence/SettingsStoreGatewayInterface.php`
- `php -l src/CustomMaintenanceBundle/Infrastructure/Persistence/PimcoreSettingsStoreGateway.php`
- `php -l src/CustomMaintenanceBundle/Infrastructure/Persistence/SettingsStorePersistenceAdapter.php`
- `php -l tests/Unit/Infrastructure/Persistence/SettingsStorePersistenceAdapterTest.php`
- `php -l tests/Unit/Service/MaintenanceConfigManagerTest.php`
- `composer test`

### Completion Notes List

- Story-Datei fuer `1.3` aus Epic-, Architektur- und Projektkontext erstellt.
- Gekapselte Persistenzschicht unter `src/CustomMaintenanceBundle/Infrastructure/Persistence/` eingefuehrt fuer Settings Store und Legacy-Read-Fallback.
- `MaintenanceConfigManager` auf die Read-Chain `Settings Store -> Legacy-PHP-Datei -> Default` sowie ausschliessliches Schreiben in den Settings Store umgestellt.
- Bundle-Konfiguration wird im Settings Store unter Scope `weblizards_custom_maintenance` und Key `config` als JSON-String persistiert.
- Default-Fallback ohne Store- und Legacy-Eintrag liefert nur die native `pimcore`-Maintenance und einen leeren `custom`-Block.
- Bestehende unbekannte Legacy-Felder bleiben bei Schreibvorgaengen erhalten.
- PHPUnit-Absicherung fuer Store-Prioritaet, Legacy-Fallback, Default-Fallback und den schreibenden Settings-Store-Pfad ergaenzt.
- Vollstaendige Suite erfolgreich: `17 tests, 86 assertions`.
- Review-Follow-up 1 behoben: strukturell defekte Settings-Store-Payloads werden jetzt im Manager eng validiert statt still auf Defaults geglaettet.
- Review-Follow-up 2 behoben: der reale Settings-Store-Adaptervertrag fuer `scope`/`key`, JSON-Write und Fehlerpfade ist jetzt separat getestet.
- Vollstaendige Suite nach den Review-Fixes erfolgreich: `22 tests, 96 assertions`.

### File List

- `_bmad-output/implementation-artifacts/1-3-settings-store-persistenz-mit-legacy-read-fallback-einfuehren.md`
- `src/CustomMaintenanceBundle/Infrastructure/Persistence/ConfigPersistenceInterface.php`
- `src/CustomMaintenanceBundle/Infrastructure/Persistence/LegacyConfigLoaderInterface.php`
- `src/CustomMaintenanceBundle/Infrastructure/Persistence/LegacyPhpConfigAdapter.php`
- `src/CustomMaintenanceBundle/Infrastructure/Persistence/PimcoreSettingsStoreGateway.php`
- `src/CustomMaintenanceBundle/Infrastructure/Persistence/SettingsStoreGatewayInterface.php`
- `src/CustomMaintenanceBundle/Infrastructure/Persistence/SettingsStorePersistenceAdapter.php`
- `src/CustomMaintenanceBundle/Resources/config/services.yml`
- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `tests/Unit/Infrastructure/Persistence/SettingsStorePersistenceAdapterTest.php`
- `tests/Unit/Service/MaintenanceConfigManagerTest.php`
- `tests/Unit/Service/StatusServiceTest.php`

### Change Log

- 2026-06-08: Story 1.3 implementiert. Settings-Store-Persistenz, Legacy-Read-Fallback und Default-Fallback eingefuehrt.
- 2026-06-09: Code-Review-Findings behoben, Store-Payload-Validierung verschaerft und Adaptervertrag explizit getestet.
