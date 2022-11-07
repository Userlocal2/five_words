<?php
/**
 * Andrey
 */

ini_set('memory_limit', '7000M');

const EXP_DIR = './tmp';
const LOG = EXP_DIR . '/log.log';
const RESULT_FILE = EXP_DIR . '/result.txt';
const LENGTH_WORDS = 5;
const LOG_COUNT = 10000000;
const DICT = './resources/words_alpha.txt';
//const DICT = './test.txt';

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
    static public $readPointersMult = 0;

    static public $L1CutLongTail = 0;

    static public $L2uniqueValidIterationMain = 0;
    static public $L2uniqueValidIterationSub = 0;
    static public $L2uniqueValidIterationSub2 = 0;
    static public $L2uniqueValidDetails = [
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
    ];

    static public $L3recalcMaxPossiblePositionsMain = 0;
    static public $L3recalcMaxPossiblePositionsSub = 0;
    static public $L3recalcMaxPossiblePositionsSub2 = 0;

    static public $L4recalcFirstPossiblePointersMain = 0;
    static public $L4recalcFirstPossiblePointersSub = 0;

}

# Level1 Optimisation - отрезать "дальний" хвост. Определить максимальное значение Pointer 5 при котором расчет еще имеет смысл.
#           Производится на стадии инициализации
#
# Level2 Optimisation - Проверка на необходимость проверять следующее слово. Отсечка по уникальности на ранней стадии.
#           Если оптимизацию производить начиная с уровня 1 - этот алгоритм, похоже это и стало основным алгоритмом поиска.
#           Хотя метод задумывался, как оптимизация.
#
# Level 3 Optimisation - Пересчет Max Possible Idx для каждого уровня Pointers. В какой-то момент неэффективно продолжать поиск.
#
# Level 4 Optimisation - Ищем первое возможное значение Pointers.
#           Ищем последнее значение, когда "произведение" больше самой "большой" строки $this->$maxMult
class main
{
    public $resultStings = [];
    public $resArray = [];
    private $uniqueCharsWordsMult = [];
    private $detailSimpleMult = [];

    private $uniqueCharsWordsSyn = [];
    private $frequencyChars = [];
    private $arrSimpleMult = [];
    private $arrMultFullString = [];

    private $maxMult;
    private $minMult;

    private $lastIdx = 0;

    private $minLevelOptL2 = 1;

    private $minLevelOptL3 = 3;  // Уровень изменения указателя при котором пересчитываются $lastPossiblePointerIdx
    private $L3Jumping = true;
    private $L3JumpingStopDiff = 5;

    private $L4Jumping = true;
    private $L4JumpingStopDiff = 20;


    private $pointers = [
        1 => 4,                 // Указатель для 1 слова
        2 => 3,                 // Указатель для 2 слова
        3 => 2,                 // Указатель для 3 слова
        4 => 1,                 // Указатель для 4 слова
        5 => 0                  // Указатель для 5 слова
    ];

    private $needRecalMaxPos = [
        1 => false,
        2 => false,
        3 => false,
        4 => false,
        5 => false,
    ];

    private $lastPossiblePointerIdx = [
        1 => 4,                 // Указатель для 1 слова
        2 => 3,                 // Указатель для 2 слова
        3 => 2,                 // Указатель для 3 слова
        4 => 1,                 // Указатель для 4 слова
        5 => 0                  // Указатель для 5 слова
    ];

    private $digUnderPointer = [];


    public function __construct()
    {
        $this->initPointers();
        $this->initData();
        $this->prepCombArray();
        $this->L3recalcMaxPossiblePositions(true);

        stat::$L1CutLongTail += CaHelpers::calcComb($this->lastIdx - $this->lastPossiblePointerIdx[5]);
        CaHelpers::toLog('Search last possible position.. finished. ');

        $economy = round(stat::$L1CutLongTail / stat::$bruteForceCounter * 100, 2);
        CaHelpers::toLog("\tEconomy: " . $economy . '%');
    }

    public function process()
    {
        // For debug
//        $this->test1();
//        die;

        stat::$startProcessing = stat::$startProcessingLastBulk = time();
        CaHelpers::toLog('Processing.. Started.');

        do {
            $q = $this->checkAndFindPositionUniqueChar(1);
            $curMult = $this->readPointersMult();

//            if ($curMult > $this->maxMult) {
//                $this->L4recalcFirstPossiblePointers();
//            }

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
            if (false === $q) {
                break;
            }
        } while ($this->nextPosition());


        CaHelpers::toLog('Processing.. finished. ' . (time() - stat::$startProcessing) . ' sec.');
    }

