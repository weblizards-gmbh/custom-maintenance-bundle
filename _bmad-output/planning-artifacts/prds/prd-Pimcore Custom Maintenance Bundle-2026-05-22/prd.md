---
title: Pimcore Custom Maintenance Bundle
status: draft
created: 2026-05-22
updated: 2026-05-27
---

# PRD: Pimcore Custom Maintenance Bundle

*Arbeitstitel fuer die erste oeffentliche Pimcore-10-Stufe.*

## 0. Document Purpose

Dieses PRD beschreibt die erste oeffentlich veroeffentlichbare Modernisierungsstufe des bestehenden Custom Maintenance Bundles fuer Pimcore `10.6.9`. Es dient als Produkt- und Scope-Grundlage fuer die anschliessende Architektur, UX-Detailarbeit und Story-Zerlegung. Fokus dieser Stufe ist die belastbare Wiederherstellung des fachlichen Status quo aus der bewaehrten Pimcore-`6.9.x`-Nutzung, kombiniert mit den wenigen Erweiterungen, die fuer ein oeffentlich veroeffentlichbares Produkt notwendig sind. Technische Ausgestaltung, Migrationsmechanik im Detail und tiefere Modernisierungsschritte fuer Pimcore `11`, `12` oder `2026.x` gehoeren nicht in dieses Dokument, soweit sie nicht direkt fuer die erste Stufe erforderlich sind.

## 1. Vision

Das Custom Maintenance Bundle ermoeglicht Pimcore-basierten Anwendungen einen fein granulierten Umgang mit Teilstoerungen, Wartungsfenstern und betrieblichen Einschraenkungen. Anstatt den gesamten Shop in einen globalen Maintenance-Mode zu zwingen, koennen einzelne fachliche Komponenten gezielt als eingeschraenkt markiert werden, sodass Anwendungscode und Frontend angemessen reagieren, ohne den gesamten Betrieb stillzulegen.

Die erste oeffentliche Produktstufe soll diesen bewaehrten Nutzen fuer Pimcore `10.6.9` zuverlaessig und veroeffentlichbar bereitstellen. Sie ist kein kompletter Neustart und kein Vollumbau fuer kuenftige Pimcore-Generationen, sondern ein bewusst schrittweiser Modernisierungsschritt: funktionale Paritaet mit dem etablierten Bestand, robuste Kompatibilitaet auf Pimcore `10`, bessere Konfigurierbarkeit im Admin-UI und eine migrationsfaehige Persistenzstrategie auf Basis von Pimcore Setting Stores.

## 2. Target User

### 2.1 Primary Persona

**Pimcore-Integrator oder Lead-Entwickler in einem Commerce-Projekt.** Diese Person verantwortet Betrieb und Weiterentwicklung eines Pimcore-basierten Shops oder Portals, integriert externe Systeme wie ERP, Mail, Suche oder weitere fachliche Dienste und braucht eine kontrollierte Moeglichkeit, Teilstoerungen im System sichtbar und auswertbar zu machen. Sie will nicht fuer jede betriebliche Stoerung den gesamten Shop abschalten, sondern fachlich sauber degradieren.

### 2.2 Jobs To Be Done

- Ich will Teilstoerungen fachlicher Komponenten getrennt vom globalen Pimcore-Maintenance-Mode abbilden koennen.
- Ich will Wartungen im Voraus planen koennen, damit Hinweise und Aktivierungen nicht manuell im richtigen Moment gesetzt werden muessen.
- Ich will Maintenance-Zustaende automatisiert und per CLI steuern koennen, damit Watchdogs oder Deployments darauf reagieren koennen.
- Ich will Maintenance-Zustaende im Anwendungscode sicher pruefen koennen, um UI und Fachlogik kontrolliert zu degradieren.
- Ich will das Bundle in Pimcore `10.6.9` belastbar einsetzen koennen, ohne auf eine alte Pimcore-Version festgenagelt zu sein.
- Ich will neue Maintenance-Arten ohne Dateiedits direkt im UI anlegen und verwalten koennen.

