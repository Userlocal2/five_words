<?php

namespace App\Base;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Clearjunction\Logger\Log\CJLogger;

/**
 * Class AppBaseTable
 * @package App\Model\Table
 */
abstract class AppTable extends Table
{

    public function initialize(array $config): void {
        $this->addBehavior(
            'Timestamp',
            [
                'events' => [
                    'Model.beforeSave' => [
                        'created_at' => 'new',
                        'updated_at' => 'always',
                    ],
                ],
            ]
        );


        // App rule tableName = pluginName_ModelName
        [$plugin] = explode('\\', get_class($this));
        $prefix = '';

        if (isset($this->tablePrefix) && is_string($this->tablePrefix)) {
            $prefix = $this->tablePrefix;
        }
        elseif ('App' != $plugin && 'Cake' != $plugin) {
            $prefix = Inflector::dasherize($plugin);
        }

        if (!empty($prefix)) {
            $prefix = Inflector::underscore($prefix);

            if ('debug_kit' != $prefix) {
                $this->setTable($prefix . '_' . $this->getTable());
            }
        }
    }

    /**
     * @param EntityInterface $entity
     * @param array           $options
     *
     * @return bool|EntityInterface|mixed
     */
    public function save(EntityInterface $entity, $options = []) {
        $saved = parent::save($entity, $options);
        if (!$saved) {
            CJLogger::sysLog(
                'Validation error of ' . get_class($entity) . PHP_EOL . var_export($entity, true),
                $entity->getErrors()
            );
        }

        return $saved;
    }

}
