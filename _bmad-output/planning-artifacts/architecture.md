---
stepsCompleted: [1, 2, 3, 4, 5, 6, 7, 8]
inputDocuments:
  - /mnt/develop/PHPStormProjects/custom-maintenance-bundle/_bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md
  - /mnt/develop/PHPStormProjects/custom-maintenance-bundle/_bmad-output/project-context.md
  - /mnt/develop/PHPStormProjects/custom-maintenance-bundle/docs/development_testing.md
workflowType: 'architecture'
project_name: 'Pimcore Custom Maintenance Bundle'
user_name: 'Thomas'
date: '2026-05-27'
lastStep: 8
status: 'complete'
completedAt: '2026-05-27'
---

# Architecture Decision Document

_Dieses Dokument wird im Architektur-Workflow schrittweise aufgebaut. Schritt 1 ist abgeschlossen; die eigentlichen Architekturentscheidungen folgen ab der Kontextanalyse._

## Project Context Analysis

### Requirements Overview

**Functional Requirements:**
Die Anforderungen gruppieren sich in fuenf Architekturbausteine: Verwaltung von Maintenance-Arten, Aktivierung und Planung, Hinweis-Ausgabe, Laufzeit-API sowie Kompatibilitaet und Migration. Architektonisch bedeutet das: Das Bundle braucht einen klaren Kern fuer Maintenance-Definitionen und -Status, eine getrennte Admin-/Persistenzschicht, stabile Laufzeit-Schnittstellen fuer Anwendungscode und eine saubere Render-/Template-Anbindung fuer Hinweise. Die Anforderungen sind nicht datenintensiv, aber stark integrationsgetrieben.

**Non-Functional Requirements:**
Die wichtigsten NFRs sind Kompatibilitaet mit Pimcore `10.6.9`, Stabilitaet oeffentlicher Integrationspunkte, klare Dokumentation der Rolling-Migration und sichtbares Fehlerverhalten bei unbekannten Tokens. Dazu kommt die bestehende Projektregel, den aktuellen Bundle-Vertrag nicht leichtfertig zu brechen: Token-Logik, Datumsformate, CLI-Namen, Twig-Funktionen und Konfigurationsverhalten sind architektonisch konservativ zu behandeln.

**Scale & Complexity:**
Das Projekt ist kein grossskaliges Plattform- oder Echtzeitsystem, sondern ein mittelkomplexes Brownfield-Bundle mit mehreren Integrationsachsen. Die Schwierigkeit liegt weniger in Last oder Datenvolumen als in sauberer Weiterentwicklung eines produktiv bewaehrten Bestands bei gleichzeitiger Pimcore-10-Modernisierung.

- Primary domain: Pimcore-Bundle / Backend / Admin-UI / Laufzeitintegration
- Complexity level: Medium
- Estimated architectural components: 6-8 zentrale Komponenten oder Verantwortungsbereiche

### Technical Constraints & Dependencies

- Zielplattform ist verbindlich Pimcore `10.6.9`
- Basis-Stack: PHP `>=8.1`, Pimcore `^10.0`, Twig, Carbon, Symfony Service Container
- Admin-UI bleibt im klassischen Pimcore-ExtJS/Legacy-Modell
- Hinweis-Templates muessen Twig-basiert sein; PHP-Templates sind ausgeschlossen
- Persistenz folgt dem Modell: lesend Setting Store, Fallback auf Legacy-PHP-Datei, sonst Default; schreibend immer Setting Store
- Bestehende Bundle-Struktur und oeffentliche Integrationspunkte sollen soweit moeglich erhalten bleiben
- Deprecation-Arbeit Richtung Pimcore `11` ist kein eigener Architekturtreiber, sondern nur opportunistisch relevant, wenn waehrend der Pimcore-10-Arbeit konkrete Hinweise mit Handlungsanweisung auftauchen

### Cross-Cutting Concerns Identified

- Rueckwaertskompatibilitaet fuer bestehende Bundle-Nutzung und Integrationspunkte
- Trennung von Admin-Verwaltung, Laufzeitlogik, Persistenz und Hinweis-Rendering
- Sichere und nachvollziehbare Rolling-Migration auf Setting Stores
- Fehler- und Logverhalten fuer unbekannte oder geloeschte Tokens
- Dokumentationspflicht fuer Migration, Template-Konfiguration und Betriebsverhalten
- Testbarkeit trotz Legacy-Admin-UI und Brownfield-Struktur

## Starter Template Evaluation

### Primary Technology Domain

Pimcore Symfony Bundle / Backend / Admin-UI Integration / Runtime Service Layer

