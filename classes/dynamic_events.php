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
 * @package   lytix_planner
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_planner;

use lytix_planner\notification_settings;

/**
 * Class dynamic_events
 */
class dynamic_events {
    /**
     * Gets event types.
     * @param int $courseid
     * @return false|mixed|\stdClass
     * @throws \dml_exception
     */
    public static function get_event_types($courseid) {
        $settings = notification_settings::test_and_set_course($courseid);
        return $settings->types;
    }

    /**
     * Sets a new event type.
     * @param int $courseid
     * @param string $typegerman
     * @param string $typeenglish
     * @throws \dml_exception
     */
    public static function set_event_types($courseid, $typegerman, $typeenglish) {
        global $DB;
        // TODO check if type already exists. If not retrun new types, else return false.
        $record      = notification_settings::test_and_set_course($courseid);
        $types       = json_decode($record->types, true);
        array_push($types['en'], $typeenglish);
        array_push($types['de'], $typegerman);

        $newtypes     = [
            'de' => $types['de'],
            'en' => $types['en'],
        ];
        $record->types = json_encode($newtypes);

        $DB->update_record('lytix_planner_crs_settings', $record);
    }
}
