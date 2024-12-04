<?php

namespace Qwwwest\Namaskar;

/**
 * Infini
 * this class allows us to transform safely a text based configuration file into
 * a PHP object to handle them.
 *
 */


class ZenConfig
{

    private $filename = null;
    private $pseudofilename = null;

    public $parsed = [];
    private $externals = [];
    private $currentline = 0;
    private $overwrite;
    private $pairs = [
        '[' => ']',
        '{' => '}',
        '"' => '"',
        "'" => "'",
        '"""' => '"""',
        "'''" => "'''",
        '<<<' => '>>>',

    ];

    public function __construct($overwrite = true)
    {
        $this->overwrite = $overwrite;
    }

    /**
     * error
     * function to outputting errors
     * @param  mixed $message
     * @param  mixed $line
     * @return void
     */
    private function error($message, $line = '')
    {
        $file = $this->filename ? basename($this->filename) : null;
        if ($file) {
            $linenumber = $this->currentline + 1;
        } else {
            $file = $this->pseudofilename;
            $linenumber = $this->currentline + 1;
        }

        die("Infini, Error in parsing $file at line $linenumber: $message<br>$line");
        //die('');
    }

    /**
     * addFile
     * function to parse a string into an Infini Object
     * @param  string $file
     * @param  string $fileIsOptional
     * @return bool (file added or not)
     */
    public function addFile($file, $fileIsOptional = false)
    {
        if (!\is_file($file)) {
            if ($fileIsOptional)
                return false;
            die("File not found: $file");
        }

        $str = file_get_contents($file);
        $this->filename = $file;
        $this->parseString($str);

        return true;
    }

    /**
     * parseString
     * function to parse a string into an Infini Object
     * @param  mixed $str
     * @param  mixed $externals
     * @param  mixed $pseudofilename
     * @return array
     */
    public function parseString($str, &$externals = [], $pseudofilename = false)
    {
        $ini = &$this->parsed;
        $varname = $openchar = $value = $closechar = $type = null;
        $supersection = &$this->parsed;
        $this->externals = $externals;
        $closecharregex = '';
        if ($pseudofilename) {
            $this->pseudofilename = $pseudofilename;
        }

        $lines = explode("\n", $str);

        $nblines = count($lines);
        $mode = 0; // 1 = multiline mode;

        $this->currentline = 0;
        for ($i = &$this->currentline; $i < $nblines; $i++) {

            $line = $lines[$i];
            //one line mode
            if ($mode === 0) {

                // [[super-section]]
                if (preg_match('/^\s*\[\[([A-Za-z0-9_\.]+)\]\]\s*$/', $line, $matches)) {

                    $sections = explode('.', $matches[1]);

                    $supersection = &$this->parsed;
                    foreach ($sections as $ii => $section) {

                        if (!isset($supersection[$section])) {
                            $supersection[$section] = [];
                        }
                        if (gettype($supersection[$section]) != 'array') {
                            $this->error("Section $section already defined as a variable before", $line);
                        }
                        $supersection = &$supersection[$section];
                    }
                    //$supersection = &$ini;
                    continue;
                }

                // [section]
                if (preg_match('/^\s*\[([A-Za-z0-9_\.]+)(\[])?\]\s*$/', $line, $matches)) {
                    if ($matches[1] === '...') {
                        //[...] go back to global section
                        $ini = &$this->parsed;
                        continue;
                    }
                    $sections = explode('.', $matches[1]);
                    // sections like [toto.titi[]] will add an element to the array titi.
                    $pushArray = (isset($matches[2]));

                    $ini = &$supersection;
                    foreach ($sections as $ii => $section) {

                        if (!isset($ini[$section])) {
                            $ini[$section] = [];
                        }

                        if (gettype($ini[$section]) != 'array') {
                            $this->error("Section $section already defined as a variable before", $line);
                        }
                        $ini = &$ini[$section];
                    }
                    if ($pushArray) {
                        $ini[] = [];
                        $ini = &$ini[count($ini) - 1];

                    }
                    continue;
                }

                // skip one line comments and empty lines line
                if (preg_match("/^\s*(#.*|\/\/.*)?$/", $line) === 1) {
                    continue;
                }
                /*
                skip multiline comments
                 */

                if (preg_match("/^\s*\/\*/", $line, $matches)) {

                    $mode = 2;
                    $value = '';

                    $closecharregex = '/^\*\/\s*$/';

                    continue;
                }

                // name:type: value
                // start multiline mode
                // (?:\[])?
                if (preg_match('/^\s*([A-Za-z0-9_\.]+(?:\[])?)(:[a-z]+)?\s*:\s+([{\[]|"""|<<<)\s*$/', $line, $matches)) {

                    $mode = 1;
                    $varname = $matches[1];

                    $type = $matches[2];
                    $openchar = $matches[3];
                    $value = '';

                    $closechar = $this->pairs[$matches[3]] ?? false;
                    if ($closechar === false) {
                        $this->error('invalid opening char for multiline value', $line);
                    }
                    $closecharregex = '/^' . preg_quote($closechar) . '\s*$/';

                    continue;
                }
                // name:type = 'value'; // oneliner;
                if (preg_match('/^\s*([A-Za-z0-9_\.]+)(:[a-z]+)?\s*:\s*(.+)\s*$/', $line, $matches)) {
                    $varname = $matches[1];
                    $type = $matches[2];
                    $value = $matches[3];

                    //TODO make sure overwriting is OK
                    if (isset($ini[$varname]) && !$this->overwrite) {
                        $this->error("$varname already defined !!!", $line);
                    }

                    $this->setVar($varname, $ini, $this->parseValue($value, $type, $i), $i);
                    //$ini[$varname] = $this->parseValue($value,$type,$i);
                    continue;
                }

                echo "mode=$mode";
                $this->error('syntax error', $line);
            }
            if ($mode === 1) //multiline
            {
                if (preg_match($closecharregex, $line) === 1) {
                    //match end of multiline

                    $mode = 0;
                    $this->setVar($varname, $ini, $this->parseValue($openchar . $value . $closechar, $type, $i), $i);
                    // $ini[$varname] = $this->parseValue($openchar . $value . $closechar, $type, $i);
                    continue;
                } else {

                    $value .= $line . "\n";
                    continue;
                }
                // $i++;
                //die("error line $i: $line");
            }
            if ($mode === 2 && (preg_match($closecharregex, $line) === 1)) //multiline comments
            {
                $mode = 0;
            }
        }

        return $this->parsed;
    }

