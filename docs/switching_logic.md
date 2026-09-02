# Schaltlogik

Dieses Dokument beschreibt die gegenwärtige und geplante Schaltlogik des Bundles auf fachlicher Ebene. Es dient als Referenz für Betrieb, Weiterentwicklung und künftige Epic-5-Arbeit.

## Zwei getrennte Stränge

Das Bundle behandelt zwei verwandte, aber getrennte Probleme:

1. Bestimmte Bereiche oder Integrationen können unbenutzbar werden oder bewusst in einen degradierten Zustand versetzt werden.
2. Nutzer sollen zu passenden Zeitpunkten angemessen auf diese Umstände hingewiesen werden.

Daraus folgen zwei getrennte Logikachsen:

- **Maintenance-Schaltlogik**: Ist ein Bereich fachlich gerade in Maintenance?
- **Hinweis-Logik**: Soll einem Nutzer dazu gerade ein Hinweis gezeigt werden?

Diese Achsen dürfen gekoppelt sein, sind aber nicht identisch.

## Quellenmodell

Die wirksame Schaltlogik entsteht aus mehreren Quellen mit unterschiedlicher Autorität:

- **Admin-UI**
- **Zeitsteuerung**
- **technische Schaltpfade** wie CLI, Monitoring, Jobs oder künftige APIs

Wichtig:

- Das **Admin-UI** gilt als bewusste, interaktive Willensbekundung.
- Die **Zeitsteuerung** ist Teil der vom Admin gewollten Konfiguration.
- **CLI, Monitoring, Jobs und sonstige technische Pfade** gelten als nicht-interaktive Schaltquellen.

## Semantik von `fixed`

`fixed` ist **kein Zustand einer Maintenance**, sondern eine **Eigenschaft zum Schutz vor technischen Umschaltern**.

`fixed` bedeutet:

- nicht-interaktive technische Schaltpfade dürfen den Maintenance-Eintrag nicht normal umschalten
- ein expliziter `override` darf diesen Schutz bewusst brechen
- `fixed` beschreibt nicht den fachlich wirksamen Maintenance-Zustand

### Was `fixed` betrifft

`fixed` betrifft insbesondere:

- CLI-Schaltbefehle
- Monitoring- oder Job-gesteuerte Schaltbefehle
- künftige APIs oder Integrationen, die ohne interaktive Bedienung Zustandsänderungen schreiben

### Was `fixed` nicht betrifft

`fixed` blockiert ausdrücklich **nicht**:

- Änderungen im Admin-UI
- die Wirksamkeit einer konfigurierten Zeitsteuerung
- die reine Auswertung des effektiven Zustands
- die Hinweis-Logik

Begründung:
Die Zeitsteuerung ist kein fremder Automatismus, sondern Ausdruck des vom Admin konfigurierten Willens.

## Normativer Kernsatz

`fixed` schützt vor nicht-interaktiven Zustandsänderungen durch technische Schaltpfade. Es schützt nicht vor Admin-Änderungen im UI und nicht vor der Wirksamkeit einer konfigurierten Zeitsteuerung.

## Zustandsmodell der Maintenance

Für die eigentliche Maintenance-Schaltung soll zwischen drei Modi unterschieden werden:

- `inaktiv`
- `aktiv`
- `zeitgesteuert`

### Semantik der Modi

#### `inaktiv`

Die Maintenance ist nicht wirksam, bis ein bewusster Schaltimpuls sie aktiviert oder in einen anderen Modus überführt.

#### `aktiv`

Die Maintenance ist sofort wirksam und bleibt es, bis ein bewusster Schaltimpuls sie deaktiviert oder in einen anderen Modus überführt.

#### `zeitgesteuert`

Die Maintenance folgt einem konfigurierten Startzeitpunkt `ab` und optional einem Endzeitpunkt `bis`.

- `ab` ist verpflichtend.
- `bis` ist optional.

##### `zeitgesteuert` mit `ab` und `bis`

Die Maintenance ist nur innerhalb des definierten Zeitfensters wirksam.

##### `zeitgesteuert` mit `ab` und ohne `bis`

Die Maintenance wird ab dem konfigurierten Startzeitpunkt automatisch wirksam und bleibt aktiv, bis ein tatsächlicher Abschalt-Impuls erfolgt.

Beispiel:

