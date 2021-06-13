<?php
return [
    'artisan' => [
        'command_filter' => [
            'transaction:*',
            'command:test'
        ],
    ],
];