    private function initData()
    {
        $startTime = time();
        if ($handle = fopen(DICT, "r")) {
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
//        if(20 > rand(1,100)){
//            continue;
//            $temp[] = $line;
//        }

                if (!isset($this->uniqueCharsWordsSyn[implode($arrLine)])) {
                    foreach ($arrLine as $char) {
                        $this->uniqueCharsWordsMult[$simpleMult]['listSimpleDigit'][CaHelpers::SIMPLE_DIG_ARR[$char]] = true;
                        $this->detailSimpleMult[$simpleMult][CaHelpers::SIMPLE_DIG_ARR[$char]] = true;
                    }

                    $this->uniqueCharsWordsMult[$simpleMult]['chars'] = implode($arrLine);

                    $this->frequencyChars[$arrLine[0]][implode($arrLine)][] = $line;
                    $this->frequencyChars[$arrLine[1]][implode($arrLine)][] = $line;
                    $this->frequencyChars[$arrLine[2]][implode($arrLine)][] = $line;
                    $this->frequencyChars[$arrLine[3]][implode($arrLine)][] = $line;
                    $this->frequencyChars[$arrLine[4]][implode($arrLine)][] = $line;
                }
                $this->uniqueCharsWordsSyn[implode($arrLine)][] = $line;
            }
            fclose($handle);

        }

        krsort($this->uniqueCharsWordsMult);
        krsort($this->detailSimpleMult);
        $this->arrSimpleMult = array_keys($this->uniqueCharsWordsMult);
        $this->lastIdx = count($this->arrSimpleMult) - 1;

        for ($met = 5; $met >= 1; $met--) {
            $this->lastPossiblePointerIdx[$met] = $this->lastIdx;
        }

        stat::$bruteForceCounter = CaHelpers::calcComb($this->lastIdx);

        foreach ($this->frequencyChars as $idx => $value) {
            $this->frequencyChars[$idx] = count($value);
        }

        CaHelpers::toLog('Read and pre-Init data.. finished. ' . (time() - $startTime) . ' sec.');
        CaHelpers::toLog('     Processed: ' . stat::$processedLineFromFile . ' line');
        CaHelpers::toLog('     After Clearing: ' . ($this->lastIdx + 1) . ' words');

//        file_put_contents('./test.txt', implode("\n", $temp));
//        die();
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

    private function L3recalcMaxPossiblePositions(bool $firstInit = false)
    {
        // Ищем последнее значение pointer5, когда "произведение" больше
        // самой "меньшей" строки $his->$maxMult

        stat::$L3recalcMaxPossiblePositionsMain++;

//        $currentPointerArray = $firstInit ? [] : $this->pointers;
        $currentPointerArray = $this->pointers;
//        $firstPointer = $firstInit ? 5 : 4;

        // TODO: можно написать это эффективнее. Пока просто для проверки теории

        $firstPointer = 5;
        if (!$firstInit) {
            for ($met = 5; $met >= 2; $met--) {
                if (true == $this->needRecalMaxPos[$met]) {
                    $firstPointer = $met - 1;
                    $this->needRecalMaxPos = [
                        1 => false,
                        2 => false,
                        3 => false,
                        4 => false,
                        5 => false,
                    ];
                    break;
                }
            }
        }

        for ($point = $firstPointer; $point >= 1; $point--) {
            $this->initPointers($currentPointerArray);
//            $this->calcNewLastPosition($point);

            stat::$L3recalcMaxPossiblePositionsSub++;
            $idx = $lastValidIdx = $this->pointers[$point];

            $jumping = $this->L3Jumping;
            $lBorder = $idx;
            $rBorder = $this->lastIdx;

            while (true) {
                stat::$L3recalcMaxPossiblePositionsSub2++;
                if ($jumping && $this->L3JumpingStopDiff > abs($rBorder - $lBorder)) {
                    $jumping = false;
                    $idx = $lastValidIdx;
                }

                $this->pointers[$point] = $idx;

                for ($met = $point - 1; $met >= 1; $met--) {
                    $this->pointers[$met] = $this->pointers[$met + 1] + 1;
                }

                $res = $this->arrSimpleMult[$this->pointers[1]]
                    * $this->arrSimpleMult[$this->pointers[2]]
                    * $this->arrSimpleMult[$this->pointers[3]]
                    * $this->arrSimpleMult[$this->pointers[4]]
                    * $this->arrSimpleMult[$this->pointers[5]];

                if ($res > $this->minMult) {
                    $lastValidIdx = $lBorder = $idx;
                    $idx = $jumping ? $idx + (int)(($rBorder - $idx) / 2) : $idx + 1;
                    continue;
                } elseif ($jumping) {
                    $rBorder = $idx;
                    $idx = $idx - (int)(($idx - $lBorder) / 2);
                    continue;
                }

                $this->lastPossiblePointerIdx[$point] = --$idx;
                break;
            };
        }
        $this->initPointers($currentPointerArray);

        $res = $this->arrSimpleMult[$this->pointers[1]]
            * $this->arrSimpleMult[$this->pointers[2]]
            * $this->arrSimpleMult[$this->pointers[3]]
            * $this->arrSimpleMult[$this->pointers[4]]
            * $this->arrSimpleMult[$this->pointers[5]];

//        if ($res > $this->maxMult) {
//            $this->L4recalcFirstPossiblePointers();
//        }
    }