- Es ist bekannt, dass ein ERP-System ab 12:00 Uhr in Wartung geht.
- Es ist aber noch unklar, wann die Wartung endet.
- Die Maintenance soll deshalb ab 12:00 Uhr automatisch aktiv werden.
- Sie bleibt danach aktiv, bis das ERP-Team oder ein anderer berechtigter Pfad sie bewusst wieder abschaltet.

Wichtig:
Diese offene Zeitsteuerung ist kein Sonderfall von `fixed`, sondern Teil der eigentlichen Zustandsdefinition von `zeitgesteuert`.
Im aktuellen Implementierungsstand wird eine bereits angelaufene offene Zeitsteuerung durch einen zulässigen technischen Abschalt-Impuls beendet, indem der Abschaltzeitpunkt als faktisches Ende (`bis`) gesetzt wird.

## Zustandsmodell des Hinweises

Die Hinweislogik ist von der eigentlichen Maintenance-Zeitplanung unabhängig konfigurierbar. Gleichzeitig bleibt sie fachlich an die Maintenance gekoppelt, weil ein aktiver Maintenance-Zustand Vorrang vor einer bloßen Ankündigung hat.

Für die Hinweis-Schaltung soll zwischen vier Modi unterschieden werden:

- `stets inaktiv`
- `stets aktiv`
- `zeitplanung`
- `gekoppelt an aktive Maintenance`

### Semantik der Modi

#### `stets inaktiv`

Es wird kein Hinweis angezeigt.

#### `stets aktiv`

Es wird immer ein Hinweis angezeigt.

#### `zeitplanung`

Der Hinweis folgt einem eigenen Startzeitpunkt `ab` und optional einem Endzeitpunkt `bis`.

- `ab` ist verpflichtend.
- `bis` ist optional.

##### `zeitplanung` mit `ab` und `bis`

Der Hinweis wird nur innerhalb dieses Hinweis-Zeitfensters angezeigt.

##### `zeitplanung` mit `ab` und ohne `bis`

Der Hinweis wird ab dem konfigurierten Startzeitpunkt angezeigt und bleibt sichtbar, bis ein anderer Moduswechsel oder Abschalt-Impuls erfolgt.

Im aktuellen Runtime-Verhalten bedeutet das ausdrücklich:

- Der Hinweis bleibt auch dann sichtbar, wenn keine künftige Maintenance mehr geplant ist.
- Eine aktive Maintenance kann diesen sichtbaren Hinweis fachlich auf den `current`-Hinweis übersteuern.
- Ohne aktive Maintenance bleibt der Hinweis sichtbar, auch wenn er technisch weiterhin über den vorhandenen `upcoming`-Template-Pfad gerendert wird.

Validierung im aktuellen Implementierungsstand:

- `zeitgesteuert` bei Custom-Maintenances verlangt immer ein vollständiges `ab`
- ein optionales `bis` muss immer vollständig als Datum/Uhrzeit-Paar vorliegen
- `automatic` bei Hinweisen verlangt immer ein vollständiges `show_info_from`
- dieselbe Strenge gilt im Admin-UI auch für den Pimcore-Sondereintrag

#### `gekoppelt an aktive Maintenance`

Der Hinweis wird genau dann angezeigt, wenn die zugehörige Maintenance effektiv aktiv ist.

Wichtig:
Hier zählt nicht die bloße Rohkonfiguration, sondern der tatsächlich wirksame Maintenance-Zustand.

## Priorität zwischen Hinweisplanung und aktiver Maintenance

Auch bei unabhängiger Hinweis-Zeitplanung muss die tatsächliche Aktivität der Maintenance Vorrang haben.

Das bedeutet:

- Ist nur die Hinweis-Zeitplanung erreicht, ohne dass eine künftige Maintenance vorliegt, bleibt der Hinweis als sichtbarer Hinweis aktiv.
- Ist eine Maintenance nur geplant, kann ein `upcoming`-Hinweis erscheinen.
- Wird die Maintenance wirksam aktiv, schlägt dies jede bloße Hinweis-Planung.
- Aus einem `upcoming`-Hinweis wird dann der `current`-Hinweis.

Normativ:

- `current` schlägt `upcoming`
- `current` schlägt den bloß sichtbaren Hinweis aus der unabhängigen Hinweis-Zeitplanung
- aktive Maintenance schlägt bloße Hinweis-Planung
- die Hinweis-Zeitplanung bleibt unabhängig konfigurierbar, erzeugt aber keinen Vorrang gegenüber einer tatsächlich aktiven Maintenance

## Diagnose und Simulation

