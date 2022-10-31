<?php
declare(strict_types=1);

namespace App\Command;

use App\Lib\Bar;
use Cake\Chronos\Chronos;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

/**
 * FiveWords command.
 */
class FiveWordsCommand extends Command
{
    private ConsoleIo $io;

    private array $words_5;

    private array $double;
    private array $library;
    private array $words_indexes;

    private Bar $progress;

    private int   $words_count;
    private array $path;
    private array $uniq;

    private array $result;

    /**
     * Implement this method with your command's logic.
     *
     * @param Arguments $args The command arguments.
     * @param ConsoleIo $io   The console io
     *
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) {
        ini_set('memory_limit', -1);

        $this->io = $io;
        $io->out('START');
        $this->result = [];
        $this->uniq   = [
            'nextWord' => [],
        ];


        $path     = RESOURCES . 'words_alpha.txt';
        $wordsAll = file($path, FILE_IGNORE_NEW_LINES);

        $start = Chronos::now();
//        $testResult  = ['dwarf', 'glyph', 'jocks', 'muntz', 'vibex'];
//        $testResult  = ['ambry', 'fldxt', 'spung', 'vejoz', 'whick'];
//        $this->check = [];

        $double = [];

        $loadTime = microtime(true);
        foreach ($wordsAll as $word_dictionary) {
            if (strlen($word_dictionary) == 5) {
                $word = str_split($word_dictionary);
                if (5 !== count(array_flip($word))) {
                    continue;
                }

                $sWord = $word;
                sort($sWord);
                $sWord = implode('', $sWord);
                if (array_key_exists($sWord, $double)) {
                    $double[$sWord][] = $word_dictionary;
                    continue;
                }
                $double[$sWord] = [$word_dictionary];

                $this->words_5[] = $word;

                $i = count($this->words_5) - 1;

                foreach ($word as $letter) {
                    $this->library[$letter][$i] = $i;
                }

//                if (in_array(implode('', $word), $testResult)) {
//                    $this->check[$i] = implode('', $word);
//                }
            }
        }
        unset($wordsAll, $word, $i, $letter);


        $this->double = [];
        foreach ($double as $words_double) {
            if (1 < count($words_double)) {
                $needle = array_shift($words_double);

                $this->double[$needle] = $words_double;
            }
        }
        unset($double, $needle, $words_double);

        $this->words_indexes  = array_keys($this->words_5);
        $this->words_indexesF = array_flip($this->words_indexes);


        $word1_indexes = $this->getTwoMinMerge($this->library);

        $loadTime = microtime(true) - $loadTime;
        $io->success('Load time ' . $loadTime * 1000 . ' ms');
        unset($loadTime);

        $this->progress = new Bar($io);
        $this->progress->init(
            [
                'total' => count($word1_indexes),
                //'width' => 100,
            ]
        );
        $this->progress->tick(0);

        $this->words_count = 5;

        $this->findWords($this->words_count, $word1_indexes);

        echo PHP_EOL;
        $io->out($io->nl());

        $end = Chronos::now();
        $io->success('DONE');
        $io->out('Total time: ' . ($end->timestamp - $start->timestamp) . 's');
        $io->out('Time passed: ~' . $end->diffForHumans($start) . ' start');

        $io->success('Count found: ' . count($this->result));
        $io->success('Max Used Memory: ' . round(memory_get_peak_usage() / 1024 / 1024) . 'Mb');

        return self::CODE_SUCCESS;
    }


    private function findWords(int $count, array $word_indexes = [], array $prev_word = []) {
        if (0 == $count || !$word_indexes || $count > count($word_indexes)) {
            return;
        }

        $w   = 0;
        $end = end($word_indexes);
        do {
            $word_index = $word_indexes[$w++];
            $word       = $this->words_5[$word_index];

            $next_word          = array_merge($prev_word, $word);
            $this->path[$count] = $word_index;

            if (1 === $count) {
                $this->saveResults($next_word);
                continue;
            }

            $this->saveProcessed($next_word, array_values($this->path));

            $word_indexes_next = $this->getNextWordsIndexes($next_word);
            $word_indexes_next = $this->cleanProcessed($next_word, $word_indexes_next);

            $this->findWords($count - 1, $word_indexes_next, $next_word);

            // beauty
            if ($this->words_count == $count) {
                $this->progress->tick();
            }
        }
        while ($word_index !== $end);

        unset($this->path[$count]);
    }


    private function getNextWordsIndexes(array $word): array {
        $chek = $word;
        sort($chek);
        $chek = implode('', $chek);
        if (array_key_exists($chek, $this->uniq['nextWord'])) {
            return $this->uniq['nextWord'][$chek];
        }

        $del = [];

        $l   = 0;
        $end = count($word);
        do {
            $letter = $word[$l++];
            $del    += $this->library[$letter];
        }
        while ($l !== $end);

        $last_indexes = array_diff_key($this->words_indexesF, $del);

        $this->uniq['nextWord'][$chek] = array_keys($last_indexes);

        return $this->uniq['nextWord'][$chek];
    }

    private function saveProcessed(array $word, array $indexes) {
        if (5 == count($word)) {
            return;
        }

        sort($word);
        $word = implode('', $word);

        $indexes = array_flip($indexes);


        $this->uniq[$word] = $indexes;
    }

    private function cleanProcessed(array $wordsLetters, array $indexes): array {
        sort($wordsLetters);
        $wordsLetters = implode('', $wordsLetters);

        if ($indexes) {
            if (!empty($this->uniq[$wordsLetters])) {
                $indexes = array_flip($indexes);
                $indexes = array_keys(array_diff_key($indexes, $this->uniq[$wordsLetters]));
            }

            $library = $this->getLibrary($indexes);
            $indexes = $this->getTwoMinMerge($library);
        }

        return $indexes;
    }

    private function saveResults(array $aWords): void {
        $aWords = str_split(implode('', $aWords), 5);
        sort($aWords);
        $resWords = $this->multiplyResults($aWords);
        //$resWords = [$aWords];

        foreach ($resWords as $words) {
            sort($words);
            $sWords = implode(' ', $words);

            if (empty($this->result[$sWords])) {
                $this->result[$sWords] = $words;
                if (ConsoleIo::VERBOSE === $this->io->level()) {
                    $this->io->out(implode(' ', $words));
                }
            }

        }
    }

    /**
     * Example: ['baker', 'chivw', 'fldxt', 'jumps', 'zygon']
     *
     * @param array $aWords
     *
     * @return array[]
     */
    private function multiplyResults(array $aWords): array {
        $aResWords = [$aWords];

        $rw = 0;
        do {
            $word = $aWords[$rw++];

            if (array_key_exists($word, $this->double)) {
                $dw = 0;
                do {
                    $dword = $this->double[$word][$dw++];
                    foreach ($aResWords as $resWords) {
                        $new = array_flip($resWords);
                        if (array_key_exists($word, $new)) {
                            unset($new[$word]);
                            $new[$dword] = null;

                            $new = array_keys($new);
                            sort($new);
                            $aResWords[] = $new;
                        }
                    }
                }
                while ($dword !== end($this->double[$word]));
            }
        }
        while ($word != end($aWords));

        return $aResWords;
    }

    private function getLibrary(array $indexes): array {
        $library = [];

        $i = 0;

        do {
            $index = $indexes[$i++];

            $word = $this->words_5[$index];

            if (empty($library[$word[0]])) {
                $library[$word[0]] = [];
            }
            if (empty($library[$word[1]])) {
                $library[$word[1]] = [];
            }
            if (empty($library[$word[2]])) {
                $library[$word[2]] = [];
            }
            if (empty($library[$word[3]])) {
                $library[$word[3]] = [];
            }
            if (empty($library[$word[4]])) {
                $library[$word[4]] = [];
            }


            $library[$word[0]][] = $index;
            $library[$word[1]][] = $index;
            $library[$word[2]][] = $index;
            $library[$word[3]][] = $index;
            $library[$word[4]][] = $index;
        }
        while ($index !== end($indexes));

        return $library;
    }

    private function getTwoMinMerge(array $library): array {
        usort($library, function ($a, $b) { return count($a) - count($b); });

//        return array_keys(array_flip($library[0]) + array_flip($library[1]));
        return array_keys(array_flip(array_merge($library[0], $library[1])));
    }
}