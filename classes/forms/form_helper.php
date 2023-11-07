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

namespace lytix_planner\forms;

defined('MOODLE_INTERNAL') || die();

global $CFG;

// Moodleform is defined in formslib.php.
require_once("$CFG->libdir/formslib.php");

/**
 * Class for the planner forms.
 */
class form_helper {

    /**
     * Gets number of days of the $month.
     *
     * @param int $month
     * @param int $year
     * @return int
     */
    public static function getmonthdays($month, $year) {
        return $month == 2
                ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29)))
                : (($month - 1) % 7 % 2 ? 30 : 31);
    }

    /**
     * Get the groups for this course.
     *
     * @param \stdClass|null $course
     * @return array
     */
    private static function get_groups_options($course) {
        $groups  = groups_get_all_groups($course->id);
        $options = [];
        if ($groups) {
            $options[0] = get_string('no_group', 'lytix_planner');;
            foreach ($groups as $group) {
                $options[$group->id] = $group->name;
            }
        } else {
            $options[0] = get_string('no_group', 'lytix_planner');
        }
        return $options;
    }

    /**
     * Sets the enddate.
     *
     * @param \moodleform $mform
     * @param int            $startdate
     * @param string         $groupname
     * @param int            $enddate
     */
    public static function set_enddate($mform, $startdate, $groupname, $enddate) {

        $data = $mform->getElementValue($startdate);
        if ($enddate) {
            $defaulthour   = date("G", $enddate);
            $defaultminute = date("i", $enddate);
        } else {
            $defaulthour   = $data['hour'][0] + 1;
            $defaultminute = $data['minute'][0];
        }
        $mform->setDefault($groupname . 'hour', $defaulthour);
        $mform->setDefault($groupname . 'minute', $defaultminute);
    }

    /**
     * Sets the endtime.
     *
     * @param \moodleform $mform
     * @param string         $groupname
     */
    public static function set_endtime_selector($mform, $groupname) {
        $component = 'lytix_planner';

        $timearr     = self::get_hours_minutes_array();
        $hours       = $timearr['hours'];
        $minutes     = $timearr['minutes'];
        $timearray   = array();
        $timearray[] =& $mform->createElement('select', $groupname . 'hour', get_string('set_hour', $component), $hours);
        $timearray[] =& $mform->createElement('select', $groupname . 'minute', get_string('set_minute', $component), $minutes);
        $mform->addGroup($timearray, $groupname, get_string('set_endtime', $component), array(' '), false);
        $mform->addHelpButton($groupname, 'set_endtime', $component);
    }

    /**
     * Returns an array of hours and minutes.
     *
     * @return array
     */
    public static function get_hours_minutes_array() {
        $hours = array();
        foreach (range(0, 23, 1) as $hour) {
            if ($hour < 10) {
                array_push($hours, "0" . $hour);
            } else {
                array_push($hours, $hour);
            }
        }
        $minutes = array();
        foreach (range(0, 59, 1) as $minute) {
            if ($minute < 10) {
                array_push($minutes, "0" . $minute);
            } else {
                array_push($minutes, $minute);
            }
        }

        $timearray['hours']   = $hours;
        $timearray['minutes'] = $minutes;

        return $timearray;
    }

    /**
     * Creates the group select for this event.
     *
     * @param \moodleform $mform
     * @param \stdClass|null $course
     * @param int            $group
     * @throws \coding_exception
     */
    public static function add_groups_selector($mform, $course, $group = 0) {

        $component = 'lytix_planner';
        $options   = self::get_groups_options($course);

        $select = $mform->addElement('select', 'mgroup',
                                     get_string('set_select_group', $component), $options);
        $select->setSelected($group);
        $select->setMultiple(false);
        $mform->addHelpButton('mgroup', 'set_select_group', $component);
        $mform->disabledIf('mgroup', 'softlock', 'notchecked');
    }
}
