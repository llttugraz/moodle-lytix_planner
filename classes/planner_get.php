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
 * This is a one-line short description of the file.
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    lytix_planner
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_planner;

use external_api;

/**
 * Class planner_get
 */
class planner_get extends external_api {
    /**
     * Checks parameters.
     *
     * @return \external_function_parameters
     */
    public static function service_parameters() {
        return new \external_function_parameters(
                [
                        'id'        => new \external_value(PARAM_INT, 'CourseId', VALUE_REQUIRED),
                        'contextid' => new \external_value(PARAM_INT, 'ContextId', VALUE_REQUIRED),
                        'isstudent' => new \external_value(PARAM_BOOL, 'IsStudent', VALUE_REQUIRED),
                ]
        );
    }

    /**
     * Checks return values.
     *
     * @return \external_single_structure
     */
    public static function service_returns() {
        return new \external_single_structure(
            [
                'startDate' => new \external_value(PARAM_INT, 'CourseId', VALUE_OPTIONAL),
                'endDate' => new \external_value(PARAM_INT, 'CourseId', VALUE_OPTIONAL),
                'items' => new \external_multiple_structure(
                    new \external_single_structure(
                        [
                            'id' => new \external_value(PARAM_INT, 'ID of event', VALUE_REQUIRED),
                            'courseid' => new \external_value(PARAM_INT, 'ID of course', VALUE_REQUIRED),
                            'userid' => new \external_value(PARAM_INT, 'ID of course', VALUE_OPTIONAL),
                            'type' => new \external_value(PARAM_TEXT, 'Type of event', VALUE_OPTIONAL),
                            'marker' => new \external_value(PARAM_TEXT, 'Sign/character of marker', VALUE_REQUIRED),
                            'startdate' => new \external_value(PARAM_INT, 'Date of activity', VALUE_REQUIRED),
                            'enddate' => new \external_value(PARAM_INT, 'Date of activity', VALUE_REQUIRED),
                            'mgroup' => new \external_value(PARAM_INT, 'Group id for the event', VALUE_OPTIONAL),
                            'title' => new \external_value(PARAM_RAW, 'Title of activity', VALUE_REQUIRED),
                            'text' => new \external_value(PARAM_RAW, 'Description', VALUE_REQUIRED),
                            'room' => new \external_value(PARAM_RAW, 'Description', VALUE_OPTIONAL),
                            'completed' => new \external_value(PARAM_BOOL, 'Completion of activity', VALUE_REQUIRED),
                            'visible' => new \external_value(PARAM_BOOL, 'Visibility of activity', VALUE_REQUIRED),
                            'mandatory' => new \external_value(PARAM_BOOL, 'Mandatory of activity', VALUE_REQUIRED),
                            'graded' => new \external_value(PARAM_BOOL, 'Grading of activity', VALUE_REQUIRED),
                            'moffset' => new \external_value(PARAM_INT, 'Send offset', VALUE_OPTIONAL),
                            'moption' => new \external_value(PARAM_TEXT, 'Send option', VALUE_OPTIONAL),
                            'gradeitem' => new \external_value(PARAM_TEXT, 'Description', VALUE_OPTIONAL),
                            'send' => new \external_value(PARAM_TEXT, 'Points of activity', VALUE_OPTIONAL),
                            'countcompleted' => new \external_value(
                                PARAM_INT, 'How many students have completed the event', VALUE_OPTIONAL),
                        ], '', VALUE_OPTIONAL
                    ), '', VALUE_OPTIONAL
                ),
            ]
        );
    }

    /**
     * Gets data for planner.
     *
     * @param int $id
     * @param int $contextid
     * @param bool|int $isstudent
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public static function service($id, $contextid, $isstudent) {
        global $DB, $CFG, $USER, $COURSE;
        require_once($CFG->dirroot . '/calendar/lib.php');

        $params = self::validate_parameters(self::service_parameters(), [
                'id'        => $id,
                'contextid' => $contextid,
                'isstudent' => $isstudent
        ]);

        $lang = current_language();

        // We always must call validate_context in a webservice.
        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);

        $plannerobj = [];

        $plannerobj['startDate'] = notification_settings::getcoursestartdate($id);
        $plannerobj['endDate']   = notification_settings::getcourseenddate($id);

        $plannerevents     = $DB->get_records('lytix_planner_events', ['courseid' => $id]);
        $plannermilestones = $DB->get_records('lytix_planner_milestone', ['courseid' => $id, 'userid' => $USER->id]);

        foreach ($plannerevents as $key => $event) {
            if ($isstudent) {
                if (!group_helper::check_group($COURSE->id, $USER->id, $event)) {
                    unset($plannerevents[$key]);
                    continue;
                }
            }
            list($en, $de) = explode('_', $event->type);
            if ($lang == 'en') {
                $event->type = $en;
            } else {
                $event->type = $de;
            }
            $record = $DB->get_record('lytix_planner_event_comp', ['eventid'  => $event->id,
                                                                   'courseid' => $event->courseid, 'userid' => $USER->id]);
            // Count how many students have completed this event.
            $eventcompletedby = $DB->count_records_sql("
                SELECT COUNT(*)
                FROM {lytix_planner_event_comp} eventcomp
                WHERE eventcomp.eventid= $event->id AND eventcomp.courseid = $event->courseid AND eventcomp.completed = 1");

            $event->countcompleted = $eventcompletedby;

            if ($record && $record->completed) {
                $event->completed = 1;
                $event->color     = '#7ac943'; // Green.
            } else {
                $event->completed = 0;
            }
            if (!isset($event->graded)) {
                $event->graded = 0;
            }
        }

        foreach ($plannermilestones as $milestone) {
            list($en, $de) = explode('_', $milestone->type);
            if ($lang == 'en') {
                $milestone->type = $en;
            } else {
                $milestone->type = $de;
            }
            $milestone->visible   = 1;
            $milestone->mandatory = 0;
            $milestone->graded    = 0;
            $milestone->send    = 0;
        }

        $events              = array_merge($plannerevents, $plannermilestones);
        $plannerobj['items'] = $events;

        return $plannerobj;
    }
}
