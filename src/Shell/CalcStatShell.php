<?php

namespace App\Shell;

use App\Logic\Trans;
use Cake\Console\Shell;
use Currency\Logic\Rates;

/**
 * CalcStat shell command.
 * @property int _rate
 */
class CalcStatShell extends Shell
{
    private $__fee;

    /**
     * @var Rates
     */
    private $Rates;

    /**
     * Manage the available sub-commands along with their arguments and help
     *
     * @see http://book.cakephp.org/3.0/en/console-and-shells.html#configuring-options-and-generating-help
     *
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function getOptionParser() {
        $parser = parent::getOptionParser();

        return $parser;
    }

    private $merchant_currency;
    private $buyer_currency;
    private $settlement_currency;

    /**
     * main() method.
     *
     * @return bool|int|null Success or error code.
     */
    public function main() {
        $amount_buyer_currency = 500;
//        $cf = max(-1*(3/$amount_buyer_currency), 0.0115);
        $cf = min(max(2/$amount_buyer_currency,0.004),(10/$amount_buyer_currency));



        $this->Rates = new Rates();

        $type = 'Purchase';

        $this->merchant_currency   = 'EUR'; // EUR
        $this->buyer_currency      = 'CZK'; // CZK
        $this->settlement_currency = 'RUB'; // RUB

        $_rate_amount_merchant = 0;
        $_rate_amount_buyer    = 0;
        $_rate_settlement      = 0;

        $conversion_markup = 0.04;
        $this->__fee       = 0.00;


        $this->out('Tr->type = ' . $type);

        $this->out('Tr->merchant_currency = ' . $this->merchant_currency);
        $this->out('Tr->buyer_currency = ' . $this->buyer_currency);
        $this->out('Tr->settlement_currency = ' . $this->settlement_currency);


        if (!empty($_rate_amount_merchant)) {
            $this->out('Tr->_rate_amount_merchant = ' . $_rate_amount_merchant);
        }
        if (empty($_rate_amount_buyer) && ($this->buyer_currency != $this->merchant_currency)) {
            $rateDB             = $this->Rates->get($this->merchant_currency, $this->buyer_currency);
            $_rate_amount_buyer = $rateDB->rate->getValue();
        }
        $this->out('Tr->_rate_amount_buyer = ' . $_rate_amount_buyer);
        if (!empty($_rate_settlement)) {
            $this->out('Tr->_rate_settlement = ' . $_rate_settlement);
        }

        $this->_rate_settlement_buyer = 1;
        if ($this->buyer_currency != $this->settlement_currency) {
            $rateDB                       = $this->Rates->get($this->settlement_currency, $this->buyer_currency);
            $this->_rate_settlement_buyer = $rateDB->rate->getValue();
        }

        $this->_rate_settlement_merchant = 1;
        if ($this->merchant_currency != $this->settlement_currency) {
            $rateDB                          = $this->Rates->get($this->settlement_currency, $this->merchant_currency);
            $this->_rate_settlement_merchant = (float)$rateDB->rate->getValue();
        }


        $this->out('Tr->conversion_markup = ' . $conversion_markup);
        $this->out('__Fee = ' . $this->__fee);


        $this->out('amount_merchant_currency | amount_buyer_currency | settlement_amount | settlement_rate | money');

//        $this->out('                          old                        |                          new                   ');

        // init (in API request)
        for ($i = 100; $i <= 1000; $i += 50) {
            $tr       = new Trans();
            $tr->type = $type;

            $tr->amount_merchant_currency = 1 * $i;
            if ('Payout' === $type) {
                $tr->amount_merchant_currency = -1 * $i;
            }
            $tr->_rate_amount_merchant = $_rate_amount_merchant;
            $tr->_rate_amount_buyer    = $_rate_amount_buyer;
            $tr->_rate_settlement      = $_rate_settlement;

            $tr->conversion_markup = $conversion_markup;

            $this->calc($tr);
            $old = $tr->getValStr();

            $tr->amount_merchant_currency = 1 * $i;
            if ('Payout' === $type) {
                $tr->amount_merchant_currency = -1 * $i;
            }
            $this->calcNew($tr);
            $new = $tr->getValStr();

            if (abs($tr->settlement_amount) > abs($tr->amount_buyer_currency)) {
                $this->warn($old . '     |' . $new);
            }
            else {
                $this->out($old . '     |' . $new);
            }
        }


        // init


        return Shell::CODE_SUCCESS;
    }


