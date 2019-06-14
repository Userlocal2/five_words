<?php

namespace Currency;


use App\Error\ResponseApiException;
use App\Lib\BigNumber;
use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;
use Currency\Model\Entity\Rate;
use Currency\Model\Table\RatesTable;

class Converter
{
    /**
     * @param BigNumber|string|float $amount
     * @param                        $base
     * @param                        $target
     * @param string                 $source
     *
     * @return BigNumber
     * @throws ResponseApiException
     */
    public static function convert($amount, $base, $target, $source = RatesTable::SOURCE_CBR): BigNumber {

        if (!$amount instanceof BigNumber) {
            $amount = new BigNumber($amount);
        }
        $date = FrozenDate::now();

        /** @var Rate $rate */
        $rate = TableRegistry::getTableLocator()->get('Currency.Rates')
            ->find()
            ->where(compact('source', 'base', 'target', 'date'))
            ->first();

        if (!$rate) {

            throw new ResponseApiException(sprintf('No rate %s -> %s found', $base, $target));
        }

        $result = clone $amount;
        $result->multiply($rate->rate);

        return $result;
    }
}
