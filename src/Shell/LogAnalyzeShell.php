<?php


namespace App\Shell;


use App\Lib\Convert;
use App\Logic\Entity\Log;
use Cake\Console\Shell;
use Cake\Filesystem\File;

class LogAnalyzeShell extends Shell
{
    /**
     * @var array|Shell
     */
    private $groupCount = [];
    /**
     * @var array|Shell
     */
    private $messagesCount = [];

    public function main() {
//        $filePath = TMP . 'Boost.txt';
        $filePath = TMP . 'BoostLogsCw.txt';

//        $file = new File($filePath);
//        $file->lock = true;
//        $fileContent = $file->read();
//        $file->close();


        $statistic     = [];

        $data             = [];
        $row              = '';
        $bigMessagesCount = 0;
        if ($file = fopen($filePath, "r")) {
            $rows_to_collect = 1000000 * -1 - 100000 * 0 - 100000 * 0;
            while (!feof($file) && count($data) > $rows_to_collect) {
                $line = fgets($file);

                if ('{' === trim($line)) {
                    $row = $line;
                }
                elseif ('}' === trim($line)) {
                    $row .= $line;
                    if (32768 < strlen($row)) {
//                        print_r($row);die();
                        $bigMessagesCount++;
                    }


//                    if(false === strpos($row, 'New request from')){
//                    if (false === strpos($row, '8c18a0aa-acad-4765-8802-e1beb80f2c0f')) { // wallet count
                    if (
                        false !== strpos($row, 'Available balance')
                        || false !== strpos($row, 'provider_status is')
                        || false !== strpos($row, 'transactions/invoice')
                        || false !== strpos($row, 'Request attempt')
                        || false !== strpos($row, 'clientBankNameByBicFillStage')
                        || false !== strpos($row, 'success_url')
                        || false !== strpos($row, 'Wallet is OK')
                        || false !== strpos($row, 'checkouts')
                        || false !== strpos($row, 'qiwi.com')
                        || false !== strpos($row, 'neteller.com')
                        || false !== strpos($row, 'sepagateway.clearjunction.com')
                        || false !== strpos($row, 'yamoney.ru')
                        || false !== strpos($row, 'creditpilot.ru')
                        || false !== strpos($row, 'YandexMoney')
                        || false !== strpos($row, 'clearbank')
                        || false !== strpos($row, 'Summary processing info')
                        || false !== strpos($row, 'reports/transactionReport')
                        || false !== strpos($row, 'GetPostbackTask')
                        || false !== strpos($row, 'postback')
                        || false !== strpos($row, 'Postback')
                        || false !== strpos($row, 'DNSResolveSuccess')
                        || false !== strpos($row, 'prepareTransactionStage')
                        || false !== strpos($row, 'ClientCheckProcessing')
                        || false !== strpos($row, 'getInvoice')
                        || false !== strpos($row, 'Geo Location')
                        || false !== strpos($row, 'provider_status')
                        || false !== strpos($row, 'ProviderStatus')
                        || false !== strpos($row, 'TransferEvent')
                        || false !== strpos($row, 'SendEmail')
                        || false !== strpos($row, 'approved automatically')
                        || false !== strpos($row, 'form for manual input')
                        || false !== strpos($row, 'requisitesFillStage')
                        || false !== strpos($row, 'Aml check final state')
                        || false !== strpos($row, 'Response.')
                        || false !== strpos($row, 'SepaBalanceNotification')
                        || false !== strpos($row, 'Invalid BIC')
                        || false !== strpos($row, 'Invalid BIC')
                        || false !== strpos($row, 'Funding')
                        || false !== strpos($row, 'transfer')
                        || false !== strpos($row, 'Authorizing')
                        || false !== strpos($row, 'PayoutReturn')
                        || false !== strpos($row, 'settled')
                        || false !== strpos($row, 'PurchaseReturn')
                        || false !== strpos($row, 'Refund')
                        || false !== strpos($row, 'WithdrawalPause')
                    ) {
                        $row = '';
                        continue;
                    }

                    $this->statistic($row);


//                    $data[] = $row;
                    $row = '';
                }
                else {
                    $row .= $line;
                }
            }
            fclose($file);
        }
//$this->out($bigMessagesCount);die;


        arsort($this->messagesCount);

        $statistic['groupCount']    = $this->groupCount;
        $statistic['messagesCount'] = $this->messagesCount;

        print_r($statistic);
    }


    public function statistic($row){
        $row = \GuzzleHttp\json_decode($row);
//                    $row    = Convert::mapToObject($row, new Log());


        if (!array_key_exists($row->group, $this->groupCount)) {
            $this->groupCount[$row->group] = 0;
        }
        $this->groupCount[$row->group]++;


        if ('clearjunction/Transactions/SortByUuid' === trim($row->group)) {
            $start = strpos($row->message, ': ') + 2;
            $end   = strpos($row->message, ' | ');

            $mess = substr($row->message, $start, $end - $start);

            if (ctype_digit($mess[0])) {
                $start2 = strpos($mess, ': ') + 2 + $start;

                $mess = substr($row->message, $start2, $end - $start2);
            }

            if (!array_key_exists($mess, $this->messagesCount)) {
                $this->messagesCount[$mess] = 0;
            }
            $this->messagesCount[$mess]++;
        }
    }

}