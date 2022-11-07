<?php

/**
 * Denis
 */

final class Challenge
{
    static $debugCntr         = 0;
    static $emptyCharCounters = [];

    static $words          = [];
    static $CHARS_MAP      = [
        'a' => 0,
        'b' => 0,
        'c' => 0,
        'd' => 0,
        'e' => 0,
        'f' => 0,
        'g' => 0,
        'h' => 0,
        'i' => 0,
        'j' => 0,
        'k' => 0,
        'l' => 0,
        'm' => 0,
        'n' => 0,
        'o' => 0,
        'p' => 0,
        'q' => 0,
        'r' => 0,
        's' => 0,
        't' => 0,
        'u' => 0,
        'v' => 0,
        'w' => 0,
        'x' => 0,
        'y' => 0,
        'z' => 0,
    ];
    static $charsWeightMap = [];
    static $charCounters   = [];

    static $synonyms    = [];
    static $newSynonyms = [];

    static $synonymsCount = 0;

    public function __construct() {
        ini_set('memory_limit', '4300M');
        $cntr = 0;
        foreach (Challenge::$CHARS_MAP as $char => $number) {
            $cntr++;
            Challenge::$CHARS_MAP[$char]         += $cntr;
            Challenge::$charsWeightMap[$cntr]    = 0;
            Challenge::$charCounters[$cntr]      = 0;
            Challenge::$emptyCharCounters[$cntr] = 0;
        }
    }

    static function initWords($filePath) {
        $linesFromFile = [];

        $fileContent = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $fileLength  = count($fileContent);
        $l           = 0;
        do {
            $line = $fileContent[$l];

            $lineLength = strlen($line);
            if (5 !== $lineLength) {
                continue;
            }
            $uniqueCharsLine = count_chars($line, 3);
            if ($lineLength !== strlen($uniqueCharsLine)) {
                continue;
            }

            if (!isset(Challenge::$synonyms[$uniqueCharsLine])) {
                Challenge::$synonyms[$uniqueCharsLine] = [$line];
                Challenge::$synonymsCount++;

                $uniqueAliasCharsForSearch = [];

                $uniqueAliasChars = str_split($uniqueCharsLine);
                foreach ($uniqueAliasChars as $char) {
                    $charIdx = Challenge::$CHARS_MAP[$char];
                    Challenge::$charCounters[$charIdx]++;
                    $uniqueAliasCharsForSearch[$charIdx] = true;
                }

                $linesFromFile[] = [$uniqueCharsLine, $uniqueAliasCharsForSearch, 0];
            }
            else {
                Challenge::$synonyms[$uniqueCharsLine][] = $line;
            }

        }
        while (++$l < $fileLength);

        $countLines = Challenge::$synonymsCount;
        foreach (Challenge::$charCounters as $charIdx => $weight) {
            Challenge::$charsWeightMap[$charIdx] = (int)(($weight / $countLines) * 10000);
        }

        $i           = 0;
        $charWeights = Challenge::$charsWeightMap;
        do {
            $line  = $linesFromFile[$i];
            $chars = $line[1];
            foreach ($chars as $idx => $char) {
                $chars[$idx] = $charWeights[$idx];
            }
            asort($chars);
            $linesFromFile[$i][2] = reset($chars);
            $linesFromFile[$i][1] = $chars;

            $newIdx                          = implode('.', array_keys($chars));
            Challenge::$newSynonyms[$newIdx] = Challenge::$synonyms[$line[0]];
        }
        while (++$i < $countLines);

        Challenge::$synonyms = [];

        usort($linesFromFile, function ($a, $b) {
            $weightA = $a[2];
            $weightB = $b[2];

            if ($weightA == $weightB) {
                return 0;
            }

            return ($weightA < $weightB) ? -1 : 1;
        });

        $id = 0;
        do {
            [, $chars] = $linesFromFile[$id];
            $xx = [];
            foreach ($chars as $char => $isset) {
                $xx[$char] = true;
            }
            $cleanArray[] = $xx;
        }
        while (++$id < $countLines);

        Challenge::$words = $cleanArray;
    }

