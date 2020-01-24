<?php

namespace emoji;

require_once 'Unicode.php';
use emoji\Unicode AS U;

class Groups {
    protected const DEFAULT_DATA_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'core';
    protected const EMOJI_SEQUENCE_CHAR = '-'; //characters that separates list of emoji
    protected const EMOJI_DERIVED_START = '{'; //characters that starts multi-character emoji
    protected const EMOJI_DERIVED_END = '}';   //characters that ends multi-character emoji
    protected const SEMICOLON_PLACEHOLDER = '"\u003B"'; //JSON-encoded UNICODE character that replaces semicolon (used as separator in the file)

    protected $path;

    /**
     * Groups constructor.
     * @param string|null $path (optional; default: self::DEFAULT_DATA_DIR) Path where CLDR data are stored.
     */
    public function __construct(string $path = null) {
        $this->path = $path ?? self::DEFAULT_DATA_DIR;
    }

    /**
     * Load file with emoji categories
     *
     * @param string $name (optional, default: labels.txt) Change if you want to load and test different file.
     * @param string $path (optional, default: common\properties) Change if you want to load file from a different folder.
     * @return string Name of the file
     */
    protected function getLabelFile(string $name = 'labels.txt', string $path = 'common' . DIRECTORY_SEPARATOR . 'properties') : string {
        $file = realpath($this->path . DIRECTORY_SEPARATOR . ($path ? $path . DIRECTORY_SEPARATOR : '') . $name);

        if (!file_exists($file)) {
            throw new \InvalidArgumentException("File $file not found. Please make sure the UNICODE CLDR package is extracted in data folder.");
        }

        return $file;
    }

    /**
     * Groups.txt uses semicolon as field separator so semicolon is encoded as another UNICODE character representing semicolon
     * @param string $string
     * @return string
     */
    protected static function fixSemicolon(string $string) : string {
        return str_replace(json_decode(self::SEMICOLON_PLACEHOLDER), ';', $string);
    }

    protected static function getGroupName(string $group) {
        $group = preg_replace('/\s+/', '', $group); //works as trim() but also between words
        $group = strtolower($group);
        $group = str_replace('&', '_', $group);
        $group = self::fixSemicolon($group);
        return $group;
    }

    public function parse() {
        $file = $this->getLabelFile();

        $data = file($file); //read whole file and parse it into array or rows
        $groups = [];

        foreach ($data as $row) {
            $row = trim($row);
            if ('' === $row || '#' === $row[0]) {
                continue; //this is empty row or a comment, ignore it
            }

            if (preg_match('/^\[(?<list>[^\]]+)\]\s+;(?<group>[^;]+);(?<type>.*)$/', $row, $matches)) {
                //fix $matches into better format
                $matches = (object)$matches; //allow to access as object
                $matches->group = self::getGroupName($matches->group);
                $matches->type = self::getGroupName($matches->type);
                $matches->list = self::fixSemicolon($matches->list);
                $matches->found = [];

                //check how many characters there are in the list
                $count = U::len($matches->list);

                echo 'Processing group ', $matches->group, ':', $matches->type, PHP_EOL;

                //Note: reading Nth character of UTF-8 string takes N^2 time (because it must be parsed char by char each time)
                //For that reason we will always split the string to 1st character and the rest
                //...until we reach the end (i.e. empty list)
                while (strlen($matches->list)) {
                    $char = U::sub($matches->list, 0, 1); //get first char
                    $matches->list = substr($matches->list, strlen($char)); //get the rest; process as single-byte string to process it faster

                    if (self::EMOJI_DERIVED_START === $char) { //process multi-character emoji
                        $end = strpos($matches->list, self::EMOJI_DERIVED_END);
                        $char = substr($matches->list, strlen(self::EMOJI_DERIVED_START)-1, $end - strlen(self::EMOJI_DERIVED_END) + 1); //get chars between start and end character
                        $matches->list = substr($matches->list, $end + strlen(self::EMOJI_DERIVED_END));
                        echo '  * Found multi-character emoji ', $char, ' (', U::len($char), ' chars: ';
                        $split = [];
                        for ($j = 0, $count = U::len($char); $j < $count; ++$j) {
                            $split[] = U::codepoint($char, $j);
                        }
                        echo implode(', ', $split), ')', PHP_EOL;
                        $matches->found[] = $char;
                    }
                    else if (strlen($matches->list) && self::EMOJI_SEQUENCE_CHAR === $matches->list[0]) { //process whole sequence of characters
                        $end = U::char($matches->list, self::EMOJI_SEQUENCE_CHAR);
                        echo '  * Processing emoji sequence ', $char, ' - ', $end, '(', U::codepoint($char), ' - ', U::codepoint($end), ')' . PHP_EOL;
                        $matches->list = U::sub($matches->list, self::EMOJI_SEQUENCE_CHAR . $end);

                        for ($j = U::ord($char), $k = U::ord($end); $j <= $k; ++$j) {
                            echo '    * Found emoji ', U::chr($j), ' (', U::codepoint($j), ')', PHP_EOL;
                            $matches->found[] = U::chr($j);
                        }
                    }
                    else { //just a single emoji
                        echo '  * Found emoji ', $char, ' (', U::codepoint($char), ')', PHP_EOL;
                        $matches->found[] = $char;
                    }
                }

                echo '  => Group contains ', count($matches->found), ' emoji', PHP_EOL;

                if (!array_key_exists($matches->group, $groups)) {
                    $groups[$matches->group] = [];
                }
                if (!array_key_exists($matches->type, $groups[$matches->group])) {
                    $groups[$matches->group][$matches->type] = [];
                }
                $groups[$matches->group][$matches->type] = array_merge($groups[$matches->group][$matches->type], $matches->found);
            }
            else {
                echo "ERROR: row has unexpected format: $row", PHP_EOL;
            }
        }

        echo PHP_EOL . 'Group recapitulation:' . PHP_EOL;
        foreach ($groups as $name => $group) {
            $count = 0;
            foreach ($group as $type) {
                $count += count($type);
            }
            echo '  Group ', $name, ' contains ', $count, ' emoji' . PHP_EOL;
        }

        echo PHP_EOL, 'Finished processing groups', PHP_EOL, 'hint: if your console does not support UTF-8 you can dump the output into a file and then open it with UTF-8 encoding to see the emoji.', PHP_EOL;

        return $groups;
    }
}