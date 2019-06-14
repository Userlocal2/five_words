<?php

namespace Currency\Logic;

use Currency\Model\Entity\Rate;

class Rates
{

    /**
     * @var \Currency\Model\Table\RatesTable
     */
    private $CurrencyRates;

    public function __construct() {
        $this->CurrencyRates = \Cake\ORM\TableRegistry::getTableLocator()->get('Currency.Rates');
    }

    public function get($base, $target) {

        /** @var Rate $rate */
        $rate = $this->CurrencyRates->find()->where([
            'base'   => $base,
            'target' => $target,
        ])
            ->order('date DESC')
            ->first();

        return $rate;
    }

}