Für Betrieb und Weiterentwicklung gibt es im Admin-Panel einen schreibgeschützten Diagnose-Pfad.

Er dient dazu,

- den aktuellen Formularstand oder die aktuell geladene Konfiguration gegen einen frei gewählten Simulationszeitpunkt auszuwerten
- wirksame Maintenance- und Hinweis-Zustände pro Eintrag sichtbar zu machen
- die jeweilige Ursache wie manueller Status, Zeitfenster oder technischer Aktiv-Override nachvollziehbar zu machen

Wichtig:

- Die Diagnose verwendet denselben fachlichen Auswertungskern wie Runtime und Hinweis-Rendering.
- Die Diagnose speichert keine Konfiguration.
- CLI und künftige APIs bleiben operative Schaltpfade und werden dadurch nicht zu Konfigurations- oder Planungswerkzeugen erweitert.

## Effektive Entscheidungsreihenfolge

Für die wirksame Maintenance-Entscheidung müssen Schreibschutz und Zustandsauswertung getrennt betrachtet werden.

### Basis-Konfiguration und technischer Override

Für die Maintenance-Schaltung sollen zwei Ebenen unterschieden werden:

1. **Basis-Konfiguration**
2. **technischer Override**

Die Basis-Konfiguration beschreibt den vom Admin gewollten Grundzustand:

- `inaktiv`
- `aktiv`
- `zeitgesteuert`

Der technische Override beschreibt einen zusätzlichen, nicht-interaktiven Eingriff, der die Basis-Konfiguration vorübergehend überlagern kann.

Im aktuellen Zielmodell ist damit nur ein **technischer Aktivierungs-Override** gemeint. Ein eigener technischer Deaktivierungs-Override ist nicht vorgesehen.

Wichtig:

- Die Basis-Konfiguration bleibt erhalten.
- Ein technischer Eingriff zerstört die Zeitsteuerung nicht.
- Nach Wegfall des technischen Overrides fällt die Auswertung auf die Basis-Konfiguration zurück.

### 1. `fixed` entscheidet nicht über die Wirksamkeit

`fixed` ist kein Teil der eigentlichen Aktiv/Inaktiv-Auswertung.

`fixed` entscheidet nur:

- ob ein nicht-interaktiver technischer Pfad den konfigurierten Zustand ändern darf
- oder ob dieser Schreibversuch blockiert werden muss

Die Frage, ob eine Maintenance effektiv aktiv ist, wird erst danach aus dem gültigen Modus und den Zeitwerten ausgewertet.

### 2. Die Wirksamkeit ergibt sich aus dem konfigurierten Modus

Die eigentliche Maintenance-Wirksamkeit ergibt sich aus:

- dem konfigurierten Modus `inaktiv`, `aktiv` oder `zeitgesteuert`
- den dazugehörigen Zeitwerten `ab` und optional `bis`

Die Auswertung kennt danach nur noch die gültige Konfiguration, nicht mehr den ursprünglichen Schaltpfad.

### 3. Auswertung der Maintenance-Modi

#### `inaktiv`

Die Maintenance ist effektiv inaktiv.

#### `aktiv`

Die Maintenance ist effektiv aktiv.

#### `zeitgesteuert`

Die Maintenance wird abhängig von den konfigurierten Zeitwerten ausgewertet:

- vor `ab`: effektiv inaktiv
- ab `ab` ohne `bis`: effektiv aktiv
- zwischen `ab` und `bis`: effektiv aktiv
- nach `bis`: effektiv inaktiv

### 4. Quellen greifen beim Schreiben ein, nicht bei der Auswertung

Die verschiedenen Quellen konkurrieren nicht in einer parallelen Laufzeitentscheidung, sondern beim Schreiben der Konfiguration.

#### Admin-UI

- darf immer schreiben
- darf `fixed` ignorieren
- darf Modus und Zeitwerte ändern

#### Zeitsteuerung

- schreibt nicht selbst
- wird nur ausgewertet
- ist Teil der vom Admin gewollten Konfiguration

#### CLI, API, Monitoring, Jobs

- dürfen schreiben, wenn `fixed = false`
- dürfen nicht normal schreiben, wenn `fixed = true`
- dürfen mit explizitem `override` trotzdem schreiben
- dürfen weiterhin nur operative Aktivierungs- oder Deaktivierungsimpulse ausführen
- konfigurieren weder Zeitsteuerung noch Hinweisplanung

Normativ für diese operativen Impulse:

