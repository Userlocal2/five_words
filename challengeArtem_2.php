<?php
ini_set('memory_limit', '7000M');

const EXP_DIR = './tmp';
const LOG = EXP_DIR . '/log.log';
const RESULT_FILE = EXP_DIR . '/result.txt';
const LENGTH_WORDS = 5;
const LOG_COUNT = 10000000;

class CaHelpers
{
//    const SIMPLE_DIG_ARR = ["a" => 2, "b" => 3, "c" => 5, "d" => 7, "e" => 11, "f" => 13, "g" => 17, "h" => 19, "i" => 23, "j" => 29, "k" => 31, "l" => 37, "m" => 41, "n" => 43, "o" => 47, "p" => 53, "q" => 59, "r" => 61, "s" => 67, "t" => 71, "u" => 73, "v" => 79, "w" => 83, "x" => 89, "y" => 97, "z" => 101];

/// Наиболее редко встречаемые встречаемые символы будут иметь меньшее значение. "экоронмия" около 0%
//    const SIMPLE_DIG_ARR =  ['q'=>2,  'x'=>3,  'j'=>5,  'z'=>7,  'v'=>11,  'f'=>13,  'w'=>17,  'b'=>19,  'k'=>23,  'g'=>29,  'p'=>31,  'm'=>37,  'h'=>41,  'd'=>43,  'c'=>47,  'y'=>53,  't'=>59,  'l'=>61,  'n'=>67,  'u'=>71,  'r'=>73,  'o'=>79,  'i'=>83,  's'=>89,  'e'=>97,  'a'=>101];

    // Наиболее часто встречаемые символы будут иметь меньшее значение. "экоронмия" около 80%
    const SIMPLE_DIG_ARR = ['a' => 2, 'e' => 3, 'i' => 5, 's' => 7, 'o' => 11, 'r' => 13, 'u' => 17, 'n' => 19, 'l' => 23, 't' => 29, 'y' => 31, 'c' => 37, 'd' => 41, 'h' => 43, 'm' => 47, 'p' => 53, 'g' => 59, 'k' => 61, 'b' => 67, 'w' => 71, 'f' => 73, 'v' => 79, 'z' => 83, 'j' => 89, 'x' => 97, 'q' => 101];

    static private function pData(int $timestamp = null): string
    {
        return date("Y-m-d H:i:s", null === $timestamp ? time() : $timestamp);
    }

    static public function toLog(string $text): bool
    {
        $text = PHP_EOL . CaHelpers::pData() . ' ' . $text;
        file_put_contents(LOG, $text, FILE_APPEND);
        echo $text;

        return true;
    }

    static public function calcComb($dim, $exp = 5): int
    {
        // Приблизительные коэффициент отношение "полного объема" к "количеству сочетаний".
        switch ($exp) {
            case 5:
                $factor = 120;
                break;
            case 4:
                $factor = 24;
                break;
            case 3:
                $factor = 6;
                break;
            case 2:
                $factor = 2;
                break;
            case 1:
                $factor = 1;
                break;
            default:
                return 0;

        }

        return (int)(pow($dim, $exp) / $factor);


    }

    static public function getResultStrings(array $arr)
    {
        $resArray = $arr[0];

        for ($met = 1; $met <= 4; $met++) {
            $tmpResArray = [];
            foreach ($resArray as $resIdx => $resValue) {
                foreach ($arr[$met] as $idx => $value) {
                    $tmpResArray[] = $resValue . ' ' . $value;
                }
            }
            $resArray = $tmpResArray;
        }


        return $resArray;
    }
}

class stat
{
    static public $startTime;
    static public $startProcessing;
    static public $startProcessingLastBulk;

    static public $processedLineFromFile = 0;

    static public $bruteForceCounter = 0;
    static public $algoEconomy = 0;

    static public $uniqueValidationIteration = 0;

    static public $jumpingEconomy = [
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
    ];

    static public $uniqueValidationEconomy = [
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
    ];

    static public $readPointersMult = 0;
    static public $countRecalcPossiblePosition = 0;

}

class main
{
    public $resultStings = [];
    public $resArray = [];
    private $uniqueCharsWordsMult = [];
    private $uniqueCharsWordsSyn = [];
    private $frequencyChars = [];
    private $arrSimpleMult = [];
    private $arrMultFullString = [];

    private $maxMult;
    private $minMult;

    private $lastIdx = 0;

    private $lastValidIdxPointer5;

