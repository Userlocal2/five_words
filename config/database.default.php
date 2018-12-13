<?php
return [
    'Datasources' => [
        'default' => [
            'className'        => 'Cake\Database\Connection',
            'driver'           => 'Cake\Database\Driver\Mysql',
            'persistent'       => false,
            'host'             => 'localhost',
            /**
             * CakePHP will use the default DB port based on the driver selected
             * MySQL on MAMP uses port 8889, MAMP users will want to uncomment
             * the following line and set the port accordingly
             */
            //'port' => 'non_standard_port_number',
            'username'         => 'vagrant',
            'password'         => 'vagrant',
            'database'         => 'science',
            'encoding'         => 'utf8',
            'timezone'         => 'UTC',
            'flags'            => [],
            'cacheMetadata'    => false, // may be changed to true if fix cache renew (need for correct testing)
            'log'              => false,

            /**
             * Set identifier quoting to true if you are using reserved words or
             * special characters in your table or column names. Enabling this
             * setting will result in queries built using the Query Builder having
             * identifiers quoted when creating SQL. It should be noted that this
             * decreases performance because each query needs to be traversed and
             * manipulated before being executed.
             */
            'quoteIdentifiers' => false,

            /**
             * During development, if using MySQL < 5.6, uncommenting the
             * following line could boost the speed at which schema metadata is
             * fetched from the database. It can also be set directly with the
             * mysql configuration directive 'innodb_stats_on_metadata = 0'
             * which is the recommended value in production environments
             */
            //'init' => ['SET GLOBAL innodb_stats_on_metadata = 0'],

            'url' => env('DATABASE_URL', null),
        ],

        /**
         * The test connection is used during the test suite.
         */
        'test'   => [
            'className'        => 'Cake\Database\Connection',
            'driver'           => 'Cake\Database\Driver\Mysql',
            'persistent'       => false,
            'host'             => 'localhost',
            'username'         => 'vagrant',
            'password'         => 'vagrant',
            'database'         => 'science_test',
            'encoding'         => 'utf8',
            'timezone'         => 'UTC',
            'cacheMetadata'    => false,
            'quoteIdentifiers' => false,
            'log'              => false,
            'url'              => env('DATABASE_TEST_URL', null),
        ],
    ],
];