Das Projekt ist kein Greenfield-Applikationsstarter, sondern ein bestehendes Pimcore-Bundle mit produktiver Historie. Der relevante Architektur-Ausgangspunkt ist daher nicht ein neues App-Starter-Template, sondern die kontrollierte Weiterentwicklung des vorhandenen Bundle-Bestands fuer Pimcore `10.6.9`.

### Starter Options Considered

**Option 1: Externer Greenfield-Starter**  
Fuer dieses Projekt nicht geeignet. Klassische App-Starter oder Boilerplates wuerden die vorhandene Bundle-Struktur, Admin-Integration und oeffentlichen Laufzeit-Schnittstellen nicht sinnvoll konservieren.

**Option 2: Offizielle Pimcore-Bundle-Konventionen als Referenz**  
Geeignet als Referenzrahmen. Pimcore-Bundles folgen dem Symfony-Bundle-System, koennen `AbstractPimcoreBundle` als Schnellstart-Basis verwenden und binden Admin-Assets ueber die Bundle-Mechanik ein. Diese Konventionen bestaetigen die Richtung des bestehenden Projekts, ersetzen aber keinen Brownfield-Migrationspfad.

**Option 3: Bestehendes Repository als Architektur-Startpunkt**  
Dies ist die passende Wahl fuer diese Architektur. Die erste Implementierungsphase soll auf dem vorhandenen Bundle aufsetzen, die bestehende Struktur gezielt modernisieren und nur dort umbauen, wo Pimcore-10.6.9-Kompatibilitaet, Twig-Template-Umstellung, Setting-Store-Migration und UI-Erweiterungen es erfordern.

### Selected Starter: Bestehender Bundle-Bestand als Brownfield Foundation

**Rationale for Selection:**
- Das Produkt existiert bereits produktiv und hat einen bestaetigten Funktionskern.
- Die Architektur muss bestehende Bundle-Integrationen erhalten statt sie durch ein neues Startergeruest zu ersetzen.
- Die offiziellen Pimcore-Bundle-Konventionen stuetzen die bestehende Richtung, insbesondere `AbstractPimcoreBundle`, Bundle-Installer und Admin-Asset-Einbindung.
- Das eigentliche Architekturproblem ist kontrollierte Modernisierung, nicht Initialisierung eines neuen Projekts.

**Initialization Command:**

```bash
# Kein externer Starter-CLI-Schritt
# Architektur- und Implementierungsbasis ist das bestehende Repository
```

**Architectural Decisions Provided by Starter:**

**Language & Runtime:**
- PHP `>=8.1`
- Pimcore `10.6.9` als Zielplattform
- Symfony-Bundle-Modell als strukturelle Grundlage

**Styling Solution:**
- Bestehende Bundle-Assets und Legacy-Admin-UI-Struktur bleiben Ausgangspunkt
- Keine neue Frontend- oder Build-Tool-Kette als Starterentscheidung

**Build Tooling:**
- Kein neuer App-Starter
- Weiterarbeit auf bestehender Composer-/Bundle-Struktur

**Testing Framework:**
- PHPUnit bleibt die vorgesehene Testbasis
- Testinfrastruktur wird innerhalb des bestehenden Repositories ergaenzt oder vervollstaendigt, nicht ueber einen Starter vorgegeben

**Code Organization:**
- Bestehende Bundle-Struktur bleibt Leitplanke:
  - `Service/`
  - `Controller/`
  - `Command/`
  - `Twig/`
  - `Resources/`
  - `DependencyInjection/`

**Development Experience:**
- Brownfield-first statt scaffold-first
- Offizielle Pimcore-Bundle-Konventionen dienen als Referenz fuer Modernisierungsschritte
- Erster Implementierungsschritt ist kein Projekt-Scaffold, sondern das gezielte Herstellen einer tragfaehigen Pimcore-10.6.9-kompatiblen Bundle-Architektur

## Core Architectural Decisions

### Decision Priority Analysis

**Critical Decisions (Block Implementation):**
- Konfigurationspersistenz wird auf Pimcore Settings Store ausgerichtet, ohne neue eigene Datenbanktabellen
- Legacy-PHP-Datei bleibt ausschliesslich Read-Fallback im Rolling-Release
- Die Bundle-Konfiguration wird intern in ein kanonisches Domain-Modell normalisiert, statt quer durch Controller, UI und Services mit Roharrays zu arbeiten

**Important Decisions (Shape Architecture):**
- Strukturierte Konfiguration im Settings Store wird als kontrolliert serialisierte Datenstruktur abgelegt
- Persistenz und Laufzeitbewertung werden voneinander getrennt
- Zeitfenster-, Status- und Tokenlogik sitzen in einer zentralen Domain-/Service-Schicht

