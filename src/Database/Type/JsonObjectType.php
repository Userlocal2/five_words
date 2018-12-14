<?php

namespace App\Database\Type;


use Cake\Database\Driver;
use Cake\Database\Type;

/**
 * Class JsonObjectType
 * @package App\Database\Type
 */
class JsonObjectType extends Type
{

    /**
     * @param mixed  $value
     * @param Driver $driver
     *
     * @return mixed
     */
    public function toPHP($value, Driver $driver) {

        return \GuzzleHttp\json_decode($value);
    }

    /**
     * @param mixed  $value
     * @param Driver $driver
     *
     * @return mixed|string
     */
    public function toDatabase($value, Driver $driver) {

        if (\is_array($value)) {
            $value = \GuzzleHttp\json_encode($value);
            $value = '{' . trim($value, '{}[]') . '}';
        }

        return $value;
    }

}
