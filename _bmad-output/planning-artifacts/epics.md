---
stepsCompleted: [1, 2, 3, 4]
inputDocuments:
  - /mnt/develop/PHPStormProjects/custom-maintenance-bundle/_bmad-output/planning-artifacts/prds/prd-Pimcore Custom Maintenance Bundle-2026-05-22/prd.md
  - /mnt/develop/PHPStormProjects/custom-maintenance-bundle/_bmad-output/planning-artifacts/architecture.md
  - /mnt/develop/PHPStormProjects/custom-maintenance-bundle/docs/development_testing.md
status: complete
---

# Pimcore Custom Maintenance Bundle - Epic Breakdown

## Overview

This document provides the complete epic and story breakdown for Pimcore Custom Maintenance Bundle, decomposing the requirements from the PRD, UX Design if it exists, and Architecture requirements into implementable stories.

## Requirements Inventory

### Functional Requirements

FR1: Ein Administrator kann im Admin-UI eine neue Custom-Maintenance-Art anlegen.  
FR2: Ein Administrator kann eine bestehende Custom-Maintenance-Art im Admin-UI nach Sicherheitsabfrage loeschen; auch aktive Custom-Maintenance-Arten bleiben loeschbar.  
FR3: Die native Pimcore-Maintenance ist nicht loeschbar und wird als permanenter Sonderfall behandelt.  
FR4: Ein Administrator oder technischer Prozess kann den Status einer Maintenance-Art manuell aktivieren oder deaktivieren.  
FR5: Das Bundle kann eine Maintenance-Art anhand hinterlegter Zeitfenster automatisch als aktiv oder inaktiv behandeln.  
FR6: Das Bundle bleibt fuer relevante Maintenance-Operationen ueber CLI ansprechbar.  
FR7: Bestehende Schutzregeln gegen unerwuenschte Rueckschaltungen bleiben erhalten.  
FR8: Das Bundle kann Hinweise fuer geplante oder aktive Maintenance-Zustaende ausgeben.  
FR9: Hinweis-Templates muessen Twig-basiert sein; PHP-Templates sind ausgeschlossen.  
FR10: Administratoren oder Integratoren koennen Twig-Hinweis-Templates fuer einzelne Maintenance-Posten konfigurieren; zwei Standard-Templates fuer bevorstehende und gegenwaertige Maintenance werden mitgeliefert.  
FR11: Anwendungscode kann den Status einer Maintenance-Art ueber die oeffentliche Bundle-API pruefen.  
FR12: Prueft Anwendungscode einen unbekannten oder geloeschten Token, schreibt das Bundle einen Fehler ins Anwendungslog.  
FR13: Ein Administrator kann den Token einer Maintenance-Art speichern, ohne dass das UI dies wegen moeglicher Anwendungscode-Referenzen blockiert oder warnt.  
FR14: Das Bundle unterstuetzt Pimcore `10.6.9` als verbindliche Zielplattform.  
FR15: Deprecation-Hinweise Richtung Pimcore `11` werden nur opportunistisch im Rahmen der Pimcore-10.6.9-Arbeit behandelt, wenn konkrete Handlungsanweisungen sichtbar sind.  
FR16: Persistenz folgt einer Rolling-Migration: lesend Settings Store, Fallback auf Legacy-PHP-Datei, sonst Default; schreibend immer Settings Store.  
FR17: Bestehende Installationen duerfen kontrolliert migriert oder uebernommen werden; die Migration laeuft still im Hintergrund und wird in der Doku klar beschrieben.

### NonFunctional Requirements

NFR1: Die Kernfaehigkeiten des Bundles muessen auf Pimcore `10.6.9` belastbar funktionieren.  
NFR2: Bestehende oeffentliche Integrationspunkte wie Twig-Funktionen, CLI-Befehle und Laufzeit-API sollen soweit moeglich stabil bleiben.  
NFR3: Installation, Rolling-Migration, Persistenz-Fallback und Template-Konfiguration muessen in der Dokumentation klar beschrieben werden.  
NFR4: Unbekannte Tokens und relevante Fehlersituationen duerfen nicht stillschweigend verschwinden, sondern muessen nachvollziehbar sichtbar werden.