### 2.3 Non-Users (v1)

- Endnutzer des Shops; sie konsumieren nur die Auswirkungen der Hinweise, verwalten das Bundle aber nicht selbst.
- Teams, die bereits in der ersten Stufe volle Kompatibilitaet fuer Pimcore `11`, `12` oder `2026.x` erwarten.
- Teams, die in der ersten Stufe bereits eine deutlich feinere semantische Maintenance-Logik fuer Anzeige- oder Aktivierungsregeln erwarten.

### 2.4 Key User Journeys

- **UJ-1. Integrator legt eine neue fachliche Maintenance-Art im Admin-UI an.**
  - **Persona + context:** Ein Integrator erweitert einen laufenden Shop um eine neue externe Abhaengigkeit, etwa einen Versand- oder Maildienst.
  - **Entry state:** Er ist im Pimcore-Admin angemeldet und oeffnet das Bundle-UI.
  - **Path:** Er erstellt eine neue Maintenance-Art, vergibt einen alphanumerischen Token, uebernimmt die sinnvollen Defaults, speichert die Konfiguration und sieht die neue Art anschliessend in der Verwaltungsoberflaeche.
  - **Climax:** Die neue Maintenance-Art ist sofort als konfigurierbarer Zustand verfuegbar.
  - **Resolution:** Anwendungscode kann kuenftig gegen diesen Token pruefen.

- **UJ-2. Betreiber plant eine Maintenance im Voraus und laesst sie automatisch wirksam werden.**
  - **Persona + context:** Ein Betreiber kennt ein bevorstehendes Wartungsfenster eines angebundenen Drittsystems.
  - **Entry state:** Die Maintenance-Art existiert bereits.
  - **Path:** Er hinterlegt Start- und Endzeit sowie die gewuenschten Hinweisdaten im Bundle, speichert die Konfiguration und verlaesst das System.
  - **Climax:** Zum geplanten Zeitpunkt gilt die Maintenance automatisch als aktiv und der passende Hinweis wird ausgegeben.
  - **Resolution:** Nach Ablauf des Zeitfensters endet der Zustand wieder ohne manuellen Eingriff.

- **UJ-3. Anwendungscode reagiert kontrolliert auf einen aktiven fachlichen Maintenance-Zustand.**
  - **Persona + context:** Ein Entwickler implementiert degradierendes Verhalten fuer einen Shop-Bereich.
  - **Entry state:** Das Bundle ist installiert und konfiguriert.
  - **Path:** Der Code prueft den Status eines Tokens ueber die oeffentliche API und unterdrueckt daraufhin etwa Preisanzeige oder Anfrageversand.
  - **Climax:** Das Frontend bleibt nutzbar, obwohl eine Teilkomponente gestoert ist.
  - **Resolution:** Die Stoerung ist fachlich sichtbar, ohne dass der gesamte Shop abgeschaltet wird.
  - **Edge case:** Ist der gepruefte Token unbekannt oder geloescht, wird dies im Anwendungslog als Fehler protokolliert.

- **UJ-4. Ein Watchdog oder Operator aktiviert bzw. deaktiviert eine Maintenance per CLI.**
  - **Persona + context:** Ein betriebliches Hilfssystem oder ein Operator erkennt eine Stoerung oder deren Ende.
  - **Entry state:** Ein technischer Prozess kann den Pimcore-CLI-Befehl ausfuehren.
  - **Path:** Die Maintenance wird per Kommando aktiviert oder deaktiviert; Schutzregeln verhindern ungewollte Rueckschaltungen, wenn dies konfiguriert ist.
  - **Climax:** Der fachliche Zustand ist ohne Admin-UI steuerbar.
  - **Resolution:** Betriebliche Automatisierung und Shop-Verhalten bleiben gekoppelt.

