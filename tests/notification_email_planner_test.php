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
 * Testcases for lytix_planner
 *
 * @package    lytix_planner
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_planner;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../classes/email_quota_exception.php');

use advanced_testcase;
use lytix_helper\dummy;
use lytix_planner\task\send_planner_notifications;

/**
 * Class notification_email_planner_test
 * @coversDefaultClass \lytix_planner\task\send_planner_notifications
 * @group learners_corner
 */
class notification_email_planner_test extends advanced_testcase {

    /**
     * Variable for course.
     *
     * @var \stdClass|null
     */
    private $course = null;

    /**
     * Variable for the students
     * @var array
     */
    private $students = [];

    /**
     * Variable for the context
     * @var bool|\context|\context_course|null
     */
    private $context = null;

    /**
     * Variable for the students
     * @var \stdClass|null
     */
    private $crssettings = null;

    /**
     * Setup called before any test case.
     */
    public function setUp(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course            = new \stdClass();
        $course->fullname  = 'Planner Notification Test Course';
        $course->shortname = 'planner_notification_test_course';
        $course->category  = 1;

        $this->students = dummy::create_fake_students(10);
        $return = dummy::create_course_and_enrol_users($course, $this->students);
        $this->course = $return['course'];
        $this->context  = \context_course::instance($this->course->id);

        $this->crssettings = notification_settings::test_and_set_course($this->course->id);
        $this->crssettings->enable_course_notifications = 1;
        $this->crssettings->enable_user_customization = 1;
        $DB->update_record('lytix_planner_crs_settings', $this->crssettings);
        // Set platform.
        set_config('platform', 'learners_corner', 'local_lytix');
        set_config('course_list', $this->course->id, 'local_lytix');
    }

    /**
     * Executes task planner_event_email_for_course and planner_milestone_email_for_course.
     *
     * @throws \dml_exception
     */
    public function execute_task() {
        $sender = new send_planner_notifications();
        $sender->execute();
    }

    /**
     * Creates a fake planner event.
     * @param int $days
     * @param string $title
     * @param string $text
     * @param int $send
     * @return \stdClass
     * @throws \dml_exception
     */
    private function create_event($days = 3, $title = 'Title', $text = 'Text...', $send = 0) {

        $eventdate = new \DateTime('now');
        $eventdate->modify('+' . $days . ' days');
        $types = json_decode($this->crssettings->types);
        $type = $types->en[0] . '_' . $types->de[0];
        $event = dummy::create_fake_planner_event($this->course, $type, $type[0], $eventdate->getTimestamp(),
            $eventdate->getTimestamp(), $title, $text, 'HS G', 1, 0, 0, $send);
        return $event;
    }

    /**
     * Creates n fake planner milstns
     * @param int $cnt
     * @param int $duedate
     * @param string $title
     * @param string $text
     * @param int $offset
     * @param string $option
     * @param int $completed
     * @param int $send
     * @throws \dml_exception
     */
    private function create_milestones($cnt, $duedate = 3, $title = 'Title', $text = 'Text...',
                                       $offset = 3, $option = 'email', $completed = 0, $send = 0) {
        for ($i = 0; $i < $cnt; $i++) {

            $date = new \DateTime('now');
            $date->modify('+' . $duedate . ' days');
            $studi = $this->students[$i];

            dummy::create_fake_planner_milestone($this->course, $studi, 'Milestone', 'M', $date->getTimestamp(),
                $date->getTimestamp(), $title, $text, $offset, $option, $completed, $send);
        }
    }

    /**
     * Counts and asserts the sendet mails.
     * @param int $cnt
     * @throws \dml_exception
     */
    private function count_mails($cnt) {
        $sink = $this->redirectEmails();
        $this->execute_task();
        $messages = $sink->get_messages();

        $this->assertEquals($cnt, count($messages), "$cnt mails should have been send.");

        $sink = $this->redirectEmails();
        $this->execute_task();
        $messages = $sink->get_messages();

        $this->assertEquals(0, count($messages), "all mails should have already been send.");
    }

    /**
     * Test get_name of task.
     * @covers ::get_name
     * @return void
     */
    public function test_task_get_name() {
        $task = new send_planner_notifications();
        self::assertEquals("Send Planner Notifications Task for subplugin lytix_planner", $task->get_name());
    }

    // EVENTS.
    /**
     * Case1: 1 event  in 3 days.
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     * @throws \dml_exception
     */
    public function test_event_case1() {
        $this->create_event();
        $this->count_mails(10);
    }

