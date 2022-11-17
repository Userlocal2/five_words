<?php
//ini_set('memory_limit', '7000M');
//ini_set('error_reporting', E_COMPILE_ERROR);

const EXP_DIR     = './tmp';
const LOG         = EXP_DIR . '/log.log';
const RESULT_FILE = EXP_DIR . '/result.txt';
const DICT        = './resources/words_alpha.txt';


class CaHelpers
{
//    const SIMPLE_DIG_ARR = ["a" => 2, "b" => 3, "c" => 5, "d" => 7, "e" => 11, "f" => 13, "g" => 17, "h" => 19, "i" => 23, "j" => 29, "k" => 31, "l" => 37, "m" => 41, "n" => 43, "o" => 47, "p" => 53, "q" => 59, "r" => 61, "s" => 67, "t" => 71, "u" => 73, "v" => 79, "w" => 83, "x" => 89, "y" => 97, "z" => 101];

/// Наиболее редко встречаемые встречаемые символы будут иметь меньшее значение. "экоронмия" около 0%
//    const SIMPLE_DIG_ARR =  ['q'=>2,  'x'=>3,  'j'=>5,  'z'=>7,  'v'=>11,  'f'=>13,  'w'=>17,  'b'=>19,  'k'=>23,  'g'=>29,  'p'=>31,  'm'=>37,  'h'=>41,  'd'=>43,  'c'=>47,  'y'=>53,  't'=>59,  'l'=>61,  'n'=>67,  'u'=>71,  'r'=>73,  'o'=>79,  'i'=>83,  's'=>89,  'e'=>97,  'a'=>101];

    // Наиболее часто встречаемые символы будут иметь меньшее значение. "экоронмия" около 80%
    const SIMPLE_DIG_ARR = [
        'a' => 2,
        'e' => 3,
        'i' => 5,
        's' => 7,
        'o' => 11,
        'r' => 13,
        'u' => 17,
        'n' => 19,
        'l' => 23,
        't' => 29,
        'y' => 31,
        'c' => 37,
        'd' => 41,
        'h' => 43,
        'm' => 47,
        'p' => 53,
        'g' => 59,
        'k' => 61,
        'b' => 67,
        'w' => 71,
        'f' => 73,
        'v' => 79,
        'z' => 83,
        'j' => 89,
        'x' => 97,
        'q' => 101
    ];

    static private function pData(int $timestamp = null): string {
        return date('Y-m-d H:i:s', null == $timestamp ? time() : $timestamp);
    }

    static public function toLog(string $text): bool {
        $text = PHP_EOL . CaHelpers::pData() . ' ' . $text;
        file_put_contents(LOG, $text, FILE_APPEND);
        echo $text;

        return true;
    }

