<?php

namespace Currency\Source;


use App\Database\Type\RateType;
use App\Lib\BigNumber;
use Cake\I18n\FrozenDate;
use Cake\Log\Log;
use Cake\Utility\Text;
use Cake\Utility\Xml;

class CbrSource
{
    const URL = 'https://www.cbr.ru/scripts/XML_daily.asp?date_req=:date';
    const LOG_GROUP_NAME = 'CurrencyRatesUpdate';

    /**
     * @var FrozenDate
     */
    protected $date;

    protected $currencies = [
        'USD',
        'RUB',
        'EUR',
        'GBP',

        // from Gate
        'JPY',
        'UAH',
        'ZAR',
//        'EUR',
//        'GBP',
//        'USD',
        'KRW',
        'CHF',
        'SEK',
        'CZK',
        'UZS',
        'TMT',
        'TRY',
        'TJS',
        'SGD',
        'XDR',
        'RON',
        'PLN',
        'NOK',
        'MDL',
        'CNY',
        'KGS',
        'CAD',
        'KZT',
        'INR',
        'DKK',
        'HUF',
        'BRL',
        'BGN',
        'BYN',
        'AMD',
        'AZN',
        'AUD',

    ];

    /**
     * CbrSource constructor.
     *
     * @param string     $dateString
     * @param array|null $currencies
     */
    public function __construct($dateString = 'now', ?array $currencies = null) {
        $this->date = new FrozenDate($dateString);

        if (null !== $currencies) {
            $this->currencies = $currencies;
        }
    }

    /**
     * @return array - [base => ..., target => ..., rate => ...]
     */
    public function getRates() {
        $result  = [];
        $xmlData = $this->getXmlData();

        foreach ($xmlData['ValCurs']['Valute'] as $row) {
            $currency = $row['CharCode'];

            if (!\in_array($currency, $this->currencies)) {
                continue;
            }
            $rateValue   = str_replace(',', '.', $row['Value']);
            $rateNominal = $row['Nominal'];

            // RUB -> XXX
            $result[] = [
                'base'   => $currency,
                'target' => 'RUB',
                'rate'   => (new BigNumber($rateValue, RateType::PRECISION))->divide($rateNominal),
            ];
        }

        for ($i = count($result) - 1; $i >= 0; $i--) {
            // XXX -> RUB
            $result[] = [
                'base'   => $result[$i]['target'],
                'target' => $result[$i]['base'],
                'rate'   => (clone $result[$i]['rate'])->pow(-1),
            ];

            for ($j = $i - 1; $j >= 0; $j--) {
                // XXX -> YYY
                $result[] = [
                    'base'   => $result[$j]['base'],
                    'target' => $result[$i]['base'],
                    'rate'   => (clone $result[$j]['rate'])->divide($result[$i]['rate']->getValue()),
                ];
                // YYY -> XXX
                $result[] = [
                    'base'   => $result[$i]['base'],
                    'target' => $result[$j]['base'],
                    'rate'   => (clone $result[$i]['rate'])->divide($result[$j]['rate']->getValue()),
                ];
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getXmlData() {
        $url = Text::insert(self::URL, ['date' => $this->date->format('d.m.Y')]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);

        $shots = 0;
        do {
            $res = curl_exec($ch);
            $shots++;
        } while ((!$res || '' === $res) && $shots < 4);

        if (!$res) {
            $res['error']         = curl_errno($ch);
            $res['error_message'] = curl_error($ch);
            $res['status']        = curl_getinfo($ch);
            curl_close($ch);
            Log::write(LOG_ERR, \GuzzleHttp\json_encode($res), ['scope' => [self::LOG_GROUP_NAME]]);

            return [];
        }
        curl_close($ch);

        try {
            $xml = Xml::toArray(Xml::build($res));
        }
        catch (\Throwable $e) {
            Log::write(LOG_ERR, $e->getMessage(), ['scope' => [self::LOG_GROUP_NAME]]);

            return [];
        }

        return $xml;
    }

}
