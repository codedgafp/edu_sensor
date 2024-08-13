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
 * Html handler
 *
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     Rémi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class html_handler implements handler_interface {

    /**
     *  Add new task log
     *
     * @param \stdClass $logdata
     * @return void
     */
    public function write($logdata): void {
        $logresult = 'Page : ' . $logdata->page;
        $logresult .= ', duration : ' . $logdata->duration;
        $logresult .= ', median ; ' . $logdata->median;
        $logresult .= ', min : ' . $logdata->min . ', max : ' . $logdata->max;
        foreach ($logdata->tasks as $taskname => $taskdata) {
            $logresult .= $this->get_task_data($taskdata, $taskname);
        }
        echo $logresult;
    }

    /**
     *  Get task data to log format
     *
     * @param array $taskdata
     * @param string $taskname
     * @return string
     */
    public function get_task_data($taskdata, $taskname) {
        $result = '';

        foreach (\page_recorder::$taskdata as $key) {
            $result .= ',' . $taskname . '_' . $key . '=' . $taskdata[$key];
        }

        return $result;
    }
}
