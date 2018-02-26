<?php

/**
 * @author Rusavskiy Vitaliy <rusavskiy.v@bintime.com>
 */
class varDumperCasper
{
    static public $depth = 15;
    static public $highlight = true;
    static private $isConsole = false;
    static private $backtrace = null;
    static private $dataStr;
    static private $id;

    public static function dumpAsString($var, $depth = 10, $highlight = false)
    {
        return CasperDumper::dumpAsString($var, $depth, $highlight);
    }

    static function dump($var, $name = '', $button = false)
    {
        if (false === YII_DEBUG) {
            return false;
        }

        self::$isConsole = empty($_SERVER['SERVER_NAME']);
        self::$id = mt_rand();

        if (function_exists('debug_backtrace')) {
            self::$backtrace = debug_backtrace();
        } else {
            self::$backtrace[0]['file'] = 'UNKNOWN FILE';
            self::$backtrace[0]['line'] = 'UNKNOWN LINE';
        }
        self::$backtrace[0]['name'] = $name;

        self::createData($var);
        self::view();
    }

    static private function createData($var)
    {
        if (self::$isConsole) {
            self::$dataStr = $var;
        } else {
            ob_start();
            CasperDumper::dump($var, self::$depth, self::$highlight);
            self::$dataStr = ob_get_clean();
        }
    }

    static private function view()
    {
        if (self::$isConsole) {
            self::viewConsole();
        } else {
            self::viewBlock();
        }
    }

    static private function viewBlock()
    {
        ?>
        <style>
            .debuger_casper {
                background: aliceblue;
                margin: 3px;
                border: 1px solid black;
                border-radius: 10px;
                padding: 10px;
                position: relative;
                z-index: 99999;
                word-wrap: break-word;
                font-size: 11px;
            }

            .debuger_casper p {
                margin: 10px 0;
                color: #000AFF;
                font-size: 13px;
            }

            .debuger_casper p span {
                background: #FFFF7B;
                border-radius: 10px;
                padding: 5px;
                font-weight: bold;
            }
        </style>
        <div class="debuger_casper">
            <p>
                <?php
                echo (self::$backtrace[0]['name'])
                    ? '<span>' . self::$backtrace[0]['name'] . '</span> '
                    : '';

                echo ' Dump - ' . self::$backtrace[0]['file']
                    . ' : <span>' . self::$backtrace[0]['line'] . '</span> '
                    . '<br>';
                ?>
            </p>
            <?php echo self::$dataStr; ?>
        </div>
        <?php
    }

    static private function viewConsole()
    {
        echo (self::$backtrace[0]['name'])
            ? self::$backtrace[0]['name']
            : '';

        echo ' Dump - ' . self::$backtrace[0]['file']
            . ':' . self::$backtrace[0]['line'];

        echo PHP_EOL;
        print_r(self::$dataStr);
        echo PHP_EOL;
    }
}

/**
 * varDumperCasper is intended to replace the buggy PHP function var_dump and print_r.
 * It can correctly identify the recursively referenced objects in a complex
 * object structure. It also has a recursive depth control to avoid indefinite
 * recursive display of some peculiar variables.
 *
 * varDumperCasper can be used as follows,
 * <pre>
 * varDumperCasper::dump($var);
 * </pre>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.utils
 * @since 1.0
 */
class CasperDumper
{
    private static $_objects;
    private static $_output;
    private static $_depth;

    /**
     * Displays a variable.
     * This method achieves the similar functionality as var_dump and print_r
     * but is more robust when handling complex objects such as Yii controllers.
     * @param mixed $var variable to be dumped
     * @param integer $depth maximum depth that the dumper should go into the variable. Defaults to 10.
     * @param boolean $highlight whether the result should be syntax-highlighted
     */
    public static function dump($var, $depth = 10, $highlight = false)
    {
        echo static::dumpAsString($var, $depth, $highlight);
    }

    /**
     * Dumps a variable in terms of a string.
     * This method achieves the similar functionality as var_dump and print_r
     * but is more robust when handling complex objects such as Yii controllers.
     * @param mixed $var variable to be dumped
     * @param integer $depth maximum depth that the dumper should go into the variable. Defaults to 10.
     * @param boolean $highlight whether the result should be syntax-highlighted
     * @return string the string representation of the variable
     */
    public static function dumpAsString($var, $depth = 10, $highlight = false)
    {
        self::$_output = '';
        self::$_objects = [];
        self::$_depth = $depth;
        self::dumpInternal($var, 0);
        if ($highlight) {
            $result = highlight_string("<?php\n" . self::$_output, true);
            self::$_output = preg_replace('/&lt;\\?php<br \\/>/', '', $result, 1);
        }

        return self::$_output;
    }