## 3. Glossary

- **Maintenance-Art** — Ein fachlich definierter Maintenance-Zustand, der entweder die native Pimcore-Maintenance oder eine benutzerdefinierte Custom-Maintenance repraesentiert.
- **Native Pimcore-Maintenance** — Der bestehende globale Maintenance-Kontext von Pimcore, der im Bundle als besonderer, nicht loeschbarer Regeltyp behandelt wird.
- **Custom-Maintenance-Art** — Eine frei definierbare Maintenance-Art fuer eine fachliche oder technische Teilkomponente wie ERP, Mail oder Suche.
- **Token** — Der technische, alphanumerische Schluessel einer Maintenance-Art. Er dient als API- und Laufzeitreferenz im Anwendungscode.
- **Hinweis** — Die fuer Benutzer sichtbare Information zu einer geplanten oder aktiven Maintenance.
- **Hinweis-Template** — Ein Twig-basiertes Template, mit dem ein Hinweis ausgegeben wird.
- **Setting Store** — Die Ziel-Persistenz in Pimcore, in die die Konfiguration der ersten Stufe schreibend abgelegt wird.
- **Legacy-PHP-Datei** — Die bestehende Konfigurationsdatei `custommaintenance.php`, aus der bei der Rolling-Migration lesend uebergangsweise noch gelesen werden kann.

## 4. Features

### 4.1 Verwaltung von Maintenance-Arten

**Description:** Das Bundle muss die native Pimcore-Maintenance und frei definierbare Custom-Maintenance-Arten in einer einheitlichen Verwaltungsoberflaeche abbilden. In der ersten oeffentlichen Stufe ist das Anlegen und Loeschen von Custom-Maintenance-Arten erstmals direkt im UI moeglich. Realizes UJ-1.

**Functional Requirements:**

#### FR-1: Custom-Maintenance-Arten im UI anlegen

Ein Administrator kann im Admin-UI eine neue Custom-Maintenance-Art anlegen. Realizes UJ-1.

**Consequences (testable):**
- Das UI bietet eine Aktion zum Anlegen einer neuen Custom-Maintenance-Art.
- Der Token ist ein Pflichtfeld.
- Der Token akzeptiert nur alphanumerische Zeichen.
- Beim Anlegen werden sinnvolle Defaults gesetzt: `active = nein`, `show_info = nein`, Datums- und Zeitfelder leer bzw. `null`.

#### FR-2: Custom-Maintenance-Arten im UI loeschen

Ein Administrator kann eine bestehende Custom-Maintenance-Art im Admin-UI loeschen. Realizes UJ-1.

**Consequences (testable):**
- Das UI verlangt vor dem Loeschen einer Custom-Maintenance-Art eine explizite Sicherheitsabfrage.
- Nach dem Loeschen ist die Maintenance-Art nicht mehr in der Verwaltungsoberflaeche vorhanden.
- Nach dem Loeschen gilt der Token fuer kuenftige Pruefungen als unbekannt.
- Auch eine aktuell aktive Custom-Maintenance-Art ist nach ausdruecklicher Bestaetigung loeschbar.

#### FR-3: Native Pimcore-Maintenance schuetzen

Die Native Pimcore-Maintenance kann nicht geloescht werden.

**Consequences (testable):**
- Das UI bietet fuer die Native Pimcore-Maintenance keine Loeschaktion an oder blockiert sie serverseitig.
- Persistenz- und Verwaltungslogik behandeln die Native Pimcore-Maintenance als permanent vorhandenen Sonderfall.

### 4.2 Aktivierung, Planung und betriebliche Steuerung

**Description:** Die erste Stufe stellt fuer Pimcore `10.6.9` den bewaehrten fachlichen Kern wieder her: Maintenance-Arten koennen manuell und zeitgesteuert aktiviert bzw. deaktiviert und ueber CLI sowie betriebliche Automatisierung angesprochen werden. Realizes UJ-2, UJ-4.