    private $minPointerIdxLevel2Opt = 1;
    private $minPointerIdxLevel3Opt = 3;  // Уровень изменения указателя при котором пересчитываются $lastPossiblePointerIdx
    private $pointers = [
        1 => 4,                 // Указатель для 1 слова
        2 => 3,                 // Указатель для 2 слова
        3 => 2,                 // Указатель для 3 слова
        4 => 1,                 // Указатель для 4 слова
        5 => 0                  // Указатель для 5 слова
    ];

    private $lastPossiblePointerIdx = [
        1 => 4,                 // Указатель для 1 слова
        2 => 3,                 // Указатель для 2 слова
        3 => 2,                 // Указатель для 3 слова
        4 => 1,                 // Указатель для 4 слова
        5 => 0                  // Указатель для 5 слова
    ];

    private $digUnderPointer = [];

//    'activePointer' => 1,   // Активный указатель
//    'prevActiveIdx' => 4,   // Предыдущей index активного указателя


    public function __construct()
    {
        $this->initPointers();
        $this->initData();
        $this->prepCombArray();
        $this->initLastPosPointers();
        stat::$algoEconomy += CaHelpers::calcComb($this->lastIdx - $this->lastValidIdxPointer5);

        CaHelpers::toLog('Search last possible position.. finished. ');
        $economy = round(stat::$algoEconomy / stat::$bruteForceCounter * 100, 2);
        CaHelpers::toLog('     Economy: ' . $economy . '%');
    }

    public function process()
    {
        stat::$startProcessing = stat::$startProcessingLastBulk = time();
        CaHelpers::toLog('Processing.. Started.');

        do {

            $curMult = $this->readPointersMult();

            if (1 < ($debug = $curMult / $this->maxMult)) {
                $point = $this->pointers;
                $this->jumpToFirstValidMult();
            }

            foreach ($this->arrMultFullString as $idx => $value) {
                if ((string)$curMult === (string)$value) {
                    $arrWords = [];
                    for ($met = 5; $met >= 1; $met--) {
                        $mult = $this->arrSimpleMult[$this->pointers[$met]];
                        $string = $this->uniqueCharsWordsMult[$mult]['chars'];
                        $arrWords[] = $this->uniqueCharsWordsSyn[$string];
                    }
                    $this->resultStings = array_merge($this->resultStings, CaHelpers::getResultStrings($arrWords));
                }
            }


            if (false == $this->nextPosition()) {
                break;
            }
        } while (true);

        CaHelpers::toLog('Processing.. finished. ' . (time() - stat::$startProcessing) . ' sec.');
    }

    private function initData()
    {
        $startTime = time();
        if ($handle = fopen("./resources/words_alpha.txt", "r")) {
            while (($line = fgets($handle)) !== false) {
                stat::$processedLineFromFile++;
                $line = trim($line);

                if (LENGTH_WORDS !== strlen($line) || LENGTH_WORDS !== strlen(count_chars($line, 3))) {
                    continue;
                }

                $arrLine = str_split($line);
                sort($arrLine);

                $simpleMult = 1
                    * CaHelpers::SIMPLE_DIG_ARR[$arrLine[0]]
                    * CaHelpers::SIMPLE_DIG_ARR[$arrLine[1]]
                    * CaHelpers::SIMPLE_DIG_ARR[$arrLine[2]]
                    * CaHelpers::SIMPLE_DIG_ARR[$arrLine[3]]
                    * CaHelpers::SIMPLE_DIG_ARR[$arrLine[4]];

                # ------ For Debug
//        if(1 < rand(1,100)){
//            continue;
//        }

                if (!isset($this->uniqueCharsWordsSyn[implode($arrLine)])) {
                    foreach ($arrLine as $char) {
                        $this->uniqueCharsWordsMult[$simpleMult]['listSimpleDigit'][CaHelpers::SIMPLE_DIG_ARR[$char]] = true;
                    }

                    $this->uniqueCharsWordsMult[$simpleMult]['chars'] = implode($arrLine);


                    $this->frequencyChars[$arrLine[0]][implode($arrLine)][] = $line;
                    $this->frequencyChars[$arrLine[1]][implode($arrLine)][] = $line;
                    $this->frequencyChars[$arrLine[2]][implode($arrLine)][] = $line;
                    $this->frequencyChars[$arrLine[3]][implode($arrLine)][] = $line;
                    $this->frequencyChars[$arrLine[4]][implode($arrLine)][] = $line;
                }

                $this->uniqueCharsWordsSyn[implode($arrLine)][] = $line;
//                $this->uniqueCharsWordsMult[$simpleMult][implode($arrLine)][] = $line;
//                $this->uniqueCharsWordsMult[$simpleMult]['listWords'][] = $line;


            }
            fclose($handle);
            krsort($this->uniqueCharsWordsMult);
            $this->arrSimpleMult = array_keys($this->uniqueCharsWordsMult);
            $this->lastIdx = count($this->arrSimpleMult) - 1;

            $this->lastValidIdxPointer5 = $this->lastIdx;

        }

//        for ($met = 1; $met <= 5; $met++) {
//            $this->lastPossiblePointerIdx[$met] = $this->lastIdx - ($met - 1);
//        }

        stat::$bruteForceCounter = CaHelpers::calcComb($this->lastIdx);

        foreach ($this->frequencyChars as $idx => $value) {
            $this->frequencyChars[$idx] = count($value);
        }

        CaHelpers::toLog('Read and pre-Init data.. finished. ' . (time() - $startTime) . ' sec.');
        CaHelpers::toLog('     Processed: ' . stat::$processedLineFromFile . ' line');
        CaHelpers::toLog('     After Clearing: ' . ($this->lastIdx + 1) . ' words');
    }

