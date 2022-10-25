<?php
/**
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Currency\Command;

use App\Base\AppCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\FrozenDate;
use Cake\ORM\TableRegistry;
use Currency\Model\Table\RatesTable;
use Currency\Source\CbrSource;

class UpdateCommand extends AppCommand
{

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

//        $parser->setDescription('Plugin Shell perform various tasks related to plugin.')
//            ->addSubcommand(
//                'Cbr',
//                [
//                    'help'   => 'Update Rates',
//                    'parser' => $this->Cbr->getOptionParser(),
//                ]
//            );

        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io) {
        $Rates = TableRegistry::getTableLocator()->get('Currency.Rates');

        $Rates->getConnection()->transactional(function () use ($Rates) {

//            foreach (['now', 'tomorrow'] as $date) {
            foreach (['now'] as $date) {
                $sourceAndDate = [
                    'source' => RatesTable::SOURCE_CBR,
                    'date'   => new FrozenDate($date),
                ];

                $rates = (new CbrSource($date))->getRates();

                if ($rates) {
                    $Rates->deleteAll($sourceAndDate);

                    $query = $Rates->query()->insert(array_keys(current($rates) + $sourceAndDate));

                    foreach ($rates as $rate) {
                        $query->values($rate + $sourceAndDate);
                    }
                    $query->execute();
                }
            }
        });

        return self::CODE_SUCCESS;
    }
}
