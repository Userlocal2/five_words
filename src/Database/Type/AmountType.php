<?php

namespace App\Database\Type;


use App\Lib\BigNumber;
use Cake\Database\Driver;
use Cake\Database\Type;

/**
 * Class AmountType
 * @package App\Database\Type
 */
class AmountType extends Type
{

    /**
     * @param mixed  $value
     * @param Driver $driver
     *
     * @return BigNumber|mixed
     */
    public function toPHP($value, Driver $driver) {

        return new BigNumber($value);
    }

    /**
     * @param        $value
     * @param Driver $driver
     *
     * @return mixed|string
     */
    public function toDatabase($value, Driver $driver) {

        if (!$value instanceof BigNumber) {
            $value = new BigNumber($value);
        }

        return $value->getValue();
    }

}