    private function calc(Trans $tr) {
        // amount_merchant_currency
        $tr->amount_merchant_currency = $this->_convert('merchant', $tr);

        // amount_buyer_currency
        $tr->amount_buyer_currency = $this->_convert('buyer', $tr);

        // settlement_rate
        {
            $settlCurr = $this->settlement_currency;
            $buyCurr   = $this->buyer_currency;

            $rate               = 1;
            $conversion_mark_up = 0;
            if ($settlCurr != $buyCurr) {
                $rateDB = $this->Rates->get($settlCurr, $buyCurr);
                $rate   = $rateDB->rate->getValue();

                $conversion_mark_up = $tr->conversion_markup;
            }

            // for tests
            if (!empty($tr->_rate_settlement)) {
                $rate = $tr->_rate_settlement;
            }

            $result_rate = $rate * (1 + $conversion_mark_up);
            if ('Payout' === $tr->type) {
                $result_rate = $rate * (1 - $conversion_mark_up);
            }
            $tr->settlement_rate = round($result_rate, 4);
        }

        //settlement_amount
        {
            $res = round($tr->amount_buyer_currency / $tr->settlement_rate * (1 - $this->__fee), 2);
            if ('Payout' === $tr->type) {
                $res = round($tr->amount_buyer_currency / $tr->settlement_rate * (1 + $this->__fee), 2);
            }
            $tr->settlement_amount = $res;

            $tr->settlement_amount_clean = round(
                $tr->amount_buyer_currency / $this->_rate_settlement_buyer * (1 - $this->__fee),
                2
            );
            $tr->settlement_amount_clean = round(
                $tr->amount_merchant_currency / $this->_rate_settlement_merchant * (1 - $this->__fee),
                2
            );

            $tr->settlement_amount_merchant = round(
                $tr->settlement_amount * $this->_rate_settlement_merchant * (1 - $this->__fee),
                2
            );
        }


        // our money
        $tr->money = $tr->amount_buyer_currency / $tr->_rate_amount_buyer - $tr->settlement_amount_merchant;
    }

    public function _convert($who, Trans $tr) {
        $tr->_amount       = $tr->amount_merchant_currency;
        $conversion_markup = 1;
        if ('buyer' === $who && ($this->buyer_currency != $this->merchant_currency)) {
            $conversion_markup = 1 + $tr->conversion_markup;
            switch ($tr->type) {
                case 'Payout':
                case 'Refund':
                    {
                        $conversion_markup = 1 - $tr->conversion_markup;
                        break;
                    }
                default:
                    break;
            }
        }

        $currency = "{$who}_currency";
        $rate     = 1;

        if ($this->$currency != $this->merchant_currency) {
            $rateDB = $this->Rates->get($this->merchant_currency, $this->$currency);
            $rate   = $rateDB->rate->getValue();
        }


        // for tests
        if ('merchant' === $who) {
            if (!empty($tr->_rate_amount_merchant)) {
                $rate = $tr->_rate_amount_merchant;
            }
            $tr->_rate_amount_merchant = $rate;
        }
        if ('buyer' === $who) {
            if (!empty($tr->_rate_amount_buyer)) {
                $rate = $tr->_rate_amount_buyer;
            }
            $tr->_rate_amount_buyer = $rate;
        }


        $res = $tr->_amount * $rate * $conversion_markup;
        if ('merchant' === $who) {
            $merchant_external_fee = 0.0000;

            $res = $res / (1 - $merchant_external_fee);
        }
        $res = 'Payout' === $tr->type ? -abs($res) : $res;

        return number_format(round($res, 2), 2, '.', '');
    }


