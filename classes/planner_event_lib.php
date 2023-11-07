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
use lytix_logs\logger;
use lytix_planner\dynamic_events;

/**
 * Class planner_event_lib
 */
class planner_event_lib extends external_api {
    /**
     * Checks planner event parameters.
     *
     * @return \external_function_parameters
     */
    public static function planner_event_parameters() {
        return new \external_function_parameters(
                array(
                        'contextid'    => new \external_value(PARAM_INT, 'The context id for the course', VALUE_REQUIRED),
                        'jsonformdata' => new \external_value(PARAM_RAW, 'The data from the milestone form (json).', VALUE_REQUIRED)
                )
        );
    }

    /**
     * Checks planner event return values.
     *
     * @return \external_single_structure
     */
    public static function planner_event_returns() {
        return new \external_single_structure(
                [
                        'success' => new \external_value(PARAM_BOOL, 'Milestone updated / inserted', VALUE_REQUIRED)
                ]
        );
    }

    /**
     * Creates, updates or deletes planner event.
     *
     * @param int                   $contextid
     * @param false|mixed|\stdClass $jsonformdata
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public static function planner_event($contextid, $jsonformdata) {
        global $DB, $USER, $COURSE;
        $params = self::validate_parameters(self::planner_event_parameters(), [
                'contextid'    => $contextid,
                'jsonformdata' => $jsonformdata
        ]);

        $lang = current_language();

        // We always must call validate_context in a webservice.
        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);

        $data           = array();
        $serialiseddata = json_decode($params['jsonformdata']);
        parse_str($serialiseddata, $data);

        $types = dynamic_events::get_event_types($COURSE->id);
        $types = json_decode($types, true);

        $startdate = (new
        \DateTime($data['startdate']['day'] . '-' . $data['startdate']['month'] . '-' . $data['startdate']['year']));
        $enddate   = (new
        \DateTime($data['startdate']['day'] . '-' . $data['startdate']['month'] . '-' . $data['startdate']['year']));

        $startdate->setTime($data['startdate']['hour'], $data['startdate']['minute']);
        $data['startdate'] = $startdate->getTimestamp();

        $enddate->setTime($data['enddatehour'], $data['enddateminute']);
        $data['enddate'] = $enddate->getTimestamp();

        $data['text'] = ((object) $data)->text['text'];
        $data['room'] = ((object) $data)->room['text'];
        $data['send'] = 0;

        if (key_exists('select_other_german', $data) && key_exists('select_other_english', $data)) {
            if (!in_array($data['select_other_english'], $types['en']) ||
                !in_array($data['select_other_german'], $types['de'])) {
                dynamic_events::set_event_types($COURSE->id, $data['select_other_german'], $data['select_other_english']);
                $types          = dynamic_events::get_event_types($COURSE->id);
                $types          = json_decode($types, true);
                $data['type']   = end($types['en']) . '_' . end($types['de']);
                $data['marker'] = $data['type'][0];
            } else {
                $index        = array_search($data['select_other_english'], $types['en']);
                $data['type'] = $types['en'][$index] . '_' . $types['de'][$index];
            }
        } else {
            $index          = $data['type'];
            $data['type']   = $types['en'][$index] . '_' . $types['de'][$index];
            $data['marker'] = $types[$lang][$index][0];
        }

        if ($data['id'] != -1 && $data['delete'] == 1) {
            $success = $DB->delete_records('lytix_planner_events', ['id' => $data['id']]);
            if ($success) {
                logger::add($USER->id, $data['courseid'], $params['contextid'], logger::TYPE_DELETE, logger::TYPE_EVENT,
                            $data['id']);
            }
        } else if ($data['id'] != -1) {
            $success = $DB->update_record('lytix_planner_events', (object) $data);
            if ($success) {
                logger::add($USER->id, $data['courseid'], $params['contextid'], logger::TYPE_EDIT, logger::TYPE_EVENT,
                            $data['id']);
            }
        } else {
            $success = $DB->insert_record('lytix_planner_events', (object) $data);
            if ($success) {
                logger::add($USER->id, $data['courseid'], $params['contextid'], logger::TYPE_ADD, logger::TYPE_EVENT, $success);
            }
        }

        for ($i = 1; $i <= 5; $i++) {
            if ($data['moreevents']) {
                $startdatename = 'date' . $i;
                $enddatename   = 'endtime' . $i;
                $enddatehour   = $enddatename . 'hour';
                $enddateminute = $enddatename . 'minute';

                if ($data[$startdatename]['enabled']) {
                    $startdate = (new
                    \DateTime(
                        $data[$startdatename]['day'] . '-' . $data[$startdatename]['month'] . '-' . $data[$startdatename]['year']));
                    $enddate   = (new
                    \DateTime(
                        $data[$startdatename]['day'] . '-' . $data[$startdatename]['month'] . '-' . $data[$startdatename]['year']));

                    $startdate->setTime($data[$startdatename]['hour'], $data[$startdatename]['minute']);
                    $data['startdate'] = $startdate->getTimestamp();
                    $enddate->setTime($data[$enddatehour], $data[$enddateminute]);
                    $data['enddate'] = $enddate->getTimestamp();

                    $success = $DB->insert_record('lytix_planner_events', (object) $data);
                    if ($success) {
                        logger::add(
                            $USER->id, $data['courseid'], $params['contextid'], logger::TYPE_ADD, logger::TYPE_EVENT, $success);
                    }
                }
            }
        }

        return [
                'success' => (bool) $success,
        ];
    }

    /**
     * Checks planner event completed parameters.
     *
     * @return \external_function_parameters
     */
    public static function planner_event_completed_parameters() {
        return new \external_function_parameters(
                array(
                        'contextid'    => new \external_value(PARAM_INT, 'The context id for the course'),
                        'jsonformdata' => new \external_value(PARAM_RAW, 'The data from the milestone form (json).')
                )
        );
    }

