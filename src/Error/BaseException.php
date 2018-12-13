<?php

namespace App\Error;

use Throwable;


/**
 * Class BaseApiException
 * @package App\Error
 */
abstract class BaseException extends \Exception
{

    protected $errorList;

    protected $details = '';

    /**
     * @param string $details
     *
     * @return $this
     */
    public function setDetails(string $details) {
        $this->details = $details;

        return $this;
    }

    public function __toString() {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}