# Custom Maintenance Bundle
This bundle let's you schedule Pimcore- and Custom Maintenances of userdefined types.
The information, if a maintenance is scheduled or in progress can be displayed to the use.
Additionally, you can use this information in your code and e.g. prevent the user from logging
in or adding items to a cart.
  
# Install the bundle
```bash
composer require weblizards/tag-management-bundle
```
and install bundle in pimcore's bundle administration.

Website translations are provided in german and english, please adjust them (search for `custommaintenance.` in pimcore's shared translations) to your needs.

# General installation steps


Set `/var/config/custommaintenance.php` to your needs.
Section `pimcore` is mandatory, in `custom` you can add as many sections as you need.

# Settings example
```php
return [
    "pimcore" => [
        "show_info" => "never",
        "show_info_from" => [
            "date" => "20.12.2018",
            "time" => "12:21"
        ],
        "planned" => [
            "from" => [
                "date" => "04.12.2018",
                "time" => "12:21"
            ],
            "to" => [
                "date" => "05.12.2018",
                "time" => "12:21"
            ]
        ],
        "document" => "/de/maintenance/pimcore"
    ],
    "custom" => [
        "prices" => [
            "active" => "false",
            "description" => "ERP",
            "show_info" => "never",
            "show_info_from" => [
                "date" => "21.12.2018",
                "time" => "00:00"
            ],
            "planned" => [
                "from" => [
                    "date" => "21.12.2018",
                    "time" => "12:00"
                ],
                "to" => [
                    "date" => "28.12.2018",
                    "time" => "23:00"
                ]
            ],
            "document" => "/de/maintenance/erp"
        ],
        "order" => [
            "active" => "false",
            "description" => "Orders",
            "show_info" => "never",
            "show_info_from" => [
                "date" => "11.12.2018",
                "time" => "12:21"
            ],
            "planned" => [
                "from" => [
                    "date" => "18.12.2018",
                    "time" => "00:00"
                ],
                "to" => [
                    "date" => "18.12.2018",
                    "time" => "10:30"
                ]
            ],
            "document" => "/de/maintenance/orders"
        ],
    ]
];
```

# Usage
## Indicating maintenances with engine
You can use the templating helper in your layout or view:
```php
<?= $this->customMaintenance(); ?>
```
This will display scheduled maintenances, or current, if applicable.
You can finetune this by calling the corresponding part yourself:

```php
<?= $this->customMaintenance()->indicateUpcoming(); ?>
```
```php
<?= $this->customMaintenance()->indicateCurrent(); ?>
```

## Indicate maintenances with twig
You can use the templating helper in your layout or view.

For any maintenance:
```twig
{{ indicateCustomMaintenance() }}
```

For just upcoming or current:
```twig
{{ indicateUpcomingMaintenance() }}
{{ indicateCurrentMaintenance() }}
```


You will have to provide stylings for the output.
Example (in less-css):
```less
div.custommaintenance {
  &.custommaintenance_upcoming, &.custommaintenance_current {
    position: fixed;
    bottom: 0;
    width: 100%;
    padding: 10px;
    background-color: white;
    color: white;
    text-align: center;
    z-index: 1000;

    a {
      color: white;
      text-decoration: underline;
    }

  }
}
```
## Using the API

There are several methods in StatusService to handle maintenances:

* `getValidTokens()` returns an array of strings of valid tokens, minus "pimcore" since this is not handled by this Bundle
* `getStatus($token)` returns the state of a maintenance. The returned string is as defined as in `StatusService::STATUS_*`
* `setStatus($token, $state)` sets the state. `$state` needs to be a string as defined as in  `StatusService::STATUS_*`

## Checking for current maintenance
Use `Status::isActive(<token>)` to check for a maintenance. Token is either `pimcore` for pimcores 
internal maintenance or any other token you defined under `custom`. Omit token if
you want to check for any. 

Example:

```php
<?php

namespace App\Controller;

use Pimcore\Controller\FrontendController;
use Weblizards\CustomMaintenanceBundle\Service\StatusService;

class DefaultController extends FrontendController
{
    public function defaultAction(StatusService $status)
    {
        // Check, if a maintenance of the ERP is in progress.
        // If yes, e.g. signalize the view to omit a login link
        $this->view->preventLogin = $status->isActive("erp"); 
    }
}
```

## Switching states on the command line
It's possible to set and check the states of a custom maintenance on the console, e.g. to
work with it in cases on an overloaded frontend or to use with automated processes.

To list the available maintenance-tokens:
```bash
$ bin/console weblizards:custommaintenance:activate list-tokens
```

To show the status of a token:
```bash
$ bin/console weblizards:custommaintenance:activate show-status --token=<TOKEN>
```

To activate a maintenance:
```bash
$ bin/console weblizards:custommaintenance:activate activate --token=<TOKEN>
```

To deactivate a maintenance:
```bash
$ bin/console weblizards:custommaintenance:activate deactivate --token=<TOKEN>
```