- `aktivieren` setzt einen **technischen Aktivierungs-Override**
- `deaktivieren` entfernt diesen **technischen Aktivierungs-Override**
- `deaktivieren` bedeutet dabei nicht automatisch, dass die Basis-Konfiguration auf `inaktiv` umgeschrieben wird
- ist eine offene Zeitsteuerung ohne `bis` bereits gestartet, beendet ein zulässiges `deaktivieren` diese offene Phase durch Setzen eines effektiven Endzeitpunkts

Ein separater technischer Deaktivierungs-Override wird bewusst nicht eingeführt, weil dafür derzeit kein belastbarer fachlicher Anwendungsfall gesehen wird.

### 5. Normative Konfliktregel

Konflikte zwischen verschiedenen Quellen werden nicht während der Zustandsauswertung „ausgefochten“, sondern beim Schreibvorgang entschieden.

Normativ:

- der effektive Zustand ergibt sich immer aus der zuletzt gültig gesetzten Konfiguration
- die Zeitsteuerung wird anschließend auf diese gültige Konfiguration angewendet
- `fixed` schützt vor unzulässigen technischen Schreibzugriffen, nicht vor der späteren Wirksamkeit der gültigen Konfiguration

### 6. Beispielszenarien

#### Offene Zeitsteuerung

- Modus `zeitgesteuert`
- `ab = 12:00`
- kein `bis`

Ergebnis:

- vor 12:00 Uhr: inaktiv
- ab 12:00 Uhr: aktiv
- danach weiterhin aktiv, bis ein echter Abschalt-Impuls erfolgt

Wird in diesem Zustand per CLI oder künftig per API ein Deaktivierungs-Impuls ausgeführt, gilt:

- bei `fixed = false` darf dieser Impuls den technischen Override entfernen; ist die offene Zeitsteuerung bereits angelaufen, wird der Abschaltzeitpunkt zugleich als Ende gesetzt
- bei `fixed = true` wird dieser Impuls ohne explizites `override` blockiert

Wichtig:
Die offene Zeitsteuerung ohne `bis` erzeugt keinen unauflösbaren Dauerzustand. Sie bleibt durch zulässige operative Abschalt-Impulse beendbar.

#### Vorzeitiger Ausfall vor geplanter Zeitsteuerung

- Basis-Konfiguration: `zeitgesteuert`
- `ab = 18:00`
- vor 18:00 Uhr ist die Maintenance daher eigentlich noch nicht aktiv
- um 16:00 Uhr fällt der Bereich ungeplant aus
- ein technischer Pfad setzt daraufhin `aktivieren`

Ergebnis:

- es wird ein technischer Aktivierungs-Override gesetzt
- die Maintenance ist sofort aktiv

Fällt der technische Grund später weg und wird `deaktivieren` ausgeführt, gilt:

- der technische Override wird entfernt
- die Basis-Konfiguration bleibt unverändert erhalten
- vor 18:00 Uhr ist die Maintenance danach wieder inaktiv
- ab 18:00 Uhr wird sie wieder aktiv, weil die Zeitsteuerung weiterbesteht

Wichtig:
Technische Aktivierung und technische Deaktivierung sollen damit als temporäre Überlagerung der Basis-Konfiguration verstanden werden, nicht als irreversible Zerstörung einer bestehenden Zeitplanung.

#### Admin-Schaltung gegen Monitoring-Rücknahme

- Admin setzt im UI auf `aktiv`
- `fixed = true`
- ein Monitoring versucht später per CLI auf `inaktiv` zu schalten

Ergebnis:

- der CLI-Schreibversuch wird blockiert
- der wirksame Zustand bleibt aktiv

#### Zeitfenster trotz `fixed`

- Modus `zeitgesteuert`
- `ab = 22:00`
- `bis = 02:00`
- `fixed = true`

Ergebnis:

- um 23:00 Uhr ist die Maintenance aktiv
- nach 02:00 Uhr ist sie wieder inaktiv
- `fixed` verhindert diese Auswertung nicht, weil die Zeitsteuerung Teil der gültigen Admin-Konfiguration ist

## Beispiel

### Monitoring gegen bewusste Aktivierung

- Ein Admin aktiviert eine Maintenance im UI, weil konkrete Wartungsarbeiten stattfinden sollen.
- Parallel existiert eine externe Überwachung einer ERP-Schnittstelle.
- Diese Überwachung aktiviert die Maintenance per CLI, wenn die Schnittstelle stört, und will sie später wieder deaktivieren, sobald die Schnittstelle antwortet.

