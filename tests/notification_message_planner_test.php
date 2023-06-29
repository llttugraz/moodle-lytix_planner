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
 * Class notification_email_planner_test
 *
 * @group learners_corner
 * @coversDefaultClass \lytix_planner\notification_email
 */
class notification_message_planner_test extends advanced_testcase {
    /**
     * Variable for the context
     *
     * @var bool|\context|\context_course|null
     */
    private $context = null;

    /**
     * Variable for course.
     *
     * @var \stdClass|null
     */
    private $course = null;

    /**
     * Variable for the students
     *
     * @var \stdClass|null
     */
    private $crssettings = null;

    /**
     * Variable for the students
     *
     * @var array
     */
    private $students = [];

    /**
     * Setup called before any test case.
     */
    public function setUp(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course            = new \stdClass();
        $course->fullname  = 'Planner Notification Message Test Course';
        $course->shortname = 'planner_notification_message_test_course';
        $course->category  = 1;

        $this->students = dummy::create_fake_students(10);
        $courseandusers = dummy::create_course_and_enrol_users($course, $this->students);
        $this->course   = $courseandusers['course'];
        $this->context  = \context_course::instance($this->course->id);

        $this->crssettings = notification_settings::test_and_set_course($this->course->id);
        $this->crssettings->enable_course_notifications = 1;
        $this->crssettings->enable_user_customization = 1;
        $DB->update_record('lytix_planner_crs_settings', $this->crssettings);
    }

    /**
     * Executes task planner_event_email_for_course and planner_milestone_email_for_course.
     *
     * @param false|mixed|\stdClass $course
     * @throws \dml_exception
     */
    public function execute_task($course) {
        $messager = new notification_email();
        $messager->planner_notifications($course->id);
    }

    /**
     * Creates a fake planner event.
     *
     * @param int    $days
     * @param string $title
     * @param string $text
     * @param int    $send
     * @return \stdClass
     * @throws \dml_exception
     */
    private function create_event($days = 3, $title = 'Title', $text = 'Text...', $send = 0) {
        global $DB;
        $eventdate = new \DateTime('now');
        $eventdate->modify('+' . $days . ' days');
        $types                    = json_decode($this->crssettings->types);
        $type                     = $types->en[0] . '_' . $types->de[0];
        $types->options[0]        = 'message';
        $this->crssettings->types = json_encode($types);
        $DB->update_record('lytix_planner_crs_settings', $this->crssettings);
        $event = dummy::create_fake_planner_event($this->course, $type, $type[0], $eventdate->getTimestamp(),
                                                  $eventdate->getTimestamp(), $title, $text, 'HS G', 1, 0, 0, $send);
        return $event;
    }

    /**
     * Creates n fake planner milstns
     *
     * @param int    $cnt
     * @param int    $duedate
     * @param string $title
     * @param string $text
     * @param int    $offset
     * @param string $option
     * @param int    $completed
     * @param int    $send
     * @throws \dml_exception
     */
    private function create_milestones($cnt, $duedate = 3, $title = 'Title', $text = 'Text...',
                                       $offset = 3, $option = 'message', $completed = 0, $send = 0) {
        for ($i = 0; $i < $cnt; $i++) {

            $date = new \DateTime('now');
            $date->modify('+' . $duedate . ' days');
            $studi = $this->students[$i];

            dummy::create_fake_planner_milestone($this->course, $studi, 'Milestone', 'M', $date->getTimestamp(),
                                                 $date->getTimestamp(), $title, $text, $offset, $option, $completed, $send);
        }
    }

    /**
     * Counts and asserts the sendet messages.
     *
     * @param int $cnt
     * @throws \dml_exception
     */
    private function count_messages($cnt) {
        $sink = $this->redirectMessages();
        $this->execute_task($this->course);
        $messages = $sink->get_messages();

        $this->assertEquals($cnt, count($messages), "$cnt messages should have been send.");

        $sink = $this->redirectMessages();
        $this->execute_task($this->course);
        $messages = $sink->get_messages();

        $this->assertEquals(0, count($messages), "all messages should have already been send.");
    }

    // EVENTS.

    /**
     * Case1: 1 event  in 3 days.
     * @covers ::planner_notifications
     * @covers ::send_notification_mails
     * @covers ::write_notification_messages
     *
     * @throws \dml_exception
     */
    public function test_event() {
        $this->create_event();
        $this->count_messages(10);
    }