### Additional Requirements

- Brownfield-first: Kein externer Starter, sondern kontrollierte Weiterentwicklung des bestehenden Pimcore-Bundles.  
- Keine neuen Bundle-spezifischen Datenbanktabellen fuer die erste Stufe.  
- Die Bundle-Konfiguration wird intern in ein kanonisches Domain-Modell normalisiert, statt mit Roharrays in mehreren Schichten zu arbeiten.  
- Persistenz wird ueber einen zentralen Persistence Adapter gekapselt.  
- Strukturierte Konfiguration im Settings Store wird kontrolliert serialisiert gespeichert.  
- Controller, Twig-Extension und CLI bleiben duenne Adapter auf gemeinsame Services.  
- Statusbewertung, Notice-Rendering und Konfigurationspersistenz muessen ueber dieselbe kanonische Konfigurationsquelle laufen.  
- Admin-UI bleibt im klassischen Pimcore-ExtJS-/Legacy-Modell.  
- Tests sollen unter `tests/` organisiert werden, getrennt nach Unit- und Integration-Tests.  
- Domain- und Infrastructure-Grenzen sollen explizit eingefuehrt werden, ohne die bestehende Bundle-Struktur zu zerstoeren.  
- Logging unbekannter Tokens erfolgt zentral und nicht UI-spezifisch.  
- Das konkrete Serialisierungsformat fuer den Settings Store ist in der Umsetzung festzulegen, bleibt aber ein internes Infrastrukturdetail.  
- Der erste technische Implementierungsschritt ist die Konsolidierung der Bundle-Basis fuer Pimcore `10.6.9` mit Domain-Modell und zentralen Persistence Adaptern.

### UX Design Requirements

Kein separates UX-Design-Dokument vorhanden.

### FR Coverage Map

FR1: Epic 2 - Neue Custom-Maintenance-Art im UI anlegen  
FR2: Epic 2 - Custom-Maintenance-Art im UI loeschen  
FR3: Epic 2 - Native Pimcore-Maintenance schuetzen  
FR4: Epic 3 - Maintenance manuell aktivieren/deaktivieren  
FR5: Epic 3 - Zeitfenster automatisch auswerten  
FR6: Epic 3 - CLI-Steuerung beibehalten  
FR7: Epic 3 - Schutz vor ungewollter Rueckschaltung beibehalten  
FR8: Epic 4 - Hinweise fuer geplante/aktive Maintenances ausgeben  
FR9: Epic 4 - Hinweis-Templates auf Twig umstellen  
FR10: Epic 4 - Hinweis-Templates konfigurierbar machen  
FR11: Epic 3 - Laufzeit-API fuer Statuspruefungen beibehalten  
FR12: Epic 3 - Unbekannte Tokens sichtbar als Fehler behandeln  
FR13: Epic 2 - Token-Aenderungen ohne Schutzwarnung erlauben  
FR14: Epic 1 - Pimcore 10.6.9 verbindlich unterstuetzen  
FR15: Epic 1 - Deprecations Richtung Pimcore 11 opportunistisch behandeln  
FR16: Epic 1 - Lesend Legacy-PHP-Datei, schreibend Setting Store  
FR17: Epic 1 - Migration bestehender Installationen zulassen

## Epic List

### Epic 1: Pimcore-10-Basis und Rolling-Migration bereitstellen
Integratoren koennen das Bundle auf Pimcore `10.6.9` installieren, bestehende Konfigurationen weiterverwenden oder kontrolliert in den neuen Persistenzpfad uebernehmen und auf einer stabilen technischen Basis weiterarbeiten.  
**FRs covered:** FR14, FR15, FR16, FR17

