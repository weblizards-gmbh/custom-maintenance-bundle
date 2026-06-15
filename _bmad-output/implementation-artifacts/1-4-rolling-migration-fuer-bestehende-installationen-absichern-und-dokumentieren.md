# Story 1.4: Rolling-Migration fuer bestehende Installationen absichern und dokumentieren

Status: done

<!-- Note: Validation is optional. Run validate-create-story for quality check before dev-story. -->

## Story

As a Integrator,
I want bestehende Installationen kontrolliert in den neuen Persistenzpfad uebernehmen koennen,
so that das Upgrade auf die Pimcore-10-Version ohne sichtbaren Migrationsbruch moeglich ist.

## Acceptance Criteria

1. **Given** eine bestehende Installation mit Legacy-PHP-Konfiguration  
   **When** das Bundle unter Pimcore `10.6.9` betrieben wird  
   **Then** kann die bestehende Konfiguration weiterhin gelesen und fachlich genutzt werden  
   **And** die Migration darf fuer Administratoren im Hintergrund ablaufen.
2. **Given** ein erfolgreicher Schreibvorgang nach Nutzung einer Legacy-Konfiguration  
   **When** das Bundle die Konfiguration persistiert  
   **Then** liegt der kanonische Stand anschliessend im Settings Store.
3. **Given** die erste oeffentliche Version des Bundles  
   **When** ein Integrator die Dokumentation nutzt  
   **Then** sind Rolling-Migration, Lesefallback, Schreibziel und Default-Verhalten klar beschrieben.

## Tasks / Subtasks

- [x] Story-1.3-Verhalten gegen die Migrations-ACs abgleichen (AC: 1, 2)
  - [x] Bestaetigen, dass bestehende Legacy-Konfigurationen weiter gelesen werden koennen.
  - [x] Bestaetigen, dass nach erfolgreichem Speichern der kanonische Stand ausschliesslich im Settings Store landet.
  - [x] Keine neue Migrationsaktion fuer Administratoren einfuehren.
- [x] Oeffentliche Dokumentation auf den echten Rolling-Migrationspfad aktualisieren (AC: 3)
  - [x] `README.md` auf Settings-Store-first, Legacy-Fallback und Default-Verhalten umstellen.
  - [x] `docs/development_testing.md` auf den aktuellen Persistenzpfad und manuelle Verifikationsschritte aktualisieren.
  - [x] Schreibziel, Lesefallback und stillen Hintergrundcharakter der Migration explizit beschreiben.
- [x] Scope bewusst klein halten (AC: 1, 2, 3)
  - [x] Keine neue Migrations-UI, keine neue CLI und kein weiterer Persistenzumbau.
  - [x] Installer- und Runtime-Code nur dann anfassen, wenn die ACs mit dem bestehenden Story-1.3-Code nicht erfuellt waeren.

### Review Findings

- [x] [Review][Patch] Migrationsdoku behauptet Settings-Store-Kanon, waehrend der Installer weiterhin an der Legacy-Datei haengt [README.md:17, src/CustomMaintenanceBundle/Tools/Installer.php:34]

## Dev Notes

### Story Intent

Story `1.4` ist die Absicherungs- und Dokumentationsstory zur bereits eingefuehrten Rolling-Migration. Der eigentliche Persistenzpfad wurde in Story `1.3` implementiert; hier geht es darum, die vorhandene Brownfield-Uebernahme explizit zu machen, die Doku auf den realen Stand zu ziehen und keinen sichtbaren Migrationsbruch fuer bestehende Installationen zu erzeugen.

### Epic Context

- Story `1.1` hat die Pimcore-10-Basis konsolidiert.
- Story `1.2` hat das kanonische Domain-Modell eingezogen.
- Story `1.3` hat die Read-Chain `Settings Store -> Legacy -> Default` und das exklusive Write-Target `Settings Store` umgesetzt.
- Story `1.4` dokumentiert und haertet dieses Verhalten fuer Integratoren.

### Previous Story Intelligence

