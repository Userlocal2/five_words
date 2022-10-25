<?php

namespace App\Base;

use App\Lib\Convert;
use GuzzleHttp\Utils;

/**
 * Class BaseEntity
 * @package App\Base
 */
abstract class BaseEntity
{
    /**
     * @return array
     */
    public function toArray() {
        return Convert::objectToArray($this);
    }

    /**
     * @return string
     */
    public function toJson() {
        return Utils::jsonEncode($this->toArray(), JSON_PRETTY_PRINT);
    }
}