    /**
     * Case2: n events in 3 days.
     * @covers ::planner_notifications
     *
     * @throws \dml_exception
     */
    public function test_n_events() {

        $this->create_event(3, 'Title', "Text...", 0);
        $this->create_event(3, 'Title2', "Text2...", 0);

        $this->count_messages(10);
    }

    /**
     * Case3: 1 event  in n days
     * @covers ::planner_notifications
     *
     * @throws \dml_exception
     */
    public function test_event_n_days() {
        global $DB;

        $n = 15;
        $this->create_event($n);
        for ($i = 0; $i < 3; $i++) {
            $student = $this->students[$i];
            $setting = notification_settings::test_and_set_user($this->course->id, $student->id);

            // Change the options.
            $eventtypes = dynamic_events::get_event_types($this->course->id);
            $eventtypes = json_decode($eventtypes, true);

            $counteventtypes = count($eventtypes['offset']);
            for ($j = 0; $j < $counteventtypes; $j++) {
                $eventtypes['offset'][$j] = $n;
            }

            $setting->types = json_encode($eventtypes);
            $DB->update_record('lytix_planner_usr_settings', $setting);
        }

        $this->count_messages(3);
    }

    /**
     * Case4: 1 event in 3 days 50% option none
     * @covers ::planner_notifications
     *
     * @throws \dml_exception
     */
    public function test_event_option_none() {
        global $DB;

        $n = 3;
        $this->create_event($n);

        for ($i = 0; $i < 5; $i++) {
            $student     = $this->students[$i];
            $setting     = notification_settings::test_and_set_user($this->course->id, $student->id);
            $eventtypes = dynamic_events::get_event_types($this->course->id);
            $eventtypes = json_decode($eventtypes, true);

            $counteventtypes = count($eventtypes['offset']);
            for ($j = 0; $j < $counteventtypes; $j++) {
                $eventtypes['options'][$j] = 'none';
            }

            $setting->types = json_encode($eventtypes);
            $DB->update_record('lytix_planner_usr_settings', $setting);
        }

        $this->count_messages(5);
    }

    /**
     * Case5: 1 event in 3 days 50% completed.
     * @covers ::planner_notifications
     *
     * @throws \dml_exception
     */
    public function test_event_completed() {

        $n     = 3;
        $event = $this->create_event($n);

        for ($i = 0; $i < 5; $i++) {
            $studi = $this->students[$i];
            dummy::complete_fake_planner_event($event->id, $this->course->id, $studi->id,
                                               1, 0, (new \DateTime('now'))->getTimestamp());
        }

        $this->count_messages(5);
    }

    // Milestones.

    /**
     * Case1: one mlstn one mail
     * Case2: mlstn completed
     * @covers ::planner_notifications
     */
    public function test_mlstn_and_completed() {
        $this->create_milestones(1);

        $this->count_messages(1);

        // Completion.
        $studi = $this->students[0];

        $date = new \DateTime('now');
        $date->modify('+3 days');

        $mlstn = dummy::create_fake_planner_milestone($this->course, $studi, 'Milestone', 'M', $date->getTimestamp(),
                                                      $date->getTimestamp(), 'Title', 'Text', 3, 'message', 0, 0);

        $mlstn->completed = 1;

        dummy::update_fake_planner_milestone($mlstn);

        $this->count_messages(0);
    }

    /**
     * Case3: 5 mlstns
     * @covers ::planner_notifications
     */
    public function test_n_mlstns() {

        $this->create_milestones(5);

        $this->count_messages(5);
    }

    /**
     * Case4: 10 mlstns 5 messages.
     * @covers ::planner_notifications
     */
    public function test_mlstn_5_message() {
        $this->create_milestones(5);

        for ($i = 5; $i < 10; $i++) {
            $date = new \DateTime('now');
            $date->modify('+7 days');
            $studi = $this->students[$i];

            dummy::create_fake_planner_milestone($this->course, $studi, 'Milestone', 'M', $date->getTimestamp(),
                                                 $date->getTimestamp(), 'Title', 'Text', 4, 'message', 0, 0);
        }
        $this->count_messages(5);
    }

    /**
     * Case5: 5 mlstns 0 messages.
     * @covers ::planner_notifications
     */
    public function test_mlstn_no_message() {
        $this->create_milestones(5, 3, 'Title', 'Text...', 3, 'none', 0, 0);
        $this->count_messages(0);
    }

}