**Deferred Decisions (Post-MVP):**
- Feingranulare Sichtbarkeits- und Aktivierungsregeln
- Weitergehende Optimierung fuer kuenftige Pimcore-Generationen
- Tiefergehende Deprecation-Haertung ausserhalb konkreter Pimcore-10-Hinweise

### Data Architecture

**Primary persistence model:**
- Pimcore Settings Store ist die Zielpersistenz
- Keine neuen Bundle-spezifischen Datenbanktabellen fuer die erste Stufe
- Keine Rueckmigration in die Legacy-PHP-Datei

**Persistence structure:**
- Ein dedizierter Persistence Adapter kapselt alle Lese-/Schreibzugriffe
- Lesereihenfolge:
  1. Settings Store
  2. Legacy-PHP-Datei
  3. Default-Konfiguration mit nativer Pimcore-Maintenance
- Schreibvorgaenge gehen immer in den Settings Store

**Serialization strategy:**
- Da der Settings Store nur skalare Typen vorsieht, wird die Bundle-Konfiguration als kontrolliert serialisierte String-Repräsentation gespeichert
- Die Serialisierung bleibt ein internes Infrastrukturdetail; ausserhalb des Adapters arbeitet das System mit einem normalisierten Domain-Modell

**Canonical domain model:**
- `MaintenanceConfigSet` als Wurzel der geladenen Konfiguration
- `MaintenanceEntry` fuer native Pimcore- und Custom-Maintenance-Arten
- `MaintenanceSchedule` fuer geplante Zeitfenster
- `MaintenanceNoticeConfig` fuer Hinweis- und Template-Konfiguration
- `MaintenanceToken` als validierter technischer Schluessel

**Validation strategy:**
- Validierung erfolgt zentral beim Lesen, Migrieren und Speichern
- Ungueltige Tokens oder strukturell defekte Daten werden nicht stillschweigend weitergereicht
- Unbekannte Tokens im Laufzeitgebrauch werden als Fehler protokolliert, bleiben aber von der Persistenzvalidierung getrennt

**Migration approach:**
- Rolling-Migration bleibt fuer Administratoren still im Hintergrund
- Sobald ein gueltiger Schreibvorgang erfolgt, wird die Konfiguration im Settings Store kanonisch persistiert
- Die Doku beschreibt Lesefallback, Schreibziel und Default-Verhalten explizit

**Caching strategy:**
- In-Request-Runtime-Cache fuer bereits geladene Konfiguration ist sinnvoll
- Kein separater verteilter Cache in der ersten Stufe erforderlich
- Cache darf Persistenz- oder Logging-Verhalten nicht verdecken

### Decision Impact Analysis

**Implementation Sequence:**
1. Domain-Modell fuer Maintenance-Konfiguration festlegen
2. Persistence Adapter mit Read-Chain und Write-Target Settings Store bauen
3. Legacy-PHP-Loader als Fallback kapseln
4. Zentrale Validierungs- und Normalisierungsschicht einfuehren
5. Laufzeitservices, CLI und Admin-UI auf das kanonische Modell umstellen

**Cross-Component Dependencies:**
- Admin-UI haengt von validierbaren Schreibmodellen ab
- Twig-Hinweislogik haengt von stabilen Notice- und Schedule-Daten ab
- CLI und Laufzeit-API haengen von derselben Status- und Tokenquelle ab
- Migration, Logging und Dokumentation greifen in dieselbe Persistenzschicht ein

### API & Communication Patterns

**Primary communication model:**
- Keine neue REST- oder GraphQL-API fuer die erste Stufe
- Interne Kommunikation erfolgt synchron ueber klar getrennte Services und ein gemeinsames Domain-Modell
- Oeffentliche Bundle-Schnittstellen bleiben:
  - Admin-Controller fuer Konfigurationsverwaltung
  - Twig-Funktionen fuer Hinweis-Ausgabe
  - CLI fuer betriebliche Steuerung
  - PHP-Laufzeit-API fuer Statuspruefungen im Anwendungscode

**Controller boundary:**
- Controller nehmen Requests entgegen, validieren Eingaben und delegieren an Application-/Domain-Services
- Keine Fachlogik in Admin-Controllern
- Antworten aus dem Admin-Bereich bleiben leichtgewichtig und fuer die bestehende ExtJS-UI konsumierbar

**Service communication pattern:**
- `ConfigurationService` bzw. aequivalente Application-Schicht fuer Lesen, Schreiben und Migration
- `StatusService` bzw. aequivalente Laufzeitschicht fuer Statusbewertung
- `NoticeRenderingService` bzw. aequivalente Schicht fuer Hinweisdaten und Template-Auswahl
- Gemeinsame Nutzung derselben kanonischen Konfigurationsquelle statt paralleler Logikpfade

