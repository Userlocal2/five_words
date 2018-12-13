<?php

namespace App\Lib;

/**
 * Class PlatformConfig
 * @package App\Lib
 */
class PlatformConfig
{

    /**
     * @var array
     * @required
     */
    public $hosts;

    /**
     * @var bool|null
     */
    public $send_postbacks = true;

    /**
     * @var bool|null
     */
    public $send_email = true;
}