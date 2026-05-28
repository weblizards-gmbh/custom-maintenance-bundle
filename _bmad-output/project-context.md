---
project_name: 'Pimcore Custom Maintenance Bundle'
user_name: 'Thomas'
date: '2026-05-22'
sections_completed: ['technology_stack', 'language_specific_rules', 'framework_specific_rules', 'testing_rules', 'code_quality_style_rules', 'development_workflow_rules', 'critical_dont_miss_rules']
existing_patterns_found: 8
status: 'complete'
rule_count: 87
optimized_for_llm: true
---

# Projektkontext fuer AI Agents

_Diese Datei enthaelt kritische Regeln und Muster, die AI Agents bei Implementierungen in diesem Projekt beachten muessen. Fokus auf kurze, projektspezifische Regeln statt allgemeiner Best Practices._

---

## Technology Stack & Versions

- PHP `>=8.1`
- Pimcore `^10.0`
- Symfony Service Container ueber YAML-Service-Definitionen in `src/CustomMaintenanceBundle/Resources/config/services.yml`
- Twig fuer Frontend-Ausgabe, erweitert ueber `Weblizards\CustomMaintenanceBundle\Twig\Extensions`
- Carbon fuer Datums- und Zeitfensterlogik
- PHPUnit `^10.5` ist als Dev-Dependency vorgesehen
- Composer PSR-4-Autoload:
  - `Weblizards\CustomMaintenanceBundle\` -> `src/CustomMaintenanceBundle`
  - `Weblizards\CustomMaintenanceBundle\Test\` -> `tests`
- Pimcore-Admin-Integration basiert auf klassischem ExtJS/Prototype-Stil-JavaScript in `Resources/public/js/pimcore/`
- Bundle-Ressourcen folgen der klassischen Pimcore-/Symfony-Bundle-Struktur unter `Resources/` fuer `config`, `views`, `public` und Installationsdateien
- Laufzeitkonfiguration wird nicht ueber Symfony-Config, sondern ueber die PHP-Datei `PIMCORE_PRIVATE_VAR . '/config/custommaintenance.php'` gehalten

## Critical Implementation Rules

### Language-Specific Rules

- Neue PHP-Dateien beginnen mit `<?php` und `declare(strict_types=1);`
- Namespaces muessen dem bestehenden PSR-4-Mapping folgen: `Weblizards\CustomMaintenanceBundle\...`
- Klassen- und Dateinamen bleiben PascalCase; Methoden und Properties bleiben camelCase
- Bevorzuge typed properties und konkrete Rueckgabetypen, wenn bestehende Signaturen dadurch nicht gebrochen werden
- Constructor Injection ist der Standard; keine neue Service-Lokalisierung ueber den Container einfuehren
- Bestehende Service-Grenzen beibehalten:
  - `Service/` fuer Fachlogik
  - `Twig/` fuer Twig-Funktionen als duenne Delegation
  - `Command/` fuer CLI-Aufrufe
  - `Controller/` fuer HTTP-/Admin-Endpunkte
- Bundle-Konfiguration nicht auf Symfony-Config umstellen, solange die bestehende Dateistruktur auf `custommaintenance.php` basiert
- Datumswerte im Bundleformat halten:
  - Datum: `d.m.Y`
  - Zeit: `H:i`
- Fuer Zeitfenster und Vergleiche vorhandene Carbon-Nutzung fortsetzen statt neue Date-Handling-Stile einzumischen
- Keine Debug-Ausgaben wie `dump()` oder `console.log()` in neuem oder geaendertem Produktionscode hinterlassen
- Exceptions im bestehenden Stil nur dort werfen, wo der Call-Site-Flow sie bereits erwartet; bei neuen APIs klare, enge Fehlergrenzen setzen
- Oeffentliche Signaturen konservativ behandeln, weil das Bundle von Twig, Console, Controller-Routen und externer Pimcore-Konfiguration konsumiert wird

### Framework-Specific Rules

- Pimcore-Bundle-Struktur beibehalten; neue Bundle-Bausteine an den vorhandenen Orten ergaenzen statt neue Architekturachsen einzufuehren
- Service-Registrierung erfolgt ueber `Resources/config/services.yml`; neue Services, Twig-Extensions oder Controller konsistent dort verdrahten
- Controller fuer den Admin-Bereich bleiben kompatibel zur vorhandenen Pimcore-/Symfony-Annotation-Routing-Struktur
- Admin-Funktionalitaet nicht in Twig oder Services ziehen; HTTP-Ein-/Ausgabe bleibt im Controller
- Twig-Erweiterungen bleiben duenne Adapter ueber `Twig\Extensions`; Fachlogik bleibt im `StatusService` oder in neuen Services
- Frontend-HTML fuer Wartungshinweise weiter ueber Twig-Templates in `Resources/views/partials/` rendern statt HTML in PHP-Strings aufzubauen
- Bundle-Assets und Admin-Assets weiterhin ueber die Bundle-Mechanik und die in `WeblizardsCustomMaintenanceBundle` registrierten Pfade anbinden
- Admin-UI ist Legacy-Pimcore-UI auf ExtJS/Prototype-Stil-Basis:
  - bei Aenderungen bestehenden Stil fortsetzen
  - keine modernen Frontend-Frameworks oder Build-Pipelines einfuehren
  - keine Modulsyntax oder Transpilation voraussetzen
- Admin-UI verwendet Pimcore-Globals wie `pimcore.globalmanager`, `pimcore.plugin.broker` und `Ext.*`; neue UI-Erweiterungen muessen in dieses Modell passen
- Installationslogik bleibt im Bundle-Installer; Initialdateien und Uebersetzungen dort pflegen statt implizit zur Laufzeit anzulegen
- Laufzeitdaten fuer Wartungen kommen aus `custommaintenance.php`; Framework-Code darf diese Struktur lesen und schreiben, aber nicht stillschweigend umformen
- Routen, Command-Namen, Twig-Funktionsnamen und Asset-Pfade als oeffentliche Integrationspunkte behandeln und nur aendern, wenn die Aenderung explizit gewollt ist

### Testing Rules

- Testaenderungen muessen sich am tatsaechlichen Repo-Zustand orientieren, nicht nur an Dokuannahmen
- `composer.json` sieht PHPUnit `^10.5` und `vendor/bin/phpunit` vor; neue automatisierte Tests sollen darauf aufbauen
- Wenn neue Tests eingefuehrt werden, das fehlende `tests/`-Verzeichnis und eine passende PHPUnit-Struktur explizit anlegen statt implizit vorauszusetzen
- Testdateien unter `tests/` ablegen und am PSR-4-Dev-Namespace `Weblizards\CustomMaintenanceBundle\Test\` ausrichten
- Bei Aenderungen an Service-Logik zuerst auf isolierbare Unit-Tests fuer `Service/`, `Config`-nahe Logik und Datumslogik zielen
- Bei Aenderungen an Commands Rueckgabecodes, Konsolenausgaben und Optionen gezielt testen
- Bei Aenderungen an Twig-Erweiterungen primaer die delegierte Service-Logik testen; Template-Rendering nur dort testen, wo Ausgabeform wirklich fachlich relevant ist
- Admin-UI auf ExtJS-Basis ist schwer automatisiert testbar; dort bevorzugt Backend- und Integrationslogik absichern statt fragile JS-UI-Tests zu erfinden
- Dokumentation und Tests synchron halten:
  - keine Testbefehle dokumentieren, die im Repo nicht existieren
  - neue Testinfrastruktur in `README` oder `docs/` nachziehen, wenn sie eingefuehrt wird
- Vorhandene Fehler oder Inkonsistenzen im Bestand nicht in Tests einzementieren; Tests sollen gewolltes Verhalten absichern, nicht offensichtliche Debug- oder Legacy-Artefakte

### Code Quality & Style Rules

- Kleine, gezielte Aenderungen bevorzugen; keine grossen Refactorings ohne ausdruecklichen Auftrag
- Bestehende Ordnerstruktur und Verantwortlichkeiten respektieren statt Logik quer durch das Bundle neu zu verteilen
- Oeffentliche Integrationsflaechen konservativ behandeln:
  - Service-Namen
  - Command-Signaturen
  - Twig-Funktionsnamen
  - Routen
  - Konfigurationsschluessel
- Neue Kommentare nur dort ergaenzen, wo der fachliche Zweck oder ein Legacy-Zwang sonst unklar waere
- Vorhandene Englisch-/Deutsch-Mischung im Code nicht weiter ausweiten; bei neuen fachlichen Texten konsistent mit dem jeweiligen Kontext bleiben
- Uebersetzbare UI-Texte nicht hart kodieren, wenn sie in bestehende Translation- oder Konfigurationspfade passen
- Ressourcen klar trennen:
  - PHP-Logik nicht in Templates verschieben
  - HTML nicht in Controller oder Services aufbauen
  - Installationsdefaults nicht in Laufzeitcode duplizieren
- Keine toten Imports, Debug-Reste oder ungenutzten Properties in neuem Code hinterlassen
- Bei Aenderungen immer auch auf offensichtliche Bestandsprobleme im betroffenen Bereich achten und sie nur dann mit anfassen, wenn das Risiko kontrollierbar bleibt
- Dokumentationsaussagen nur dann aktualisieren oder ergaenzen, wenn sie nach der Codeaenderung weiterhin tatsaechlich ausfuehrbar und wahr sind

### Development Workflow Rules

- Bei neuen Aenderungen zuerst den betroffenen Integrationspunkt identifizieren:
  - Console
  - Admin-Controller
  - Twig-Extension
  - Service-Logik
  - Installer
  - Resource-Dateien
- Aenderungen so planen, dass Bundle-API und Konfigurationsstruktur fuer bestehende Pimcore-Installationen moeglichst stabil bleiben
- Fuer lokale Verifikation primaer die im Repo vorgesehenen Wege nutzen:
  - Composer-Autoload
  - `vendor/bin/phpunit` bzw. `composer test`, wenn Tests vorhanden sind
- BMAD-Artefakte fuer AI-Arbeit unter `_bmad-output/` halten; sie sind Arbeitskontext, nicht automatisch Produktdokumentation
- Vor Dokuupdates pruefen, ob die beschriebenen Befehle, Dateinamen und Paketnamen im aktuellen Repo wirklich stimmen
- Brownfield-Regel: erst den Ist-Zustand lesen, dann erweitern; keine Annahmen aus generischen Symfony- oder Pimcore-Defaults ueber den Projektbestand stellen
- Wenn ein Eingriff bestehende Konfigurationen oder Integrationspunkte brechen koennte, die Aenderung explizit sichtbar und begruendbar halten statt still umzudeuten

### Critical Don't-Miss Rules

- Keine bestehenden Dokumentationsangaben blind uebernehmen:
  - `README.md` nennt aktuell noch einen falschen `composer require`-Paketnamen
  - `docs/development_testing.md` beschreibt Tests, die im aktuellen Repo so nicht vorhanden sind
- Keine Debug-Artefakte in produktivem Code belassen oder reproduzieren:
  - PHP-Debugausgaben wie `dump()`
  - JavaScript-Debugausgaben wie `console.log()`
- Die Konfigurationsstruktur unter `custommaintenance.php` ist Teil des faktischen Bundle-Vertrags; Schluessel wie `pimcore`, `custom`, `active`, `fixed`, `show_info`, `planned` und `document` nicht leichtfertig aendern
- Der Token `pimcore` ist ein Sonderfall und wird in Teilen der Logik anders behandelt als `custom`-Tokens; diese Unterscheidung bei Erweiterungen erhalten
- Datums- und Zeitformate sind fachlich relevant; keine stillen Formatwechsel einfuehren, die Admin-UI, Speicherung und Laufzeitlogik auseinanderlaufen lassen
- Oeffentliche Integrationspunkte nicht versehentlich brechen:
  - Command-Name `weblizards:custommaintenance:control`
  - Admin-Routen unter `/admin/weblizards_custom_maintenance/...`
  - Twig-Funktionen wie `indicateCustomMaintenance` und `isMaintenanceActive`
  - Bundle-Asset-Pfade unter `/bundles/weblizardscustommaintenance/...`
- Legacy-Code nicht ungeprueft modernisieren, wenn dadurch die Pimcore-10-Kompatibilitaet oder vorhandene Admin-Integration gefaehrdet wird
- Bei Arbeiten im betroffenen Bereich offensichtliche Bestandsprobleme bewusst einordnen:
  - nur beheben, wenn sie im Auftrag liegen oder das Risiko der eigentlichen Aenderung direkt betreffen
  - nicht stillschweigend semantische Aenderungen hinter "Cleanup" verstecken

---

## Usage Guidelines

**For AI Agents:**

- Diese Datei vor Implementierungen lesen
- Im Zweifel die restriktivere, bestaendigere Variante waehlen
- Oeffentliche Integrationspunkte und Konfigurationsstruktur konservativ behandeln
- Neue projektspezifische Regeln hier ergaenzen, wenn sich stabile Muster etablieren

**For Humans:**

- Diese Datei knapp und projektspezifisch halten
- Bei Aenderungen an Stack, Tests oder Integrationspunkten aktualisieren
- Veraltete Regeln entfernen, wenn sie nicht mehr zum Codebestand passen
- Doku und Projektkontext gemeinsam pflegen, wenn sich Arbeitsablaeufe aendern

Last Updated: `2026-05-22`