    private function prepCombArray()
    {
        $startTime = time();
        $idxArray = 0;
        foreach (CaHelpers::SIMPLE_DIG_ARR as $idx => $val) {
            $tmpArray = CaHelpers::SIMPLE_DIG_ARR;
            unset($tmpArray[$idx]);
            $result = 1;

            foreach ($tmpArray as $idx2 => $val2) {
                $this->resArray[$idxArray]['multipliers'][$idx2] = $val2;
                $result *= $val2;
            }

            $this->resArray[$idxArray]['multiplication'] = $result;

            $this->maxMult = !isset($this->maxMult) || $this->maxMult < $result
                ? $result
                : $this->maxMult;

            $this->minMult = !isset($this->minMult) || $this->minMult > $result
                ? $result
                : $this->minMult;


            $idxArray++;
        }

        foreach ($this->resArray as $idx => $value) {
            $this->arrMultFullString[$idx] = $value['multiplication'];
        }
        CaHelpers::toLog('Init data.. finished. ' . (time() - $startTime) . ' sec.');
    }

    private function initLastPosPointers(bool $useCurrentPosition = false)
    {
        stat::$countRecalcPossiblePosition++;
        // Ищем последнее значение pointer5, когда "произведение" больше
        // самой "меньшей" строки $his->$maxMult

        $currentPointerArray = $useCurrentPosition ? $this->pointers : [];

        $this->initPointers($currentPointerArray);


        for ($point = 5; $point >= 1; $point--) {
            $idx = $this->pointers[$point];
            do {
                $count = $idx;
                for ($met = $point; $met >= 1; $met--) {
                    $this->pointers[$met] = $count++;
                }

                $res = $this->readPointersMult();
                if (1 < ($debug = $res / $this->minMult)) {
                    $idx++;
                    continue;
                }

//4 = 526
//3 1286
                // 2 3533
// 5973

                $this->lastPossiblePointerIdx[$point] = --$idx;
                $this->initPointers($currentPointerArray);
                break;
            } while (true);
        }
        $this->lastValidIdxPointer5 = $this->lastPossiblePointerIdx[5];
    }


    private function jumpToFirstValidMult()
    {
        // Ищем последнее значение, когда "произведение" больше самой "большой" строки $his->$maxMult
        $lastPointerGroup = 1;

        do {
            if (1 != $lastPointerGroup) {
                for ($met = $lastPointerGroup; $met >= 1; $met--) {
                    $this->pointers[$met] = $this->pointers[$met + 1] + 1;
                }
            }
            $res = $this->readPointersMult();

            if (1 < ($debug = $res / $this->maxMult)) {
                if ($this->pointers[$lastPointerGroup] == $this->lastPossiblePointerIdx[$lastPointerGroup]) {
                    $lastPointerGroup++;
                }
                $this->pointers[$lastPointerGroup]++;
                if ($lastPointerGroup >= $this->minPointerIdxLevel2Opt) {
                    $this->checkAndFindPositionUniqueChar($lastPointerGroup);
                }
            } else {
                $this->pointers[$lastPointerGroup]--;
                if (1 == $lastPointerGroup) {
                    break;
                }
                if ($lastPointerGroup >= $this->minPointerIdxLevel2Opt) {
                    $this->checkAndFindPositionUniqueChar($lastPointerGroup);
                }
                for ($met = $lastPointerGroup; $met >= 1; $met--) {
                    $this->pointers[$met] = $this->pointers[$met + 1] + 1;
                }

                $lastPointerGroup--;
            }
        } while (true);
    }

