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
 * Plugin library functions.
 *
 * @package    lytix_planner
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * load event completed form
 * @param false|mixed|\stdClass $args
 * @return string
 */
function lytix_planner_output_fragment_new_event_completed_form($args) {
    $mform = new \lytix_planner\forms\event_completed_form(null, $args);

    return $mform->render();
}

/**
 * load new event form
 * @param false|mixed|\stdClass $args
 * @return string
 */
function lytix_planner_output_fragment_new_event_form($args) {
    $mform = new \lytix_planner\forms\event_form(null, $args);

    return $mform->render();
}

/**
 * load new milestone form
 * @param false|mixed|\stdClass $args
 * @return string
 */
function lytix_planner_output_fragment_new_milestone_form($args) {
    $mform = new \lytix_planner\forms\milestone_form(null, $args);

    return $mform->render();
}

/**
 * load new course notification settings form
 * @param false|mixed|\stdClass $args
 * @return string
 */
function lytix_planner_output_fragment_new_course_notification_settings_form($args) {
    $mform = new \lytix_planner\forms\course_notification_settings_form(null, $args);

    return $mform->render();
}

/**
 * load new user notification settings form
 * @param false|mixed|\stdClass $args
 * @return string
 */
function lytix_planner_output_fragment_new_user_notification_settings_form($args) {
    $mform = new \lytix_planner\forms\user_notification_settings_form(null, $args);

    return $mform->render();
}
