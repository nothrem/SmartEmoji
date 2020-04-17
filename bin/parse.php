<?php

use xml\Data;

//PHP 7.2 is required
if (PHP_VERSION_ID < 70200) {
    die('PHP 7.2 is required!');
}
if (!function_exists('mb_chr') || !\extension_loaded('mbstring')) {
    die('PHP extension mbstring is required');
}
$langs = $argv[1] ?? [];

set_include_path(__DIR__.'/../php');

//Find all emoji defined by UNICODE
require_once 'Sequences.php';
require_once 'Unicode.php';

use emoji\Unicode AS U;
$sequence = new \emoji\Sequences();
$emoji = $sequence->parse('emoji-sequences.txt');
$emoji = array_merge($emoji, $sequence->parse('emoji-zwj-sequences.txt'));

//Process emoji groups
require_once 'Groups.php';

$groups = new \emoji\Groups();
$groups = $groups->parse();
$emojiStat = array_pop($groups);
$allEmojiInGroupList=array_pop($groups);
$filters = array_shift($groups);

array_shift($emojiStat);       // Remove filter group

//Process emoji annotations (translated names)
$annotations = [];
if (empty($langs)) {
    echo 'Parameter with language list not found, skipping translation processing.', PHP_EOL;
}
else {
    require_once 'Annotations.php';

    $annotations = new \emoji\Annotations();
    $annotations = $annotations->parse($langs, $emoji);

//Process group translations
    require_once 'Main.php';

    $main = new \emoji\Main();
    $main->load($langs);

    $groupTranslations = [];

    $main->getCharacterLabel('en', 'activities'); //just a random label to preload the labels from XMLs
    echo PHP_EOL, 'Translating emoji groups...', PHP_EOL;

    foreach ($main->getLanguages() as $lang) {
        if (!array_key_exists($lang, $groupTranslations)) {
            $groupTranslations[$lang] = $groups;
        }
//  Checking of not found emojies in Annotation block

//        $notFound = 0;
//        foreach ($annotations[$lang] as $char => $annotation) {
//            if (!in_array ( $char, $allEmojiInGroupList, true )) {
//                $notFound++;
//                $newEmoji = [
//                    'i'=>$char,
//                    'n'=>$annotation['n'],
//                ];
//                is_null($annotation['k']) ? null : $newEmoji['k']=$annotation['k'] ;
//                $groupTranslations[$lang]['other'][Data::JSON_LIST]['other'][Data::JSON_LIST][] = $newEmoji;
//                echo 'Warning: emoji ', $char, ' not found in Sequences.', PHP_EOL;
//                fwrite(STDERR, 'Warning: emoji ' . $char . ' not found in Sequences.' . PHP_EOL);
//            }
//        }

        foreach ($groups as $curGroup) {
            echo '  Translating group ', $curGroup[Data::JSON_NAME], ' into language ', $lang, '...';
            $transWord = $main->getCharacterLabel($lang, $curGroup[Data::JSON_NAME]);
            is_null($transWord) ? null :  $groupTranslations[$lang][$curGroup[Data::JSON_NAME]][Data::JSON_NAME] = $transWord;
            echo ' Translation: "', $groupTranslations[$lang][$curGroup[Data::JSON_NAME]][Data::JSON_NAME], '"', PHP_EOL;

            foreach ($curGroup[Data::JSON_LIST] as $curSubGroup) {
                echo '  Translating subgroup ', $curSubGroup[Data::JSON_NAME], ' into language ', $lang, '...';
                $transWord = $main->getCharacterLabel($lang, $curSubGroup[Data::JSON_NAME]);
                is_null($transWord) ? null :  $groupTranslations[$lang][$curGroup[Data::JSON_NAME]][Data::JSON_LIST][$curSubGroup[Data::JSON_NAME]][Data::JSON_NAME] = $transWord;
                echo ' Translation: "', $groupTranslations[$lang][$curGroup[Data::JSON_NAME]][Data::JSON_LIST][$curSubGroup[Data::JSON_NAME]][Data::JSON_NAME], '"', PHP_EOL;
            }
        }
    }
    echo PHP_EOL, 'Finished translating emoji groups.', PHP_EOL, PHP_EOL;

    //Check if Annotations and Sequences match
    foreach ($main->getLanguages() as $lang) {
        $annotationL = $annotations[$lang];
        $filter[$lang]=$filters;
        foreach($groupTranslations[$lang] as &$curGroup) {
            foreach ($curGroup[Data::JSON_LIST] as &$subGroup) {
                foreach ($subGroup[Data::JSON_LIST] as $key=>&$value){
                    if ( U::codelast($key)=='fe0f') {
                        $key = U::trimlast($key);
                    }
                    is_null($annotationL[$key][Data::JSON_NAME]) ? null : $value[Data::JSON_NAME]=$annotationL[$key][Data::JSON_NAME];
                    is_null($annotationL[$key][Data::JSON_KEYWORDS]) ? null : $value[Data::JSON_KEYWORDS]=$annotationL[$key][Data::JSON_KEYWORDS];
                }
            }
        }
 // Translate of filters
        foreach ($filter[$lang][Data::JSON_LIST] as &$subGroup) {
            foreach ($subGroup[Data::JSON_LIST] as $key=>&$value){
                if (array_key_exists($value[Data::JSON_NAME], $annotationL)) {
                    $value[Data::JSON_NAME]=$annotationL[$value[Data::JSON_NAME]][Data::JSON_NAME];
                }
            }
        }

        // Implementing of the modifiers
        foreach ($groupTranslations[$lang] as &$curGroup) {
            foreach ($curGroup[Data::JSON_LIST] as &$subGroup) {
                foreach ($subGroup[Data::JSON_LIST] as $key => &$value) {
                    if ($value[Data::JSON_MODIFIER] !== null) {
                        $thisKeys = explode(',', $value[Data::JSON_MODIFIER]);
                        foreach ($thisKeys as $thisKey) {
                            $thisKey=trim($thisKey);
                            if ($thisKey!=='') $prevEmoji[$thisKey]=$key;
                        }
                        unset($subGroup[Data::JSON_LIST][$key]);
                    } else {
                        $prevEmoji = &$value;
                    }
                }
            }
        }
    }

    //Creation of KEYWORDS object
    $keywords = [];
    foreach ($main->getLanguages() as $lang) {
        $keywords[$lang] = [];
        foreach ($groupTranslations[$lang] as &$curGroup) {
            foreach ($curGroup[Data::JSON_LIST] as &$subGroup) {
                foreach ($subGroup[Data::JSON_LIST] as $key => &$value) {
                    if ($value[Data::JSON_KEYWORDS] !== null) {
                        $thisKeys = explode(Data::JSON_KEY_DELIM, $value[Data::JSON_KEYWORDS]);
                        $thisKeys[] = $value[Data::JSON_NAME];
                        foreach ($thisKeys as $thisKey) {
                            $thisKey = mb_strtolower($thisKey);
                            if (!array_key_exists($thisKey, $keywords[$lang])) {
                                $keywords[$lang][$thisKey] = [];
                            }
                            $keywords[$lang][$thisKey][] = $key;
                        }
                        unset($value[Data::JSON_KEYWORDS]);
                    }
                }
            }
        }
        ksort($keywords[$lang], SORT_LOCALE_STRING);
    }

    foreach ($emoji as $char => $annotation) {
        foreach ($main->getLanguages() as $lang) {
            if (!array_key_exists($char, $annotations[$lang])) {
                echo 'Warning: emoji ', $char, ' not found in Annotations of language ', $lang, '.', PHP_EOL;
                fwrite(STDERR, 'Warning: emoji ' . $char . ' not found in Annotations of language ' . $lang . '.' . PHP_EOL);
            }
        }
    }

//Save emoji and group translations into file
    if ($main!==null) {
        foreach ($main->getLanguages() as $lang) {
            $annotationFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'emoji' . DIRECTORY_SEPARATOR . 'groups.' . $lang . '.json';
            echo 'Saving ', count($allEmojiInGroupList ?? []), ' emoji and ', count($groupTranslations[$lang] ?? []), ' groups into file ', $annotationFile, PHP_EOL;
            file_put_contents($annotationFile, json_encode([
                'groups' => $groupTranslations[$lang] ?? [],
                'filters' => $filter[$lang][Data::JSON_LIST] ?? [],
                'keywords' => $keywords[$lang] ?? [],
            ], JSON_THROW_ON_ERROR + JSON_UNESCAPED_UNICODE)); //Crash on invalid JSON instead of creating empty file

        }

// Save emoji statistic information into file emoji-statistic.txt
        $statisticFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR. '..' . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'emoji' . DIRECTORY_SEPARATOR . 'emoji-statistics' . '.txt';
        $handle = fopen($statisticFile, 'c');
        if ($handle){
            fwrite ( $handle, PHP_EOL . 'groups: '.count($emojiStat). PHP_EOL);
            fwrite ( $handle, PHP_EOL . 'emojis: '.count($allEmojiInGroupList). PHP_EOL);
            foreach ($emojiStat as $name => $group) {
                fwrite ( $handle, PHP_EOL . $emojiStat[$name][Data::JSON_ROW].PHP_EOL);
                foreach ($group[Data::JSON_LIST] as $type => $subGroup) {
                    fwrite ( $handle, $emojiStat[$name][Data::JSON_LIST][$type][Data::JSON_ROW]);
                }
            }
            fclose($handle);
        }

    }
}

//Everything done
echo 'Done.', PHP_EOL;