**Functional Requirements:**

#### FR-4: Maintenance-Zustaende manuell setzen

Ein Administrator oder technischer Prozess kann den Status einer Maintenance-Art manuell aktivieren oder deaktivieren. Realizes UJ-2, UJ-4.

**Consequences (testable):**
- Das Bundle bietet fuer Maintenance-Arten einen speicherbaren aktiven bzw. inaktiven Zustand.
- Eine manuelle Statusaenderung wirkt ohne zusaetzliche Migration oder Dateiedits.
- Der aktivierte Zustand ist ueber Admin-UI und Laufzeitpruefung konsistent sichtbar.

#### FR-5: Geplante Zeitfenster automatisch auswerten

Das Bundle kann eine Maintenance-Art anhand hinterlegter Zeitfenster automatisch als aktiv oder inaktiv behandeln. Realizes UJ-2.

**Consequences (testable):**
- Fuer eine Maintenance-Art koennen Start- und Endzeit hinterlegt werden.
- Innerhalb eines gueltigen Zeitfensters wird die Maintenance als aktiv behandelt.
- Nach Ende des Zeitfensters endet die automatische Aktivierung ohne manuellen Eingriff.

#### FR-6: CLI-Steuerung beibehalten

Das Bundle muss fuer relevante Maintenance-Operationen ueber CLI ansprechbar bleiben. Realizes UJ-4.

**Consequences (testable):**
- Tokens koennen ueber CLI aufgelistet werden.
- Der Status eines Tokens kann ueber CLI abgefragt werden.
- Ein Token kann ueber CLI aktiviert und deaktiviert werden.

#### FR-7: Schutz vor ungewollter Rueckschaltung beibehalten

Das Bundle muss bestehende Schutzregeln gegen unerwuenschte Rueckschaltungen weiterhin unterstuetzen. Realizes UJ-4.

**Consequences (testable):**
- Eine konfigurierte Schutzregel kann eine Deaktivierung oder Rueckschaltung blockieren.
- Blockierte Rueckschaltungen fuehren nicht zu stillen Statuswechseln.

**Feature-specific NFRs:**
- Die Aktivierungslogik dieser ersten Stufe bleibt semantisch nah am bestehenden Bundle-Verhalten; [ASSUMPTION: Es wird kein fachlicher Neustart der Aktivierungsregeln fuer Pimcore 10 angestrebt, sondern primaer belastbare Paritaet].

### 4.3 Hinweise und Twig-basierte Ausgabe

**Description:** Das Bundle muss geplante oder aktive Maintenance-Zustaende fuer Benutzer sichtbar machen koennen. In der ersten Stufe wird die bestehende Ausgabe auf Twig-basierte Hinweis-Templates ausgerichtet, weil PHP-Templates nicht mehr unterstuetzt werden. Realizes UJ-2, UJ-3.

**Functional Requirements:**

#### FR-8: Hinweise fuer geplante oder aktive Maintenances ausgeben

Das Bundle kann Hinweise fuer geplante oder aktive Maintenance-Zustaende ausgeben. Realizes UJ-2, UJ-3.

**Consequences (testable):**
- Das Bundle kann mindestens aktuelle und bevorstehende Maintenances fuer Benutzer darstellen.
- Ein Hinweis kann auf eine Hilfeseite oder weiterfuehrende Information verlinken.
- Die Ausgabe erfolgt konsistent ueber die vorgesehenen Bundle-Helfer bzw. Twig-Funktionen.

#### FR-9: Hinweis-Templates auf Twig umstellen

Hinweis-Templates muessen Twig-basiert sein. Realizes UJ-2, UJ-3.

**Consequences (testable):**
- Die Hint-Ausgabe verwendet keine PHP-Templates als Zielpfad.
- Das Bundle liefert Twig-basierte Templates fuer die Hinweis-Ausgabe.
- Bestehende Integrationspunkte fuer die Hinweis-Ausgabe bleiben fuer Pimcore-10-kompatible Anwendungen nutzbar.