**Error handling standard:**
- Persistenz- und Validierungsfehler werden fachlich klar getrennt von Laufzeitfehlern bei unbekannten Tokens
- Unbekannte Tokens werden geloggt, aber nicht als stiller Infrastrukturzustand behandelt
- Admin-Schreibfehler muessen fuer UI und Betrieb nachvollziehbar rueckmeldbar sein

**Routing pattern:**
- Bundle-interne Admin-Routen bleiben klassische Symfony-/Pimcore-Bundle-Routen
- Keine zusaetzliche Routing-Architektur fuer MVP
- Vorhandene Bundle-Routen behalten Vorrang als technische Integrationspunkte

**Asynchronous communication:**
- Kein eigener Event-Bus oder Messaging-Layer fuer MVP
- Zeitbezogene Bewertung bleibt Teil der Status-/Maintenance-Logik
- Pimcore-Maintenance-Mechaniken koennen spaeter fuer betriebliche Entkopplung genutzt werden, sind aber kein Zwang fuer die erste Architektur

### Decision Impact Analysis

**Implementation Sequence:**
1. Service-Grenzen zwischen Konfiguration, Statusbewertung und Rendering festziehen
2. Admin-Controller auf reine Ein-/Ausgabe und Delegation reduzieren
3. Laufzeit-API und CLI gegen dieselbe Application-/Domain-Schicht ausrichten
4. Logging- und Fehlerkontrakte fuer unbekannte Tokens und Schreibfehler vereinheitlichen

**Cross-Component Dependencies:**
- Admin-UI haengt an stabilen Controller-Antworten und validierten Commands
- Twig-Ausgabe haengt an einer klaren Notice- und Statusschnittstelle
- CLI haengt an denselben Application-Services wie UI und Laufzeitlogik
- Dokumentation haengt an stabilen, kleinen oeffentlichen Integrationspunkten statt neuer API-Flaechen

## Implementation Patterns & Consistency Rules

### Pattern Categories Defined

**Critical Conflict Points Identified:**
6 Bereiche, in denen verschiedene AI Agents sonst widerspruechliche Entscheidungen treffen koennten:
- Domain-Modell vs. Roharray-Nutzung
- Service-Grenzen
- Persistenz- und Migrationszugriffe
- Naming von Tokens, Klassen und Dateien
- Fehler- und Logging-Verhalten
- Test- und Template-Ablage

### Naming Patterns

**Configuration & Domain Naming Conventions:**
- Externe Konfigurationsschluessel bleiben kompatibel zum faktischen Bundle-Vertrag:
  - `pimcore`
  - `custom`
  - `active`
  - `fixed`
  - `show_info`
  - `planned`
  - `document`
- Technische Tokens fuer Custom-Maintenance-Arten sind alphanumerisch
- Domain-Klassen verwenden PascalCase:
  - `MaintenanceConfigSet`
  - `MaintenanceEntry`
  - `MaintenanceSchedule`
  - `MaintenanceNoticeConfig`
  - `MaintenanceToken`
- Methoden und Properties in PHP bleiben camelCase
- Neue Service-Klassen werden nach Verantwortung benannt, nicht generisch:
  - `ConfigurationService`
  - `StatusEvaluationService`
  - `NoticeRenderingService`
  - `SettingsStorePersistenceAdapter`

**Code Naming Conventions:**
- PHP-Dateien und Klassen: PascalCase
- Twig-Templates: bestehende Bundle-Konvention beibehalten, also lowercase-Dateinamen in `Resources/views/...`
- Admin-JavaScript-Dateien bleiben im vorhandenen Stil und in der vorhandenen Struktur unter `Resources/public/js/pimcore/`

### Structure Patterns

**Project Organization:**
- Fachlogik bleibt in `Service/`
- Persistenzspezifische Logik wird als eigener Adapter/Infrastructure-Bereich organisiert, nicht im Controller
- Twig-Erweiterungen bleiben duenne Adapter
- Controller bleiben fuer Request/Response und UI-Anbindung zustaendig
- Commands bleiben reine CLI-Einstiegspunkte

**File Structure Patterns:**
- Admin-Routen und Bundle-Konfiguration bleiben unter `Resources/config/pimcore/`
- Services bleiben ueber `Resources/config/services.yml` verdrahtet
- Twig-Hinweis-Templates liegen unter `Resources/views/`
- Bundle-Assets bleiben unter `Resources/public/`
- Tests liegen gesammelt unter `tests/`, nicht verstreut im Bundle

### Format Patterns

**Persistence/Data Format Rules:**
- Ausserhalb des Persistence Adapters arbeitet kein Code mit unstrukturierten Persistenzrohdaten
- Persistenz wird in ein kanonisches Domain-Modell normalisiert
- Datums- und Zeitwerte bleiben fachlich im bestehenden Format kompatibel:
  - Datum `d.m.Y`
  - Zeit `H:i`