    /**
     * Case2: n events in 3 days.
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     * @throws \dml_exception
     */
    public function test_event_case2() {

        $this->create_event(3, 'Title1', "Text1...", 0);
        $this->create_event(3, 'Title2', "Text2...", 0);

        $this->count_mails(10);
    }

    /**
     * Case3: 1 event  in n days
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     * @covers \lytix_planner\notification_settings::test_and_set_user
     * @covers \lytix_planner\dynamic_events::get_event_types
     * @throws \dml_exception
     */
    public function test_event_case3() {
        global $DB;

        $n = 14;
        $this->create_event($n);
        for ($i = 0; $i < 3; $i++) {
            $studi = $this->students[$i];
            $setting = notification_settings::test_and_set_user($this->course->id, $studi->id);

            // Change the options accordingly.
            $types = dynamic_events::get_event_types($this->course->id);
            $types = json_decode($types, true);

            for ($j = 0; $j < count($types['offset']); $j++) {
                $types['offset'][$j] = $n;
            }

            $setting->types = json_encode($types);
            $DB->update_record('lytix_planner_usr_settings', $setting);
        }

        $this->count_mails(3);
    }

    /**
     * Case4: 1 event in 3 days 50% option none
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     * @covers \lytix_planner\notification_settings::test_and_set_user
     * @covers \lytix_planner\dynamic_events::get_event_types
     * @throws \dml_exception
     */
    public function test_event_case4() {
        global $DB;

        $n = 3;
        $this->create_event($n);

        for ($i = 0; $i < 5; $i++) {
            $studi = $this->students[$i];
            $setting = notification_settings::test_and_set_user($this->course->id, $studi->id);
            $types = dynamic_events::get_event_types($this->course->id);
            $types = json_decode($types, true);

            for ($j = 0; $j < count($types['offset']); $j++) {
                $types['options'][$j] = 'none';
            }

            $setting->types = json_encode($types);
            $DB->update_record('lytix_planner_usr_settings', $setting);
        }

        $this->count_mails(5);
    }

    /**
     * Case5: 1 event in 3 days 50% completed.
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     * @covers \lytix_planner\notification_settings::test_and_set_event_comp
     * @throws \dml_exception
     */
    public function test_event_case5() {
        $n = 3;
        $event = $this->create_event($n);

        for ($i = 0; $i < 5; $i++) {
            $studi = $this->students[$i];
            notification_settings::test_and_set_event_comp($event->id, $this->course->id, $studi->id, 0, 1);
        }

        $this->count_mails(5);
    }

