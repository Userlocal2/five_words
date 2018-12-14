<?php

namespace App\Database\Type;


class DateTimeType extends \Cake\Database\Type\DateTimeType
{
    protected $_format = 'Y-m-d\TH:i:s.u';

    public static $dateTimeClass = 'Cake\I18n\Time';

    // TODO finish logic and uncomment mapping in config/microservices/general_ledger.php
}