- Serialisierung fuer den Settings Store bleibt internes Detail und darf nicht nach aussen durchsickern

**Response/Error Format Rules:**
- Admin-Controller liefern einfache, UI-taugliche Antwortstrukturen
- Schreib- und Validierungsfehler werden klar von Laufzeitfehlern getrennt
- Unbekannte Tokens erzeugen Log-Fehler mit eindeutigem Hinweis, dass der Token nicht gefunden wurde

### Communication Patterns

**Service Interaction Rules:**
- Statusbewertung nutzt dieselbe Konfigurationsquelle wie Admin-UI und CLI
- Keine parallelen Logikpfade fuer:
  - Token-Aufloesung
  - Zeitfensterbewertung
  - Hinweis-Template-Auswahl
- Migration wird nur ueber die zentrale Persistenzschicht ausgefuehrt
- Twig und Controller sprechen nicht direkt mit Persistenzdetails

**Logging Patterns:**
- Anwendungslog fuer unbekannte oder geloeschte Tokens
- Keine Debug-Ausgaben wie `dump()` oder `console.log()` in Produktionspfaden
- Logging-Level und Fehlermeldungen sollen operativ nachvollziehbar sein, nicht nur fuer Entwickler lesbar

### Process Patterns

**Validation Patterns:**
- Token-Validierung zentral, nicht verteilt auf UI, Controller und Service mit unterschiedlichen Regeln
- Schreibvalidierung vor Persistenz
- Laufzeitvalidierung getrennt von Migrations- und Persistenzvalidierung
- Native Pimcore-Maintenance wird in allen Schreib- und Loeschpfaden als Sonderfall respektiert

**Error Handling Patterns:**
- Controller fangen fachlich erwartbare Fehler ab und liefern UI-verwertbare Rueckmeldungen
- Services werfen keine UI-spezifischen Antworten
- Fehler duerfen nicht durch Defaults verdeckt werden, wenn dadurch fachlich relevante Probleme unsichtbar wuerden

### Enforcement Guidelines

**All AI Agents MUST:**
- denselben kanonischen Konfigurationspfad verwenden statt neue Nebenpfade aufzubauen
- Fachlogik aus Controller-, Twig- und Command-Schichten heraushalten
- neue Persistenz- oder Migrationslogik ausschliesslich ueber den zentralen Adapter einfuehren
- bestehende Bundle-Konventionen fuer Routen, Assets, Services und Templates respektieren
- Debug-Reste und stilles Fehlerschlucken vermeiden

**Pattern Enforcement:**
- Architekturverletzungen werden dort korrigiert, wo ein Agent Roharrays, Controllerlogik oder duplizierte Statuslogik einfuehrt
- Neue Patterns werden nur dann ergaenzt, wenn wiederholt dieselbe Konfliktstelle auftaucht
- PRD, Architektur und Project Context muessen bei grundlegenden Pattern-Aenderungen synchron nachgezogen werden

### Pattern Examples

**Good Examples:**
- Controller delegiert `saveAction` an einen Konfigurationsservice statt Persistenz direkt zu schreiben
- Twig-Extension ruft nur einen Rendering-/Statusservice auf
- CLI und Admin-UI nutzen dieselbe Status- und Schreiblogik
- Settings-Store-Serialisierung ist ausschliesslich im Persistence Adapter gekapselt

**Anti-Patterns:**
- Zeitfensterlogik erneut im Controller oder Twig entscheiden
- unbekannte Tokens in einem Codepfad loggen und in einem anderen still ignorieren
- Migration teilweise im Controller, teilweise im Service und teilweise im Installer unterbringen
- neue Konfigurationsformate einfuehren, die den bestehenden Bundle-Vertrag umgehen

## Project Structure & Boundaries

### Complete Project Directory Structure

