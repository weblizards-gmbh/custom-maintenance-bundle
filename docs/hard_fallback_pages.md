# Harte HTML-Fallback-Seiten

Dieses Dokument beschreibt Epic 6 auf fachlicher und technischer Ebene. Es behandelt die völlig autarken statischen HTML-Seiten, die außerhalb der normalen Bundle- und PHP-Laufzeit als letzte Fallback-Ebene durch den Webserver ausgeliefert werden können.

## Zielbild

Das Bundle soll zwei harte statische Fallback-Artefakte bereitstellen:

- eine Seite für **geplante Voll-Maintenance**
- eine Seite für **unerwartete harte Fehlerfälle**

Beide Artefakte müssen:

- ohne PHP lauffähig sein
- ohne Twig zur Laufzeit auskommen
- keine externen Assets voraussetzen
- vom Webserver direkt auslieferbar sein

Gleichzeitig sollen diese Artefakte nicht rein manuell gepflegt werden. Im laufenden Betrieb sollen redaktionell pflegbare Pimcore-Documents als Quelle dienen, deren veröffentlichter Stand statisch ausgerendert und auf die Zielartefakte geschrieben wird.

## Kanonische Fälle

Für Epic 6 werden nur vier Fälle betrachtet:

1. **Normalbetrieb**
2. **partielle Custom-Maintenance**
3. **gewollte Pimcore-Voll-Maintenance**
4. **unerwarteter harter Fehlerfall mit Webserver-Fallback**

Die eigentliche harte Fallback-Ebene betrifft nur **Fall 3** und **Fall 4**.

### Fall 1: Normalbetrieb

Die Anwendung funktioniert regulär.

Folge:

- keine harte Fallback-Seite
- keine besondere Webserver-Umschaltung

### Fall 2: Partielle Custom-Maintenance

Einzelne Bereiche oder Integrationen werden durch die Anwendungslogik deaktiviert, der übrige Auftritt bleibt benutzbar.

Folge:

- keine harte Fallback-Seite
- Steuerung bleibt innerhalb von Bundle und Anwendung

### Fall 3: Gewollte Pimcore-Voll-Maintenance

Die native Pimcore-Maintenance ist aktiv. Die Anwendung soll dann nicht auf Pimcores Standardseite enden, sondern auf einer vom Bundle vorbereiteten und später aus Pimcore gepflegten statischen Maintenance-Seite.

Folge:

- der Webserver muss gezielt auf das statische Maintenance-Artefakt schalten können
- der Schaltpunkt muss an die native Pimcore-Maintenance gekoppelt werden

### Fall 4: Unerwarteter harter Fehlerfall

Die Anwendung liefert keine brauchbare Antwort mehr, etwa durch native `50x`-Fehler, Upstream-Ausfälle oder andere nicht mehr von Pimcore/Symfony/PHP sinnvoll abgefangene Situationen.

Folge:

- der Webserver muss auf ein bereits vorhandenes statisches Fehlerartefakt zurückfallen können
- das Artefakt muss präventiv existieren

## Grundarchitektur

Epic 6 trennt vier Ebenen:

1. **Initiale Minimal-Artefakte**
2. **Pimcore-Quell-Documents**
3. **statischer Export**
4. **Webserver-Verdrahtung**

### Initiale Minimal-Artefakte

Das Bundle muss bereits direkt nach Installation minimale statische HTML-Dateien mitbringen.

Zweck:

- sofortige Auslieferbarkeit ohne Mitwirkung von Admin, Redaktion oder Entwicklung
- sicherer Ausgangszustand, bis echte Pimcore-Documents gepflegt und exportiert wurden

Diese Artefakte sind ausdrücklich nur Startpunkte und dürfen später überschrieben werden.

Im aktuellen Zuschnitt von Story 6.1 liegen diese Initial-Artefakte im Bundle unter:

- `src/CustomMaintenanceBundle/Resources/install/fallback/maintenance.html`
- `src/CustomMaintenanceBundle/Resources/install/fallback/error.html`

Sie sind bewusst noch nicht die späteren operativen Webserver-Zielpfade, sondern die mitgelieferten Ausgangsartefakte.

### Pimcore-Quell-Documents

Für beide harten Fälle soll ein Pimcore-Document als redaktionelle Quelle dienen:

- ein Document für geplante Maintenance
- ein Document für generische Fehler/Störungen

Das Bundle liefert dafür geeignete Beispiel-Templates mit, die sofort nutzbar sind und mindestens einen redaktionell pflegbaren Hauptinhalt bereitstellen, etwa per WYSIWYG.

Im aktuellen Zuschnitt von Story 6.2 liegen diese Beispiel-Templates im Bundle unter:

- `src/CustomMaintenanceBundle/Resources/views/fallback/maintenance_document.html.twig`
- `src/CustomMaintenanceBundle/Resources/views/fallback/error_document.html.twig`

Die eigentlichen Pimcore-Documents werden bewusst nicht automatisch erzeugt. Stattdessen werden vorhandene oder manuell angelegte Documents aus dem Tree im Settings Store als Quelle hinterlegt.

### Statischer Export

Die Quell-Documents werden nicht erst im Störungsfall ausgewertet, sondern **vorher**.

Der Exportzeitpunkt ist:

- das Speichern oder Aktualisieren des relevanten und veröffentlichten Pimcore-Documents
- die Rekonfiguration der im Bundle verwendeten Quell-Documents im Admin-Panel

Dabei wird das Document vollständig zu statischem HTML ausgerendert und überschreibt das zugehörige Zielartefakt.

Wichtig:

- dieser Schritt setzt ein funktionsfähiges Pimcore voraus
- im eigentlichen Störungs- oder Maintenance-Fall greift nur noch der Webserver auf das bereits vorhandene Artefakt zu
- wird die Quellzuordnung entfernt, wird kein Artefakt gelöscht
- ist die konfigurierte Quelle nicht veröffentlicht oder nicht mehr renderbar, bleibt das letzte gültige Artefakt unverändert bestehen

Im aktuellen Zuschnitt von Story 6.3 liegen die Standard-Zielpfade bei:

- `%kernel.project_dir%/public/_maintenance/maintenance.html`
- `%kernel.project_dir%/public/_maintenance/error.html`

Diese Pfade sind statische Bundle-Konfiguration und können über YAML angepasst werden.

Wichtig:

- der PHP-/Pimcore-Prozess muss auf das Zielverzeichnis Schreibrechte haben
- bei den Standardpfaden betrifft das insbesondere `%kernel.project_dir%/public/_maintenance/`
- kann das Verzeichnis nicht angelegt oder beschrieben werden, schlägt der Export fehl und das letzte gültige Artefakt bleibt unverändert
- falls nötig, können die Exportziele per YAML auf einen bereits beschreibbaren Pfad umgestellt werden

### Webserver-Verdrahtung

Die eigentliche harte Umschaltung geschieht nicht im Bundle-Request zur Laufzeit, sondern durch den Webserver.

Das Bundle liefert dazu:

- statische HTML-Zielartefakte
- zusätzliche webserverlesbare Steuerartefakte für die Maintenance-Kopplung
- Dokumentation und Konfigurationsmuster für Apache und Nginx
- mitgelieferte Beispiel-Snippets im Bundle

Wichtig für die weitere Umsetzung:

- die laufende Umschaltung darf keinen Reload von Apache oder Nginx voraussetzen
- deshalb sind dynamisch neu geschriebene Webserver-Include-Dateien **nicht** der primäre Runtime-Mechanismus
- statische Apache-/Nginx-Snippets sind sinnvoll, aber nur als einmalig eingerichtete Konfiguration
- der eigentliche Laufzeitimpuls muss über Markerdateien oder ähnlich reloadfreie Artefakte erfolgen

## Kopplung an die native Pimcore-Maintenance

Die native Pimcore-Maintenance ist der fachliche Schalter für die gewollte Voll-Maintenance.

Technisch relevant ist dabei nicht nur der Request-Listener, sondern vor allem der Aktivierungs- und Deaktivierungsmechanismus.

### Nativer Pimcore-Mechanismus

Pimcore verwendet eine Maintenance-Datei unterhalb des Konfigurationsverzeichnisses und prüft diese in einem Request-Listener.

Außerdem dispatcht Pimcore beim Umschalten Systemevents:

- `pimcore.system.maintenance_mode.activate`
- `pimcore.system.maintenance_mode.deactivate`

Diese Events sind der geeignete Integrationspunkt für das Bundle.

### Bundle-Kopplung

Das Bundle soll auf diese Pimcore-Events reagieren und dabei zusätzliche, webserverlesbare Steuerartefakte erzeugen oder zurücknehmen.

Beim Aktivieren:

- Maintenance-Signal erzeugen oder aktualisieren
- Ausnahmeregeln exportieren
- harte Webserver-Umschaltung vorbereiten

Beim Deaktivieren:

- Maintenance-Signal zurücknehmen
- harte Webserver-Umschaltung wieder aufheben

### Story-6.4-Entscheidung für den Runtime-Mechanismus

Für Story 6.4 wird als technischer Kern ein **markerdateibasierter Ansatz** festgelegt.

Begründung:

- Apache- und Nginx-Konfigurationen werden bei geänderten Include-Dateien nicht automatisch neu eingelesen
- ein auf Includes basierender Runtime-Schalter würde deshalb zusätzliche Reload-Logik erzwingen
- Markerdateien können dagegen von beiden Webservern pro Request geprüft werden, ohne dass eine Konfigurationsänderung nötig ist

Daraus folgt:

- Apache- und Nginx-Snippets bleiben statisch und werden einmalig eingerichtet
- das Bundle ändert zur Laufzeit nur Markerdateien und optionale Zustandsartefakte
- die Webserver-Konfiguration prüft diese Markerdateien pro Request

## Ausnahmeregeln im harten Maintenance-Fall

Die harte Webserver-Umschaltung darf nicht blind strenger sein als Pimcore selbst.

Deshalb sollen bestehende Pimcore-Ausnahmen soweit technisch sinnvoll übernommen werden.

### Pflicht-Ausnahmen

- `127.0.0.1`
- die Session, mit der die Maintenance aktiviert wurde

### Zusätzliche konfigurierbare Ausnahme

Zusätzlich soll mindestens eine weitere bewusst konfigurierbare Ausnahme unterstützt werden:

- IP- oder CIDR-Allowlist

Typische Anwendungsfälle:

- Büro-VPN
- feste Betriebs-IP
- internes Monitoring- oder Administratornetz

### Bewusste Einschränkung

Die Session-Ausnahme soll im harten Fallback zunächst **cookie-basiert** nachgebildet werden.

Nicht als Default vorgesehen:

- geheime URLs
- freie Query-Parameter als Bypass
- unkontrollierte Header-Bypässe

### Sonderfall CLI-Aktivierung

Wird die native Pimcore-Maintenance per CLI aktiviert, entsteht keine brauchbare Browser-Session-Ausnahme.

In diesem Fall bleiben nur:

- `127.0.0.1`
- zusätzliche konfigurierbare IP-/CIDR-Ausnahmen

Dieses Verhalten ist fachlich akzeptiert und muss dokumentiert werden.

## Konkreter Story-Schnitt 6.4

Story 6.4 koppelt die native Pimcore-Maintenance an reloadfrei auswertbare Steuerartefakte.

### Quelle der Wahrheit

Die fachliche Quelle der Wahrheit bleibt Pimcores eigener Maintenance-Mechanismus:

- Aktivierung über `\Pimcore\Tool\Admin::activateMaintenanceMode()`
- Deaktivierung über `\Pimcore\Tool\Admin::deactivateMaintenanceMode()`
- Zustandsdatei unter `PIMCORE_CONFIGURATION_DIRECTORY . '/maintenance.php'`
- Events `pimcore.system.maintenance_mode.activate` und `pimcore.system.maintenance_mode.deactivate`

Das Bundle führt **keinen zweiten Maintenance-Status** ein, sondern leitet daraus nur Webserver-Steuerartefakte ab.

### Vorgesehene Runtime-Artefakte

Für die harte Pimcore-Maintenance soll das Bundle zusätzlich zu den HTML-Seiten ein kleines Runtime-Verzeichnis beschreiben.

Vorgesehene Artefakte:

- `maintenance-active.flag` als globales Aktivsignal
- `session-<session-id>.flag` als Freigabe für die aktivierende Browser-Session
- `state.json` als rein diagnostisches Zustandsartefakt

Empfohlener Zielort:

- `%kernel.project_dir%/public/_maintenance/runtime/`

Im aktuellen Implementierungsstand ist genau dieser Pfad auch der Default der YAML-Konfiguration:

- `hard_fallback_runtime.directory`

Zusätzliche statische IP-Ausnahmen können derzeit über folgende YAML-Konfiguration ergänzt werden:

- `hard_fallback_runtime.allowed_ips`

Die HTML-Artefakte bleiben davon getrennt:

- `%kernel.project_dir%/public/_maintenance/maintenance.html`
- `%kernel.project_dir%/public/_maintenance/error.html`

### Verhalten bei Aktivierung

Beim Event `pimcore.system.maintenance_mode.activate` soll das Bundle:

1. Pimcores `maintenance.php` einlesen
2. die darin hinterlegte `sessionId` auswerten
3. `maintenance-active.flag` erzeugen oder aktualisieren
4. bei brauchbarer Browser-Session zusätzlich `session-<session-id>.flag` erzeugen
5. `state.json` mit Diagnoseinformationen schreiben

### Verhalten bei Deaktivierung

Beim Event `pimcore.system.maintenance_mode.deactivate` soll das Bundle:

