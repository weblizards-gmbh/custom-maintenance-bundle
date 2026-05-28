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
4. Das Speichern ueber `/admin/weblizards_custom_maintenance/adminpanel/save` schreibt die Legacy-Konfiguration weiterhin ohne offensichtliche Fehler.
5. Twig-Funktionen wie `indicateCustomMaintenance()` und `isMaintenanceActive()` sind im Host-Projekt aufrufbar.
6. Der Command `weblizards:custommaintenance:control` ist vorhanden und liefert erwartbare Ausgaben fuer `list-tokens` und `show-status`.

## Wichtige Hinweise

- Die aktuelle Persistenzbasis ist noch die Legacy-Datei `PIMCORE_PRIVATE_VAR . '/config/custommaintenance.php'`.
- Die geplante Rolling-Migration auf Pimcore Settings Store gehoert zu spaeteren Stories und ist in diesem Dokument deshalb noch nicht beschrieben.
- Admin-UI-Tests sind wegen der Legacy-ExtJS-Oberflaeche derzeit primaer manuell sinnvoll; Backend- und Service-Logik sollten bevorzugt automatisiert abgesichert werden.