### Epic 2: Maintenance-Arten im Admin-UI verwalten
Administratoren koennen native und benutzerdefinierte Maintenance-Arten im Admin-UI sicher verwalten, neue Arten anlegen, Tokens pflegen und Custom-Arten kontrolliert loeschen.  
**FRs covered:** FR1, FR2, FR3, FR13

### Epic 3: Maintenance-Zustaende steuern und im Anwendungscode auswerten
Betreiber und Integratoren koennen Maintenance-Zustaende manuell, geplant und per CLI steuern; Anwendungscode kann sie stabil auswerten, und unbekannte Tokens werden sichtbar geloggt.  
**FRs covered:** FR4, FR5, FR6, FR7, FR11, FR12

### Epic 4: Benutzerhinweise und Twig-Templates fuer Maintenances bereitstellen
Anwendungen koennen geplante und aktive Maintenances nutzerseitig sichtbar machen, mit Twig-basierten Standard-Templates starten und pro Maintenance-Posten eigene Hinweis-Templates konfigurieren.  
**FRs covered:** FR8, FR9, FR10

## Epic 1: Pimcore-10-Basis und Rolling-Migration bereitstellen

Integratoren koennen das Bundle auf Pimcore `10.6.9` installieren, bestehende Konfigurationen weiterverwenden oder kontrolliert in den neuen Persistenzpfad uebernehmen und auf einer stabilen technischen Basis weiterarbeiten.

### Story 1.1: Bundle-Basis fuer Pimcore 10.6.9 konsolidieren

As a Integrator,
I want das Bundle auf Pimcore `10.6.9` lauffaehig und sauber verdrahtet einsetzen koennen,
So that die erste oeffentliche Version auf einer stabilen technischen Basis startet.

**Acceptance Criteria:**

**Given** eine Pimcore-`10.6.9`-Installation mit eingebundenem Bundle
**When** das Bundle geladen und initialisiert wird
**Then** sind Bundle-Klasse, Service-Wiring, Routing und Assets kompatibel mit Pimcore `10.6.9`
**And** offensichtliche technische Altlasten, die die Grundfunktionsfaehigkeit auf Pimcore `10.6.9` verhindern, sind bereinigt

**Given** bestehende oeffentliche Integrationspunkte des Bundles
**When** die technische Basis modernisiert wird
**Then** bleiben CLI, Twig-Funktionen und Laufzeit-API soweit moeglich stabil
**And** es wird keine neue externe API oder neue Persistenzachse eingefuehrt

### Story 1.2: Kanonisches Domain-Modell fuer Maintenance-Konfiguration einfuehren

As a Entwickler,
I want ein kanonisches Domain-Modell fuer Maintenance-Konfigurationen nutzen,
So that Konfigurationslogik nicht mehr verteilt ueber Roharrays in mehreren Schichten lebt.

**Acceptance Criteria:**

**Given** die bestehende Bundle-Konfiguration
**When** Konfigurationsdaten im Bundle verarbeitet werden
**Then** werden sie in ein kanonisches Domain-Modell normalisiert
**And** dieses Modell deckt mindestens Maintenance-Eintraege, Token, Zeitfenster und Hinweis-Konfiguration ab

**Given** Controller, Services und Twig-nahe Logik
**When** sie mit Bundle-Konfiguration arbeiten
**Then** greifen sie nicht direkt auf unstrukturierte Persistenzrohdaten zu
**And** Persistenzdetails bleiben ausserhalb des Domain-Modells gekapselt

### Story 1.3: Settings-Store-Persistenz mit Legacy-Read-Fallback einfuehren

As a Integrator,
I want dass das Bundle Konfigurationen aus dem neuen Persistenzpfad lesen und dorthin schreiben kann,
So that die Migration von der Legacy-PHP-Datei kontrolliert und ohne Bruch erfolgt.

**Acceptance Criteria:**