#### FR-10: Hinweis-Templates konfigurierbar machen

Administratoren oder Integratoren koennen die fuer Hinweise verwendeten Twig-Templates konfigurieren. Realizes UJ-3.

**Consequences (testable):**
- Das Bundle liefert zwei Twig-basierte Standard-Templates mit:
  - einem Hinweis auf eine bevorstehende Maintenance
  - einem Hinweis auf eine gegenwaertige Maintenance
- Das Bundle bietet einen konfigurierbaren Weg, fuer einzelne Maintenance-Posten eigene Twig-Templates fuer diese Hinweisarten zu bestimmen.
- Konfigurierte Templates werden bei der Ausgabe verwendet.

### 4.4 Laufzeit-API und Fehlerverhalten

**Description:** Anwendungscode muss Maintenance-Zustaende weiterhin gezielt pruefen koennen. Gleichzeitig muss das Bundle problematische Konfigurationen sichtbar machen, anstatt unbekannte Tokens stillschweigend zu ignorieren. Realizes UJ-3.

**Functional Requirements:**

#### FR-11: Maintenance-Zustaende programmatisch pruefbar halten

Anwendungscode kann den Status einer Maintenance-Art ueber die oeffentliche Bundle-API pruefen. Realizes UJ-3.

**Consequences (testable):**
- Eine Laufzeit-API fuer Statuspruefungen bleibt verfuegbar.
- Tokens koennen einzeln adressiert werden.
- Das Bundle bleibt fuer degradierendes Verhalten im Anwendungscode einsetzbar.

#### FR-12: Unbekannte Tokens sichtbar als Fehler behandeln

Prueft Anwendungscode einen unbekannten oder geloeschten Token, schreibt das Bundle einen Fehler ins Anwendungslog. Realizes UJ-3.

**Consequences (testable):**
- Unbekannte oder geloeschte Tokens werden im Log mit Fehlerniveau protokolliert.
- Der Logeintrag macht deutlich, dass der Token nicht gefunden wurde.
- Das Verhalten ist fuer geloeschte und nie vorhandene Tokens konsistent.

#### FR-13: Token-Aenderungen ohne Schutzwarnung erlauben

Ein Administrator kann den Token einer Maintenance-Art speichern, ohne dass das UI dies aufgrund moeglicher Anwendungscode-Referenzen blockiert oder zusaetzlich warnt.

**Consequences (testable):**
- Eine Token-Aenderung kann gespeichert werden.
- Das UI fuehrt keine zusaetzliche Warn- oder Blockadelogik fuer bestehende Anwendungscode-Referenzen ein.
- Die Verantwortung fuer Folgewirkungen einer Token-Aenderung liegt bewusst beim Betreiber oder Integrator.

### 4.5 Kompatibilitaet und Rolling-Migration

**Description:** Die erste Stufe ist ein oeffentlich veroeffentlichbares Pimcore-`10.6.9`-Release mit klarer Migrationsstrategie. Bestehende Installationen duerkfen in einen moderneren Persistenzpfad hineinwachsen, ohne dass die erste Stufe schon eine Komplettmigration auf spaetere Pimcore-Generationen loesen muss.

**Functional Requirements:**

#### FR-14: Pimcore 10.6.9 verbindlich unterstuetzen

Das Bundle unterstuetzt Pimcore `10.6.9` als verbindliche Zielplattform.

**Consequences (testable):**
- Installation und Grundkonfiguration sind auf Pimcore `10.6.9` moeglich.
- Die Kernfaehigkeiten aus diesem PRD funktionieren auf Pimcore `10.6.9`.

#### FR-15: Deprecations Richtung Pimcore 11 reduzieren

Soweit innerhalb der ersten Stufe realistisch, reduziert das Bundle Deprecation-Hinweise, die den spaeteren Weg Richtung Pimcore `11` blockieren oder unnötig erschweren.

