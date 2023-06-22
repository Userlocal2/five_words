<?php
$words = [];
$file = fopen('./resources/words_alpha.txt', 'r');
while (($line = fgets($file)) !== false) {
    $word = trim($line);
    if (strlen($word) == 5 && strlen($word) == count(array_unique(str_split($word)))) {
        $words[implode(array_unique(str_split($word)))] = $word;
    }
}
fclose($file);

function search_words($letters, $words, $used) {
    if (empty($letters)) {
        foreach ($used as $u) {
            if (strlen($u) == 5) {
                echo $u . "\n";
            }
        }
        exit();
    }
    $counts = array_count_values(str_split(implode($words)));
    asort($counts);
    $rarest = key($counts);
    $newWords = $words;
    if (count($letters) % 5 != 0) {
        $newWords[] = $rarest;
    }
    foreach ($newWords as $word) {
        if (($word && $rarest !== null) && strpos($word, $rarest) !== false) {
            $newLetters = array_diff($letters, str_split($word));
            $newWords = array_filter($words, function ($w) use ($word) {
                return !count(array_intersect(str_split($w), str_split($word)));
            });
            $newUsed = $used;
            $newUsed[] = $word;
            search_words($newLetters, $newWords, $newUsed);
        }
    }
}

search_words(range('a', 'z'), array_values($words), []);