**Given** vorhandene Konfiguration im Pimcore Settings Store
**When** das Bundle Konfiguration liest
**Then** wird der Settings Store als primaere Quelle verwendet

**Given** keine Konfiguration im Settings Store, aber eine vorhandene Legacy-PHP-Datei
**When** das Bundle Konfiguration liest
**Then** wird die Legacy-PHP-Datei als Read-Fallback verwendet

**Given** weder Settings Store noch Legacy-PHP-Datei enthalten Bundle-Konfiguration
**When** das Bundle initial Konfiguration aufloest
**Then** wird ein sinnvoller Default mit nativer Pimcore-Maintenance erzeugt
**And** es werden keine weiteren Custom-Maintenance-Arten implizit erzeugt

**Given** ein gueltiger Schreibvorgang auf die Konfiguration
**When** das Bundle persistiert
**Then** wird immer in den Settings Store geschrieben
**And** es erfolgt keine Rueckschreibung in die Legacy-PHP-Datei

### Story 1.4: Rolling-Migration fuer bestehende Installationen absichern und dokumentieren

As a Integrator,
I want bestehende Installationen kontrolliert in den neuen Persistenzpfad uebernehmen koennen,
So that das Upgrade auf die Pimcore-10-Version ohne sichtbaren Migrationsbruch moeglich ist.

**Acceptance Criteria:**

**Given** eine bestehende Installation mit Legacy-PHP-Konfiguration
**When** das Bundle unter Pimcore `10.6.9` betrieben wird
**Then** kann die bestehende Konfiguration weiterhin gelesen und fachlich genutzt werden
**And** die Migration darf fuer Administratoren im Hintergrund ablaufen

**Given** ein erfolgreicher Schreibvorgang nach Nutzung einer Legacy-Konfiguration
**When** das Bundle die Konfiguration persistiert
**Then** liegt der kanonische Stand anschliessend im Settings Store

**Given** die erste oeffentliche Version des Bundles
**When** ein Integrator die Dokumentation nutzt
**Then** sind Rolling-Migration, Lesefallback, Schreibziel und Default-Verhalten klar beschrieben

### Story 1.5: Opportunistische Pimcore-10-Deprecations im Basisschnitt mitbehandeln

As a Maintainer,
I want konkrete Pimcore-10-Deprecation-Hinweise mit Handlungsanweisung im Rahmen der Basismodernisierung mitbehandeln,
So that vermeidbare spaetere Reibung Richtung Pimcore `11` reduziert wird, ohne den Scope aufzublasen.

**Acceptance Criteria:**

**Given** waehrend der Pimcore-`10.6.9`-Konsolidierung auftretende Deprecation-Hinweise
**When** diese konkrete Handlungsanweisungen fuer betroffene Stellen enthalten
**Then** duerfen sie im Rahmen derselben Basisarbeiten mit behoben werden

**Given** unscharfe oder nur allgemein zukunftsbezogene Deprecation-Themen
**When** sie keinen klaren unmittelbaren Handlungsbedarf fuer die Pimcore-`10.6.9`-Faehigkeit ausloesen
**Then** werden sie nicht zum eigenen Umsetzungsblock in Epic 1 gemacht

## Epic 2: Maintenance-Arten im Admin-UI verwalten

Administratoren koennen native und benutzerdefinierte Maintenance-Arten im Admin-UI sicher verwalten, neue Arten anlegen, Tokens pflegen und Custom-Arten kontrolliert loeschen.

### Story 2.1: Maintenance-Arten aus der kanonischen Konfiguration im Admin-UI darstellen

As a Administrator,
I want alle vorhandenen Maintenance-Arten im Admin-UI aus einer einheitlichen Konfigurationsquelle sehen,
So that ich native und benutzerdefinierte Eintraege zentral verwalten kann.

**Acceptance Criteria:**