    /**
     * @param mixed $var variable to be dumped
     * @param integer $level depth level
     */
    private static function dumpInternal($var, $level)
    {
        switch (gettype($var)) {
            case 'boolean':
                self::$_output .= $var ? 'true' : 'false';
                break;

            case 'integer':
                self::$_output .= "$var";
                break;

            case 'double':
                self::$_output .= "$var";
                break;

            case 'string':
                self::$_output .= "'" . addslashes($var) . "'";
                break;

            case 'resource':
                self::$_output .= '{resource}';
                break;

            case 'NULL':
                self::$_output .= "null";
                break;

            case 'unknown type':
                self::$_output .= '{unknown}';
                break;

            case 'array':
                if (self::$_depth <= $level) {
                    self::$_output .= '[...]';
                } elseif (empty($var)) {
                    self::$_output .= '[]';
                } else {
                    $keys = array_keys($var);
                    $spaces = str_repeat(' ', $level * 4);
                    self::$_output .= '[';
                    foreach ($keys as $key) {
                        self::$_output .= "\n" . $spaces . '    ';
                        self::dumpInternal($key, 0);
                        self::$_output .= ' => ';
                        self::dumpInternal($var[$key], $level + 1);
                        self::$_output .= ',';
                    }
                    self::$_output .= "\n" . $spaces . ']';
                }
                break;

            case 'object':
                if (($id = array_search($var, self::$_objects, true)) !== false) {
                    self::$_output .= get_class($var) . '#' . ($id + 1) . '(...)';
                } elseif (self::$_depth <= $level) {
                    self::$_output .= get_class($var) . '(...)';
                } else {
                    $id = array_push(self::$_objects, $var);
                    $className = get_class($var);
                    $spaces = str_repeat(' ', $level * 4);
                    self::$_output .= "$className#$id\n" . $spaces . '(';
                    foreach ((array)$var as $key => $value) {
                        $keyDisplay = strtr(trim($key), "\0", ':');
                        self::$_output .= "\n" . $spaces . "    [$keyDisplay] => ";
                        self::dumpInternal($value, $level + 1);
                    }
                    self::$_output .= "\n" . $spaces . ')';
                }
                break;
        }
    }

    /**
     * Exports a variable as a string representation.
     *
     * The string is a valid PHP expression that can be evaluated by PHP parser
     * and the evaluation result will give back the variable value.
     *
     * This method is similar to `var_export()`. The main difference is that
     * it generates more compact string representation using short array syntax.
     *
     * It also handles objects by using the PHP functions serialize() and unserialize().
     *
     * PHP 5.4 or above is required to parse the exported value.
     *
     * @param mixed $var the variable to be exported.
     * @return string a string representation of the variable
     */
    public static function export($var)
    {
        self::$_output = '';
        self::exportInternal($var, 0);

        return self::$_output;
    }

    /**
     * @param mixed $var Variable to be exported.
     * @param integer $level Depth level.
     */
    private static function exportInternal($var, $level)
    {
        switch (gettype($var)) {
            case 'NULL':
                self::$_output .= 'null';
                break;

            case 'array':
                if (empty($var)) {
                    self::$_output .= '[]';
                } else {
                    $keys = array_keys($var);
                    $outputKeys = ($keys !== range(0, count($var) - 1));
                    $spaces = str_repeat(' ', $level * 4);
                    self::$_output .= '[';
                    foreach ($keys as $key) {
                        self::$_output .= "\n" . $spaces . '    ';
                        if ($outputKeys) {
                            self::exportInternal($key, 0);
                            self::$_output .= ' => ';
                        }
                        self::exportInternal($var[$key], $level + 1);
                        self::$_output .= ',';
                    }
                    self::$_output .= "\n" . $spaces . ']';
                }
                break;

            case 'object':
                self::$_output .= 'unserialize(' . var_export(serialize($var), true) . ')';
                break;

            default:
                self::$_output .= var_export($var, true);
                break;
        }
    }
}
