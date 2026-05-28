# Custom Maintenance Bundle

Das Bundle ermoeglicht fein granulierte Maintenance-Zustaende fuer Pimcore-basierte Anwendungen. Neben der nativen Pimcore-Maintenance koennen eigene fachliche Maintenances definiert, geplant, manuell geschaltet und im Anwendungscode oder Frontend ausgewertet werden.

Der aktuelle Stand dieses Repositories zielt auf Pimcore `10.x`, mit Story-basierter Modernisierung fuer `10.6.9`.

## Installation

```bash
composer require weblizards/custom-maintenance-bundle
```

Anschliessend das Bundle in Pimcore aktivieren und installieren.

## Konfiguration

Die Legacy-Konfiguration liegt derzeit unter:

```text
/var/config/custommaintenance.php
```

Die Struktur basiert auf einem nativen `pimcore`-Block und beliebigen Eintraegen unter `custom`.

Beispiel:

```php
<?php

declare(strict_types=1);

return [
    'pimcore' => [
        'show_info' => 'never',
        'show_info_from' => [
            'date' => '20.12.2018',
            'time' => '12:21',
        ],
        'planned' => [
            'from' => [
                'date' => '04.12.2018',
                'time' => '12:21',
            ],
            'to' => [
                'date' => '05.12.2018',
                'time' => '12:21',
            ],
        ],
        'document' => '/de/maintenance/pimcore',
    ],
    'custom' => [
        'prices' => [
            'active' => 'false',
            'fixed' => 'false',
            'description' => 'ERP',
            'show_info' => 'never',
            'show_info_from' => [
                'date' => '21.12.2018',
                'time' => '00:00',
            ],
            'planned' => [
                'from' => [
                    'date' => '21.12.2018',
                    'time' => '12:00',
                ],
                'to' => [
                    'date' => '28.12.2018',
                    'time' => '23:00',
                ],
            ],
            'document' => '/de/maintenance/erp',
        ],
    ],
];
```

## Twig-Nutzung

Hinweise koennen ueber die vorhandenen Twig-Funktionen eingebunden werden:

```twig
{{ indicateCustomMaintenance() }}
{{ indicateUpcomingMaintenance() }}
{{ indicateCurrentMaintenance() }}
```

Den Status einzelner Maintenances koennen Anwendungen ueber Twig ebenfalls pruefen:

```twig
{% if isMaintenanceActive('prices') %}
    {# degradiertes Verhalten #}
{% endif %}
```

## PHP-API

Die zentrale Runtime-API liegt im `StatusService`.

- `getValidTokens()` liefert die Custom-Tokens.
- `getStatus($token)` liefert den gespeicherten Status (`true` oder `false` als String-Konstanten des Services).
- `setStatus($token, $state)` setzt den Status fuer einen Custom-Token.
- `isActive($token)` prueft, ob eine Maintenance aktiv ist.

Beispiel:

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Weblizards\CustomMaintenanceBundle\Service\StatusService;

class DefaultController extends FrontendController
{
    public function defaultAction(StatusService $status): void
    {
        $this->view->preventLogin = $status->isActive('prices');
    }
}
```

## CLI

Die CLI-Steuerung laeuft ueber den Command:

```bash
bin/console weblizards:custommaintenance:control list-tokens
bin/console weblizards:custommaintenance:control show-status --token=prices
bin/console weblizards:custommaintenance:control activate --token=prices
bin/console weblizards:custommaintenance:control deactivate --token=prices
```

Optional:

- `--porcelain` fuer maschinenlesbare Ausgabe
- `--override-fixed` zum Uebersteuern des `fixed`-Schutzes

## Entwicklung und Tests

- PHPUnit ist ueber `composer test` bzw. `vendor/bin/phpunit` vorgesehen.
- Hinweise zur lokalen Pimcore-Einbindung und Verifikation stehen in [docs/development_testing.md](docs/development_testing.md).