**Consequences (testable):**
- Relevante Deprecations werden nicht als eigener geplanter Feature-Block behandelt.
- Tauchen waehrend der Pimcore-`10.6.9`-Arbeit konkrete Deprecation-Hinweise mit klarer Handlungsanweisung auf, duerfen sie im Rahmen der Umsetzung mit behoben werden.
- Deprecation-Beseitigung bleibt Mittel zum Zweck und ersetzt nicht den Fokus auf Pimcore-`10.6.9`-Funktionsfaehigkeit.

**Out of Scope:**
- Eine Supportzusage fuer Pimcore `11`, `12` oder `2026.x`.
- Allgemeine Vorab-Haertung fuer unbekannte oder noch nicht konkret handlungsleitende Pimcore-`11`-Themen.

#### FR-16: Lesend Legacy-PHP-Datei, schreibend Setting Store

Die Persistenz folgt einer Rolling-Migration auf Pimcore Setting Stores.

**Consequences (testable):**
- Das Bundle liest zunaechst aus dem Setting Store.
- Ist dort kein Datensatz vorhanden, liest es aus der Legacy-PHP-Datei.
- Ist auch dort nichts vorhanden, erzeugt oder verwendet es einen sinnvollen Default mit Native Pimcore-Maintenance.
- Schreibvorgaenge landen immer im Setting Store.

#### FR-17: Migration bestehender Installationen zulassen

Das Bundle darf fuer bestehende Installationen einen Migrationspfad nutzen, solange er kontrolliert und fuer die erste Stufe akzeptabel bleibt.

**Consequences (testable):**
- Bestehende Installationen mit Legacy-PHP-Datei koennen funktional weiterarbeiten oder kontrolliert uebernommen werden.
- Die Migration ist mit dem Rolling-Release-Verhalten vereinbar.
- Die Migration kann fuer Administratoren still im Hintergrund ablaufen.
- Die Doku beschreibt die Migrationslogik und das Fallback-Verhalten deutlich.
- [ASSUMPTION: Eine einmalige implizite Uebernahme beim ersten Schreibvorgang ist zulaessig, sofern sie nachvollziehbar bleibt.]

**Cross-cutting NFRs:**
- **NFR-1 Compatibility:** Die Kernfaehigkeiten des Bundles muessen auf Pimcore `10.6.9` belastbar funktionieren.
- **NFR-2 Stability of public integrations:** Bestehende oeffentliche Integrationspunkte wie Twig-Funktionen, CLI-Befehle und Laufzeit-API sollen soweit moeglich stabil bleiben.
- **NFR-3 Documentation clarity:** Installation, Rolling-Migration, Persistenz-Fallback und Template-Konfiguration muessen in der Dokumentation klar beschrieben werden.
- **NFR-4 Observability:** Unbekannte Tokens und relevante Fehlersituationen duerfen nicht stillschweigend verschwinden, sondern muessen nachvollziehbar sichtbar werden.

## 5. Non-Goals (Explicit)

- Keine tiefere fachliche Neuerfindung der Anzeige-Logik fuer Hinweise.
- Keine tiefere fachliche Neuerfindung der Aktivierungs- und Ausloeselogik fuer Maintenances.
- Keine Supportzusage fuer Pimcore `11`, `12` oder `2026.x` in dieser ersten Stufe.
- Keine Modernisierung der Admin-Oberflaeche auf das neue Pimcore-`2026.x`-UI.
- Keine breite funktionale Expansion ueber den bewaehrten Kern plus die bereits priorisierten Erweiterungen hinaus.

## 6. MVP Scope

### 6.1 In Scope