    private function calcNew(Trans $tr) {
        // amount_merchant_currency
        $tr->amount_merchant_currency = $this->_convertNew('merchant', $tr);

        // amount_buyer_currency
        $tr->amount_buyer_currency = $this->_convertNew('buyer', $tr);

        // settlement_rate
        {
            $settlCurr = $this->settlement_currency;
            $buyCurr   = $this->buyer_currency;

            $rate               = 1;
            $conversion_mark_up = 0;
            if ($settlCurr != $buyCurr) {
                $rateDB = $this->Rates->get($settlCurr, $buyCurr);
                $rate   = $rateDB->rate->getValue();

                $conversion_mark_up = $tr->conversion_markup;
            }

            // for tests
            if (!empty($tr->_rate_settlement)) {
                $rate = $tr->_rate_settlement;
            }
            $rate = 1 / $rate; // must be or die ))

            $result_rate = $rate * (1 - $conversion_mark_up);
            if ('Payout' === $tr->type) {
                $result_rate = $rate * (1 + $conversion_mark_up);
            }

            $tr->settlement_rate = round($result_rate, 4);
        }


        //settlement_amount
        {
            $res = round($tr->amount_buyer_currency * $tr->settlement_rate * (1 - $this->__fee), 2);

            if ('Payout' === $tr->type) {
                $res = round($tr->amount_buyer_currency * $tr->settlement_rate * (1 + $this->__fee), 2);
            }

            $tr->settlement_amount = $res;

            $tr->settlement_amount_clean = round(
                $tr->amount_buyer_currency / $this->_rate_settlement_buyer * (1 - $this->__fee),
                2
            );
            $tr->settlement_amount_clean = round(
                $tr->amount_merchant_currency / $this->_rate_settlement_merchant * (1 - $this->__fee),
                2
            );

            $tr->settlement_amount_merchant = round(
                $tr->settlement_amount * $this->_rate_settlement_merchant * (1 - $this->__fee),
                2
            );
        }


        // our money
        $tr->money = $tr->amount_buyer_currency / $tr->_rate_amount_buyer - $tr->settlement_amount_merchant;
    }


    public function _convertNew($who, Trans $tr) {
        $tr->_amount       = $tr->amount_merchant_currency;
        $conversion_markup = 1;
        if ('buyer' === $who && ($this->buyer_currency != $this->merchant_currency)) {
            $conversion_markup = 1 - $tr->conversion_markup;
            switch ($tr->type) {
                case 'Payout':
                case 'Refund':
                    {
                        $conversion_markup = 1 + $tr->conversion_markup;
                        break;
                    }
                default:
                    break;
            }
        }

        $currency = "{$who}_currency";
        $rate     = 1;

        if ($this->$currency != $this->merchant_currency) {
            $rateDB = $this->Rates->get($this->merchant_currency, $this->$currency);
            $rate   = $rateDB->rate->getValue();
        }


        // for tests
        if ('merchant' === $who) {
            if (!empty($tr->_rate_amount_merchant)) {
                $rate = $tr->_rate_amount_merchant;
            }
            $tr->_rate_amount_merchant = $rate;
        }
        if ('buyer' === $who) {
            if (!empty($tr->_rate_amount_buyer)) {
                $rate = $tr->_rate_amount_buyer;
            }
            $tr->_rate_amount_buyer = $rate;
        }

        $res = $tr->_amount * $rate / $conversion_markup;
        if ('merchant' === $who) {
            $merchant_external_fee = 0.0000;

            $res = $res / (1 - $merchant_external_fee);
        }
        $res = 'Payout' === $tr->type ? -abs($res) : $res;

        return number_format(round($res, 2), 2, '.', '');
    }
}