    private function checkAndFindPositionUniqueChar($pointerIdx)
    {
        $checkArray = $testArray = [];

//        $this->initLastPosPointers(true);
        for ($met = 5; $met >= $pointerIdx; $met--) {
            stat::$uniqueValidationIteration++;
            if (0 == stat::$uniqueValidationIteration % LOG_COUNT) {
                CaHelpers::toLog('Processed Time: ' . (time() - stat::$startProcessing) . ' sec.');
                CaHelpers::toLog('     Found ' . count($this->resultStings) . ' strings');
                CaHelpers::toLog('     Optimisation (2) Iteration: '
                    . number_format(stat::$uniqueValidationIteration, 0, '.', ' ') . ')');
                CaHelpers::toLog('     Optimisation (3) Recalc: '
                    . number_format(stat::$countRecalcPossiblePosition, 0, '.', ' ') . ')');

                $economy = round(stat::$algoEconomy / stat::$bruteForceCounter * 100, 2);
                CaHelpers::toLog('     Economy Total: ' . number_format(stat::$algoEconomy, 0, '.', ' ') . '. ' . $economy . '%');

                CaHelpers::toLog('     Economy Validation unique (level 2): ');
                for ($log = 5; $log >= 1; $log--) {
                    CaHelpers::toLog('          Pointer ' . $log . ': '
                        . number_format(stat::$uniqueValidationEconomy[$log], 0, '.', ' '));
                }


                CaHelpers::toLog('     Debug (pointers)');
                for ($log = 5; $log >= 1; $log--) {
                    CaHelpers::toLog('          Pointer ' . $log . ': ' . $this->pointers[$log]);
                }

                CaHelpers::toLog('     -----');
                for ($log = 5; $log >= 1; $log--) {
                    CaHelpers::toLog('          Last Position Pointer ' . $log . ': ' . $this->lastPossiblePointerIdx[$log]);
                }
            }

            $testArray = $checkArray;

            do {
                $countEconomy = 1;
                $simpleMult = $this->arrSimpleMult[$this->pointers[$met]];
                $this->digUnderPointer[$met] = $this->uniqueCharsWordsMult[$simpleMult]['listSimpleDigit'];

                //For Debug
//                if (!is_int($met)) {
//                    echo 'aaa';
//                }

                if (!isset($this->digUnderPointer[$met])) {
                    echo 'bbb';
                }

                $testArray += $this->digUnderPointer[$met];

                if (LENGTH_WORDS * (5 - $met + 1) !== count($testArray)) {
                    while ($this->pointers[$met] >= $this->lastPossiblePointerIdx[$met]) {
                        $met++;
                        if (6 === $met) {
                            return false;
                        }
                        if ($met >= $this->minPointerIdxLevel3Opt) {
                            $this->pointers[$met]++;
                            for ($metA = $met - 1; $metA >= 1; $metA--) {
                                $this->pointers[$metA] = $this->pointers[$metA + 1] + 1;
                            }
                            // TODO: тут неточность. Есть вероятность "пропустить" некоторое количество вариантов.
                            $this->initLastPosPointers(true);
                            $this->pointers[$met]--;
                        }
                        $countEconomy = 0;
                    }

                    $this->pointers[$met]++;
                    stat::$uniqueValidationEconomy[$met] += $countEconomy;
                    for ($metA = $met - 1; $metA >= 1; $metA--) {
                        $this->pointers[$metA] = $this->pointers[$metA + 1] + 1;
                    }
                    $met = 5;
                    $checkArray = [];
                    $testArray = $checkArray;
                    continue;
                }
                break;

            } while (true);
            $checkArray += $this->digUnderPointer[$met];
        }

        return true;
    }