    /**
     * Case6: Tests if an email is sent to the one user, who has not completed the event.
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     * @covers \lytix_planner\dynamic_events::set_event_types
     * @covers \lytix_planner\dynamic_events::get_event_types
     * @throws \dml_exception
     */
    public function test_event_case6() {
        $eventdate = new \DateTime('now');
        $eventdate->modify('+' . 3 . ' days');
        dynamic_events::set_event_types($this->course->id, "Informatik", "Computer-Science");

        $types = dynamic_events::get_event_types($this->course->id);
        $types = json_decode($types);
        $type = end($types->en) . '_' . end($types->de);
        self::assertEquals("Computer-Science_Informatik", $type);
        $event = dummy::create_fake_planner_event($this->course, $type, $type[0], $eventdate->getTimestamp(),
                                                  $eventdate->getTimestamp(), "TITLE", "TEXT", 'HS G', 1, 0, 0, 0);

        for ($i = 1; $i < 10; $i++) {
            $student = $this->students[$i];
            dummy::complete_fake_planner_event($event->id, $this->course->id, $student->id,
                                               1, 0, (new \DateTime('now'))->getTimestamp());
        }
        $sink = $this->redirectEmails();
        $this->execute_task();
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages), "1 mail should have been send.");
        self::assertEquals($this->students[0]->email, $messages[0]->to);
    }

    /**
     * Case7: Tests events and groups.
     * @covers \lytix_planner\group_helper::check_group
     * @throws \dml_exception
     */
    public function test_check_group() {
        global $DB;
        $event = $this->create_event();

        // Case no group.
        $event->mgroup = 0;
        $DB->update_record('lytix_planner_events', $event);
        self::assertEquals(true, group_helper::check_group($this->course->id, $this->students[0]->id, $event));

        // Case std group.
        $event->mgroup = 1;
        $DB->update_record('lytix_planner_events', $event);
        self::assertEquals(false, group_helper::check_group($this->course->id, $this->students[0]->id, $event));

        // Case custom group.
        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));
        groups_add_member($group1, $this->students[0]);
        $event->mgroup = $group1->id;
        $DB->update_record('lytix_planner_events', $event);
        self::assertEquals(true, group_helper::check_group($this->course->id, $this->students[0]->id, $event));
    }

    /**
     * Case8: Tests if an email is sent, if the event is within the offset.
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     * @throws \dml_exception
     */
    public function test_event_case8() {
        global $DB;
        // Create event not within the offset.
        $this->create_event(4);

        $this->count_mails(0);

        $types = json_decode($this->crssettings->types);
        $types->offset[0] = 4;
        $this->crssettings->types = json_encode($types);
        $this->crssettings->enable_user_customization = 0;

        $DB->update_record('lytix_planner_crs_settings', $this->crssettings);

        $this->count_mails(10);
    }

    // Milestones.
    /**
     * Case1: one mlstn one mail -> not completed.
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     */
    public function test_mlstn_case1() {
        $this->create_milestones(1);

        $this->count_mails(1);
    }

    /**
     * Case2: mlstn completed
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     * @throws \dml_exception
     */
    public function test_mlstn_case2() {
        $studi = $this->students[0];

        $date = new \DateTime('now');
        $date->modify('+3 days');

        $mlstn = dummy::create_fake_planner_milestone($this->course, $studi, 'Milestone', 'M', $date->getTimestamp(),
            $date->getTimestamp(), 'Title', 'Text', 3, 'email', 0, 0);

        $mlstn->completed = 1;

        dummy::update_fake_planner_milestone($mlstn);

        $this->count_mails(0);
    }

    /**
     * Case3: 5 mlstns
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     */
    public function test_mlstn_case3() {

        $this->create_milestones(5);

        $this->count_mails(5);
    }

    /**
     * Case4: 10 mlstns 5 mails.
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     */
    public function test_mlstn_case4() {
        $this->create_milestones(5);

        for ($i = 5; $i < 10; $i++) {
            $date = new \DateTime('now');
            $date->modify('+7 days');
            $studi = $this->students[$i];

            dummy::create_fake_planner_milestone($this->course, $studi, 'Milestone', 'M', $date->getTimestamp(),
                $date->getTimestamp(), 'Title', 'Text', 4, 'email', 0, 0);
        }
        $this->count_mails(5);
    }

    /**
     * Case5: 5 mlstns 0 mails.
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     */
    public function test_mlstn_case5() {
        $this->create_milestones(5, 3, 'Title', 'Text...', 3, 'none', 0, 0);
        $this->count_mails(0);
    }

    /**
     * Case6: Tests if an email is sent to the one user who has milestones that are not completed.
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     * @throws \dml_exception
     */
    public function test_mlstn_case6() {
        $date = new \DateTime('now');
        $date->modify('+3 days');
        dummy::create_fake_planner_milestone($this->course, $this->students[0], 'Milestone', 'M', $date->getTimestamp(),
                                             $date->getTimestamp(), 'Title', 'Text', 3, 'email', 0, 0);

        for ($i = 1; $i < 10; $i++) {
            $student = $this->students[$i];
            dummy::create_fake_planner_milestone($this->course, $student, 'Milestone', 'M', $date->getTimestamp(),
                                                 $date->getTimestamp(), 'Title', 'Text', 3, 'email', 1, 0);
        }
        $sink = $this->redirectEmails();
        $this->execute_task();
        $messages = $sink->get_messages();
        $this->assertEquals(1, count($messages), "1 mail should have been send.");
        self::assertEquals($this->students[0]->email, $messages[0]->to);
    }

    /**
     * Case7: Tests if an email is sent, if the milestone is not within the offset.
     * @covers ::execute
     * @covers \lytix_planner\notification_email::planner_notifications
     * @throws \dml_exception
     */
    public function test_mlstn_case7() {
        $date = new \DateTime('now');
        $date->modify('+7 days');
        // Create event not within the offset.
        $record = dummy::create_fake_planner_milestone($this->course, $this->students[0], 'Milestone', 'M', $date->getTimestamp(),
                                             $date->getTimestamp(), 'Title', 'Text', 4, 'email', 0, 0);

        // Not within offset.
        $this->count_mails(0);

        $record->moffset = 7;
        dummy::update_fake_planner_milestone($record);

        $this->count_mails(1);
    }
}