    /**
     * __invoke
     * to use the object name as a shortcut
     * $config("?plop.plip"); // true or false
     * $config("!plop.plip"); // die if foes not exist
     * $config("plop.plip"); // returns the value
     * $config("plop.plip", value); // set the value
     * @return mixed
     */
    public function __invoke()
    {
        $args = func_get_args();

        if (count($args) === 1 && strpos($args[0], '?') === 0) {
            $sub = substr($args[0], 1);
            return $this->isset($sub);
        }

        if (count($args) === 1 && strpos($args[0], '!') === 0) {
            $sub = substr($args[0], 1);
            return !$this->isset($sub);
            // if (!$this->isset($sub)) {
            //     die($sub . " must exist");
            // }
            // ;
        }

        if (count($args) === 0) {
            return $this->parsed;
        }
        if (count($args) === 1) {
            return $this->value($args[0]);
        }
        if (count($args) === 2) {
            // if(is_array( $args[1]) )$this->setVar($args[0], $this->parsed, null, 0); 
            return $this->setVar($args[0], $this->parsed, $args[1]);
        }
        dump($args);
        die('__invoke count argument mismatch in ZenConfig');
    }

    public function &array_merge_recursive_distinct(array &$array1, array &$array2)
    {

        if (isset($array2['_addByPush'])) {
            unset($array2['_addByPush']);

            array_push($array1, ...$array2);
            return $array1;
        }


        //special case : numeric arrays are replaced
        $isNumeric = $this->IsArrayKeyNumericSequentialZeroBased($array1)
            && $this->IsArrayKeyNumericSequentialZeroBased($array2);
        if ($isNumeric) {
            return $array2;
        }

        $merged = &$array1;
        foreach ($array2 as $key => &$value) {
            // if($reset && isset($value[0])) {
            //     $merged[$key] = $value;
            // }
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    public function IsArrayKeyNumericSequentialZeroBased($InputArray)
    {
        if (!is_array($InputArray)) {
            return false;
        }

        if (count($InputArray) <= 0) {
            return true;
        }

        return array_keys($InputArray) === range(0, count($InputArray) - 1);
    }

    /**
     * setVar
     * create the data structure from something like "plop.plip = 3"
     * @param  mixed $varname  ex: plop.plip
     * @param  mixed $ini
     * @param  mixed $value
     * @param  mixed $reset
     * @return mixed
     */
    private function setVar($varname, &$ini, $value)
    {
        $forceArray = substr($varname, -2) === '[]';
        if ($forceArray) {
            $varname = substr($varname, 0, -2);



        }


        $vars = explode('.', $varname);

        $last = count($vars) - 1;
        foreach ($vars as $ii => $var) {

            if ($ii < $last && !isset($ini[$var])) {

                $ini[$var] = [];
            }
            if ($ii < $last && gettype($ini[$var]) === 'array') {

                $ini = &$ini[$var];
                continue;
            }
            if ($ii < $last && gettype($ini[$var]) === 'object') {

                $ini = &$ini[$var];
                continue;
            }
            if ($ii === $last && !$forceArray && gettype($ini) === 'object') {

                return $ini->{$var} = $value;
            }

            if ($ii === $last && $forceArray && gettype($ini) === 'object') {

                if (!isset($ini->{$var}))
                    $ini->{$var} = [];

                ($ini->{$var})[] = $value;

                return $ini->{$var};
            }

            if ($ii === $last && $forceArray && (!isset($ini[$var]))) {
                $ini[$var] = ['_addByPush' => '', $value];

                return $ini[$var];
            }

            if ($ii === $last && $forceArray) {
                ($ini[$var])[] = $value;

                return $ini[$var];
            }


            if ($ii === $last && (!isset($ini[$var]))) {
                return $ini[$var] = $value;
            }



            // we mix it with existing array
            if (
                $ii === $last
                && gettype($ini[$var]) === 'array'
                && gettype($value) === 'array'
            ) {

                $merged = $this->array_merge_recursive_distinct($ini[$var], $value);
                $ini[$var] = $merged;
                return $merged;
            }

            // we mix it with existing object
            if ($ii === $last && gettype($ini[$var]) === 'object' && gettype($value) === 'object') {
                foreach ($value as $prop => $val) {
                    if (property_exists($this, $prop)) {
                        $ini->{$prop} = $val;
                    }

                }
                return $ini[$var];
            }

            //TODO: add an option to force redefining values...
            if ($this->overwrite) {
                return $ini[$var] = $value;
            } else {
                $this->error("varname ($varname), $var already defined before", '');
            }

        }
    }

    /**
     * value
     * return the value ex "menu.navbar.fr"
     * @param  mixed $varname
     * @return mixed
     */
    public function value($varname)
    {
        if ($varname === 'true') {
            return true;
        }

        if ($varname === 'false') {
            return false;
        }

        //blep: literal string value "'toto'" => "toto" 
        if (strlen($varname) > 1 && substr($varname, 0, 1) === "'" && substr($varname, -1) === "'") {
            return substr($varname, 1, -1);
        }

        $vars = explode('.', $varname);
        //$ini = & $this->parsed;
        $last = count($vars) - 1;
        $ini = &$this->parsed;
        foreach ($vars as $ii => $var) {

            if ($var === '$' && $ii === 0) {

                $ini = &$this->externals;
                continue;
            }

            if ($ii < $last && gettype($ini) === 'array') {

                // if (!isset($ini[$var])) {
                //     return null;
                // }
                if (!($ini[$var] ?? false)) {
                    return null;
                }

                $ini = &$ini[$var];
                continue;
            }

            if ($ii < $last && gettype($ini) === 'object') {

                if (!($ini->{$var} ?? false)) {
                    return null;
                }

                $ini = &$ini->{$var};

                continue;
            }
            if ($ii === $last && gettype($ini) === 'object') {
                return $ini->{$var} ?? null;
            }
            if ($ii === $last && gettype($ini) === 'array') {
                return $ini[$var] ?? null;
            }
        }
    }

    /**
     * isset
     * return whether a variable exist
     * @param  mixed $varname
     * @return true|false
     */
    function isset($varname)
    {
        $vars = explode('.', $varname);
        //$ini = & $this->parsed;
        $last = count($vars) - 1;
        $ini = &$this->parsed;

        foreach ($vars as $ii => $var) {

            if ($var === '$' && $ii === 0) {

                $ini = &$this->externals;
                continue;
            }

            if ($ii < $last && !isset($ini[$var])) {
                return false;
            }
            if ($ii < $last && gettype($ini[$var]) === 'array') {

                $ini = &$ini[$var];
                continue;
            }

            if ($ii === $last) {
                return isset($ini[$var]);
            }
        }
        return false;
    }

    /**
     * parseValue
     *
     * @param  string $value
     * @return mixed return PHP data (int, bool, string,
     *  multilined string, obj, array...)
     */
    private function parseValue($value)
    {
        $value = trim($value);

        // true
        if ($value === 'true') {
            return true;
        }

        // false
        if ($value === 'false') {
            return false;
        }

        // null
        if ($value === 'null') {
            return null;
        }

        // float
        if (preg_match('/^[0-9]*\.[0-9]+$/', $value) && settype($value, 'float')) {
            return $value;
        }

        // integer
        if (preg_match('/^[0-9]+$/', $value) && settype($value, 'int')) {
            return $value;
        }

        if (preg_match('/^([$]?[A-Za-z0-9_\.]+)$/', $value)) {
            return $this->value($value);
        }

        if (preg_match('/^"""(.*)"""$/s', $value, $matches)) {

            // string

            return trim($matches[1]);
        }

        if (preg_match('/^<<<(.*)>>>$/s', $value, $matches)) {

            // string

            return trim($matches[1]);
        }

        if (preg_match('/^(.)(.*)(.)$/s', $value, $matches)) {

            // string
            if ($matches[1] === $matches[3] && ($matches[1] === '"' || $matches[1] === "'")) {
                return $matches[2];
            }

            // json
            if (
                ($matches[1] === '{' && $matches[3] === "}")
                || ($matches[1] === '[' && $matches[3] === "]")
            ) {
                // this add double quotes to keys:
                $regex = "/({|,)(?:\s*)(?:')?([a-z_]+)(?:')?(?:\s*):/"; // look for object names


                $value = preg_replace($regex, "$1\"$2\":", $value); // all object names should be double quoted



                // remove optional "," at the end of json arrays
                $value = preg_replace('/,\s*\]$/m', ']', trim($value));


                $json = json_decode($value, true);

                if ($json === null) {
                    dd($value);
                    $this->error("parsing error in value '$value'", '');
                }

                return $json;
            }

            //literal strings
            return $value;

            // $this->error("invalid type for $value", '');
        }
    }
}