- Funktionale Wiederherstellung des bewaehrten Kernverhaltens auf Pimcore `10.6.9`
- Manuelle und geplante Aktivierung bzw. Deaktivierung von Maintenance-Arten
- Hinweis-Ausgabe fuer geplante und aktive Maintenances
- Twig-basierte Hinweis-Templates
- UI-gestuetztes Anlegen und Loeschen von Custom-Maintenance-Arten
- Rolling-Migration auf Setting Stores mit Lesefallback auf Legacy-PHP-Datei
- Erhalt der Laufzeit-API und CLI-Funktionalitaet
- Fehlerlogging fuer unbekannte oder geloeschte Tokens

### 6.2 Out of Scope for MVP

- Feineres Regelsystem fuer Sichtbarkeit von Hinweisen
- Feineres Regelsystem dafuer, wann eine Maintenance als aktiv gilt
- Neue UI-Plattform fuer kuenftige Pimcore-Generationen
- Vollstaendige Zukunftssicherung fuer Pimcore `11+`

## 7. Success Metrics

**Primary**

- **SM-1**: Die erste oeffentliche Version laesst sich auf Pimcore `10.6.9` installieren und die Kernfaehigkeiten aus FR-1 bis FR-17 sind funktional verfuegbar. Validates FR-14, FR-16, FR-17.
- **SM-2**: Ein Integrator kann ohne Dateiedit mindestens eine neue Custom-Maintenance-Art im UI anlegen, speichern und wieder loeschen. Validates FR-1, FR-2, FR-3.
- **SM-3**: Bestehende Kernnutzungen des Bundles aus dem Pimcore-6-Bestand bleiben fuer die erste Pimcore-10-Stufe fachlich wieder moeglich: Planung, manuelle Steuerung, zeitgesteuerte Aktivierung, Hinweis-Ausgabe, API- und CLI-Nutzung. Validates FR-4, FR-5, FR-6, FR-8, FR-11.

**Secondary**

- **SM-4**: Die Persistenz arbeitet nach der definierten Rolling-Migrationsstrategie, ohne dass neue Konfiguration nur noch in der Legacy-PHP-Datei landet. Validates FR-16, FR-17.
- **SM-5**: Hinweis-Ausgabe ist Twig-basiert und ohne PHP-Template-Pfad einsetzbar. Validates FR-9, FR-10.
- **SM-6**: Unbekannte oder geloeschte Tokens werden im Betrieb sichtbar als Fehler protokolliert. Validates FR-12.
- **SM-7**: Token-Aenderungen und Loeschungen folgen der definierten Produktlogik: Speichern ohne Zusatzwarnung, Loeschen nach Sicherheitsabfrage. Validates FR-2, FR-13.

**Counter-metrics (do not optimize)**

- **SM-C1**: Nicht auf moeglichst viele neue Features optimieren, wenn dadurch die belastbare Pimcore-`10.6.9`-Faehigkeit leidet. Counterbalances SM-1.
- **SM-C2**: Nicht auf aggressive Modernisierung Richtung Pimcore `11+` optimieren, wenn dadurch die erste veroeffentlichbare Stufe fuer Pimcore `10.6.9` instabil wird. Counterbalances SM-1, SM-4.
- **SM-C3**: Nicht auf harte Schutzmechanismen im UI optimieren, wenn dadurch die einfache Verwalzbarkeit der Maintenance-Arten unnoetig leidet. Counterbalances SM-2, SM-7.

## 8. Open Questions

Aktuell keine offenen Produktfragen mehr. Technische Detailpruefungen, die sich erst waehrend der Implementierung zeigen, werden in Architektur oder Umsetzung behandelt.

## 9. Assumptions Index

- §4.2 / FR-7 — [ASSUMPTION: Es wird kein fachlicher Neustart der Aktivierungsregeln fuer Pimcore 10 angestrebt, sondern primaer belastbare Paritaet.]
- §4.5 / FR-17 — [ASSUMPTION: Eine einmalige implizite Uebernahme beim ersten Schreibvorgang ist zulaessig, sofern sie nachvollziehbar bleibt.]