    private function L4recalcFirstPossiblePointers()
    {
        // В данной конкретной задаче возможно оптимизировать ТОЛЬКО 1-й уровень.
        //   Произведение букв из 4-х слов НИКОГДА не будет больше возможного максимума.
        //   Похоже, что это бесполезная херня..
        stat::$L4recalcFirstPossiblePointersMain++;
        $lastPointerGroup = 1;

        $idx = $lastValidIdx = $this->pointers[$lastPointerGroup];

        $jumping = $this->L4Jumping;
        $lBorder = $idx;
        $rBorder = $this->lastPossiblePointerIdx[1];

        while (true) {
            stat::$L4recalcFirstPossiblePointersSub++;
            if ($jumping && $this->L4JumpingStopDiff > abs($rBorder - $lBorder)) {
                $jumping = false;
                $idx = $lastValidIdx;
            }

            $this->changePointer($lastPointerGroup, $idx);
            $res = $this->readPointersMult();

            if ($res > $this->maxMult) {
                $lastValidIdx = $lBorder = $idx;
                $idx = $jumping ? $idx + (int)(($rBorder - $idx) / 2) : $idx + 1;
                continue;
            } elseif ($jumping) {
                $rBorder = $idx;
                $idx = $idx - (int)(($idx - $lBorder) / 2);
                continue;
            }

            $this->changePointer($lastPointerGroup, --$idx);
            break;
        };
    }

    private function checkAndFindPositionUniqueChar($pointerIdx)
    {
        stat::$L2uniqueValidIterationMain++;

        $pointers = &$this->pointers;
        $changedLevel = &$this->needRecalMaxPos;
        $arrSimpleMult = &$this->arrSimpleMult;
        $detailSimpleMult = &$this->detailSimpleMult;
        $lastPossiblePointerIdx = &$this->lastPossiblePointerIdx;

        $checkArray = [];

//        $this->L3recalcMaxPossiblePositions();
        for ($pointLevel = 5; $pointLevel >= $pointerIdx; $pointLevel--) {
            $checkArray[$pointLevel] = 5 === $pointLevel ? [] : $checkArray[$pointLevel + 1];
            stat::$L2uniqueValidIterationSub++;

            // for debug
            if (0 == stat::$L2uniqueValidIterationSub % LOG_COUNT) {
                $this->statusToLog();
            }

            do {
                $testArray = $checkArray[$pointLevel];

                stat::$L2uniqueValidIterationSub2++;
//                $countEconomy = 1;
                $simpleMult = $arrSimpleMult[$pointers[$pointLevel]];

//                $curWordArray = $detailSimpleMult[$simpleMult];
//                $testArray += $curWordArray;
                $testArray += $detailSimpleMult[$simpleMult];;

                // First 5 = LENGTH_WORDS
                // Second 5 - level of pointer
                //  Замена константы на значение дало прирост ~0.5 - 1с на 100 млн. итерациях
                if (5 * (5 - $pointLevel + 1) !== count($testArray)) {
                    while ($pointers[$pointLevel] >= $lastPossiblePointerIdx[$pointLevel]) {
                        if (5 === $pointLevel++) {
                            return false;
                        }

                        // ЕПТЕЛЬ!!!! Вот это было оно!!! с 26 до 8 сек.
                        $checkArray[$pointLevel] = 5 === $pointLevel ? [] : $checkArray[$pointLevel + 1];

                        if ($pointLevel >= $this->minLevelOptL3) {
                            $pointers[$pointLevel]++;
                            $changedLevel[$pointLevel] = true;


                            for ($metA = $pointLevel - 1; $metA >= 1; $metA--) {
                                $pointers[$metA] = $pointers[$metA + 1] + 1;
                                $changedLevel[$metA] = true;

                            }
                            // TODO: тут неточность. Есть вероятность "пропустить" некоторое количество вариантов.
                            $this->L3recalcMaxPossiblePositions();

                            $pointers[$pointLevel]--;
                            $changedLevel[$pointLevel] = true;
                        }
//                        $countEconomy = 0;
                    }

                    $pointers[$pointLevel]++;
                    $changedLevel[$pointLevel] = true;

//                    stat::$L2uniqueValidDetails[$met] += $countEconomy;
                    for ($metA = $pointLevel - 1; $metA >= 1; $metA--) {
//                        $this->pointers[$metA] = $this->pointers[$metA + 1] + 1;
//                        $this->changePointer($metA, $this->pointers[$metA + 1] + 1);
                        $pointers[$metA] = $pointers[$metA + 1] + 1;
                        $changedLevel[$metA] = true;
                    }
//                    $pointLevel = 5;
//                    $checkArray = $testArray = [];
                    continue;
                }
                break;

            } while (true);
            $checkArray[$pointLevel] = $testArray;
        }

        return true;
    }

