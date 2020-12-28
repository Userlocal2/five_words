<?php

return [
    'LogDir' => LOGS,

    /**
     * Configures logging options
     */
    'Log'            => [
        'debug'   => [
            'className' => 'Cake\Log\Engine\FileLog',
            'path'      => LOGS,
            'file'      => 'debug',
            'url'       => env('LOG_DEBUG_URL', null),
            'scopes'    => false,
            'levels'    => ['notice', 'info', 'debug'],
        ],
        'error'   => [
            'className' => 'Cake\Log\Engine\FileLog',
            'path'      => LOGS,
            'file'      => 'error',
            'url'       => env('LOG_ERROR_URL', null),
            'scopes'    => false,
            'levels'    => ['warning', 'error', 'critical', 'alert', 'emergency'],
        ],
        // To enable this dedicated query log, you need set your datasource's log flag to true
        'queries' => [
            'className' => 'Cake\Log\Engine\FileLog',
            'path'      => LOGS,
            'file'      => 'queries',
            'url'       => env('LOG_QUERIES_URL', null),
            'scopes'    => ['queriesLog'],
        ],
    ],
];
