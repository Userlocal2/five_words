<?php

use Cake\ORM\TableRegistry;
use Migrations\AbstractMigration;

class CreateCurrencyRates extends AbstractMigration
{
    public function change() {
        $table = $this->table(TableRegistry::get('Currency.Rates')->getTable());

        $table
            ->addColumn(
                'date',
                'date', [
                'null' => false,
            ])
            ->addColumn(
                'base',
                'string', [
                'limit'   => 3,
                'null'    => false,
                'comment' => 'Alpha currency code',
            ])
            ->addColumn(
                'target',
                'string', [
                'limit'   => 3,
                'null'    => false,
                'comment' => 'Alpha currency code',
            ])
            ->addColumn(
                'source',
                'string', [
                'null'    => false,
                'comment' => 'Rate source code',
            ])
            ->addColumn(
                'rate',
                'decimal', [
                'null'    => false,
                'precision' => 15,
                'scale' => \App\Database\Type\RateType::PRECISION,
                'comment' => 'Rate value',
            ])
            ->addColumn(
                'created_at',
                'timestamp', [
                'null' => false,
            ]);

        $table->addIndex(['date', 'source']);
        $table->addIndex(['base', 'target']);

        $table->create();
    }
}
