<?php

namespace App\Logic;

/**
 * @property string amount_merchant_currency
 * @property string amount_buyer_currency
 * @property float  conversion_markup
 * @property int    $_rate_amount_merchant
 * @property int    $_rate_amount_buyer
 * @property int    _rate_settlement
 * @property string type
 * @property float  settlement_rate
 * @property float  settlement_amount
 * @property string _amount
 */
class Trans
{

    public $settlement_amount_clean;

    public function getValStr() {
        $str   = $this->tablize($this->amount_merchant_currency);

        $buyer = ' ' . $this->amount_buyer_currency;
        $buyer .= '(' . round($this->amount_buyer_currency / $this->_rate_amount_buyer, 2) . ') ';
        $str   .= $this->tablize($buyer);

        $settlement = ' ' . $this->settlement_amount;
        $settlement .= '(' . $this->settlement_amount_merchant . ':' . $this->settlement_amount_clean . ') ';
        $str   .= $this->tablize($settlement);
        $str   .= $this->tablize($this->settlement_rate);
        $str   .= ' ' . $this->tablize($this->money);

        return $str;
    }


    public function tablize($str) {
        return str_pad($str, 16, ' ', STR_PAD_LEFT);
    }

}