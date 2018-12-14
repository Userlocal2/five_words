<?php

namespace Currency\Shell\Task;


use Cake\Console\Shell;
use Cake\I18n\FrozenDate;
use Currency\Model\Table\RatesTable;
use Currency\Source\CbrSource;

/**
 * @property \Currency\Model\Table\RatesTable Rates
 */
class CbrTask extends Shell
{
    public function initialize() {
        parent::initialize();

        $this->loadModel('Currency.Rates');
    }

    public function main(): int {

        $this->Rates->getConnection()->transactional(function() {

//            foreach (['now', 'tomorrow'] as $date) {
            foreach (['now'] as $date) {
                $sourceAndDate = [
                    'source' => RatesTable::SOURCE_CBR,
                    'date'   => new FrozenDate($date),
                ];

                $rates = (new CbrSource($date))->getRates();

                if ($rates) {
                    $this->Rates->deleteAll($sourceAndDate);

                    $query = $this->Rates->query()->insert(array_keys(current($rates) + $sourceAndDate));

                    foreach ($rates as $rate) {
                        $query->values($rate + $sourceAndDate);
                    }
                    $query->execute();
                }
            }
        });

        return Shell::CODE_SUCCESS;
    }
}