**Given** eine geladene Bundle-Konfiguration
**When** das Admin-UI geoeffnet wird
**Then** werden native Pimcore-Maintenance und vorhandene Custom-Maintenance-Arten aus derselben kanonischen Konfigurationsquelle dargestellt
**And** die Darstellung basiert nicht auf einem separaten, UI-spezifischen Datenmodell

**Given** Maintenance-Arten mit bestehenden Werten
**When** das UI die Daten laedt
**Then** sind relevante Felder fuer Verwaltung und Bearbeitung im UI verfuegbar
**And** die Werte entsprechen dem kanonischen Konfigurationsstand

### Story 2.2: Neue Custom-Maintenance-Art mit Defaults anlegen

As a Administrator,
I want im Admin-UI eine neue Custom-Maintenance-Art mit sinnvollen Defaults anlegen,
So that ich neue fachliche Stoerungs- oder Wartungstypen ohne Dateiedits verwalten kann.

**Acceptance Criteria:**

**Given** das Admin-UI fuer Maintenance-Verwaltung
**When** ich eine neue Custom-Maintenance-Art anlege
**Then** steht mir ein Pflichtfeld fuer den technischen Token zur Verfuegung
**And** der Token akzeptiert nur alphanumerische Zeichen

**Given** eine neu angelegte Custom-Maintenance-Art
**When** sie initial erzeugt wird
**Then** werden sinnvolle Defaults gesetzt
**And** `active` ist standardmaessig deaktiviert
**And** `show_info` ist standardmaessig deaktiviert
**And** Datums- und Zeitfelder sind initial leer bzw. `null`

### Story 2.3: Bestehende Maintenance-Art bearbeiten und Token speichern

As a Administrator,
I want bestehende Maintenance-Arten inklusive Token aendern und speichern koennen,
So that ich meine Konfiguration im laufenden Betrieb anpassen kann.

**Acceptance Criteria:**

**Given** eine bestehende Maintenance-Art im Admin-UI
**When** ich ihre bearbeitbaren Felder aendere und speichere
**Then** werden die Aenderungen ueber die zentrale Konfigurationslogik persistiert
**And** die Aktualisierung landet im Settings Store

**Given** eine bestehende Custom-Maintenance-Art
**When** ich den Token aendere und speichere
**Then** wird die Aenderung nicht durch zusaetzliche Warn- oder Blockadelogik verhindert
**And** die Verantwortung fuer moegliche Folgewirkungen bleibt beim Betreiber oder Integrator

### Story 2.4: Native Pimcore-Maintenance im UI als geschuetzten Sonderfall behandeln

As a Administrator,
I want die native Pimcore-Maintenance im UI verwalten, aber nicht loeschen koennen,
So that der zentrale Sonderfall des Bundles technisch geschuetzt bleibt.

**Acceptance Criteria:**

**Given** die native Pimcore-Maintenance im Admin-UI
**When** ich ihre Darstellung oder verfuegbare Aktionen sehe
**Then** ist sie als besonderer, permanenter Eintrag erkennbar
**And** es wird keine Loeschaktion angeboten oder serverseitig zugelassen

**Given** ein Loeschversuch gegen die native Pimcore-Maintenance
**When** dieser technisch ausgeloest wird
**Then** wird er blockiert
**And** der Sonderfall bleibt auch ausserhalb der UI-Logik erhalten

### Story 2.5: Custom-Maintenance-Arten kontrolliert loeschen

As a Administrator,
I want benutzerdefinierte Maintenance-Arten nach Bestaetigung loeschen koennen,
So that nicht mehr benoetigte Konfigurationseintraege aus dem System entfernt werden koennen.

**Acceptance Criteria:**

**Given** eine bestehende Custom-Maintenance-Art im Admin-UI
**When** ich den Loeschvorgang ausloese
**Then** wird eine explizite Sicherheitsabfrage verlangt

**Given** eine bestaetigte Loeschaktion fuer eine Custom-Maintenance-Art
**When** der Vorgang abgeschlossen wird
**Then** ist der Eintrag nicht mehr in der Verwaltungsoberflaeche vorhanden
**And** der Token gilt fuer kuenftige Pruefungen als unbekannt

