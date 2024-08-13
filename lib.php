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
 * Edu Sensor lib.
 *
 * @copyright  2023 Edunao SAS (contact@edunao.com)
 * @author     Rémi <remi.colet@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Recorders.
require_once(__DIR__ . '/classes/page_recorder.php');
require_once(__DIR__ . '/classes/null_page_recorder.php');
require_once(__DIR__ . '/classes/task_recorder.php');

// Handlers.
require_once(__DIR__ . '/classes/Handler/handler_interface.php');
require_once(__DIR__ . '/classes/Handler/html_handler.php');
require_once(__DIR__ . '/classes/Handler/apache_handler.php');

/**
 * return microsecond to millisecond.
 *
 * @param $duration
 * @return float
 */
function lib_edu_sensor_micro_to_milli($duration) {
    return floor($duration * 1000);
}
