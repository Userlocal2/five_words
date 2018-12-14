<?php

namespace App\Lib;

/**
 * Class BigNumber
 * @package App\Lib
 */
class BigNumber extends \Moontoast\Math\BigNumber implements \JsonSerializable
{
    /**
     * Default value number of digits after the decimal
     */
    public const DEFAULT_PRECISION = 2;

    /**
     * Redefinition parent method
     *
     * @param     $number
     * @param int $scale
     */
    public function __construct($number, $scale = self::DEFAULT_PRECISION) {
        parent::__construct($number, $scale);
    }

    /**
     * @return float|mixed
     */
    public function jsonSerialize() {

        return (float)$this->__toString();
    }
}