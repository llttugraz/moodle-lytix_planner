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

/**
 * Helper class for the events with groups.
 */
class group_helper {

    /**
     * Checks if the user is in the corresponding group for the event.
     * @param int $courseid
     * @param int $userid
     * @param \stdClass|null $event
     * @return bool
     */
    public static function check_group($courseid, $userid, $event) {
        if ($event->mgroup == '0' || $event->mgroup == 0) {
            return true;
        } else {
            $usrgroups = groups_get_user_groups($courseid, $userid);
            $usrgroups = reset($usrgroups);
            foreach ($usrgroups as $usrgroup) {
                if ($usrgroup == (int)$event->mgroup) {
                    return true;
                }
            }
        }
        return false;
    }
}
