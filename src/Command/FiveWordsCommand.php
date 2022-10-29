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
//        ini_set('memory_limit', -1);

        $this->io = $io;
        $io->out('START');


        $path     = TMP . 'words_alpha.txt';
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

        $loadTime = microtime(true) - $loadTime;
        $io->success('Load time ' . $loadTime * 1000);
        unset($loadTime);

        $this->words_indexes = array_keys($this->words_5);


        $word1_indexes = $this->getTwoMinMerge($this->library);
//        $word1_indexes = $this->getTwoMaxMerge($this->libraryR);


        $this->progress = new Bar($io);
        $this->progress->init(
            [
                'total' => count($word1_indexes),
                'width' => 100,
            ]
        );
        $this->progress->tick(0);

        $this->words_count = 5;

        $this->findWords($this->words_count, $word1_indexes);
//        $this->findStrait($word1_indexes);

        echo PHP_EOL;
        $io->out($io->nl());

        $end = Chronos::now();
        $io->success('DONE');
        $io->out('Total time: ' . ($end->timestamp - $start->timestamp) . 's');
        $io->out('Time passed: ~' . $end->diffForHumans($start) . ' start');

        $io->success('Count found: ' . count($this->result));

        return self::CODE_SUCCESS;
    }


    private function findWords(int $count, array $word_indexes = [], array $prev_word = []) {
        $w = 0;
        do {
            $word_index = $word_indexes[$w++];
            $word       = $this->words_5[$word_index];

            $all_word           = array_merge($prev_word, $word);
            $this->path[$count] = $word_index;

            if (1 === $count) {
                $this->saveResults($all_word);
                continue;
            }

            $this->saveProcessed($all_word, array_values($this->path));

            $word_indexes_next = $this->getIndexes($all_word, $this->words_indexes);
            $word_indexes_next = $this->cleanProcessed($all_word, $word_indexes_next);

            if (1 !== $count && !empty($word_indexes_next)) {
                $this->findWords($count - 1, $word_indexes_next, $all_word);
            }

            // beauty
            if ($this->words_count == $count) {
                $this->progress->tick();
            }
        }
        while ($word_index !== end($word_indexes));

        unset($this->path[$count]);
    }

    private function findStrait(array $word1_indexes) {

        $w1 = 0;
        do {
            $word1_index = $word1_indexes[$w1++];

            $word1     = $this->words_5[$word1_index];
            $all_word1 = $word1;

            $word2_indexes = $this->getIndexes($all_word1, $this->words_indexes);
            $word2_indexes = $this->cleanProcessed($all_word1, $word2_indexes);

            $w2 = 0;

            do {
                /** WORD 2 **/
                $word2_index = $word2_indexes[$w2++];

                $word2     = $this->words_5[$word2_index];
                $all_word2 = array_merge($all_word1, $word2);
                $this->saveProcessed($all_word2, [$word1_index, $word2_index]);

                $word3_indexes = $this->getIndexes($all_word2, $this->words_indexes);
                $word3_indexes = $this->cleanProcessed($all_word2, $word3_indexes);

                if (!empty($word3_indexes)) {


                    $w3 = 0;
                    do {
                        /** WORD 3 **/
                        $word3_index = $word3_indexes[$w3++];
                        $word3       = $this->words_5[$word3_index];
                        $all_word3   = array_merge($all_word2, $word3);
                        $this->saveProcessed($all_word3, [$word1_index, $word2_index, $word3_index]);

                        $word4_indexes = $this->getIndexes($all_word3, $this->words_indexes);
                        $word4_indexes = $this->cleanProcessed($all_word3, $word4_indexes);

                        if (!empty($word4_indexes)) {

                            $w4 = 0;
                            do {
                                $word4_index = $word4_indexes[$w4++];
                                /** WORD 4 **/
                                $word4     = $this->words_5[$word4_index];
                                $all_word4 = array_merge($all_word3, $word4);
                                $this->saveProcessed($all_word4, [
                                    $word1_index,
                                    $word2_index,
                                    $word3_index,
                                    $word4_index,
                                ]);

                                $word5_indexes = $this->getIndexes($all_word4, $this->words_indexes);
                                $word5_indexes = $this->cleanProcessed($all_word4, $word5_indexes);

                                foreach ($word5_indexes as $word5_index) {
                                    /** WORD 5 **/
                                    $word5     = $this->words_5[$word5_index];
                                    $all_words = array_merge($all_word4, $word5);

                                    if (25 !== count(array_flip($all_words))) {
                                        echo 'Wrong algo ';
                                    }

                                    $this->saveResults($all_words);
                                }

                            }
                            while ($word4_index !== end($word4_indexes));
                        }
                    }
                    while ($word3_index !== end($word3_indexes));
                }
            }
            while ($word2_index !== end($word2_indexes));
            // beauty
            //$progress->increment()->draw();
            $this->progress->tick();
        }
        while ($word1_index !== end($word1_indexes));
    }


    private function getIndexes(array $word, array $prev_indexes): array {
        $del = [];

        $l = 0;
        do {
            $letter = $word[$l++];
            $del    += $this->library[$letter];
        }
        while ($letter !== end($word));

        $last_indexes = array_diff_key(array_flip($prev_indexes), $del);

        return array_keys($last_indexes);
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

        return array_keys(array_flip($library[0]) + array_flip($library[1]));
    }
}