```text
custom-maintenance-bundle/
├── README.md
├── composer.json
├── composer.lock
├── phpunit.xml.dist
├── .gitignore
├── docs/
│   └── development_testing.md
├── tests/
│   ├── Unit/
│   │   ├── Service/
│   │   ├── Domain/
│   │   └── Infrastructure/
│   ├── Integration/
│   │   ├── Command/
│   │   ├── Controller/
│   │   └── Persistence/
│   └── Fixtures/
├── src/
│   └── CustomMaintenanceBundle/
│       ├── WeblizardsCustomMaintenanceBundle.php
│       ├── Command/
│       │   └── ControlCommand.php
│       ├── Controller/
│       │   └── AdminpanelController.php
│       ├── DependencyInjection/
│       │   ├── Configuration.php
│       │   └── WeblizardsCustomMaintenanceExtension.php
│       ├── Domain/
│       │   ├── Model/
│       │   │   ├── MaintenanceConfigSet.php
│       │   │   ├── MaintenanceEntry.php
│       │   │   ├── MaintenanceSchedule.php
│       │   │   ├── MaintenanceNoticeConfig.php
│       │   │   └── MaintenanceToken.php
│       │   └── Validation/
│       │       └── MaintenanceConfigValidator.php
│       ├── Service/
│       │   ├── ConfigurationService.php
│       │   ├── StatusEvaluationService.php
│       │   ├── NoticeRenderingService.php
│       │   └── LegacyConfigLoader.php
│       ├── Infrastructure/
│       │   └── Persistence/
│       │       ├── SettingsStorePersistenceAdapter.php
│       │       ├── LegacyPhpConfigAdapter.php
│       │       └── ConfigSerialization.php
│       ├── Twig/
│       │   └── Extensions.php
│       ├── Tools/
│       │   └── Installer.php
│       └── Resources/
│           ├── config/
│           │   ├── services.yml
│           │   └── pimcore/
│           │       └── routing.yml
│           ├── install/
│           │   ├── custommaintenance.php
│           │   ├── admin_translations.csv
│           │   └── shared_translations.csv
│           ├── public/
│           │   ├── css/
│           │   │   ├── backend.css
│           │   │   └── frontend.css
│           │   ├── icons/
│           │   │   ├── support.svg
│           │   │   └── clock_flat_white.svg
│           │   └── js/
│           │       └── pimcore/
│           │           ├── startup.js
│           │           └── AdminPanel.js
│           └── views/
│               ├── partials/
│               │   ├── indicatecurrent.html.twig
│               │   └── indicateupcoming.html.twig
│               └── configurable/
│                   ├── upcoming_default.html.twig
│                   └── current_default.html.twig
└── _bmad-output/
    └── planning-artifacts/
        ├── architecture.md
        └── prds/
```

### Architectural Boundaries

**API Boundaries:**
- Keine neue externe Web-API fuer MVP
- Admin-Zugriffe laufen ueber `Controller/`
- Laufzeit-API bleibt PHP-intern ueber Services
- CLI bleibt eigener Einstiegspunkt ueber `Command/`

**Component Boundaries:**
- `Domain/` enthaelt nur kanonisches Modell und Validierung
- `Service/` enthaelt Anwendungslogik
- `Infrastructure/Persistence/` enthaelt Settings-Store-, Legacy- und Serialisierungsdetails
- `Twig/` und `Controller/` bleiben Adapter-Schichten zur Aussenwelt

**Service Boundaries:**
- `ConfigurationService` orchestriert Laden, Schreiben und Migration
- `StatusEvaluationService` bewertet aktiv/inaktiv und Zeitfenster
- `NoticeRenderingService` bestimmt Notice-Daten und Template-Auswahl
- `LegacyConfigLoader` oder Adapter kapselt Altlasten, statt sie in alle Services zu streuen

**Data Boundaries:**
- Nur `Infrastructure/Persistence/` kennt den Settings Store und die Legacy-PHP-Datei
- Nur `Domain/Model/` definiert das kanonische Konfigurationsmodell
- Nur `Service/` darf Domain-Modell fuer Fachentscheidungen zusammensetzen und verwenden

### Requirements to Structure Mapping

**Feature/FR Mapping:**
- Verwaltung von Maintenance-Arten:
  - `Controller/AdminpanelController.php`
  - `Service/ConfigurationService.php`
  - `Domain/Model/*`
  - `Infrastructure/Persistence/*`
  - `Resources/public/js/pimcore/AdminPanel.js`
- Aktivierung, Planung, CLI:
  - `Command/ControlCommand.php`
  - `Service/StatusEvaluationService.php`
  - `Domain/Model/MaintenanceSchedule.php`
- Hinweise und Twig:
  - `Service/NoticeRenderingService.php`
  - `Twig/Extensions.php`
  - `Resources/views/partials/`
  - `Resources/views/configurable/`
- Rolling-Migration:
  - `Service/ConfigurationService.php`
  - `Infrastructure/Persistence/SettingsStorePersistenceAdapter.php`
  - `Infrastructure/Persistence/LegacyPhpConfigAdapter.php`
  - `Tools/Installer.php`

**Cross-Cutting Concerns:**
- Logging unbekannter Tokens:
  - `Service/StatusEvaluationService.php`
  - zentrale Logging-Nutzung statt UI-spezifischer Sonderfaelle
- Validierung:
  - `Domain/Validation/MaintenanceConfigValidator.php`
