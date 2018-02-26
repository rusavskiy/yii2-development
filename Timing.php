<?php
/**
 * @author Rusavskiy Vitaliy <rusavskiy.v@bintime.com>
 */

/**
 * The class is designed for time measurement of execution parts of the code.
 */
class Timing
{
    /**
     * Checkpoints data.
     *
     * @var array
     */
    private static $data = [];
    /**
     * Contains information about start time for checkpoints.
     *
     * @var array
     */
    private static $startStopData = [];

    /**
     * Remove checkpoint by identifier.
     *
     * @param string $identifier Checkpoint identifier.
     *
     * @return boolean
     */
    public static function clear($identifier = '')
    {
        if (!empty($identifier)) {
            self::$data[$identifier] = [];
        } else {
            self::$data = [];
        }

        return true;
    }

    /**
     * Perform initialization of component.
     *
     * @param string  $identifier Identifier for checkpoint.
     * @param boolean $return Indicate whether time must be returned.
     *
     * @throws InvalidParamException If checkpoint identifier is empty.
     * @return float|string
     */
    public static function init($identifier = '', $return = false)
    {
        if (is_numeric($identifier) || $identifier) {
            if (empty(self::$data[$identifier])) {
                list($msec, $sec) = explode(chr(32), microtime());
                self::$data[$identifier]['time'] = $sec + $msec;
                self::$data[$identifier]['memory'] = memory_get_usage(true);
            } else {
                list($msec, $sec) = explode(chr(32), microtime());
                $time = round(($sec + $msec) - self::$data[$identifier]['time'], 5);
                $memory = memory_get_usage(true) - self::$data[$identifier]['memory'];


                if (!empty($_SERVER['REQUEST_URI'])) {
                    $time = "<pre style='background: rgb(139, 255, 227);color: rgb(0, 51, 255);font-weight: bold;'>"
                        . 'Timing: ' . $identifier . ' -> '
                        . $time . '{sec} ' . number_format($memory, 0, ' ', '.') . '{memory}</pre>';
                } else {
                    $time = 'Timing: ' . $identifier . ' -> ' . $time . '{sec} '
                        . number_format($memory, 0, ' ', '.') . '{memory}' . PHP_EOL;
                }

                if ($return) {
                    return $time;
                } else {
                    echo $time;
                }
            }
        } else {
            throw new InvalidParamException('empty identifier');
        }
    }

    /**
     * Start time checkpoint.
     *
     * @param string $identifier Identifier for checkpoint.
     *
     * @return void
     * @author Vadym Stepanov <vadim.stepanov.ua@gmail.com>
     */
    public static function start($identifier)
    {
        list($msec, $sec) = explode(chr(32), microtime());
        self::$startStopData[$identifier]['time'] = $sec + $msec;
    }

    /**
     * Return total time from start checkpoint by identifier.
     *
     * @param string $identifier Identifier for checkpoint.
     *
     * @return float
     * @author Vadym Stepanov <vadim.stepanov.ua@gmail.com>
     */
    public static function stop($identifier)
    {
        if (!isset(self::$startStopData[$identifier])) {
            return 0;
        }

        list($msec, $sec) = explode(chr(32), microtime());

        return ($sec + $msec) - self::$startStopData[$identifier]['time'];
    }

    /**
     * Format duration in seconds with using specified precision
     *
     * @param float $time Duration in [seconds].[milliseconds].
     *
     * @return string
     * @author Vadym Stepanov <vadym.stepanov@bintime.com>
     * @date 27.03.2017
     */
    public static function formatDurationSimple($time, $precision = 5)
    {
        return \number_format($time, $precision, '.', '');
    }
}