**Given** eine aktuell aktive Custom-Maintenance-Art
**When** ich ihre Loeschung ausdruecklich bestaetige
**Then** darf sie geloescht werden
**And** es erfolgt keine zusaetzliche Aktiv-Blockade fuer den Loeschpfad

## Epic 3: Maintenance-Zustaende steuern und im Anwendungscode auswerten

Betreiber und Integratoren koennen Maintenance-Zustaende manuell, geplant und per CLI steuern; Anwendungscode kann sie stabil auswerten, und unbekannte Tokens werden sichtbar geloggt.

### Story 3.1: Manuellen Aktiv-/Inaktiv-Status ueber die neue Konfigurationsbasis auswerten

As a Betreiber,
I want den manuellen Status einer Maintenance-Art verlaesslich setzen und auswerten koennen,
So that fachliche Teilstoerungen gezielt aktiviert und beendet werden koennen.

**Acceptance Criteria:**

**Given** eine vorhandene Maintenance-Art mit gespeichertem Status
**When** der Status manuell auf aktiv oder inaktiv gesetzt wird
**Then** wird der Zustand ueber die kanonische Konfigurationsbasis gespeichert und ausgewertet
**And** der Status ist konsistent fuer Admin-UI und Laufzeitpruefung verfuegbar

**Given** eine inaktive oder aktive Maintenance-Art
**When** ihr Zustand zur Laufzeit abgefragt wird
**Then** liefert das Bundle das fachlich erwartete Aktiv-/Inaktiv-Ergebnis
**And** die Statusauswertung basiert nicht auf einem separaten Nebenpfad

### Story 3.2: Geplante Zeitfenster in die Statusbewertung integrieren

As a Betreiber,
I want geplante Start- und Endzeitpunkte fuer Maintenances automatisch auswerten lassen,
So that Wartungsfenster ohne manuellen Eingriff wirksam werden.

**Acceptance Criteria:**

**Given** eine Maintenance-Art mit hinterlegtem Start- und Endzeitpunkt
**When** sich die aktuelle Zeit innerhalb des geplanten Zeitfensters befindet
**Then** wird die Maintenance als aktiv behandelt

**Given** ein geplantes Zeitfenster, das noch nicht begonnen hat oder bereits abgelaufen ist
**When** der Status ausgewertet wird
**Then** wird die Maintenance entsprechend ausserhalb des Zeitfensters nicht ueber die Planungslogik aktiviert

**Given** Datums- und Zeitwerte im bestehenden Bundle-Format
**When** sie verarbeitet werden
**Then** bleiben die fachlich erwarteten Formate `d.m.Y` und `H:i` kompatibel

### Story 3.3: CLI-Steuerung auf die gemeinsame Status- und Konfigurationslogik ausrichten

As a Operator,
I want Maintenance-Arten weiterhin ueber CLI steuern und abfragen koennen,
So that betriebliche Prozesse und Watchdogs denselben fachlichen Kern nutzen wie UI und Laufzeitlogik.

**Acceptance Criteria:**

**Given** das Bundle in einer Pimcore-`10.6.9`-Installation
**When** ich Tokens ueber CLI aufliste
**Then** werden die verfuegbaren Maintenance-Typen aus der gemeinsamen Konfigurationsquelle ermittelt

**Given** ein vorhandener Token
**When** ich seinen Status ueber CLI abfrage oder ihn aktiviere bzw. deaktiviere
**Then** laufen diese Vorgaenge ueber dieselbe fachliche Status- und Konfigurationslogik wie im restlichen Bundle
**And** es wird kein separater CLI-spezifischer Logikpfad eingefuehrt

### Story 3.4: Schutz vor ungewollter Rueckschaltung beibehalten

