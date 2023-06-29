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
 * Cron tasks
 *
 * @package    lytix_planner
 * @category   task
 * @author     Guenther Moser
 * @copyright  2020 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$tasks = array(
    // Cronjob to store the click events into the DB.
    array(
        'classname' => 'lytix_planner\task\send_planner_notifications',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '5',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),
    array(
        'classname' => 'lytix_planner\task\send_tug_report_notifications',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '5',
        'day' => '*',
        'dayofweek' => '6',
        'month' => '*'
    ),
);
