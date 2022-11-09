<?php
//ini_set('memory_limit', '7000M');
ini_set('error_reporting', E_COMPILE_ERROR);

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

    static $msCollector = 0;
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
    public  $resultArray          = [];
    public  $resultStings         = [];
    private $uniqueCharsWordsMult = [];
    private $detailSimpleMult     = [];

    private $uniqueCharsWordsSyn = [];
    private $frequencyChars      = [];
    private $arrSimpleMult       = [];

    private $maxMult;
    private $minMult;

    private $lastIdx = 0;

    private $L3LevelRecalc   = 5;
    private $L3RecalcedLevel = 0;

    private $L4Jumping         = true;
    private $L4JumpingStopDiff = 5;


    private $pointers = [
        1 => 4,                 // Указатель для 1 слова
        2 => 3,                 // Указатель для 2 слова
        3 => 2,                 // Указатель для 3 слова
        4 => 1,                 // Указатель для 4 слова
        5 => 0                  // Указатель для 5 слова
    ];

    private $pointersLastRecalc = [
        1 => 0,
        2 => 0,
        3 => 0,
        4 => 0,
        5 => 0,
    ];

    private $lastPossiblePointerIdx = [
        1 => 4,                 // Указатель для 1 слова
        2 => 3,                 // Указатель для 2 слова
        3 => 2,                 // Указатель для 3 слова
        4 => 1,                 // Указатель для 4 слова
        5 => 0                  // Указатель для 5 слова
    ];

    public function __construct() {
        $this->initData();

        foreach (CaHelpers::SIMPLE_DIG_ARR as $idx => $val) {
            $tmpArray = CaHelpers::SIMPLE_DIG_ARR;
            unset($tmpArray[$idx]);
            $result = 1;

            foreach ($tmpArray as $idx2 => $val2) {
                $result *= $val2;
            }

            $this->maxMult = !isset($this->maxMult) || $this->maxMult < $result
                ? $result
                : $this->maxMult;

            $this->minMult = !isset($this->minMult) || $this->minMult > $result
                ? $result
                : $this->minMult;
        }

        $this->L3LevelRecalc = 5;
        $this->L3recalcMaxPossiblePositions();
    }

    private function initData() {
        $detailSimpleMult     = &$this->detailSimpleMult;
        $uniqueCharsWordsMult = &$this->uniqueCharsWordsMult;
        $uniqueCharsWordsSyn  = &$this->uniqueCharsWordsSyn;

        $fileArr = explode("\r\n", file_get_contents(DICT));

        $line = trim(current($fileArr));
        do {
//                stat::$processedLineFromFile++;
            $line = trim($line);

            // LENGTH_WORDS = 5
            if (5 !== strlen($line) || 5 !== strlen(count_chars($line, 3))) {
                continue;
            }

            $arrLine = str_split($line);
            sort($arrLine);
            $sortedCharsLine = implode($arrLine);

            $simpleMult = 1
                * CaHelpers::SIMPLE_DIG_ARR[$arrLine[0]]
                * CaHelpers::SIMPLE_DIG_ARR[$arrLine[1]]
                * CaHelpers::SIMPLE_DIG_ARR[$arrLine[2]]
                * CaHelpers::SIMPLE_DIG_ARR[$arrLine[3]]
                * CaHelpers::SIMPLE_DIG_ARR[$arrLine[4]];

            if (!isset($uniqueCharsWordsSyn[$sortedCharsLine])) {
                foreach ($arrLine as $char) {
                    $detailSimpleMult[$simpleMult][CaHelpers::SIMPLE_DIG_ARR[$char]] = true;
                }

                $uniqueCharsWordsMult[$simpleMult]['chars'] = $sortedCharsLine;

                // for debug
//                    $this->frequencyChars[$arrLine[0]][$sortedCharsLine][] = $line;
//                    $this->frequencyChars[$arrLine[1]][$sortedCharsLine][] = $line;
//                    $this->frequencyChars[$arrLine[2]][$sortedCharsLine][] = $line;
//                    $this->frequencyChars[$arrLine[3]][$sortedCharsLine][] = $line;
//                    $this->frequencyChars[$arrLine[4]][$sortedCharsLine][] = $line;
            }
            $uniqueCharsWordsSyn[$sortedCharsLine][] = $line;
        }
        while ($line = trim(next($fileArr)));
        $fileArr = null;


        // for debug
//        foreach ($this->frequencyChars as $idx => $value) {
//            $this->frequencyChars[$idx] = count($value);
//        }

        krsort($uniqueCharsWordsMult);
        krsort($detailSimpleMult);
        $this->arrSimpleMult = array_keys($uniqueCharsWordsMult);
        $this->lastIdx       = count($this->arrSimpleMult) - 1;

        for ($met = 5; $met >= 1; --$met) {
            $this->lastPossiblePointerIdx[$met] = $this->pointersLastRecalc[$met] = $this->lastIdx;
        }
    }

    public function process() {
        // for debug
//        $this->test1();
//        return;
        $this->checkAndFindPositionUniqueChar();

        foreach ($this->resultArray as $idx => $value) {
            $arrWords = [];
            for ($met = 5; $met >= 0; --$met) {
                $mult       = $this->arrSimpleMult[$value[$met]];
                $string     = $this->uniqueCharsWordsMult[$mult]['chars'];
                $arrWords[] = $this->uniqueCharsWordsSyn[$string];
            }
            $this->resultStings = array_merge($this->resultStings, CaHelpers::getResultStrings($arrWords));
        }
    }

    private function checkAndFindPositionUniqueChar() {
//        stat::$L2uniqueValidIterationMain++;

        $pointers               = &$this->pointers;
        $L3LevelRecalc          = &$this->L3LevelRecalc;
        $arrSimpleMult          = &$this->arrSimpleMult;
        $detailSimpleMult       = &$this->detailSimpleMult;
        $lastPossiblePointerIdx = &$this->lastPossiblePointerIdx;

        $checkArray      = [];
        $testArray       = [];
        $countUnique     = 0;
        $lastRecalcLevel = &$this->L3RecalcedLevel;

        for ($pointLevel = 5; $pointLevel > 0; --$pointLevel) {
//            stat::$L2uniqueValidIterationSub++;

            if (!isset($pointers[$pointLevel + 1])) {
                // Это условие (в IF) должно выполниться только для 5-го уровня $pointLevel
                $checkArray[$pointLevel] = [];
                $L3LevelRecalc           = 4;
            }
            else {
                // Это условие (в IF) должно выполниться для не 0-го уровня $pointLevel..
                $checkArray[$pointLevel] = $checkArray[$pointLevel + 1];
                if ($lastRecalcLevel != $L3LevelRecalc && $L3LevelRecalc == $pointLevel) {
                    $this->L3recalcMaxPossiblePositions();
                }
                $L3LevelRecalc = $pointLevel - 1;
            }

            //  Замена константы на значение дало прирост ~0.5 - 1с на 100 млн. итерациях
            $countUnique = 5 * (5 - $pointLevel + 1);
            do {
//                stat::$L2uniqueValidIterationSub2++;
                $testArray = $checkArray[$pointLevel] + $detailSimpleMult[$arrSimpleMult[$pointers[$pointLevel]]];

                // First 5 = LENGTH_WORDS
                // Second 5 - level of pointer

                if ($countUnique > count($testArray)) {
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
                            $pointers[2]   = $pointers[3] + 1;
                            $pointers[1]   = $pointers[2] + 1;
                            $L3LevelRecalc = 2;
                        }
                        else {
                            $pointers[3]   = $pointers[4] + 1;
                            $pointers[2]   = $pointers[3] + 1;
                            $pointers[1]   = $pointers[2] + 1;
                            $L3LevelRecalc = 3;
                        }
                        continue;
                    }

                    // Переходим на следующий уровень перебора.
                    do {
//                        stat::$L2uniqueValidIterationSub4++;
                        if (5 > ++$pointLevel) {
                            $checkArray[$pointLevel] = $checkArray[$pointLevel + 1];
                        }
                        elseif (5 < $pointLevel) {
                            return false;
                        }
                        else {
                            $checkArray[5] = [];
                        }
                    }
                    while (++$pointers[$pointLevel] > $lastPossiblePointerIdx[$pointLevel]);
                    //  Замена константы на значение дало прирост ~0.5 - 1с на 100 млн. итерациях
                    $countUnique = 5 * (5 - $pointLevel + 1);

                    if (3 == $pointLevel) {
                        $pointers[2]   = $pointers[3] + 1;
                        $pointers[1]   = $pointers[2] + 1;
                        $L3LevelRecalc = 2;
                    }
                    elseif (2 == $pointLevel) {
                        $pointers[1]   = $pointers[2] + 1;
                        $L3LevelRecalc = 1;
                    }
                    elseif (4 == $pointLevel) {
                        $pointers[3]   = $pointers[4] + 1;
                        $pointers[2]   = $pointers[3] + 1;
                        $pointers[1]   = $pointers[2] + 1;
                        $L3LevelRecalc = 3;
                    }
                    else {
                        $pointers[4]   = $pointers[5] + 1;
                        $pointers[3]   = $pointers[4] + 1;
                        $pointers[2]   = $pointers[3] + 1;
                        $pointers[1]   = $pointers[2] + 1;
                        $L3LevelRecalc = 4;
                    }

//                    $this->L3recalcMaxPossiblePositions(2);
                    $lastRecalcLevel = 0;
                    continue;
                }
                break;

            }
            while (true);

            $checkArray[$pointLevel] = $testArray;
            if (1 == $pointLevel) {
                //  Такой уровень может быть в этом месте только при успехе
                $this->resultArray[] = $pointers;
                $checkArray[1]       = $checkArray[2];
                ++$pointLevel;
                ++$pointers[1];
            }
        }

        return true;
    }

    private function L3recalcMaxPossiblePositions() {
        // Ищем последнее значение pointer5, когда "произведение" больше
        // самой "меньшей" строки $his->$maxMult

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

            $currentPointerArray[$this->L3LevelRecalc] = $idx;

            for ($met = $this->L3LevelRecalc - 1; $met >= 1; --$met) {
                $currentPointerArray[$met] = $currentPointerArray[$met + 1] + 1;
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

        CaHelpers::toLog("\tDebug (pointers)");
        for ($log = 5; $log >= 1; --$log) {
            CaHelpers::toLog("\t\tPointer " . $log . ': ' . $this->pointers[$log]
                . "\t(" . $this->lastPossiblePointerIdx[$log] . "\tMax Possible)");
        }

        CaHelpers::toLog("\tStat MsCollector: " . stat::$msCollector . ' ms');


    }

    public function test1() {
        $maxFor = 700000;

        $c = 5;
        $d = 5;

        $a = 0;
        [$ms, $s] = explode(' ', microtime());
        $startMicroTime = $s * 1000 + round($ms * 1000);
        for ($met = $maxFor; $met >= 1; $met--) {
            for ($count = 5; $count >= 1; $count--) {
                ++$a;
            }
        }
        [$ms, $s] = explode(' ', microtime());
        $endMicroTime = $s * 1000 + round($ms * 1000);
        CaHelpers::toLog('1: ' . ($endMicroTime - $startMicroTime) . ' ms');

        $a = 0;
        [$ms, $s] = explode(' ', microtime());
        $startMicroTime = $s * 1000 + round($ms * 1000);
        for ($met = $maxFor; $met >= 1; $met--) {
            for ($count = 5; $count >= 1; --$count) {
                ++$a;
            }
        }
        [$ms, $s] = explode(' ', microtime());
        $endMicroTime = $s * 1000 + round($ms * 1000);
        CaHelpers::toLog('2: ' . ($endMicroTime - $startMicroTime) . ' ms');

        return;

        [$ms, $s] = explode(' ', microtime());
        $startMicroTime = $s * 1000 + round($ms * 1000);
        for ($met = $maxFor; $met >= 1; $met--) {
            $this->test3();
        }
        [$ms, $s] = explode(' ', microtime());
        $endMicroTime = $s * 1000 + round($ms * 1000);
        CaHelpers::toLog('3: ' . ($endMicroTime - $startMicroTime) . ' ms');

        return;

        $c = $this->uniqueCharsWordsMult;
        [$ms, $s] = explode(' ', microtime());
        $startMicroTime = $s * 1000 + round($ms * 1000);
        for ($met = $maxFor; $met >= 1; $met--) {
            $a = $c[51237167];
        }
        [$ms, $s] = explode(' ', microtime());
        $endMicroTime = $s * 1000 + round($ms * 1000);
        CaHelpers::toLog('4: ' . ($endMicroTime - $startMicroTime) . ' ms');

        return false;
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
//file_put_contents(EXP_DIR . '/debug.csv', implode(PHP_EOL, $obj->testArr));


die;

//$q[] = 'abcde';
//$q[] = 'fghij';
//$q[] = 'klmno';
//$q[] = 'prstq';
//$q[] = 'uvwxy';


