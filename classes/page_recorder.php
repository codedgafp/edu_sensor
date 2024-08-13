<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     Rémi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Use this class for calculate page time execution and
 * for add recorder task time execution in your.
 *
 * Initialize $MONITORINGRECORDER global with new PageRecorder() in enter site.
 */
require_once __DIR__ . '/page_recorder_interface.php';
require_once __DIR__ . '/Handler/handler_interface.php';
require_once __DIR__ . '/../lib.php';

/**
 * PageRecorder class.
 */
class page_recorder implements page_recorder_interface {

    /**
     * Time condition when all url log record.
     */
    public const CONDITION_RECORD_FOR_THIS_TIME = 'record_for_this_time';

    /**
     * Start time page execution.
     *
     * @var float $startTime
     */
    public $starttime;

    /**
     * All time logs by category.
     *
     * @var array $logs
     */
    public $logs;

    /**
     * Say if task call just once.
     *
     * @var bool[] $justoncetologs
     */
    public $justoncetologs;

    /**
     * Min age execution by task.
     *
     * @var int[] $minbytask
     */
    public $minbytask;

    /**
     * Max time execution by task.
     *
     * @var int[] $maxbytask
     */
    public $maxbytask;

    /**
     * Page information.
     *
     * @var string $page
     */
    public $page;

    /**
     * Time page execution.
     *
     * @var int $pageduration
     */
    public $pageduration;

    /**
     * Count all task log.
     * Use for median.
     *
     * @var int $countlog
     */
    public $countlog;

    /**
     * Count task logs by millisecond time execution.
     * use for median.
     *
     * @var array $countlogsbymillisecond
     */
    public $countlogsbymillisecond;

    /**
     * Count task execution by millisecond.
     *
     * @var array $counttaskbymillisecond
     */
    public $counttaskbymillisecond;

    /**
     * Logs handler name.
     *
     * @var string[]
     */
    public $handlersname;

    /**
     * Logs handler.
     *
     * @var handler\handler_interface[] $handlers
     */
    public $handlers;

    /**
     * List url when recording log.
     *
     * @var string[]
     */
    public $urlscondition;

    /**
     * maximum time not to be exceeded or page is logged.
     *
     * @var int
     */
    public $timecondition;

    /**
     * Task data list
     *
     * @var string[] $taskdata
     */
    public static $taskdata
        = [
            'duration',
            'count',
            'median',
            'min',
            'max',
        ];

    /**
     * PageRecorder construct.
     */
    public function __construct() {
        global $CFG;

        // Init data.
        $this->starttime = microtime(true);
        $this->logs = [];
        $this->handlersname = $CFG->sensorhandlers;
        $this->urlscondition = $CFG->sensorrequireurls;
        $this->timecondition = $CFG->sensortimecondition;
        $this->justoncetologs = [];
        $this->countlogsbymillisecond = [];
        $this->counttaskbymillisecond = [];
        $this->minbytask = [];
        $this->maxbytask = [];
        $this->countlog = 0;
    }

