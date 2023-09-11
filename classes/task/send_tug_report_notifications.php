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
 *
 *  *
 * @package    lytix_planner
 * @category   task
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_planner\task;

use lytix_planner\notification_email;

/**
 * Class send_tug_report_notifications
 */
class send_tug_report_notifications extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('cron_send_planner_report_notifications', 'lytix_planner');
    }

    /**
     * Execute Task.
     *
     * @throws \dml_exception
     */
    public function execute() {
        if (get_config('local_lytix', 'platform') == 'learners_corner') {

            $courseids = explode(',', get_config('local_lytix', 'course_list'));
            foreach ($courseids as $courseid) {
                if (!$courseid) {
                    continue;
                }
                $sender = new notification_email();
                $sender->mail_monthly_report((int)$courseid);
            }
        }
    }
}