    /**
     * Checks planner event completed return values.
     *
     * @return \external_single_structure
     */
    public static function planner_event_completed_returns() {
        return new \external_single_structure(
                [
                        'success' => new \external_value(PARAM_BOOL, 'Milestone updated / inserted', VALUE_REQUIRED)
                ]
        );
    }

    /**
     * Updates planner event as completed by inserting in DB lytix_planner_event_comp.
     *
     * @param int                   $contextid
     * @param false|mixed|\stdClass $jsonformdata
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public static function planner_event_completed($contextid, $jsonformdata) {
        global $DB;
        $params = self::validate_parameters(self::planner_event_completed_parameters(), [
                'contextid'    => $contextid,
                'jsonformdata' => $jsonformdata
        ]);

        // We always must call validate_context in a webservice.
        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);

        $data           = array();
        $serialiseddata = json_decode($params['jsonformdata']);
        parse_str($serialiseddata, $data);

        if ($DB->record_exists('lytix_planner_event_comp', ['eventid' => $data['eventid'], 'courseid' => $data['courseid'],
                                                            'userid'  => $data['userid']])) {
            $record = $DB->get_record('lytix_planner_event_comp', ['eventid'  => $data['eventid'],
                                                                   'courseid' => $data['courseid'], 'userid' => $data['userid']]);

            $record->completed = $data['completed'];
            $record->timestamp = $data['timestamp'];
            $success           = $DB->update_record('lytix_planner_event_comp', $record);
            if ($success) {
                logger::add($data['userid'], $data['courseid'], $params['contextid'], logger::TYPE_EDIT, logger::TYPE_EVENT,
                            $data['eventid']);
            }
        } else {
            unset($data['id']);
            unset($data['title']);
            unset($data['text']);
            unset($data['date']);
            $success = (bool) $DB->insert_record('lytix_planner_event_comp', (object) $data);
            if ($success) {
                logger::add($data['userid'], $data['courseid'], $params['contextid'], logger::TYPE_EDIT, logger::TYPE_EVENT,
                            $data['eventid']);
            }
        }

        return [
                'success' => (bool) $success,
        ];
    }

    /**
     * Checks planner event completed parameters.
     *
     * @return \external_function_parameters
     */
    public static function planner_delete_event_parameters() {
        return new \external_function_parameters(
                [
                        'contextid' => new \external_value(PARAM_INT, 'Context Id', VALUE_REQUIRED),
                        'courseid'  => new \external_value(PARAM_INT, 'Course Id', VALUE_REQUIRED),
                        'userid'    => new \external_value(PARAM_INT, 'User Id', VALUE_REQUIRED),
                        'id'        => new \external_value(PARAM_INT, 'Event entry Id', VALUE_REQUIRED),
                ]
        );
    }

    /**
     * Checks planner event completed return values.
     *
     * @return \external_single_structure
     */
    public static function planner_delete_event_returns() {
        return new \external_single_structure(
                [
                        'success' => new \external_value(PARAM_BOOL, 'Event deleted', VALUE_REQUIRED)
                ]
        );
    }

    /**
     * Deletes planner event.
     *
     * @param int $contexid
     * @param int $courseid
     * @param int $userid
     * @param int $id
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public static function planner_delete_event($contexid, $courseid, $userid, $id) {
        global $DB;

        $params = self::validate_parameters(self::planner_delete_event_parameters(), [
                'contextid' => $contexid,
                'courseid'  => $courseid,
                'userid'    => $userid,
                'id'        => $id
        ]);

        // We always must call validate_context in a webservice.
        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);

        $success = $DB->delete_records('lytix_planner_events', [
                'id'       => $params['id'],
                'courseid' => $params['courseid']]);

        if ($success) {
            logger::add($params['userid'], $params['courseid'], $params['contextid'], logger::TYPE_DELETE,
                        logger::TYPE_EVENT, $params['id']);
        }

        return [
                'success' => $success,
        ];
    }
}
