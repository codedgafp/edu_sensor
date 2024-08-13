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

namespace handler;

/**
 * Apache handler
 *
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     Rémi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class apache_handler implements handler_interface {
    /**
     *  Add new task log
     *
     * @param \stdClass $logdata
     * @return void
     */
    public function write($logdata): void {
        $logresult = '[MENTOR_SENSOR] ';
        $logresult .= $logdata->page . ' ';
        $logresult .= $logdata->userid . ' ';
        $logresult .= 'TOTAL(' . $logdata->duration . ')';
        foreach ($logdata->tasks as $taskname => $taskdata) {
            $logresult .= ',' . $this->get_task_data($taskdata, $taskname);
        }
        error_log($logresult);
    }

    /**
     *  Get task data to log format
     *
     * @param array $taskdata
     * @param string $taskname
     * @return string
     */
    public function get_task_data($taskdata, $taskname) {
        if ($taskdata['justoncetologs']) {
            return $taskname . '(' . $taskdata['duration'] . ')';
        }

        $result = $taskname . '(';
        $counttaskdata = count(\page_recorder::$taskdata);

        foreach (\page_recorder::$taskdata as $key => $taskname) {
            $result .= $taskdata[$taskname];

            if ($key + 1 < $counttaskdata) {
                $result .= ',';
            }
        }

        $result .= ')';

        return $result;
    }
}