    public function main($filePath = 'words_alpha.txt') {
        [$ms, $s] = explode(' ', microtime());
        $startMicroTime = $s * 1000 + round($ms * 1000);

        $this->initWords($filePath);

        $result = fillV2(Challenge::$words, Challenge::$synonymsCount, 0, Challenge::$charCounters);

        [$ms, $s] = explode(' ', microtime());
        $endMicroTime = $s * 1000 + round($ms * 1000);

        $synonyms = &Challenge::$newSynonyms;
        $combCnt  = 0;
        foreach ($result as $word1 => $words2) {
            foreach ($words2 as $word2 => $words3) {
                foreach ($words3 as $word3 => $words4) {
                    foreach ($words4 as $word4 => $words5) {
                        foreach ($words5 as $word5) {
                            $this->printAllResults([
                                $synonyms[$word1],
                                $synonyms[$word2],
                                $synonyms[$word3],
                                $synonyms[$word4],
                                $synonyms[$word5],
                            ], $combCnt);
                        }
                    }
                }
            }
        }

        Challenge::out('=======');

//        Challenge::out('[printing results]: ' . ((microtime(true) - $startPrint) * 1000) . ' ms');


        Challenge::out('=======');
//        Challenge::out('[full process]: ' . ((microtime(true) - $startTime) * 1000) . ' ms');
        Challenge::out('[full process]: ' . ($endMicroTime - $startMicroTime) . ' ms');

        Challenge::out('[Combinations]: ' . $combCnt);
//        Challenge::out('DebugCntr  : ' . Challenge::$debugCntr);
    }

    public static function out($msg) {
        echo $msg . PHP_EOL;
    }

    private function printAllResults($arr, &$idx) {
        foreach ($arr[0] as $w1) {
            foreach ($arr[1] as $w2) {
                foreach ($arr[2] as $w3) {
                    foreach ($arr[3] as $w4) {
                        foreach ($arr[4] as $w5) {
                            $idx++;
                            echo $idx . '| ' . $w1 . ' ' . $w2 . ' ' . $w3 . ' ' . $w4 . ' ' . $w5 . PHP_EOL;
                        }
                    }
                }
            }
        }
    }
}


function fillV2(array &$words, int $wordsLength, int $depth, array $charCounters): array {
    $depth++;

    $result             = [];
    $finishedCharsCount = 0;
    $emptyCharCounters  = &Challenge::$emptyCharCounters;

    $neededCountForLayer            = 5 - $depth;
    $neededLengthOfVectorLayerCheck = $neededCountForLayer * 5;

    for ($i = 0; $i < $wordsLength; $i++) {
        if (1 < $finishedCharsCount) {
            return $result;
        }
        $word = $words[$i];
        unset($words[$i]);

        $keys = [];
        foreach ($word as $key => $isset) {
            if (1 > --$charCounters[$key]) {
                ++$finishedCharsCount;
            }
            $keys[] = $key;
        }

        [$l1, $l2, $l3, $l4, $l5] = $keys;

        $firstLayerWordIdx = \implode('.', $keys);

        $secondLayerWords      = [];
        $nextLayerCharCounters = $emptyCharCounters;

        $nextLayerUniqueCharsCount = 0;

        foreach ($words as $secondLayerWord) {
            if (
                false === (isset($secondLayerWord[$l5])
                    || isset($secondLayerWord[$l4])
                    || isset($secondLayerWord[$l3])
                    || isset($secondLayerWord[$l2])
                    || isset($secondLayerWord[$l1]))
            ) {

                $secondLayerWords[] = $secondLayerWord;

                foreach ($secondLayerWord as $charIdx => $is) {
                    if (1 > $nextLayerCharCounters[$charIdx]++) {
                        ++$nextLayerUniqueCharsCount;
                    }
                }
            }
        }

        if ($nextLayerUniqueCharsCount < $neededLengthOfVectorLayerCheck) {
            continue;
        }

        $nextLayerCount = \count($secondLayerWords);
        if ($neededCountForLayer > $nextLayerCount) {
            continue;
        }

        if ($depth < 4) {
            $result[$firstLayerWordIdx] = fillV2($secondLayerWords, $nextLayerCount, $depth, $nextLayerCharCounters);

            continue;
        }

        $finalLayerRes = [];
        foreach ($secondLayerWords as $secondLayerWord) {
            $finalLayerRes[] = \implode('.', \array_keys($secondLayerWord));
        }
        $result[$firstLayerWordIdx] = $finalLayerRes;
    }

    return $result;
}


(new Challenge())->main('./resources/words_alpha.txt');