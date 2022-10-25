<?php

use Cake\Database\Connection;
use Cake\Database\Driver\Mysql;

return [
    'Datasources' => [
        'default' => [
            'host'             => 'mysql',
            //'port' => 'non_standard_port_number',
            'username'         => 'vagrant',
            'password'         => 'vagrant',
            'database'         => 'science',
        ],

        'additional' => [
            'className'        => Connection::class,
            'driver'           => Mysql::class,
            'persistent'       => false,

            'host'     => 'localhost',
            //'port' => 'non_standard_port_number',
            'username' => 'vagrant',
            'password' => 'vagrant',
            'database' => 'sepa',

            'timezone'         => 'UTC',
            'flags'            => [],
            'cacheMetadata'    => true,
            'log'              => false,
            'quoteIdentifiers' => false,
        ],

        /**
         * The test connection is used during the test suite.
         */
        'test'   => [
            'host'             => 'mysql',
            'username'         => 'vagrant',
            'password'         => 'vagrant',
            'database'         => 'science_test',
        ],
    ],
];