As a Betreiber,
I want bestehende Schutzregeln gegen unerwuenschte Rueckschaltungen beibehalten,
So that automatisierte oder manuelle Deaktivierungen nicht gegen fachliche Schutzregeln verstossen.

**Acceptance Criteria:**

**Given** eine Maintenance-Art mit aktiver Schutzregel gegen Rueckschaltung
**When** eine Deaktivierung oder Rueckschaltung ausgelost wird
**Then** wird der Vorgang fachlich blockiert

**Given** ein blockierter Rueckschaltvorgang
**When** das Bundle den Vorgang verarbeitet
**Then** erfolgt kein stiller Statuswechsel
**And** das Verhalten ist fuer CLI und sonstige Statuspfade konsistent

### Story 3.5: Laufzeit-API fuer Statuspruefungen stabil halten

As a Entwickler,
I want Maintenance-Zustaende weiterhin ueber die Bundle-API im Anwendungscode pruefen koennen,
So that ich Shop-Funktionen kontrolliert degradieren kann.

**Acceptance Criteria:**

**Given** Anwendungscode, der einen bekannten Token prueft
**When** die Laufzeit-API aufgerufen wird
**Then** liefert sie ein zur gemeinsamen Statuslogik konsistentes Ergebnis

**Given** bestehende Nutzung der Bundle-API in Host-Anwendungen
**When** das Bundle fuer Pimcore `10.6.9` modernisiert wird
**Then** bleiben die oeffentlichen Integrationspunkte soweit moeglich stabil
**And** es wird kein neuer, konkurrierender Laufzeitpfad eingefuehrt

### Story 3.6: Unbekannte oder geloeschte Tokens sichtbar loggen

As a Betreiber,
I want unbekannte oder geloeschte Tokens als Fehler im Anwendungslog sehen,
So that fehlerhafte Integrationen oder veraltete Anwendungscode-Referenzen sichtbar werden.

**Acceptance Criteria:**

**Given** Anwendungscode, der einen unbekannten oder geloeschten Token prueft
**When** die Laufzeitpruefung erfolgt
**Then** schreibt das Bundle einen Fehler ins Anwendungslog
**And** der Logeintrag macht deutlich, dass der Token nicht gefunden wurde

**Given** verschiedene Laufzeitpfade fuer Statuspruefungen
**When** unbekannte Tokens auftreten
**Then** ist das Logging-Verhalten konsistent
**And** unbekannte Tokens werden nicht in einem Pfad still ignoriert und in einem anderen geloggt

## Epic 4: Benutzerhinweise und Twig-Templates fuer Maintenances bereitstellen

Anwendungen koennen geplante und aktive Maintenances nutzerseitig sichtbar machen, mit Twig-basierten Standard-Templates starten und pro Maintenance-Posten eigene Hinweis-Templates konfigurieren.

### Story 4.1: Twig-basierte Standard-Hinweis-Templates bereitstellen

As a Integrator,
I want mitgelieferte Twig-Templates fuer bevorstehende und gegenwaertige Maintenances nutzen koennen,
So that die Hinweis-Ausgabe auf Pimcore `10.6.9` ohne PHP-Templates sofort einsatzfaehig ist.

**Acceptance Criteria:**

**Given** das modernisierte Bundle
**When** Hinweis-Templates fuer Maintenances verwendet werden
**Then** stehen zwei Twig-basierte Standard-Templates zur Verfuegung
**And** eines deckt bevorstehende Maintenances ab
**And** eines deckt gegenwaertige Maintenances ab

**Given** die erste oeffentliche Pimcore-10-Version
**When** die Hinweis-Ausgabe eingebunden wird
**Then** wird kein PHP-Template-Pfad mehr vorausgesetzt oder verwendet

### Story 4.2: Hinweis-Ausgabe fuer bevorstehende und aktive Maintenances auf Twig umstellen