- `MaintenanceConfigManager` liest bereits zuerst aus dem Settings Store, faellt dann auf die Legacy-Datei zurueck und erzeugt sonst einen Default.
- Erfolgreiche Schreibvorgaenge laufen bereits ausschliesslich ueber den Settings Store.
- Die Story-1.3-Tests decken Read-Prioritaet, Legacy-Fallback, Default-Fallback und Store-Schreiben bereits ab.

### Current State Analysis

- `README.md` beschrieb vor dieser Story noch die Legacy-Datei als aktuelle Persistenzbasis und nicht die Rolling-Migration.
- `docs/development_testing.md` sagte noch explizit, die Rolling-Migration gehoere zu spaeteren Stories.
- Der Runtime-Code selbst war fuer diese Story bereits ausreichend und musste nicht weiter umgebaut werden.

### Must Preserve

- Stille Migration im Hintergrund ohne separate Admin-Aktion.
- Lesereihenfolge `Settings Store -> Legacy-PHP-Datei -> Default`.
- Schreibziel nur `Settings Store`.
- Brownfield-kompatibles Verhalten fuer bestehende Legacy-Installationen.

### Explicitly Out of Scope

- Kein weiterer Persistenzumbau nach Story `1.3`.
- Keine neue UI oder CLI fuer Migration.
- Kein Logging-Ausbau oder neue fachliche Aktivierungslogik.
- Keine opportunistische Deprecation-Arbeit; das bleibt Story `1.5`.

### References

- `_bmad-output/planning-artifacts/epics.md`
- `_bmad-output/planning-artifacts/architecture.md`
- `_bmad-output/project-context.md`
- `_bmad-output/implementation-artifacts/1-3-settings-store-persistenz-mit-legacy-read-fallback-einfuehren.md`
- `README.md`
- `docs/development_testing.md`
- `src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`

## Dev Agent Record

### Agent Model Used

GPT-5 Codex

### Debug Log References

- `sed -n '1,240p' README.md`
- `sed -n '1,240p' docs/development_testing.md`
- `sed -n '1,260p' src/CustomMaintenanceBundle/Tools/Installer.php`
- `sed -n '1,320p' src/CustomMaintenanceBundle/Service/MaintenanceConfigManager.php`
- `php -l src/CustomMaintenanceBundle/Tools/Installer.php`
- `php -l tests/Unit/Tools/InstallerTest.php`
- `composer test`

### Completion Notes List

- Story-Datei fuer `1.4` aus Epic-, Architektur- und bestehendem Implementierungsstand erstellt.
- Story-1.3-Verhalten gegen die Migrations-ACs abgeglichen; der Codepfad war bereits ausreichend vorhanden.
- `README.md` auf Settings-Store-first, Legacy-Read-Fallback und konservativen Default-Fallback aktualisiert.
- `docs/development_testing.md` auf den realen Rolling-Migrationspfad, das ausschliessliche Write-Target Settings Store und die manuelle Verifikation angepasst.
- Den Story-Scope bewusst klein gehalten und keine zusaetzlichen Runtime- oder Installer-Aenderungen eingefuehrt.
- Review-Finding behoben: der Installer akzeptiert jetzt auch eine vorhandene Settings-Store-Konfiguration als installierten Zustand, ohne frische Legacy-Defaults aufzugeben.
- PHPUnit-Absicherung fuer den Installer-Vertrag ergaenzt: Store vorhanden, Legacy-Datei vorhanden und kein Persistenzpfad vorhanden.

### File List

- `_bmad-output/implementation-artifacts/1-4-rolling-migration-fuer-bestehende-installationen-absichern-und-dokumentieren.md`
- `README.md`
- `docs/development_testing.md`
- `src/CustomMaintenanceBundle/Tools/Installer.php`
- `tests/Unit/Tools/InstallerTest.php`

### Change Log

- 2026-06-09: Story 1.4 umgesetzt. Rolling-Migrationsverhalten dokumentiert und gegen den bereits eingefuehrten Persistenzpfad abgeglichen.
- 2026-06-10: Review-Finding behoben. Installer-Installationszustand an Settings Store oder Legacy-Datei ausgerichtet und per Unit-Test abgesichert.
