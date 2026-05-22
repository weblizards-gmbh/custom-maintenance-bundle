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
        'document' => '/de/aktionen/wartung',
    ],
    'custom' => [
        'prices' => [
            'active' => 'false',
            'fixed' => 'false',
            'description' => 'Preisschnittstelle',
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
            'document' => '/de/aktionen/wartung',
        ],
    ],
];
