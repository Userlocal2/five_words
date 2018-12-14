<?php

namespace Currency\Test\Fixture\Converter;

use App\TestSuite\Fixture\VerboseTestFixture;
use Cake\I18n\FrozenDate;
use Currency\Model\Table\RatesTable;

/**
 * RatesFixture
 *
 */
class RatesFixture extends VerboseTestFixture
{
    public $import = ['model' => 'Currency.Rates'];

    public function init() {
        $this->records = [
            [
                'base'   => 'EUR',
                'target' => 'USD',
                'date'   => FrozenDate::now(),
                'rate'   => 1.5,
                'source' => RatesTable::SOURCE_CBR,
            ],
        ];

        parent::init();
    }
}