- Bundle-Wiring:
  - `Resources/config/services.yml`
  - `DependencyInjection/*`

### Integration Points

**Internal Communication:**
- Controller -> Service
- Command -> Service
- Twig Extension -> Service
- Service -> Domain + Persistence Adapter
- Keine direkten Controller -> Persistence oder Twig -> Persistence Aufrufe

**External Integrations:**
- Pimcore Admin-UI ueber ExtJS/Bundle-Assets
- Pimcore Setting Store
- Legacy-PHP-Datei als Read-Fallback
- Pimcore CLI / Maintenance-Betrieb
- Host-Anwendung, die Statusabfragen im PHP-Code nutzt

**Data Flow:**
- Lesen: Settings Store -> Legacy-PHP-Datei -> Default -> Domain-Modell
- Schreiben: UI/CLI -> Validierung -> Domain-Modell -> Serialisierung -> Settings Store
- Laufzeit: Host-Anwendung/Twig -> Status-/Notice-Service -> Domain-Modell -> Ergebnis/Log

### File Organization Patterns

**Configuration Files:**
- Symfony-/Bundle-Wiring unter `Resources/config/`
- Pimcore-Routing unter `Resources/config/pimcore/`
- Installationsdefaults unter `Resources/install/`

**Source Organization:**
- Domain und Infrastruktur werden als neue, explizite Grenzen eingefuehrt
- Bestehende Bundle-Ordner bleiben erhalten und werden nicht durch neue Top-Level-Architekturen ersetzt

**Test Organization:**
- Unit-Tests nach technischer Schicht
- Integrationstests fuer Controller, Commands und Persistence
- Fixtures getrennt unter `tests/Fixtures/`

**Asset Organization:**
- Bestehende Admin-Assets bleiben unter `Resources/public/js/pimcore/`
- Standard-Twig-Templates bleiben versioniert im Bundle
- Konfigurierbare Default-Templates erhalten eigenen Unterordner zur klaren Abgrenzung

### Development Workflow Integration

**Development Server Structure:**
- Kein separater Dev-Server-Architekturzweig
- Entwicklung erfolgt im bestehenden Bundle und in einer angebundenen Pimcore-Instanz

**Build Process Structure:**
- Composer-/Bundle-basierte Entwicklung
- Kein zusaetzlicher Frontend-Build als Architekturvoraussetzung fuer MVP

**Deployment Structure:**
- Bundle bleibt als verteilbares Pimcore-Bundle strukturiert
- Persistenz- und Migrationsverhalten ist vom Bundle selbst getragen, nicht von externer Deploy-Infrastruktur

## Architecture Validation Results

### Coherence Validation ✅

**Decision Compatibility:**
Die Architekturentscheidungen sind miteinander kompatibel. Die Brownfield-Foundation passt zur Pimcore-Bundle-Struktur, die Data-Architecture-Entscheidung auf Basis von Settings Store und Legacy-Fallback stuetzt die API- und Service-Grenzen, und die Projektstruktur bildet diese Entscheidungen konsistent physisch ab. Es gibt keine offensichtlichen Widersprueche zwischen Persistenzmodell, Kommunikationsmustern und Implementierungsregeln.

**Pattern Consistency:**
Die Implementierungsmuster stuetzen die Architekturentscheidungen direkt:
- Domain-Modell statt Roharrays
- zentrale Persistenzadapter
- duenne Controller-/Twig-/CLI-Schichten
- klares Logging- und Fehlerverhalten
Die Patterns verhindern genau die Arten von Inkonsistenz, die in einem Brownfield-Bundle mit mehreren AI-Agents sonst wahrscheinlich waeren.

**Structure Alignment:**
Die Zielstruktur stuetzt die Architektur gut. Besonders wichtig ist, dass `Domain/`, `Service/` und `Infrastructure/Persistence/` als explizite neue Grenzen eingefuehrt werden, ohne die bestehende Bundle-Struktur unnoetig zu zerstoeren. Dadurch bleibt das Projekt fuer Pimcore vertraeglich und gleichzeitig implementierungsfaehiger.

### Requirements Coverage Validation ✅

**Feature Coverage:**
Alle FR-Cluster aus dem PRD sind architektonisch abgedeckt:
- Maintenance-Arten verwalten
- Status setzen und planen
- Hinweise ausgeben
- Laufzeit-API und Logging
- Pimcore-10.6.9-Kompatibilitaet und Rolling-Migration

**Functional Requirements Coverage:**
Die Architektur stuetzt alle funktionalen Anforderungen:
- Admin-UI-Verwaltung ueber Controller + Services + Persistence Adapter
- Zeitfenster- und Statuslogik ueber zentrales Domain-/Service-Modell
- Twig-Hinweis-Ausgabe ueber Rendering-Schicht
- CLI ueber denselben fachlichen Kern
- Settings-Store-Migration ueber gekapselte Persistenzlogik

