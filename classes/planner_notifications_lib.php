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

use lytix_logs\logger;
use lytix_planner\notification_settings;

/**
 * Class planner_notifications_lib
 */
class planner_notifications_lib extends \external_api {

    /**
     * Checks course notification settings parameters.
     * @return \external_function_parameters
     */
    public static function store_course_notification_settings_parameters() {
        return new \external_function_parameters(
            array(
                'contextid'    => new \external_value(PARAM_INT, 'The context id for the course', VALUE_REQUIRED),
                'courseid' => new \external_value(PARAM_INT, 'The course ID', VALUE_REQUIRED),
                'jsonformdata' => new \external_value(PARAM_RAW, 'Data form from', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Checks course notification return values.
     * @return \external_single_structure
     */
    public static function store_course_notification_settings_returns() {
        return new \external_single_structure(
            [
                'success' => new \external_value(PARAM_BOOL, 'settings updated?', VALUE_REQUIRED),
            ]
        );
    }

    /**
     * Gets course notification settings and stores them in DB.
     * @param int $contextid
     * @param int $courseid
     * @param false|mixed|\stdClass $jsonformdata
     * @return bool[]
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public static function store_course_notification_settings($contextid, $courseid, $jsonformdata) {
        global $DB;
        $params  = self::validate_parameters(self::store_course_notification_settings_parameters(), [
            'contextid' => $contextid,
            'courseid' => $courseid,
            'jsonformdata' => $jsonformdata
        ]);

        // We always must call validate_context in a webservice.
        $context = \context::instance_by_id($params['contextid'], MUST_EXIST);
        self::validate_context($context);

        $data = array();
        $serialiseddata = json_decode($params['jsonformdata']);
        parse_str($serialiseddata, $data);
        if ($data['softlock']) {
            $starttime = (new \DateTime($data['start_time']['day'].'-'.$data['start_time']['month']. '-'
                .$data['start_time']['year']))->getTimestamp();

            $endtime = (new
            \DateTime($data['end_time']['day'].'-'.$data['end_time']['month']. '-'.$data['end_time']['year']))->getTimestamp();

            $settings = notification_settings::test_and_set_course($courseid);
            $settings->courseid = $courseid;
            $settings->start_time = $starttime;
            $settings->end_time = $endtime;

            $lang = current_language();

            $types = dynamic_events::get_event_types($courseid);
            $types = json_decode($types, true);

            $plannerevents = $DB->get_records('lytix_planner_events', ['courseid' => $courseid]);

            $counttypes = count($types[$lang]);
            for ($i = 0; $i < $counttypes; $i++) {
                if ($types[$lang][$i] == "Other" || $types[$lang][$i] == "Sonstige") {
                    continue;
                }

                $typenameen = str_replace(" ", "", $types['en'][$i]);

                // Delete this type.
                if ($data['delete' . $typenameen] == 1) {
                    unset($types['de'][$i]);
                    $types['de'] = array_values($types['de']);
                    unset($types['en'][$i]);
                    $types['en'] = array_values($types['en']);
                    $counttypes--;
                    $i--;
                    continue;
                }

                // Rename this type.
                foreach ($plannerevents as $event) {
                    if ($event->type == $types['en'][$i] . '_' . $types['de'][$i]) {
                            $event->type =
                                $data['english' . $typenameen] . '_' . $data['german' . $typenameen];
                    }
                    $DB->update_record('lytix_planner_events', $event);
                }
                $types['en'][$i] = $data['english' . $typenameen];
                $types['de'][$i] = $data['german' . $typenameen];
            }

            $settings->types = json_encode($types);

            $success = $DB->update_record('lytix_planner_crs_settings', $settings);

            if ($data['new_type'] == 1) {
                if (!in_array($data['select_other_english'], $types['en']) ||
                    !in_array($data['select_other_german'], $types['de'])) {
                    dynamic_events::set_event_types($courseid, $data['select_other_german'], $data['select_other_english']);
                }
            }
        }
        return [
            'success' => (bool)$success,
        ];
    }
}