    private function nextPosition()
    {
//        $this->pointers[1]++;
        $this->changePointer(1);
        if ($this->lastPossiblePointerIdx[1] >= $this->pointers[1]) {
            if (1 >= $this->minLevelOptL2) {
//                return $this->checkAndFindPositionUniqueChar(1);
            }
            return true;
        }

        $lastPointerGroup = 2;

        do {
            if ($this->pointers[$lastPointerGroup] >= $this->lastPossiblePointerIdx[$lastPointerGroup]) {
                $lastPointerGroup++;
                if (6 === $lastPointerGroup) {
                    return false;
                }
                if (5 === $lastPointerGroup && $this->pointers[$lastPointerGroup] >= $this->lastPossiblePointerIdx[$lastPointerGroup]) {
                    return false;
                }
                continue;
            }

//            $this->pointers[$lastPointerGroup]++;
            $this->changePointer($lastPointerGroup);
            if ($lastPointerGroup >= $this->minLevelOptL2) {
//                $this->checkAndFindPositionUniqueChar($lastPointerGroup);
            }

            for ($met = $lastPointerGroup - 1; $met >= 1; $met--) {
//                $this->pointers[$met] = $this->pointers[$met + 1] + 1;
                $this->changePointer($met, $this->pointers[$met + 1] + 1);
                if ($met >= $this->minLevelOptL2) {
//                    $this->checkAndFindPositionUniqueChar($met);
                }
            }
            return true;
        } while (true);
    }

    private function changePointer(int $level, int $value = null)
    {
        $this->pointers[$level] = is_null($value) ? $this->pointers[$level] + 1 : $value;
        $this->needRecalMaxPos[$level] = true;
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
//            1 => 15,                 // Указатель для 1 слова
//            2 => 14,                 // Указатель для 2 слова
//            3 => 13,                 // Указатель для 3 слова
//            4 => 12,                 // Указатель для 4 слова
//            5 => 10                  // Указатель для 5 слова
//        ];
    }

    private function readPointersMult()
    {
        stat::$readPointersMult++;

        $mult = $this->arrSimpleMult[$this->pointers[1]]
            * $this->arrSimpleMult[$this->pointers[2]]
            * $this->arrSimpleMult[$this->pointers[3]]
            * $this->arrSimpleMult[$this->pointers[4]]
            * $this->arrSimpleMult[$this->pointers[5]];

        if (0 == stat::$readPointersMult % LOG_COUNT) {
            $this->statusToLog();
        }

        return $mult;
    }

