# Entwicklung und Testen des Custom Maintenance Bundles

Dieses Dokument beschreibt den tatsaechlichen Entwicklungs- und Testweg fuer das Bundle im aktuellen Repository-Zustand.

## Automatisierte Tests

Die Testausfuehrung ist auf PHPUnit ausgelegt.

```bash
composer test
```

oder direkt:

```bash
vendor/bin/phpunit
```

Die PHPUnit-Konfiguration liegt in `phpunit.xml.dist`. Tests gehoeren unter `tests/`.

## Lokale Einbindung in eine Pimcore-Instanz

Fuer die Entwicklung bietet sich ein Composer Path Repository an, damit das Bundle per Symlink in einer Pimcore-Installation laeuft.

Beispiel fuer die `composer.json` des Pimcore-Projekts:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../custom-maintenance-bundle",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

Danach im Pimcore-Projekt:

```bash
composer require weblizards/custom-maintenance-bundle:@dev
bin/console pimcore:bundle:enable WeblizardsCustomMaintenanceBundle
bin/console pimcore:bundle:install WeblizardsCustomMaintenanceBundle
bin/console assets:install public --symlink
```

## Manuelle Verifikation

Nach Installation und Aktivierung sollten mindestens diese Punkte geprueft werden:

1. Bundle erscheint in der Pimcore-Bundle-Verwaltung und laesst sich installieren.
2. Das Admin-Menue zeigt den Eintrag `Custom Maintenance`.
3. Das Admin-Panel laedt unter `/admin/weblizards_custom_maintenance/adminpanel/load`.
4. Das Speichern ueber `/admin/weblizards_custom_maintenance/adminpanel/save` schreibt den kanonischen Stand in den Pimcore Settings Store unter Scope `weblizards_custom_maintenance` und Key `config`.
5. Eine bestehende Legacy-Datei unter `PIMCORE_PRIVATE_VAR . '/config/custommaintenance.php'` wird weiterhin als Read-Fallback genutzt, solange noch kein Store-Eintrag existiert.
6. Wenn weder Settings Store noch Legacy-Datei Bundle-Konfiguration enthalten, erscheint nur der konservative Default mit der nativen `pimcore`-Maintenance.
7. Twig-Funktionen wie `indicateCustomMaintenance()` und `isMaintenanceActive()` sind im Host-Projekt aufrufbar.
8. Der Command `weblizards:custommaintenance:control` ist vorhanden und liefert erwartbare Ausgaben fuer `list-tokens` und `show-status`.

## Wichtige Hinweise

- Die Rolling-Migration laeuft still im Hintergrund:
  - gelesen wird zuerst aus dem Pimcore Settings Store
  - fehlt dort ein Eintrag, wird die Legacy-Datei `PIMCORE_PRIVATE_VAR . '/config/custommaintenance.php'` gelesen
  - erst wenn beide Quellen fehlen, greift der Bundle-Default
- Schreibvorgaenge aktualisieren nur den Settings Store; die Legacy-Datei wird nicht zurueckgeschrieben.
- Bestehende Installationen brauchen daher keine separate manuelle Migration, sollten den neuen Persistenzpfad aber operativ kennen.
- Admin-UI-Tests sind wegen der Legacy-ExtJS-Oberflaeche derzeit primaer manuell sinnvoll; Backend- und Service-Logik sollten bevorzugt automatisiert abgesichert werden.