As a Integrator,
I want Hinweise fuer geplante und aktive Maintenances ueber Twig-basierte Bundle-Ausgabe erzeugen,
So that Anwender sichtbare Maintenance-Informationen auf kompatible Weise erhalten.

**Acceptance Criteria:**

**Given** eine geplante oder aktive Maintenance
**When** die Hinweis-Ausgabe ueber die Bundle-Helfer oder Twig-Funktionen erfolgt
**Then** wird der passende Hinweis ueber Twig-basiertes Rendering erzeugt

**Given** ein Hinweis mit weiterfuehrender Information
**When** er ausgegeben wird
**Then** kann eine Hilfeseite oder ein weiterfuehrender Link eingebunden werden

**Given** bestehende Integrationspunkte fuer Hinweis-Ausgabe
**When** das Bundle auf Pimcore `10.6.9` modernisiert wird
**Then** bleiben diese Integrationspunkte soweit moeglich nutzbar
**And** die Ausgabe basiert auf der neuen Rendering-Schicht statt auf PHP-Templates

### Story 4.3: Template-Auswahl pro Maintenance-Posten konfigurierbar machen

As a Administrator,
I want pro Maintenance-Posten eigene Twig-Hinweis-Templates konfigurieren koennen,
So that unterschiedliche Maintenance-Arten unterschiedlich kommuniziert werden koennen.

**Acceptance Criteria:**

**Given** ein Maintenance-Posten in der Konfiguration
**When** ich die Hinweis-Template-Konfiguration bearbeite
**Then** kann ich fuer die vorgesehenen Hinweisarten eigene Twig-Templates hinterlegen

**Given** fuer einen Maintenance-Posten konfigurierte eigene Twig-Templates
**When** ein bevorstehender oder gegenwaertiger Hinweis ausgegeben wird
**Then** verwendet das Bundle die konfigurierte Template-Referenz fuer diesen Maintenance-Posten

**Given** kein individuell konfiguriertes Template fuer einen Maintenance-Posten
**When** ein Hinweis gerendert wird
**Then** faellt das Bundle auf die mitgelieferten Standard-Templates zurueck

### Story 4.4: Notice-Daten und Rendering-Pfad zentralisieren

As a Entwickler,
I want Notice-Daten und Template-Auswahl ueber eine gemeinsame Rendering-Schicht beziehen,
So that Twig-Ausgabe, Statuslogik und Konfiguration konsistent zusammenarbeiten.

**Acceptance Criteria:**

**Given** die Hinweis-Ausgabe fuer bevorstehende oder aktive Maintenances
**When** die benoetigten Daten fuer Rendering und Template-Auswahl ermittelt werden
**Then** erfolgt dies ueber eine zentrale Notice- bzw. Rendering-Schicht
**And** Twig-Extension und sonstige Aufrufer sprechen nicht direkt mit Persistenzdetails

**Given** verschiedene Maintenance-Pfade mit Hinweisen
**When** die Rendering-Logik erweitert oder angepasst wird
**Then** bleibt die Template-Auswahl konsistent
**And** es entstehen keine parallelen Logikpfade fuer Notice-Daten

### Story 4.5: Dokumentation fuer Twig-Hinweise und Template-Konfiguration aktualisieren

As a Integrator,
I want klare Dokumentation fuer Twig-basierte Hinweise und ihre Konfiguration haben,
So that ich die neue Hinweis- und Template-Logik korrekt in Projekten einsetzen kann.

**Acceptance Criteria:**

**Given** die erste oeffentliche Pimcore-10-Version des Bundles
**When** ein Integrator die Dokumentation liest
**Then** ist beschrieben, wie Twig-basierte Hinweise eingebunden werden
**And** es ist dokumentiert, dass PHP-Templates nicht mehr unterstuetzt werden

**Given** konfigurierbare Templates pro Maintenance-Posten
**When** die Dokumentation aktualisiert wird
**Then** beschreibt sie Standard-Templates, Konfigurationsweg und Fallback-Verhalten nachvollziehbar
