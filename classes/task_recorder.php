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
 * Use this class for add recorder task time execution in your project.
 *
 * Initialize $MONITORINGRECORDER global with new PageRecorder() in enter site.
 * After add initialisation TaskRecorder object in part of code where could be record task time execution.
 */

require_once(__DIR__ . '/../lib.php');

/**
 * Task recorder class
 */
class task_recorder {
    /**
     * Task name.
     *
     * @var string $taskname
     */
    private string $taskname;

    /**
     * Start time task execution.
     *
     * @var float|null $starttime
     */
    private $starttime;

    /**
     * Page recorder object.
     *
     * @var page_recorder_interface $recorder
     */
    private page_recorder_interface $recorder;

    public bool $justonce;

    /**
     * Task recorder construct.
     *
     * @param string $taskname
     * @throws Exception
     */
    public function __construct($taskname, $justonce = false) {
        global $MONITORINGRECORDER;

        // Monitoring recorder not init.
        if (!isset($MONITORINGRECORDER)) {
            // No record.
            $MONITORINGRECORDER = new null_page_recorder();
        }

        // Init data.
        $this->taskname = $taskname;
        $this->starttime = microtime(true);
        $this->recorder = $MONITORINGRECORDER;
        $this->justonce = $justonce;
    }

    /**
     * Task recorder destruct.
     * Call when function create object finish execution.
     */
    public function __destruct() {
        $this->stop();
    }

    public function stop() {
        if (!$this->starttime) {
            return;
        }

        // End time task execution.
        $endtime = microtime(true);

        // Time task execution duration.
        $duration = $endtime - $this->starttime;

        // Add time task execution to monitoring recorder log.
        $this->recorder->add_log($this->taskname, $duration, $this->justonce);

        $this->starttime = null;
    }
}