    /**
     * Checks if the url satisfies the specified conditions
     * If yes, adds id as argument if it exists
     *
     * @return bool
     */
    public function check_urls() {
        // Get URL.
        $url = $_SERVER['SCRIPT_NAME'];

        // Checks if the url satisfies the specified conditions.
        foreach ($this->urlscondition as $starturl) {
            if (!str_contains($url, $starturl)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Set url for log data.
     *
     * @return void
     */
    public function set_url_page() {
        $this->page = $_SERVER['SCRIPT_NAME'];

        // Add id to url argument.
        if (isset($_GET['id'])) {
            $this->page .= '?id=' . $_GET['id'];
        }
    }

    /**
     * Set all handler.
     *
     * @return void
     * @throws moodle_exception
     */
    public function set_handlers(): void {
        foreach ($this->handlersname as $handlername) {
            $this->set_handler($handlername);
        }
    }

    /**
     * Set handler
     *
     * @param string $handlername
     * @return void
     * @throws \moodle_exception
     */
    public function set_handler(string $handlername): void {

        // Check if file existe.
        $handlerurl = __DIR__ . '/Handler/' . $handlername . '_handler.php';
        if (!file_exists($handlerurl)) {
            var_dump($handlerurl);
            throw new \moodle_exception('Handler file not found : ' . $handlerurl);
        }

        // Require file.
        require_once($handlerurl);

        // Check if class exist.
        $handlerclassname = '\\handler\\' . $handlername . '_handler';
        if (!class_exists($handlerclassname)) {
            throw new \InvalidArgumentException("The controller '$handlername' has not been defined.");
        }

        // Instance handler.
        $this->handlers[] = new $handlerclassname();
    }

    /**
     * Add new task log
     * Check if time is min or max value
     * Add time information for median
     *
     * @param string $taskname
     * @param float $duration
     * @param bool $justonce
     * @return void
     */
    public function add_log(string $taskname, float $duration, bool $justonce): void {
        $this->justoncetologs[$taskname] = $justonce;

        if ($justonce) {
            $this->logs[$taskname] = floor($duration * 1000);
        } else {
            // Add task log tile execution by task name.
            $this->logs[$taskname][] = $duration;

            // Check if log task time is min or max value.
            $this->check_min_and_max($duration, $taskname);
        }

        // Add log task time information for median.
        $this->update_count_log($duration, $taskname);
    }

    /**
     * Check if value is min or max time.
     *
     * @param float $duration
     * @return void
     */
    public function check_min_and_max(float $duration, $taskname): void {

        // Init max time by task.
        if (!isset($this->maxbytask[$taskname])) {
            $this->maxbytask[$taskname] = $duration;
        }

        // Update max time by task.
        if ($duration > $this->maxbytask[$taskname]) {
            $this->maxbytask[$taskname] = $duration;
        }

        // Init min time by task.
        if (!isset($this->minbytask[$taskname])) {
            $this->minbytask[$taskname] = $duration;
        }

        // Update min time by task.
        if ($duration < $this->minbytask[$taskname]) {
            $this->minbytask[$taskname] = $duration;
        }
    }

    /**
     * Update count logs
     * Use for median
     *
     * @param $duration
     * @return void
     */
    public function update_count_log($duration, $taskname): void {
        $roundduration = floor($duration * 100);

        // Init count logs for this duration.
        if (!isset($this->countlogsbymillisecond[$roundduration])) {
            $this->countlogsbymillisecond[$roundduration] = 0;
        }

        // Update count logs for this duration.
        $this->countlogsbymillisecond[$roundduration]++;

        if (!isset($this->counttaskbymillisecond[$taskname][$roundduration])) {
            $this->counttaskbymillisecond[$taskname][$roundduration] = 0;
        }

        $this->counttaskbymillisecond[$taskname][$roundduration]++;

        // Update all count log.
        $this->countlog++;
    }

    /**
     * Calculate median for all time task log.
     *
     * @return int
     */
    public function get_median($countbymillisecond, $counttask): int {
        ksort($countbymillisecond);
        // Median position in "$countbymillisecond" array.
        $medianposition = round($counttask / 2);

        // Search median value.
        foreach ($countbymillisecond as $millisecond => $count) {
            // Substract to median position numbers element for this time execution.
            $medianposition -= $count;

            // If duration is in median position.
            if ($medianposition < 1) {
                return $millisecond;
            }
        }

        // No value.
        return 0;
    }

    /**
     * Get all data log.
     *
     * @return stdClass
     */
    public function get_log_data() {
        global $USER;

        $logdata = new stdClass();
        $logdata->page = $this->page;
        $logdata->userid = $USER->id ?? 'NULL';
        $logdata->duration = $this->pageduration;
        $logdata->median = $this->get_median($this->countlogsbymillisecond, $this->countlog);
        $logdata->tasks = [];
        foreach ($this->logs as $taskname => $log) {
            if ($this->justoncetologs[$taskname]) {
                $logdata->tasks[$taskname] = [
                    'justoncetologs' => true,
                    'duration' => $log,
                ];
                continue;
            }

            $logdata->tasks[$taskname] = [
                'justoncetologs' => false,
                'duration' => floor(array_sum($log) * 1000),
                'count' => count($log),
                'median' => $this->get_median(
                    $this->counttaskbymillisecond[$taskname],
                    count($log)
                ),
                'min' => floor($this->minbytask[$taskname] * 1000),
                'max' => floor($this->maxbytask[$taskname] * 1000),
            ];
        }

        return $logdata;
    }

    /**
     * Send log to handlers.
     *
     * @return void
     */
    public function send_log(): void {
        $logdata = $this->get_log_data();

        foreach ($this->handlers as $handler) {
            $handler->write($logdata);
        }
    }

    /**
     * Call when execution page is finish.
     *
     * @throws moodle_exception
     */
    public function __destruct() {
        // Check if 'REQUEST_URI' data exist.
        if (!isset($_SERVER['REQUEST_URI'])) {
            return;
        }

        // Execution page time and convert micro second to millisecond.
        $this->pageduration = lib_edu_sensor_micro_to_milli(
            microtime(true) - $this->starttime
        );

        // Check if no send log.
        if (!$this->check_urls() && $this->timecondition > $this->pageduration) {
            return;
        }

        // Set url page data for log.
        $this->set_url_page();

        // Setting handlers.
        $this->set_handlers();

        // Send log.
        $this->send_log();
    }
}
