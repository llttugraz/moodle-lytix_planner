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
 * Testcases for lytix_planner inactive users
 *
 * @package    lytix_planner
 * @category   test
 * @author     Guenther Moser
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_planner;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../classes/email_quota_exception.php');

use advanced_testcase;
use lytix_helper\dummy;

/**
 * Class notification_email_settings_test
 * @coversDefaultClass \lytix_planner\notification_email
 * @group learners_corner
 */
class notification_email_settings_test extends advanced_testcase {

    /**
     * Variable for course.
     *
     * @var \stdClass|null
     */
    private $course = null;

    /**
     * Variable for settings.
     *
     * @var false|mixed|\stdClass|null
     */
    private $settings = null;

    /**
     * Setup called before any test case.
     */
    public function setUp(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $now = new \DateTime('now');
        // Prevent monthly reports.
        set_config('semester_start', $now->format('Y-m-d'), 'local_lytix');

        // TODO create course.
        $this->course   = $this->getDataGenerator()->create_course();
        $this->settings = notification_settings::test_and_set_course($this->course->id);
        $this->settings->enable_course_notifications = 1;
        $this->settings->enable_user_customization = 1;
        $DB->update_record('lytix_planner_crs_settings', $this->settings);
    }

    /**
     * Executes planner_event_email_for_course and planner_milestone_email_for_course tasks.
     *
     * @param \stdClass $course
     * @throws \dml_exception
     */
    public function execute_task($course) {
        $sender = new notification_email();
        $sender->planner_notifications($course->id);
    }

    /**
     * Changes the setting defined by $setting to $value
     *
     * @param false|mixed|\stdClass $setting
     * @param false|mixed|\stdClass $value
     * @throws \dml_exception
     */
    private function change_setting($setting, $value) {
        global $DB;

        $this->settings->$setting = $value;
        $DB->update_record('lytix_planner_crs_settings', $this->settings);
    }

    /**
     * Changes the usser setting defined by $setting to $value
     *
     * @param int                   $userid
     * @param false|mixed|\stdClass $setting
     * @param false|mixed|\stdClass $value
     * @throws \dml_exception
     */
    private function change_user_setting($userid, $setting, $value) {
        global $DB;

        $usersettings           = notification_settings::test_and_set_user($this->course->id, $userid);
        $usersettings->$setting = $value;
        $DB->update_record('lytix_planner_usr_settings', $usersettings);
    }

    /**
     * Changes the offset.
     * @param string $offset
     * @return false|string
     */
    private function change_types_offset($offset) {

        $settingstypes  = json_decode(notification_settings::get_default_types());
        $countsettingstypes = count($settingstypes->offset);
        for ($i = 0; $i < $countsettingstypes; $i++) {
            $settingstypes->offset[$i] = $offset;
        }
        return json_encode($settingstypes);
    }

    /**
     * Changes the option.
     * @param string $options
     * @return false|string
     */
    private function change_types_options($options) {

        $settingstypes  = json_decode(notification_settings::get_default_types());
        $countsettingstypes = count($settingstypes->options);
        for ($i = 0; $i < $countsettingstypes; $i++) {
            $settingstypes->options[$i] = $options;
        }
        return json_encode($settingstypes);
    }

    /**
     * Tests if an email is sent if the event is within the offset specified by the student and is not completed.
     * @covers ::planner_notifications
     * @covers \lytix_planner\notification_settings::test_and_set_user
     * @covers \lytix_planner\notification_settings::get_default_types
     * @throws \dml_exception
     */
    public function test_email_users_not_completed_offset_setting_by_user() {
        global $DB;

        $today = new \DateTime('now');
        date_add($today, date_interval_create_from_date_string('2 days'));

        dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $today->getTimestamp(),
            $today->getTimestamp(), 'Lecture 1', 'Text 1', 'HS G', 1, 0, 0, 0);
        $user = $this->getDataGenerator()->create_user(array('email' => 'user1@example.org'));

        $role = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id, $role->id);

        $settingstypes = $this->change_types_offset('1');

        $this->change_setting("enable_user_customization", 1);
        $this->change_user_setting($user->id, "types", $settingstypes);

        $sink = $this->redirectEmails();

        $this->execute_task($this->course);

        $messages = $sink->get_messages();
        $this->assertEquals(0, count($messages), "no mail should have been sent to 1 user cause outside offset");

        $settingstypes = $this->change_types_offset('5');
        $this->change_user_setting($user->id, "types", $settingstypes);

        $sink = $this->redirectEmails();

        $this->execute_task($this->course);

        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages), "mail should have been sent to 1 user cause within offset");

    }

    /**
     * Tests if an email is sent if the notification settings were specified by the student.
     * @covers ::planner_notifications
     * @throws \dml_exception
     */
    public function test_email_user_execute_twice() {
        global $DB;

        $now = new \DateTime('now');
        date_add($now, date_interval_create_from_date_string('2 days'));

        dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $now->getTimestamp(),
            $now->getTimestamp(), 'Title 1', 'Text 1', 'HS G', 1, 0, 0, 0);
        $user1 = $this->getDataGenerator()->create_user(array('email' => 'user1@example.org'));

        $role = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($user1->id, $this->course->id, $role->id);

        $sink = $this->redirectEmails();
        $this->execute_task($this->course);
        $messages = $sink->get_messages();

        $this->assertEquals(1, count($messages), "1 mail should have been send");

        $sink = $this->redirectEmails();
        $this->execute_task($this->course);
        $messages = $sink->get_messages();

        $this->assertEquals(0, count($messages), "no mail should have been send");
    }

    /**
     * Tests if an email is sent if the notification setting was disabled by the student.
     * @covers ::planner_notifications
     * @covers \lytix_planner\notification_settings::get_default_types
     * @covers \lytix_planner\notification_settings::test_and_set_user
     * @throws \dml_exception
     */
    public function test_email_user_set_option_to_none() {
        global $DB;

        $now = new \DateTime('now');
        date_add($now, date_interval_create_from_date_string('2 days'));
        dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $now->getTimestamp(),
            $now->getTimestamp(), 'Title 1', 'Text 1', 'HS G', 1, 0, 0, 0);
        $user = $this->getDataGenerator()->create_user(array('email' => 'user1@example.org'));

        $role = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($user->id, $this->course->id, $role->id);

        $settingstypes = $this->change_types_options('none');
        $this->change_user_setting($user->id, "types", $settingstypes);

        $sink = $this->redirectEmails();
        $this->execute_task($this->course);
        $messages = $sink->get_messages();

        $this->assertEquals(0, count($messages), "no mail should have been send");

    }
}
