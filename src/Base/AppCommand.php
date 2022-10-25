<?php

namespace App\Base;

use Cake\Console\Command;
use Cake\Console\ConsoleOptionParser;

abstract class AppCommand extends Command
{

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param ConsoleOptionParser $parser The parser to be defined
     *
     * @return ConsoleOptionParser The built parser.
     */
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
        $parser = parent::buildOptionParser($parser);

        $parser
            ->addOption(
                'connection',
                [
                    'short'   => 'c',
                    'help'    => 'connection',
                    'default' => 'default',
                ]
            );

        return $parser;
    }


    public static function getConsoleCallName(): string {
        [$plugin] = explode('\\', static::class);
        if ('App' == $plugin || 'Cake' == $plugin) {
            $plugin = '';
        }
        else {
            $plugin .= '.';
        }

        $command = static::defaultName();

        return $plugin . $command;
    }
}
