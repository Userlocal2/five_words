<?php

namespace Currency\Model\Table;


use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;

class RatesTable extends Table
{
    public const SOURCE_CBR = 'CBR';

    /**
     * @param TableSchema $schema
     *
     * @return TableSchema
     */
    protected function _initializeSchema(TableSchema $schema) {
        $schema->setColumnType('rate', 'rate');

        return $schema;
    }

    public function initialize(array $config) {
        parent::initialize($config);

        $this->setPrimaryKey('id');

        $this->addBehavior(
            'Timestamp', [
                'events' => [
                    'Model.beforeSave' => [
                        'created_at' => 'new',
                    ],
                ],
            ]
        );
    }

}
