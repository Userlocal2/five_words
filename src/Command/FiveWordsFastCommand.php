<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;

/**
 * FiveWords command.
 */
class FiveWordsFastCommand extends Command
{
    private ConsoleIo $io;

    private array $words_5;

    private array $double;
    private array $library;
    private array $libraryWord;

    private array $uniq;
    private array $word_processing;

    private array $result;
    private array $word_indexes;

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

        $io->out('START');

        $totalTime = microtime(true);
        [$ms, $s] = explode(' ', microtime());
        $startMicroTime = $s * 1000 + round($ms * 1000);


        $this->io     = $io;
        $this->result = [];
        $this->uniq   = [
            'processed' => [],
        ];

        $this->libraryWord  = [];
        $this->word_indexes = [];


        $loadTime = microtime(true);

        $path     = RESOURCES . 'words_alpha.txt';
        $wordsAll = file($path, FILE_IGNORE_NEW_LINES);

        $io->success('Load File time ' . (microtime(true) - $loadTime) * 1000 . ' ms');

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
            }
        }
        unset($wordsAll, $word, $i, $letter);

        // find all words with same letters
        $this->double = [];
        foreach ($double as $words_double) {
            if (1 < count($words_double)) {
                $needle = array_shift($words_double);

                $this->double[$needle] = $words_double;
            }
        }
        unset($double, $needle, $words_double);


        $this->library = $this->getLibrary($this->words_5);
        $word1_indexes = $this->getTwoMinMerge($this->library);

        $loadTime = microtime(true) - $loadTime;
        $io->success('Prepare time ' . $loadTime * 1000 . ' ms');
        unset($loadTime);

        $this->findWords(5, $word1_indexes);

        $totalTime = microtime(true) - $totalTime;
        [$ms, $s] = explode(' ', microtime());
        $endMicroTime = $s * 1000 + round($ms * 1000);

        if ($this->result) {
            ksort($this->result);
            file_put_contents(TMP . 'result_five_words.txt', implode(PHP_EOL, array_keys($this->result)));
        }

        $io->success('DONE');
        $io->out('Total time: ' . round($totalTime * 1000) . ' ms');
        $io->out('Total time: ' . ($endMicroTime - $startMicroTime) . ' ms');

        $io->success('Count found: ' . count($this->result));
        $io->success('Max Used Memory: ' . round(memory_get_peak_usage() / 1024 / 1024) . 'Mb');

        $io->out('Result file: ' . TMP . 'result_five_words.txt');

        return self::CODE_SUCCESS;
    }


    private function findWords(int $words_count, array $word_indexes = [], array $prev_word = []) {
        if (0 == $words_count) {
            return;
        }

        foreach ($word_indexes as $index => $word) {
            $next_word = array_merge($prev_word, $word);

            if (1 == $words_count) {
                $this->saveResults($next_word);
                continue;
            }

            $this->word_processing[$words_count] = $index;

            if ($this->isProcessed()) {
                continue;
            }

            $word_indexes_next = $this->getNextWordsIndexes($words_count);
            if (!$word_indexes_next || ($words_count - 1) > count($word_indexes_next)) {
                continue;
            }

            $word_indexes_next = $this->getTwoMinMerge($this->getLibrary($word_indexes_next));

            $this->findWords($words_count - 1, $word_indexes_next, $next_word);
        }

        unset($this->word_processing[$words_count]);
    }


    private function getNextWordsIndexes(int $words_count): array {
        $prev_res = $this->word_indexes[$words_count + 1] ?? $this->getWordIndexes($this->word_processing[$words_count]);

        $this->word_indexes[$words_count] = array_intersect_key($prev_res, $this->getWordIndexes($this->word_processing[$words_count]));

        return $this->word_indexes[$words_count];
    }

    private function getWordIndexes(int $index) {
        if (empty($this->libraryWord[$index])) {
            $word = $this->words_5[$index];
            $del  = [];

            $del += $this->library[$word[0]];
            $del += $this->library[$word[1]];
            $del += $this->library[$word[2]];
            $del += $this->library[$word[3]];
            $del += $this->library[$word[4]];

            $this->libraryWord[$index] = array_diff_key($this->words_5, $del);
        }

        return $this->libraryWord[$index];
    }

    private function isProcessed(): bool {
        $key = 1;

        $key *= $this->word_processing[5] ?? 1;
        $key *= $this->word_processing[4] ?? 1;
        $key *= $this->word_processing[3] ?? 1;
        $key *= $this->word_processing[2] ?? 1;
        $key *= $this->word_processing[1] ?? 1;

        $key .= 's';

        if (array_key_exists($key, $this->uniq['processed'])) {
            return true;
        }
        $this->uniq['processed'][$key] = null;

        return false;
    }

    private function saveResults(array $aWords): void {
        $aWords = str_split(implode('', $aWords), 5);
        sort($aWords);
        $resWords = $this->multiplyResults($aWords);

        foreach ($resWords as $words) {
            sort($words);
            $sWords = implode(' ', $words);

            if (empty($this->result[$sWords])) {
                $this->result[$sWords] = $words;
                if (ConsoleIo::VERBOSE == $this->io->level()) {
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

        $rw    = 0;
        $rwEnd = count($aWords);
        do {
            $word = $aWords[$rw++];

            if (array_key_exists($word, $this->double)) {
                $dw    = 0;
                $dwEnd = count($this->double[$word]);
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
                while ($dw != $dwEnd);
            }
        }
        while ($rw != $rwEnd);

        return $aResWords;
    }

    private function getLibrary(array $indexes): array {
        $library = [];

        foreach ($indexes as $index => $word) {
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
            $library[$word[0]][$index] = $word;
            $library[$word[1]][$index] = $word;
            $library[$word[2]][$index] = $word;
            $library[$word[3]][$index] = $word;
            $library[$word[4]][$index] = $word;
        }

        return $library;
    }

    private function getTwoMinMerge(array $library): array {
        $min1  = min($library);
        $kMin1 = array_keys($library, $min1);
        unset($library[$kMin1[0]]);
        $min2 = min($library);

        return $min1 + $min2;
    }
}
