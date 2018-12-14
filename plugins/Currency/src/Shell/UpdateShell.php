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

namespace Currency\Shell;

use Cake\Console\Shell;

/** *
 * @property \Currency\Shell\Task\CbrTask $Cbr
 */
class UpdateShell extends Shell
{

    /**
     * {@inheritDoc}
     */
    public $tasks = [
        'Currency.Cbr',
    ];


    public $argv = [];

    public function getOptionParser() {
        $parser = parent::getOptionParser();

        $parser->setDescription('Plugin Shell perform various tasks related to plugin.')
            ->addSubcommand(
                'Cbr',
                [
                    'help'   => 'Update Rates',
                    'parser' => $this->Cbr->getOptionParser(),
                ]
            );

        return $parser;
    }
}
