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
 * @package    lytix_planner
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_planner;

use lytix_helper\course_settings;

/**
 * Class notification_settings
 */
class notification_settings {

    /**
     * Get the default types when no record exits for this course.
     * @return false|string
     */
    public static function get_default_types() {
        $optionsgerman = ['Vorlesung', 'Quiz', 'Aufgabe', 'Feedback', 'Prüfung', 'Abgabegespräch', 'Sonstiges'];
        $optionsenglish = ['Lecture', 'Quiz', 'Assignment', 'Feedback', 'Exam', 'Interview', 'Other'];
        $stdtypes         = [
            'de' => $optionsgerman,
            'en' => $optionsenglish,
        ];
        return json_encode($stdtypes);
    }

    /**
     * Tests and sets settings for course.
     * @param int $courseid
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    public static function test_and_set_course($courseid) {
        global $DB;
        $table    = 'lytix_planner_crs_settings';
        $settings = $DB->get_record($table, ['courseid' => $courseid]);
        // Create default settings for this course.
        if (!$settings) {
            $settings                                 = new \stdClass();
            $settings->courseid                       = $courseid;

            $settings->start_time                     = course_settings::getcoursestartdate($courseid)->getTimestamp();
            $settings->end_time                       = course_settings::getcourseenddate($courseid)->getTimestamp();

            $settings->types = self::get_default_types();

            $settings->id = $DB->insert_record($table, $settings);
        }
        return $settings;
    }

    /**
     * Tests and sets settings for user.
     * @param int $courseid
     * @param int $userid
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    public static function test_and_set_user($courseid, $userid) {
        global $DB;
        $table    = 'lytix_planner_usr_settings';
        $settings = $DB->get_record($table, ['courseid' => $courseid, 'userid' => $userid]);
        // Create default settings for this course.
        if (!$settings) {
            $settings                                 = new \stdClass();
            $settings->courseid                       = $courseid;
            $settings->userid                         = $userid;
            $crssett = self::test_and_set_course($courseid);
            $settings->types = $crssett->types;

            $settings->id = $DB->insert_record($table, $settings);
        }
        return $settings;
    }

    /**
     * Gets or creates a new event_completed record
     * @param int $eventid
     * @param int $courseid
     * @param int $userid
     * @param int $completed
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    public static function test_and_set_event_comp($eventid, $courseid, $userid, $completed = 0) {
        global $DB;
        $table    = 'lytix_planner_event_comp';
        $settings = $DB->get_record($table, ['eventid' => $eventid, 'courseid' => $courseid, 'userid' => $userid]);
        // Create default settings for this course.
        if (!$settings) {
            $settings               = new \stdClass();
            $settings->eventid      = $eventid;
            $settings->courseid     = $courseid;
            $settings->userid       = $userid;
            $settings->completed    = $completed;
            $settings->timestamp    = (new \DateTime('now'))->getTimestamp();

            $settings->id = $DB->insert_record($table, $settings);
        }

        if ((int)$settings->completed != (int)$completed) {
            $settings->completed = $completed;
        }

        return $settings;
    }

    /**
     * Gets startdate.
     * @param int $courseid
     * @return mixed
     * @throws \dml_exception
     */
    public static function getcoursestartdate($courseid) {
        $settings = self::test_and_set_course($courseid);
        return $settings->start_time;
    }

    /**
     * Gets enddate.
     * @param int $courseid
     * @return mixed
     * @throws \dml_exception
     */
    public static function getcourseenddate($courseid) {
        $settings = self::test_and_set_course($courseid);
        return $settings->end_time;
    }
}