**Non-Functional Requirements Coverage:**
Die wichtigsten NFRs sind architektonisch adressiert:
- Kompatibilitaet mit Pimcore `10.6.9`
- Stabilitaet bestehender Integrationspunkte
- Dokumentationsklarheit fuer Migration und Konfiguration
- Beobachtbarkeit durch Logging unbekannter Tokens
- Testbarkeit trotz Legacy-Admin-UI
Performance und Skalierung sind fuer diese erste Stufe kein primaerer Treiber, wurden aber durch bewusst einfache Laufzeit- und Cache-Entscheidungen ausreichend begrenzt.

### Implementation Readiness Validation ✅

**Decision Completeness:**
Die kritischen Architekturentscheidungen sind dokumentiert:
- Foundation/Starter-Entscheidung
- Persistenzmodell
- Service- und Kommunikationsgrenzen
- Zielstruktur
- Konsistenzregeln fuer AI-Agents

**Structure Completeness:**
Die Zielstruktur ist konkret genug, damit AI-Agents wissen:
- wo neue Domain-Modelle leben
- wo Persistenzadapter hingehoeren
- wo Rendering- und Statuslogik hingehoeren
- wo Tests organisiert werden sollen

**Pattern Completeness:**
Die wichtigsten Konfliktpunkte sind adressiert:
- Naming
- Schichtgrenzen
- Persistenzzugriffe
- Logging
- Validierung
- Test- und Template-Ablage

### Gap Analysis Results

**Critical Gaps:**
- Keine

**Important Gaps:**
- Konkrete Pimcore-10-Deprecation-Hinweise sind nicht vorab katalogisiert, sondern bewusst als opportunistische Umsetzungsregel behandelt
- Die genaue Form der Settings-Store-Serialisierung ist architektonisch eingegrenzt, aber noch nicht als konkretes technisches Format festgelegt

**Nice-to-Have Gaps:**
- Explizite Beispiel-Tests fuer Controller-, Persistence- und Statuslogik
- Optional spaeter eine kleine Migrations- oder Operations-Notiz fuer Maintainer

### Validation Issues Addressed

- Greenfield-vs-Brownfield-Frage wurde zugunsten des bestehenden Bundle-Bestands geklaert
- Persistenz- und Laufzeitgrenzen wurden entkoppelt
- Projektstruktur wurde an die Entscheidungen angepasst statt generisch belassen
- Offene Produktfragen wurden aus dem Architekturpfad entfernt oder in klare Guardrails umgewandelt

### Architecture Completeness Checklist

**Requirements Analysis**

- [x] Project context thoroughly analyzed
- [x] Scale and complexity assessed
- [x] Technical constraints identified
- [x] Cross-cutting concerns mapped

**Architectural Decisions**

- [x] Critical decisions documented with versions
- [x] Technology stack fully specified
- [x] Integration patterns defined
- [x] Performance considerations addressed

**Implementation Patterns**

- [x] Naming conventions established
- [x] Structure patterns defined
- [x] Communication patterns specified
- [x] Process patterns documented

**Project Structure**

- [x] Complete directory structure defined
- [x] Component boundaries established
- [x] Integration points mapped
- [x] Requirements to structure mapping complete

### Architecture Readiness Assessment

**Overall Status:** READY WITH MINOR GAPS

**Confidence Level:** high

**Key Strengths:**
- Architektur ist konsequent auf den realen Brownfield-Bestand ausgerichtet
- Persistenz, Laufzeitlogik und UI sind klar getrennt
- AI-Agent-Konfliktstellen wurden explizit adressiert
- Zielstruktur ist konkret genug fuer Story- und Implementierungsschnitt

**Areas for Future Enhancement:**
- Konkretes Serialisierungsformat fuer Settings Store
- Opportunistische Deprecation-Bereinigung waehrend Umsetzung
- Spaetere Erweiterung fuer feinere Aktivierungs- und Sichtbarkeitslogik

### Implementation Handoff

**AI Agent Guidelines:**
- Alle Architekturentscheidungen genau wie dokumentiert umsetzen
- Kein zweites Persistenz- oder Statusmodell einfuehren
- Controller, Twig und Commands als Adapter behandeln
- Domain- und Persistence-Grenzen respektieren
- Bestehende Bundle-Integrationspunkte konservativ behandeln

**First Implementation Priority:**
Bestehende Bundle-Basis fuer Pimcore `10.6.9` konsolidieren, dabei Domain-Modell und zentrale Persistence Adapter einfuehren, bevor UI- und Rendering-Erweiterungen umgesetzt werden.