    private function nextPosition()
    {
        $this->pointers[1]++;
        if ($this->lastPossiblePointerIdx[1] >= $this->pointers[1]) {
            if (1 >= $this->minPointerIdxLevel2Opt) {
                return $this->checkAndFindPositionUniqueChar(1);
            }
            return true;
        }

        $lastPointerGroup = 2;

        do {
            if ($this->pointers[$lastPointerGroup] == $this->lastPossiblePointerIdx[$lastPointerGroup]) {
                $lastPointerGroup++;
                if (6 === $lastPointerGroup) {
                    return false;
                }

                // For Debug
                if (4 == $lastPointerGroup) {
                    echo '';
                }

                if (5 === $lastPointerGroup && $this->lastValidIdxPointer5 < $this->pointers[$lastPointerGroup]) {
                    return false;
                }
                continue;
            }

            $this->pointers[$lastPointerGroup]++;
            if ($lastPointerGroup >= $this->minPointerIdxLevel2Opt) {
                $this->checkAndFindPositionUniqueChar($lastPointerGroup);
            }

            for ($met = $lastPointerGroup - 1; $met >= 1; $met--) {
                $this->pointers[$met] = $this->pointers[$met + 1] + 1;
                if ($met >= $this->minPointerIdxLevel2Opt) {
                    $this->checkAndFindPositionUniqueChar($met);
                }
            }
            return true;
        } while (true);
    }

    private function initPointers(array $pointerArray = [])
    {
        if (empty($pointerArray)) {
            $this->pointers = [
                1 => 4,                 // Указатель для 1 слова
                2 => 3,                 // Указатель для 2 слова
                3 => 2,                 // Указатель для 3 слова
                4 => 1,                 // Указатель для 4 слова
                5 => 0                  // Указатель для 5 слова
            ];
        } else {
            $this->pointers = $pointerArray;
        }


//        for debug
//        $this->pointers = [
//            1 => 5974,                 // Указатель для 1 слова
//            2 => 5973,                 // Указатель для 2 слова
//            3 => 18,                 // Указатель для 3 слова
//            4 => 1,                 // Указатель для 4 слова
//            5 => 0                  // Указатель для 5 слова
//        ];
    }

    private function readPointersMult()
    {
        stat::$readPointersMult++;
        $mult = 1;
        for ($met = 1; $met <= 5; $met++) {
            $mult *= $this->arrSimpleMult[$this->pointers[$met]];
        }

        if (0 == stat::$readPointersMult % LOG_COUNT) {
            $processed = round(stat::$readPointersMult / (stat::$bruteForceCounter - stat::$algoEconomy) * 100, 2);

            $rest = stat::$bruteForceCounter - stat::$algoEconomy - stat::$readPointersMult;
//            $forecast = round($rest / LOG_COUNT * (time() - stat::$startProcessingLastBulk));

            CaHelpers::toLog('Processed ' . number_format(stat::$readPointersMult, 0, '.', ' ') . '. Around ' . $processed . '%');
            CaHelpers::toLog('     Last Bulk: ' . (time() - stat::$startProcessingLastBulk) . ' sec.');
            CaHelpers::toLog('     Total: ' . (time() - stat::$startProcessing) . ' sec.');
//            CaHelpers::toLog('     Rest count: ' . number_format($rest, 0, '.', ' '));
//            CaHelpers::toLog('     Forecast time to finish: ' . number_format($forecast, 0, '.', ' ')
//                . ' sec. ' . date("Y-m-d H:i:s", time() + $forecast));
            stat::$startProcessingLastBulk = time();

            $economy = round(stat::$algoEconomy / stat::$bruteForceCounter * 100, 2);
            CaHelpers::toLog('     Economy Total: ' . number_format(stat::$algoEconomy, 0, '.', ' ') . '. ' . $economy . '%');

            CaHelpers::toLog('     Economy Validation unique (level 2): ' . number_format(stat::$algoEconomy, 0, '.', ' ') . '. ' . $economy . '%');
            for ($met = 5; $met >= 1; $met--) {
                CaHelpers::toLog('          Pointer ' . $met . ': ' . stat::$uniqueValidationEconomy[$met]);
            }


            CaHelpers::toLog('     Debug');
            for ($met = 5; $met >= 1; $met--) {
                CaHelpers::toLog('          Pointer ' . $met . ': ' . $this->pointers[$met]);
            }

        }

        return $mult;
    }
}

stat::$startTime = time();
CaHelpers::toLog('Start -------------------------');
$obj = new main();
$obj->process();

file_put_contents(RESULT_FILE, implode(PHP_EOL, $obj->resultStings));


CaHelpers::toLog('Max Used Memory: ' . round(memory_get_peak_usage() / 1024 / 1024) . 'Mb');
die;



//$q[] = 'abcde';
//$q[] = 'fghij';
//$q[] = 'klmno';
//$q[] = 'prstq';
//$q[] = 'uvwxy';


