<?php

$alphabet = ['a', 'b', 'c', 'd', 'e', 'f',
    'g', 'h', 'i', 'j', 'k', 'l',
    'm', 'n', 'o', 'p', 'q', 'r',
    's', 't', 'u', 'v', 'w', 'x',
    'y', 'z'];

//print(microtime()."\n");

$time = time();

$file = file(__DIR__.'/resources/words_alpha.txt');

$array_keys = [];
$array = [];

$anagram = [];

$words_arr = array_filter(array_map(function ($word) use (&$array_keys, &$array, &$anagram) {
    $word = trim($word);
    $letters = array_filter(array_unique(preg_split("//u", $word)));
    if ((mb_strlen($word) == 5) && count($letters) == 5) {

        $anagram_letters = $letters;

        sort($anagram_letters);

        $anagram_letters = implode('', $anagram_letters);

//        var_dump($anagram_letters);

        $anagram[$anagram_letters][] = $word;

//        var_dump($anagram);

        if(count($anagram[$anagram_letters]) == 1) {
            foreach($letters as $letter) {
                $array_keys[$letter][] = $word;
            }
            $array[] = $word;
        }

        return $word;
    }
    return false;
}, $file));

function get_anagram($word, $anagram) {
    $letters = array_filter(array_unique(preg_split("//u", $word)));
    sort($letters);
    $letters = implode('', $letters);

    return $anagram[$letters];
}

$array_counter = [];

foreach($array_keys as $letter => $letter_arr) {
    $array_counter[$letter] = count($letter_arr);
}

asort($array_counter);

$letters = array_slice(array_keys($array_counter), 0, 2);

$first_arr = array_merge($array_keys[$letters[0]], $array_keys[$letters[1]]);

$result_array = [];

$letters = '';

foreach ($first_arr as $key => $first_word) {
//    var_dump($first_word);
//    var_dump($key);
//    var_dump('time - '.(time() - $time));
//    echo memory_get_usage() . "\n";

    $second_arr = $array;

    preg_match_all('/['.$first_word.']/', implode('', $second_arr), $matches, PREG_OFFSET_CAPTURE);

    foreach($matches[0] as $match) {
        unset($second_arr[floor($match[1]/5)]);
    }

    $letters = [];

    preg_match_all('/\w/', implode('', $second_arr), $matches, PREG_OFFSET_CAPTURE);

    foreach($matches[0] as $match) {
        if(!isset($letters[$match[0]])) {
            $letters[$match[0]] = 0;
        }
        $letters[$match[0]] = $letters[$match[0]] + 1;
    }

    asort($letters);

    $letters = array_slice(array_keys($letters), 0, 2);

    $second_arr = array_values($second_arr);

    preg_match_all('/['.$letters[0].$letters[1].']/', implode('', $second_arr), $matches, PREG_OFFSET_CAPTURE);

    $new_arr = [];

    foreach($matches[0] as $match) {
        $new_arr[] = $second_arr[floor($match[1]/5)];
    }

    foreach($new_arr as $second_word) {

        $third_arr = array_values($second_arr);
        preg_match_all('/['.$first_word.$second_word.']/', implode('', $third_arr), $matches, PREG_OFFSET_CAPTURE);

        foreach($matches[0] as $match) {
            unset($third_arr[floor($match[1]/5)]);
        }

        if($third_arr) {

            $letters = [];

            preg_match_all('/\w/', implode('', $third_arr), $matches, PREG_OFFSET_CAPTURE);

            foreach($matches[0] as $match) {
                if(!isset($letters[$match[0]])) {
                    $letters[$match[0]] = 0;
                }
                $letters[$match[0]] = $letters[$match[0]] + 1;
            }

            asort($letters);

            $letters = array_slice(array_keys($letters), 0, 2);

            $third_arr = array_values($third_arr);

            preg_match_all('/['.$letters[0].$letters[1].']/', implode('', $third_arr), $matches, PREG_OFFSET_CAPTURE);

//    var_dump($second_arr);

            $new_arr2 = [];

            foreach($matches[0] as $match) {
//        var_dump($match);
                $new_arr2[] = $third_arr[floor($match[1]/5)];
            }

//            var_dump($new_arr2);
//        exit();

            foreach($new_arr2 as $third_word) {

                $fourth_arr = array_values($third_arr);

                preg_match_all('/['.$first_word.$second_word.$third_word.']/', implode('', $fourth_arr), $matches, PREG_OFFSET_CAPTURE);

                foreach($matches[0] as $match) {
                    unset($fourth_arr[floor($match[1]/5)]);
                }

                foreach($fourth_arr as $fourth_word) {

                    $fifth_arr = array_values($fourth_arr);

                    preg_match_all('/['.$first_word.$second_word.$third_word.$fourth_word.']/', implode('', $fifth_arr), $matches, PREG_OFFSET_CAPTURE);

                    foreach($matches[0] as $match) {
                        unset($fifth_arr[floor($match[1]/5)]);
                    }

                    foreach($fifth_arr as $fifth_word) {

//                    var_dump($first_word.$second_word.$third_word.$fourth_word.$fifth_word);
//                    print(microtime()." second arr \n");
//                    exit();

                        foreach(get_anagram($first_word, $anagram) as $first_anagram) {
                            foreach(get_anagram($second_word, $anagram) as $second_anagram) {
                                foreach(get_anagram($third_word, $anagram) as $third_anagram) {
                                    foreach(get_anagram($fourth_word, $anagram) as $fourth_anagram) {
                                        foreach(get_anagram($fifth_word, $anagram) as $fifth_anagram) {
                                            $a = [$first_anagram, $second_anagram, $third_anagram, $fourth_anagram, $fifth_anagram];
                                            asort($a);
                                            $a = implode('', $a);
                                            $result_array[$a] = $a;
                                        }
                                    }
                                }
                            }
                        }

                    }
                }
            }

        }


    }
//    var_dump($result_array);
//    print(microtime()." second arr \n");
//    exit();
}
//var_dump($anagram);
//var_dump(array_values($result_array));


$filename = './tmp/test.txt';
$somecontent = implode("\n", $result_array);

if (!$fp = fopen($filename, 'a')) {
    echo "Не могу открыть файл ($filename)";
    exit;
}

if (fwrite($fp, $somecontent) === FALSE) {
    echo "Не могу произвести запись в файл ($filename)";
    exit;
}

var_dump('time - '.(time() - $time));
//print(microtime()." second arr \n");
//exit();