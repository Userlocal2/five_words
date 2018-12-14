<?php

use Cake\Event\EventManager;
use Cake\Utility\Inflector;
use Cake\Datasource\ConnectionManager;
use Cake\Database\Type;

/**
 * Add Prefix for all Tables in Plugins
 * Rule: Plugin Currency Model Rates => Table currency_rates
 *
 * But you may specify your custom table prefix or disable it
 * by passing param $tablePrefix to model (false for disable or 'some_custom_pref' for custom prefix)
 */
EventManager::instance()->on(
    'Model.initialize',
    function ($event) {
        /** @var \Cake\Event\Event $event */
        /** @var \Cake\ORM\Table $subject */
        $subject = $event->getSubject();

        list($plugin,) = explode('\\', get_class($subject));

        // table prefix = plugin_name_
        $prefix = '';

        if (isset($subject->tablePrefix) && is_string($subject->tablePrefix)) {
            $prefix = $subject->tablePrefix;
        }
        else {
            if ('App' == $plugin || 'Cake' == $plugin) {
                if (mb_strpos($subject->getRegistryAlias(), '.')) {
                    list($plugin,) = explode('.', $subject->getRegistryAlias());
                }
                else {
                    $plugin = '';
                }
            }
            if (!empty($plugin)) {
                $prefix = $plugin;
            }
        }

        if (!empty($prefix)) {
            $prefix = Inflector::underscore($prefix);

            if ('debug_kit' != $prefix) {
                $subject->setTable($prefix . '_' . $subject->getTable());
            }
        }

        // database connection = plugin_name
        if (!empty($prefix)) {
            $pluginsConnections = \Cake\Core\Configure::read('PluginsConnections');

            if (isset($pluginsConnections[$prefix])) {
                $subject->setConnection(ConnectionManager::get($pluginsConnections[$prefix]));
            }
        }
    }
);

Type::map('amount', 'App\Database\Type\AmountType');
Type::map('rate', 'App\Database\Type\RateType');
Type::map('array', 'App\Database\Type\ArrayType');
Type::map('json_object', 'App\Database\Type\JsonObjectType');