Problem:

- Die Überwachung kennt den tatsächlichen, willentlich herbeigeführten Gesamtzustand nicht.
- Sie könnte eine vom Admin bewusst gesetzte oder über Zeitsteuerung wirksame Maintenance blind wieder ausschalten.

Lösung durch `fixed`:

- Ist `fixed = true`, dürfen technische Schaltpfade die Maintenance nicht ohne expliziten `override` zurückschalten.
- Das Admin-UI bleibt dennoch voll handlungsfähig.
- Eine konfigurierte Zeitsteuerung bleibt weiterhin wirksam.

## Konsequenzen für die Weiterentwicklung

Die Epic-5-Arbeit an der Schaltlogik sollte mindestens diese Punkte schärfen:

- klare Prioritäten zwischen manuellem Zustand, Zeitsteuerung und technischen Schaltpfaden
- saubere Trennung zwischen Maintenance-Wirksamkeit und Hinweis-Sichtbarkeit
- explizite Regeln für reservierte Sonderfälle wie `pimcore`
- gezielte Absicherung von Randfällen und widersprüchlichen Eingaben

## Separater Folgekandidat

Die Idee einer völlig autarken statischen HTML-Fallback-Seite für harte Webserver-Ausfallfälle bleibt fachlich relevant, gehört aber nicht automatisch in denselben Schaltlogik-Scope. Sie ist deshalb separat unter [hard_fallback_pages.md](hard_fallback_pages.md) beschrieben.

## Übergang vom Altmodell

Die Schärfung der Schaltlogik soll nicht nur dokumentarisch erfolgen, sondern in eine echte Migration der vorhandenen Konfiguration überführt werden.

### Grundsatz

- Die Transition auf die geschärften Begriffe und Modi soll als Migration umgesetzt werden.
- Neue Review- oder Marker-Datenstrukturen für Altbestand sollen dabei vermieden werden.
- Da das Bundle derzeit nur in wenigen Projekten im Einsatz ist, ist eine manuelle Nachkontrolle nach dem Upgrade zumutbar.

### Migration der Maintenance-Zustände

Altbestand:

- `active = true|false`
- `planned.from`
- `planned.to`

Zielmodell:

- `inaktiv`
- `aktiv`
- `zeitgesteuert` mit `ab` und optional `bis`

Empfohlene Abbildung:

- `active = false` und kein gültiges Zeitfenster
  - → `inaktiv`
- `active = true` und kein gültiges Zeitfenster
  - → `aktiv`
- `active = false` und gültiges `from`/`to`
  - → `zeitgesteuert` mit `ab = from`, `bis = to`
- `active = true` und gültiges `from`/`to`
  - → `aktiv`

Bewusstes Migrationsprinzip:

- Bestehendes aktives Verhalten hat Vorrang vor einer späteren semantischen Verfeinerung.
- Es wird keine zusätzliche Marker-Struktur eingeführt, um historische Mischzustände gesondert zu kennzeichnen.

### Migration der Hinweis-Zustände

Altbestand:

- `show_info = never|always|automatic`
- `show_info_from`
- kein `show_info_to`

Zielmodell:

- `stets inaktiv`
- `stets aktiv`
- `zeitplanung`
- `gekoppelt an aktive Maintenance`

Empfohlene Abbildung:

- `never`
  - → `stets inaktiv`
- `always`
  - → `stets aktiv`
- `automatic`
  - → `zeitplanung` mit `ab = show_info_from` und offenem Ende

Wichtig für den aktuellen Implementierungsstand:

- Dieses offene Ende bleibt auch zur Laufzeit wirksam.
- Ist `show_info_from` erreicht, bleibt der Hinweis sichtbar, bis ein anderer Moduswechsel oder Abschalt-Impuls erfolgt.

Wichtig:

- `gekoppelt an aktive Maintenance` hat keine direkte Legacy-Entsprechung.
- Dieser Modus wird deshalb nicht aus Altbestand erraten, sondern erst für neu konfigurierte Einträge verwendet.

### Ungültige oder unvollständige Legacy-Werte

Ungültige oder unvollständige Altwerte sollen nicht durch neue Datenstrukturen als Review-Fall markiert werden.

Stattdessen gilt:

- Die Migration bleibt so konservativ wie möglich.
- Eine manuelle Nachkontrolle nach dem Upgrade ist zulässig und erwartet.
- Die Migration soll keine künstlichen Zusatzmarker erzeugen, die nur den Übergang verwalten.