    public function statusToLog()
    {
        CaHelpers::toLog('Processed Time: ' . (time() - stat::$startTime) . ' sec.');
        CaHelpers::toLog("\tFound " . count($this->resultStings) . ' strings');
        CaHelpers::toLog("\tTotal Multiplications: " . number_format(stat::$readPointersMult, 0, '.', ' '));
        CaHelpers::toLog("\tLevel (1) Optimisation: " . number_format(stat::$L1CutLongTail, 0, '.', ' ')
            . ' (' . round(stat::$L1CutLongTail / stat::$bruteForceCounter * 100, 2) . '%)');

        CaHelpers::toLog("\t\tLevel (2) Main: "
            . number_format(stat::$L2uniqueValidIterationMain, 0, '.', ' '));
        CaHelpers::toLog("\t\tLevel (2) Sub: "
            . number_format(stat::$L2uniqueValidIterationSub, 0, '.', ' '));
        CaHelpers::toLog("\t\tLevel (2) Sub2: "
            . number_format(stat::$L2uniqueValidIterationSub2, 0, '.', ' '));

//        CaHelpers::toLog("\t\t\tDetails Validation unique (level 2): ");
//        for ($log = 5; $log >= 1; $log--) {
//            CaHelpers::toLog("\t\t\t\tPointer " . $log . ': '
//                . number_format(stat::$L2uniqueValidDetails[$log], 0, '.', ' '));
//        }

        CaHelpers::toLog("\t\t\tLevel (3) Main Recalc: "
            . number_format(stat::$L3recalcMaxPossiblePositionsMain, 0, '.', ' '));
        CaHelpers::toLog("\t\t\tLevel (3) Sub Recalc: "
            . number_format(stat::$L3recalcMaxPossiblePositionsSub, 0, '.', ' '));
        CaHelpers::toLog("\t\t\tLevel (3) Sub2 Recalc (change Pointer): "
            . number_format(stat::$L3recalcMaxPossiblePositionsSub2, 0, '.', ' '));

        CaHelpers::toLog("\t\t\t\tLevel (4) Main: "
            . number_format(stat::$L4recalcFirstPossiblePointersMain, 0, '.', ' '));
        CaHelpers::toLog("\t\t\t\tLevel (4) Sub: "
            . number_format(stat::$L4recalcFirstPossiblePointersSub, 0, '.', ' '));

        CaHelpers::toLog("\tDebug (pointers)");
        for ($log = 5; $log >= 1; $log--) {
            CaHelpers::toLog("\t\tPointer " . $log . ': ' . $this->pointers[$log]
                . "\t(" . $this->lastPossiblePointerIdx[$log] . "\tMax Possible)");
        }

    }

    private function forProfiling($startLevel)
    {
    }

    public function test1()
    {
        $maxFor = 500000000;
        $s = time();
        for ($met = $maxFor; $met >= 1; $met--) {
            $a = $this->uniqueCharsWordsMult[42586297]['listSimpleDigit'];
        }
        CaHelpers::toLog('empty for: ' . (time() - $s));

        $s = time();
        for ($met = $maxFor; $met >= 1; $met--) {
            $a = $this->detailSimpleMult[42586297];
        }
        CaHelpers::toLog('init var: ' . (time() - $s));
        return;

        $s = time();
        for ($met = $maxFor; $met >= 1; $met--) {
            $a = $this->uniqueCharsWordsMult[51237167];
        }
        CaHelpers::toLog('from prop: ' . (time() - $s));

        $c = $this->uniqueCharsWordsMult;
        $s = time();
        for ($met = $maxFor; $met >= 1; $met--) {
            $a = $c[51237167];
        }
        CaHelpers::toLog('from var: ' . (time() - $s));

        $b = &$this->uniqueCharsWordsMult;
        $s = time();
        for ($met = $maxFor; $met >= 1; $met--) {
            $a = $b[51237167];
        }
        CaHelpers::toLog('from link: ' . (time() - $s));


        CaHelpers::toLog(PHP_EOL);
        return false;
    }


}

stat::$startTime = time();
CaHelpers::toLog('Start -------------------------');
$obj = new main();
$obj->process();
CaHelpers::toLog("");
CaHelpers::toLog('Finished.. ------------------------');
CaHelpers::toLog("\tFound Result(s): " . count($obj->resultStings));
CaHelpers::toLog("\tTotal Time: " . (time() - stat::$startProcessing) . ' sec.');
$obj->statusToLog();

CaHelpers::toLog("\tMax Used Memory: " . round(memory_get_peak_usage() / 1024 / 1024) . 'Mb');
CaHelpers::toLog(PHP_EOL);

file_put_contents(RESULT_FILE, implode(PHP_EOL, $obj->resultStings));


die;



//$q[] = 'abcde';
//$q[] = 'fghij';
//$q[] = 'klmno';
//$q[] = 'prstq';
//$q[] = 'uvwxy';