    static public function calcComb($dim, $exp = 5): int {
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

    static public function getResultStrings(array $arr) {
        $resArray = $arr[0];

        for ($met = 1; $met < 5; ++$met) {
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
    static public $processedLineFromFile = 0;

    static public $bruteForceCounter = 0;
    static public $readPointersMult  = 0;

    static public $L1CutLongTail = 0;

    static public $L2uniqueValidIterationMain = 0;
    static public $L2uniqueValidIterationSub  = 0;
    static public $L2uniqueValidIterationSub2 = 0;
    static public $L2uniqueValidIterationSub3 = 0;
    static public $L2uniqueValidIterationSub4 = 0;
    static public $L2uniqueValidDetails       = [
        0 => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
        1 => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
        2 => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
    ];

    static public $L3recalcMaxPossiblePositionsMain = 0;
    static public $L3recalcMaxPossiblePositionsSub  = 0;
    static public $L3recalcMaxPossiblePositionsSub2 = 0;

    static public $L4recalcFirstPossiblePointersMain = 0;
    static public $L4recalcFirstPossiblePointersSub  = 0;
    static public $L4counter                         = 0;

    static $msCollector         = [];
    static $msCollectorCounter  = [];
    static $msCollector2        = [];
    static $msCollectorCounter2 = [];
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
#           Этот уровнеь удален Он не приносит профита. Инициализация происходит дольше, чем пройтись полностью.
class main
{
    public  $resultArray   = [];
    public  $resultStings  = [];
    private $bitSimpleMult = [];

    private $uniqueCharsWordsSyn = [];
    private $frequencyChars      = [];
    private $arrSimpleMult       = [];

    private $minMult;
//    private $maxMult;
    private $charBit       = [];
    private $countBit2Byte = [];

    private $lastIdx = 0;

    private $L3LevelRecalc   = 5;
    private $L3RecalcedLevel = 0;


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

    public function __construct() {
        $result = 1;
        $count  = 0;
        foreach (CaHelpers::SIMPLE_DIG_ARR as $char => $value) {
            $result               *= $value;
            $this->charBit[$char] = 1 << $count++;
        }
        $this->minMult = $result / 101;
//        $this->maxMult = $result / 2;

        $this->initData();

        for ($cnt = 0; $cnt <= 65535; ++$cnt) {
            $bit = $cnt;
            $res = 0;
            while ($bit) {
                ++$res;
                $bit &= $bit - 1;
            }

            $this->countBit2Byte[$cnt] = $res;
        }

        $this->L3LevelRecalc = 5;
        $this->L3recalcMaxPossiblePositions();
    }

    private function initData() {
        $uniqueCharsWordsSyn = &$this->uniqueCharsWordsSyn;
        $bitSimpleMult       = &$this->bitSimpleMult;
        $charBit             = &$this->charBit;
        $arrSimpleMult       = &$this->arrSimpleMult;

        $fileArr = file(DICT, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($fileArr as $line) {
//                stat::$processedLineFromFile++;

            // LENGTH_WORDS = 5
            if (isset($line[5]) || 5 !== strlen(count_chars($line, 3))) {
                continue;
            }

            $simpleMult = CaHelpers::SIMPLE_DIG_ARR[$line[0]]
                * CaHelpers::SIMPLE_DIG_ARR[$line[1]]
                * CaHelpers::SIMPLE_DIG_ARR[$line[2]]
                * CaHelpers::SIMPLE_DIG_ARR[$line[3]]
                * CaHelpers::SIMPLE_DIG_ARR[$line[4]];

            if (!isset($bitSimpleMult[$simpleMult])) {
                $arrSimpleMult[] = $simpleMult;

                //$bitSimpleMult[$simpleMult] = 0;
                $bitSimpleMult[$simpleMult] = $charBit[$line[0]]
                    | $charBit[$line[1]]
                    | $charBit[$line[2]]
                    | $charBit[$line[3]]
                    | $charBit[$line[4]];
            }
            $uniqueCharsWordsSyn[$simpleMult][] = $line;
        };
        $fileArr = null;

        krsort($bitSimpleMult, SORT_NUMERIC);
        rsort($arrSimpleMult, SORT_NUMERIC);
        $this->lastIdx = count($this->arrSimpleMult) - 1;
    }

    public function process() {
        // for debug
//        $this->test1();
//        return;

        $this->checkAndFindPositionUniqueChar();

        $arrSimpleMult       = &$this->arrSimpleMult;
        $uniqueCharsWordsSyn = &$this->uniqueCharsWordsSyn;

        foreach ($this->resultArray as $value) {
            $arrWords = [];
            for ($met = 5; $met >= 1; --$met) {
                $arrWords[] = $uniqueCharsWordsSyn[$arrSimpleMult[$value[$met]]];
            }
            $this->resultStings = array_merge($this->resultStings, CaHelpers::getResultStrings($arrWords));
        }
    }

    private function checkAndFindPositionUniqueChar() {
//        stat::$L2uniqueValidIterationMain++;

        $arrSimpleMult = $this->arrSimpleMult;
        $bitSimpleMult = $this->bitSimpleMult;
        $countBit2Byte = $this->countBit2Byte;

        $pointers               = &$this->pointers;
        $L3LevelRecalc          = &$this->L3LevelRecalc;
        $lastPossiblePointerIdx = &$this->lastPossiblePointerIdx;
        $lastRecalcLevel        = &$this->L3RecalcedLevel;

        $checkArrayBit = [];

        $pointLevel = 5;
//        for ($pointLevel = 5; $pointLevel > 0; --$pointLevel) {
        do {
//            stat::$L2uniqueValidIterationSub++;

            if (!isset($pointers[$pointLevel + 1])) {
                // Это условие (в IF) должно выполниться только для 5-го уровня $pointLevel
                $checkArrayBit[5] = 0;
                $L3LevelRecalc    = 4;
            }
            else {
                // Это условие (в IF) должно выполниться для не 0-го уровня $pointLevel..
                $checkArrayBit[$pointLevel] = $checkArrayBit[$pointLevel + 1];
                if ($lastRecalcLevel != $L3LevelRecalc && $L3LevelRecalc == $pointLevel) {
                    $this->L3recalcMaxPossiblePositions();
                }
                $L3LevelRecalc = $pointLevel - 1;
            }

            // First 5 = LENGTH_WORDS
            // Second 5 - level of pointer
            //  Замена константы на значение дало прирост ~0.5 - 1с на 100 млн. итерациях
            $countUnique = 5 * (5 - $pointLevel + 1);
            do {
//                stat::$L2uniqueValidIterationSub2++;

                $testBit = $checkArrayBit[$pointLevel] | $bitSimpleMult[$arrSimpleMult[$pointers[$pointLevel]]];

                if ($countUnique > $countBit2Byte[$testBit & 0b0000000001111111111111111] + $countBit2Byte[$testBit >> 16]) {
                    if (1 == $pointLevel && ++$pointers[1] <= $lastPossiblePointerIdx[1]) {
//                        stat::$L2uniqueValidIterationSub3++;
                        continue;
                    }

                    if (++$pointers[$pointLevel] <= $lastPossiblePointerIdx[$pointLevel]) {
                        if (2 == $pointLevel) {
                            $pointers[1]   = $pointers[2] + 1;
                            $L3LevelRecalc = 1;
                        }
                        elseif (3 == $pointLevel) {
                            $pointers[2]   = $idx = $pointers[3] + 1;
                            $pointers[1]   = ++$idx;
                            $L3LevelRecalc = 2;
                        }
                        else {
                            $pointers[3]   = $idx = $pointers[4] + 1;
                            $pointers[2]   = ++$idx;
                            $pointers[1]   = ++$idx;
                            $L3LevelRecalc = 3;
                        }
                        continue;
                    }

                    // Переходим на следующий уровень перебора.
                    do {
//                        stat::$L2uniqueValidIterationSub4++;
                        if (!isset($pointers[++$pointLevel])) {
                            return false;
                        }

                        // $checkArrayBit[$pointLevel + 1] ?? 0; ?? 0 - это сработает для 5-го уровня (самого верхнего) будет = 0;
//                        $checkArrayBit[$pointLevel] = $checkArrayBit[$pointLevel + 1] ?? 0;

//
//                        if (5 > ++$pointLevel) {
//                            $checkArrayBit[$pointLevel] = $checkArrayBit[$pointLevel + 1];
//                        }
//                        elseif (5 < $pointLevel) {
//                            return false;
//                        }
//                        else {
//                            $checkArrayBit[5] = 0;
//                        }

                    }
                    while (++$pointers[$pointLevel] > $lastPossiblePointerIdx[$pointLevel]);
                    //  Замена константы на значение дало прирост ~0.5 - 1с на 100 млн. итерациях


                    if (2 == $pointLevel) {
                        $pointers[1]      = $pointers[2] + 1;
                        $checkArrayBit[2] = $checkArrayBit[3];
                        $countUnique      = 20;
                        $L3LevelRecalc    = 1;
                    }
                    elseif (3 == $pointLevel) {
                        $pointers[2]      = $idx = $pointers[3] + 1;
                        $pointers[1]      = ++$idx;
                        $checkArrayBit[3] = $checkArrayBit[4];
                        $countUnique      = 15;
                        $L3LevelRecalc    = 2;
                    }
                    elseif (4 == $pointLevel) {
                        $pointers[3]      = $idx = $pointers[4] + 1;
                        $pointers[2]      = ++$idx;
                        $pointers[1]      = ++$idx;
                        $checkArrayBit[4] = $checkArrayBit[5];
                        $countUnique      = 10;
                        $L3LevelRecalc    = 3;
                    }
                    else {
                        $pointers[4]      = $idx = $pointers[5] + 1;
                        $pointers[3]      = ++$idx;
                        $pointers[2]      = ++$idx;
                        $pointers[1]      = ++$idx;
                        $checkArrayBit[5] = 0;
                        $countUnique      = 5;
                        $L3LevelRecalc    = 4;

                    }

                    $lastRecalcLevel = 5;
                    continue;
                }
                break;

            }
            while (true);

            $checkArrayBit[$pointLevel] = $testBit;
            if (1 == $pointLevel) {
                //  Такой уровень может быть в этом месте только при успехе
                $this->resultArray[] = $pointers;
                $checkArrayBit[1]    = $checkArrayBit[2];
                ++$pointLevel;
                ++$pointers[1];
            }
        }
        while (--$pointLevel);

        return true;
    }

    private function L3recalcMaxPossiblePositions() {
        // Ищем последнее значение pointer5, когда "произведение" больше
        // самой "меньшей" строки $his->minMult

//        stat::$L3recalcMaxPossiblePositionsMain++;
        $currentPointerArray = $this->pointers;

        $arrSimpleMult = &$this->arrSimpleMult;
        $idx           = $lastValidIdx = $currentPointerArray[$this->L3LevelRecalc];
        $jumping       = true;
        $lBorder       = $idx;

        $rBorder = $this->lastIdx;

//        stat::$L2uniqueValidDetails[0][$this->L3LevelRecalc]++;
        while (true) {
//            stat::$L3recalcMaxPossiblePositionsSub++;

            // 4 - Разница между левой и правой границе при которой нужно закончить "прыжки"
            if ($jumping && (4 > ($rBorder - $lBorder))) {
                $jumping = false;
                $idx     = $lastValidIdx;
            }

            $currentPointerArray[$this->L3LevelRecalc] = $count = $idx;

            for ($met = $this->L3LevelRecalc - 1; $met >= 1; --$met) {
                $currentPointerArray[$met] = ++$count;
            }

            $res = $arrSimpleMult[$currentPointerArray[1]]
                * $arrSimpleMult[$currentPointerArray[2]]
                * $arrSimpleMult[$currentPointerArray[3]]
                * $arrSimpleMult[$currentPointerArray[4]]
                * $arrSimpleMult[$currentPointerArray[5]];

            if ($res > $this->minMult) {
                $lastValidIdx = $lBorder = $idx;
                if ($jumping) {
                    $idx = $idx + (int)(($rBorder - $idx) / 2);
                }
                else {
                    ++$idx;
                }
                continue;
            }
            elseif ($jumping) {
                $rBorder = $idx;
                $idx     = $idx - (int)(($idx - $lBorder) / 2);
                continue;
            }

            // Из-за "кривого" сравнения float штатными средствами невозможно поставить
            //  Условие $res >= $this->minMult выше. Приходится применять такой изъеб, как ниже.
            $this->lastPossiblePointerIdx[$this->L3LevelRecalc] = (string)$res == (string)$this->minMult ? $idx :
                --$idx;
            break;
        };

        $this->L3RecalcedLevel = $this->L3LevelRecalc;
    }

    public function statusToLog() {
        CaHelpers::toLog('Details processing: ' . (time() - stat::$startTime) . ' sec.');
        CaHelpers::toLog("\tProcessed Time: " . (time() - stat::$startTime) . ' sec.');
        CaHelpers::toLog("\tFound " . count($this->resultStings) . ' strings');
        CaHelpers::toLog("\tTotal Multiplications: " . number_format(stat::$readPointersMult, 0, '.', ' '));
//        CaHelpers::toLog("\tLevel (1) Optimisation: " . number_format(stat::$L1CutLongTail, 0, '.', ' ')
//            . ' (' . round(stat::$L1CutLongTail / stat::$bruteForceCounter * 100, 2) . '%)');

        CaHelpers::toLog("\t\tLevel (2) Main: "
            . number_format(stat::$L2uniqueValidIterationMain, 0, '.', ' '));
        CaHelpers::toLog("\t\tLevel (2) Sub: "
            . number_format(stat::$L2uniqueValidIterationSub, 0, '.', ' '));
        CaHelpers::toLog("\t\tLevel (2) Sub2: "
            . number_format(stat::$L2uniqueValidIterationSub2, 0, '.', ' '));
        CaHelpers::toLog("\t\tLevel (2) Sub3: "
            . number_format(stat::$L2uniqueValidIterationSub3, 0, '.', ' '));
        CaHelpers::toLog("\t\tLevel (2) Sub4: "
            . number_format(stat::$L2uniqueValidIterationSub4, 0, '.', ' '));

//        CaHelpers::toLog("\t\t\tDetails Validation unique (level 2): ");
//        for ($count = 2; $count >= 0; --$count) {
//            CaHelpers::toLog('Level: ' . $count);
//            for ($log = 5; $log >= 1; --$log) {
//                CaHelpers::toLog("\t\t\t\t\tPointer " . $log . ': '
//                    . number_format(stat::$L2uniqueValidDetails[$count][$log], 0, '.', ' '));
//            }
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
        CaHelpers::toLog("\t\t\t\tLevel (4) Cnt: "
            . number_format(stat::$L4counter, 0, '.', ' '));

        CaHelpers::toLog("\tDebug (pointers)");
        for ($log = 5; $log >= 1; --$log) {
            CaHelpers::toLog("\t\tPointer " . $log . ': ' . $this->pointers[$log]
                . "\t(" . $this->lastPossiblePointerIdx[$log] . "\tMax Possible)");
        }

        foreach (stat::$msCollector as $idx => $value) {
            CaHelpers::toLog("\tStat MsCollector:\t" . $value . " ms.\tIDx :" . $idx . ".\tcount: " . stat::$msCollectorCounter[$idx]);
        }

        foreach (stat::$msCollector2 as $idx => $value) {
            CaHelpers::toLog("\tStat MsCollector2:\t" . $value . " ms.\tIDx :" . $idx . ".\tcount: " . stat::$msCollectorCounter2[$idx]);
        }


    }

    public function test1() {
        $maxFor = 20000000;
        [$ms, $s] = explode(' ', microtime());
        $startMicroTime = $s * 1000 + round($ms * 1000);
        for ($met = $maxFor; $met >= 1; $met--) {

        }
        [$ms, $s] = explode(' ', microtime());
        $endMicroTime = $s * 1000 + round($ms * 1000);
        CaHelpers::toLog('1: ' . ($endMicroTime - $startMicroTime) . ' ms');
        CaHelpers::toLog('1: ' . $res);

        return;
    }
}

CaHelpers::toLog('Start ===========================');
stat::$startTime = time();

[$ms, $s] = explode(' ', microtime());
$startMicroTime = $s * 1000 + round($ms * 1000);

$obj = new main();
$obj->process();

[$ms, $s] = explode(' ', microtime());
$endMicroTime = $s * 1000 + round($ms * 1000);

CaHelpers::toLog('');
CaHelpers::toLog('Finished.. ===========================');
CaHelpers::toLog("\tFound Result(s): " . count($obj->resultStings));
CaHelpers::toLog("\tTotal Time: " . ($endMicroTime - $startMicroTime) . ' ms.');
//$obj->statusToLog();

CaHelpers::toLog("\tMax Used Memory: " . round(memory_get_peak_usage() / 1024 / 1024) . 'Mb');
CaHelpers::toLog(PHP_EOL);

file_put_contents(RESULT_FILE, implode(PHP_EOL, $obj->resultStings));




