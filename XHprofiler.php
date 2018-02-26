<?php
/**
 * Class XHprofCasper
 * User: Rusavskiy Vitaliy <rusavskiy.v@bintime.com>
 *
 * @date 13.11.17
 */
class XHprofiler
{
    static public $pathXhprof;
    static public $id;
    static public $showLinks = true;
    static public $beginLink = 'http://xhprof';
    static public $flags;
    static public $ignoredFunctions = [];//['call_user_func', 'call_user_func_array'];

    static private $status = false;

    /**
     * Start profiling
     *
     * @return void
     */
    static public function start()
    {
        if (extension_loaded('xhprof')) {
            if (self::$status === true) {
                throw new \RuntimeException('XHprofiler was running!');
            }

            $xhprofRoot = self::$pathXhprof ?: realpath(\Yii::getAlias('@vendor') . '/../../xhprof');
            include_once $xhprofRoot . "/xhprof_lib/utils/xhprof_lib.php";
            include_once $xhprofRoot . "/xhprof_lib/utils/xhprof_runs.php";

            // ignore builtin functions and call_user_func* during profilingD
            xhprof_enable(self::$flags ?: XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY, self::$ignoredFunctions);
            self::$status = true;
        }
    }

    /**
     * Stop profiling
     *
     * @return void
     */
    static public function stop()
    {
        if (extension_loaded('xhprof')) {
            if (self::$status === false) {
                throw new \RuntimeException('XHprofiler has not been started!');
            }

            $xhprofData = xhprof_disable();
            $xhprofRuns = new \XHProfRuns_Default();
            $run_id = $xhprofRuns->save_run($xhprofData, self::$id ?: \Yii::$app->id);

            $params = sprintf('?run=%s&source=%s', $run_id, \Yii::$app->id);
            $profilerUrl = self::$beginLink . '/xhprof_html/index.php' . $params;
            $graphUrl = self::$beginLink . '/xhprof_html/callgraph.php' . $params;

            if (self::$showLinks && isset($_SERVER['HTTP_HOST'])) {
                echo '<a href="' . $profilerUrl . '" target="_blank">Profiler output</a><br/>
                    <a href="' . $graphUrl . '" target="_blank">Profiler graph</a><br/>';
            }

            if (self::$showLinks && isset($_SERVER['SHELL'])) {
                echo 'Profiler - ' . $profilerUrl . "\nGraph - " . $graphUrl . "\n";
            }

            self::$status = false;
        }
    }
}