1. `maintenance-active.flag` entfernen
2. vorhandene `session-*.flag`-Dateien des Bundles entfernen
3. `state.json` auf inaktiv aktualisieren

### Verhalten bei ungültigem oder technischem Sonderfall

Das Bundle muss robust gegen unklare oder technische Aktivierungswege bleiben.

Deshalb gilt:

- ist `maintenance.php` unlesbar oder ohne gültige `sessionId`, wird kein Session-Bypass erzeugt
- bleibt nur die globale Maintenance aktiv, dürfen ausschließlich die statischen IP-Ausnahmen greifen
- Dummy-Session-IDs aus CLI-Kontexten dürfen **keinen** Session-Bypass erzeugen

Bekannte Dummy-Werte aus Pimcore sind derzeit insbesondere:

- `command-line-dummy-session-id`
- `cache-warming-dummy-session-id`

### Sicherheitsanforderung an den Session-Bypass

Der Session-Bypass wird technisch nicht über PHP geprüft, sondern über den Webserver.

Damit dieser Pfad nicht missbrauchbar wird, gilt für die spätere Apache-/Nginx-Konfiguration:

- der Session-Cookie-Wert darf nur bei zulässigem Zeichenmuster berücksichtigt werden
- vorgesehen ist ein konservatives Muster wie `^[A-Za-z0-9,-]+$`
- nur wenn daraus ein gültiger Dateiname ableitbar ist, darf der Webserver auf `session-<session-id>.flag` prüfen

Dadurch wird verhindert, dass manipulierte Cookie-Werte zu beliebigen Dateipfaden führen.

## Zielartefakte

Für Epic 6 sollen mindestens diese Artefakte fachlich fest definiert sein:

- statische Maintenance-Seite
- statische Fehlerseite
- Maintenance-Markerdatei für den Runtime-Schalter
- Session-Bypass-Markerdatei für die aktivierende Browser-Session
- Zustandsartefakt für Diagnose und Nachvollziehbarkeit

Apache- und Nginx-Snippets bleiben wichtig, sind aber **statische Betriebsdokumentation** und keine dynamisch umgeschriebenen Runtime-Artefakte.

## Mitgelieferte Webserver-Snippets

Das Bundle liefert derzeit zwei Beispiel-Snippets mit:

- `src/CustomMaintenanceBundle/Resources/install/fallback/apache-hard-fallback.conf.dist`
- `src/CustomMaintenanceBundle/Resources/install/fallback/nginx-hard-fallback.conf.dist`

Beide Dateien sind bewusst als **Beispiele** zu verstehen und müssen auf das Zielsystem angepasst werden.

Typische Anpassungspunkte:

- Session-Cookie-Name, falls nicht `PHPSESSID`
- zusätzliche erlaubte IPs
- absoluter `public/`-Pfad bei Nginx
- Besonderheiten von Reverse-Proxy- oder `php-fpm`-Betrieb

## Apache-Beispiel

Das Apache-Beispiel arbeitet mit:

- `mod_rewrite`
- `ErrorDocument`
- Markerprüfung über `%{DOCUMENT_ROOT}/_maintenance/runtime/...`

Logik:

- `/_maintenance/runtime/` wird gesperrt
- `127.0.0.1` bleibt immer erlaubt
- zusätzliche IP-Ausnahmen können per Rewrite-Regel ergänzt werden
- Session-Bypass greift nur bei gültigem Cookie-Wert und vorhandener `session-<id>.flag`
- aktive Pimcore-Maintenance beantwortet nicht erlaubte Requests mit `503`
- `ErrorDocument 503` liefert die statische Maintenance-Seite
- `ErrorDocument 500`, `502` und `504` liefern die statische Fehlerseite

Wichtig:

- bei Reverse-Proxy-Betrieb kann zusätzlich `ProxyErrorOverride On` nötig sein
- das Beispiel ist für den VirtualHost-Kontext gedacht

## Nginx-Beispiel

Das Nginx-Beispiel arbeitet im `server`-Kontext mit:

- exakten `location`-Blöcken für die statischen HTML-Dateien
- Sperrung von `/_maintenance/runtime/`
- Markerprüfung per `if (-f ...)`
- `error_page` für Maintenance- und Fehlerfall

Logik:

- `maintenance-active.flag` aktiviert die harte Maintenance
- `127.0.0.1` und ergänzte statische IPs heben die Sperre wieder auf
- ein gültiger Session-Cookie-Wert wird auf `session-<id>.flag` geprüft
- `503` liefert die statische Maintenance-Seite
- `500`, `502` und `504` liefern die statische Fehlerseite

Wichtig:

- `__PUBLIC_ROOT__` muss auf den absoluten Pfad des `public/`-Verzeichnisses gesetzt werden
- bei abweichendem Session-Cookie-Namen muss `$cookie_PHPSESSID` angepasst werden

## Webserver-Prinzipien

### Maintenance-Fall

Bei aktiver nativer Pimcore-Maintenance muss der Webserver:

- Maintenance-Aktivität erkennen
- Ausnahmen prüfen
- bei nicht erlaubten Requests direkt das statische Maintenance-Artefakt ausliefern

### Fehlerfall

Bei harten Fehlern muss der Webserver:

- auf ein bereits vorhandenes statisches Fehlerartefakt zurückfallen
- nicht erst versuchen, Pimcore oder Bundle-Logik für die Seitenerzeugung zu verwenden

### Unterschied zwischen beiden Fällen

Fall 3 und Fall 4 dürfen nicht vermischt werden:

- **Maintenance** ist geplant und bewusst geschaltet
- **Fehlerfall** ist ungeplant und technisch motiviert

Deshalb braucht es zwei getrennte HTML-Zielartefakte und getrennte Webserver-Regeln.

## Story-Schnitt für Epic 6

### Story 6.1: Minimale initiale Fallback-Artefakte mitliefern

Das Bundle liefert zwei sofort nutzbare statische HTML-Dateien mit:

- geplante Maintenance
- generischer Fehlerfall

Diese Artefakte sind:

- vollständig autark
- sofort auslieferbar
- als Startpunkte gedacht

### Story 6.2: Pimcore-Quell-Documents und Beispiel-Templates bereitstellen

Das Bundle liefert zwei Pimcore-taugliche Beispiel-Templates:

- Maintenance-Document
- Fehler-Document

Diese Templates sollen:

- sofort nutzbar sein
- mindestens einen redaktionell pflegbaren Hauptinhalt bieten
- später als Quelle für den statischen Export dienen

### Story 6.3: Export beim Speichern und Veröffentlichen automatisch anstoßen

Wird eines der relevanten Pimcore-Documents gespeichert oder aktualisiert und ist veröffentlicht, soll das zugehörige statische HTML-Artefakt automatisch neu erzeugt werden. Gleiches gilt, wenn die Quellzuordnung im Bundle auf ein anderes Document umgestellt wird.

Anforderungen:

- nur relevante Documents exportieren
- Zielartefakt überschreiben
- letzten gültigen Stand bei Exportfehler bewahren
- letzten gültigen Stand auch bei unveröffentlichter oder entfernter Quelle bewahren
- Fehler protokollieren

### Story 6.4: Native Pimcore-Maintenance mit hartem Fallback koppeln

Das Bundle koppelt sich an die nativen Pimcore-Maintenance-Events und erzeugt daraus webserverlesbare Steuerartefakte.

Ziel:

- kein zweiter manueller Schalter neben Pimcore
- definierte Ausnahmeregeln
- saubere Rücknahme bei Deaktivierung

### Story 6.5: Zielartefakte und Zuordnung verbindlich festlegen

Für Maintenance, Fehlerfall und Webserver-Includes müssen feste Zielpfade und klare Zuordnungen definiert werden.

Wichtig:

- Maintenance- und Fehlerfall bleiben getrennt
- Quell-Document und Zielartefakt sind eindeutig zuordenbar

### Story 6.6: Apache- und Nginx-Integration dokumentieren

Das Bundle dokumentiert konkrete Konfigurationsmuster für:

- Apache
- Nginx

Dabei müssen jeweils getrennt beschrieben werden:

- harte Maintenance-Umschaltung
- harte Fehler-Fallbacks
- Ausnahmeregeln
- Grenzen des CLI-Falls

## Abgrenzungen

Epic 6 behandelt ausdrücklich **nicht**:

- partielle Custom-Maintenances
- normale Hinweislogik innerhalb von Twig und PHP
- spontane Seitenerzeugung im Störungsfall selbst
- vollautomatische Manipulation realer Webserver-Konfigurationen durch das Bundle

## Verhältnis zu Epic 5

Epic 5 und Epic 6 ergänzen sich, behandeln aber unterschiedliche Ebenen:

- **Epic 5** schärft die fachliche Schaltlogik innerhalb von Bundle und Anwendung
- **Epic 6** schafft eine autarke, webserverseitige Fallback-Ebene außerhalb der normalen PHP-Laufzeit

Die genaue Maintenance- und Hinweissemantik bleibt in [switching_logic.md](switching_logic.md) beschrieben. Dieses Dokument ergänzt sie um die harte Auslieferungsebene für Voll-Maintenance und Webserver-Fehlerfälle.
