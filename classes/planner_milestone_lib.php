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

/**
 * Class planner_milestone_lib
 */
class planner_milestone_lib extends external_api {
    /**
     * Checks parameters.
     * @return \external_function_parameters
     */
    public static function planner_milestone_parameters() {
        return new \external_function_parameters(
            array(
                'contextid'    => new \external_value(PARAM_INT, 'The context id for the course', VALUE_REQUIRED),
                'jsonformdata' => new \external_value(PARAM_RAW, 'The data from the milestone form (json).', VALUE_REQUIRED)
            )
        );
    }

    /**
     * Checks return value.
     * @return \external_single_structure
     */
    public static function planner_milestone_returns() {
        return new \external_single_structure(
            [
                'success' => new \external_value(PARAM_BOOL, 'Milestone updated / inserted', VALUE_REQUIRED)
            ]
        );
    }

    /**
     * Gets milestone data.
     * @param int $contextid
     * @param false|mixed|\stdClass $jsonformdata
     * @return bool[]
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public static function planner_milestone($contextid, $jsonformdata) {
        global $DB;
        $params  = self::validate_parameters(self::planner_milestone_parameters(), [
            'contextid' => $contextid,
            'jsonformdata' => $jsonformdata
        ]);

        // We always must call validate_context in a webservice.
        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);

        $data = array();
        $serialiseddata = json_decode($params['jsonformdata']);
        parse_str($serialiseddata, $data);

        $startdate = (new
        \DateTime($data['startdate']['day'] . '-' . $data['startdate']['month'] . '-' . $data['startdate']['year']));
        $enddate = (new
        \DateTime($data['startdate']['day'] . '-' . $data['startdate']['month'] . '-' . $data['startdate']['year']));

        $startdate->setTime($data['startdate']['hour'], $data['startdate']['minute']);
        $data['startdate'] = $startdate->getTimestamp();

        $enddate->setTime($data['hour'], $data['minute']);
        $data['enddate'] = $enddate->getTimestamp();

        $data['text'] = ((object) $data)->text['text'];

        if ($data['id'] != -1) {
            $data['type'] = "Milestone_Meilenstein";
            $data['marker'] = 'M';
            $success = $DB->update_record('lytix_planner_milestone', (object)$data);
            if ($success) {
                logger::add($data['userid'], $data['courseid'], $params['contextid'], logger::TYPE_EDIT,
                    logger::TYPE_MILESTONE, $data['id']);
            }
        } else {
            $data['type'] = "Milestone_Meilenstein";
            $data['marker'] = 'M';
            $success = $DB->insert_record('lytix_planner_milestone', (object)$data);
            if ($success) {
                logger::add($data['userid'], $data['courseid'], $params['contextid'], logger::TYPE_ADD,
                    logger::TYPE_MILESTONE, $success);
            }
        }

        return [
            'success' => (bool)$success,
        ];
    }

    /**
     * Checks planner milestone completed parameters.
     *
     * @return \external_function_parameters
     */
    public static function planner_delete_milestone_parameters() {
        return new \external_function_parameters(
                [
                        'contextid' => new \external_value(PARAM_INT, 'Context Id', VALUE_REQUIRED),
                        'courseid' => new \external_value(PARAM_INT, 'Course Id', VALUE_REQUIRED),
                        'userid' => new \external_value(PARAM_INT, 'User Id', VALUE_REQUIRED),
                        'id' => new \external_value(PARAM_INT, 'Milestone entry Id', VALUE_REQUIRED),
                ]
        );
    }

    /**
     * Checks planner milestone completed return values.
     *
     * @return \external_single_structure
     */
    public static function planner_delete_milestone_returns() {
        return new \external_single_structure(
                [
                        'success' => new \external_value(PARAM_BOOL, 'Milestone deleted', VALUE_REQUIRED)
                ]
        );
    }

    /**
     * Deletes planner milestone.
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
    public static function planner_delete_milestone($contexid, $courseid, $userid, $id) {
        global $DB;

        $params  = self::validate_parameters(self::planner_delete_milestone_parameters(), [
                'contextid' => $contexid,
                'courseid' => $courseid,
                'userid' => $userid,
                'id' => $id
        ]);

        // We always must call validate_context in a webservice.
        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);

        $success = $DB->delete_records('lytix_planner_milestone', [
                'id' => $params['id'],
                'courseid' => $params['courseid']]);

        if ($success) {
            logger::add($params['userid'], $params['courseid'], $params['contextid'], logger::TYPE_DELETE,
                        logger::TYPE_MILESTONE, $params['id']);
        }

        return [
                'success' => $success,
        ];
    }
}
