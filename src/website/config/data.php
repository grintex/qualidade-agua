<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Spreadsheets 
    |--------------------------------------------------------------------------
    |
    | Info of all Google Spreadsheets that will be downloaded and used as
    | data source. Every entry in 'spreadsheets' shoud be:
    |
    | 'dados_coletados' => [
    |    'name' => 'Something',                   // local name
    |    'url' => 'https://docs.google.com/...',  // public URL
    |    'header_at' => 2,                        // row (zero-start) where the header is
    |    'data_starts_at' => 4,                   // row (zero-start) where data begins
    |  ],
    */
    'spreadsheets' => [
        'dados_coletados' => [
            'name' => '1 - Dados coletados',
            'url' => env('DATA_SPREADSHEET_URL') . '?gid=0&single=true&output=tsv',
            'header_at' => 2,
            'data_starts_at' => 4,
            'schema' => [
                'types' => [],
                'indexes' => [],
            ]
        ],

        'uso_subterraneas' => [
            'name' => '2 - Usos das subterrâneras',
            'url' => env('DATA_SPREADSHEET_URL') . '?gid=1301226682&single=true&output=tsv',
            'header_at' => 1,
            'data_starts_at' => 2,
        ],

        'classes_superficiais' => [
            'name' => '3 - Classes das superficiais',
            'url' => env('DATA_SPREADSHEET_URL') . '?gid=808541941&single=true&output=tsv',
            'header_at' => 3,
            'data_starts_at' => 4,
        ],

        'parametros_abastecimento' => [
            'name' => '4 - Parâmetros para abastecimento',
            'url' => env('DATA_SPREADSHEET_URL') . '?gid=1962852963&single=true&output=tsv',
            'header_at' => 1,
            'data_starts_at' => 9,
        ],

        'parametros_subterraneas' => [
            'name' => '5 - Parâmetros para subterrâneas',
            'url' => env('DATA_SPREADSHEET_URL') . '?gid=679520210&single=true&output=tsv',
            'header_at' => 1,
            'data_starts_at' => 2,
        ],

        'parametros_superficiais' => [
            'name' => '6 - Parâmetros para superficiais',
            'url' => env('DATA_SPREADSHEET_URL') . '?gid=450891538&single=true&output=tsv',
            'header_at' => 1,
            'data_starts_at' => 2,
        ],

        'parametros_abastecimento_rs' => [
            'name' => '7 - Parâmetros para abastecimento - RS',
            'url' => env('DATA_SPREADSHEET_URL') . '?gid=450891538&single=true&output=tsv',
            'header_at' => 1,
            'data_starts_at' => 2,
        ]
    ]
];
