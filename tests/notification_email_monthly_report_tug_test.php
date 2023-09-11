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
 * Testcases for local_lytix
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
use lytix_planner\task\send_tug_report_notifications;


/**
 * Class notification_email_monthly_report_tug_test
 * @coversDefaultClass \lytix_planner\notification_email
 * @group learners_corner
 */
class notification_email_monthly_report_tug_test extends advanced_testcase {

    /**
     * Variable for course.
     *
     * @var \stdClass|null
     */
    private $course = null;

    /**
     * Setup called before any test case.
     */
    public function setUp(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();

        set_config('platform', 'learners_corner', 'local_lytix');
        $fivemonthsago = new \DateTime('5 months ago');
        $fivemonthsago->setTime(0, 0);
        set_config('semester_start', $fivemonthsago->format('Y-m-d'), 'local_lytix');
        $today = new \DateTime('today midnight');
        date_add($today, date_interval_create_from_date_string('1 day'));
        set_config('semester_end', $today->format('Y-m-d'), 'local_lytix');
        // Create course and enable notifications.
        $this->course = $this->getDataGenerator()->create_course(['startdate' => $fivemonthsago->getTimestamp()]);
        $crssettings = notification_settings::test_and_set_course($this->course->id);
        $crssettings->enable_course_notifications = 1;
        $crssettings->enable_user_customization = 1;
        $DB->update_record('lytix_planner_crs_settings', $crssettings);

        // Set platform.
        set_config('platform', 'learners_corner', 'local_lytix');
        // Add course to config list.
        set_config('course_list', $this->course->id, 'local_lytix');

        global $DB;
        $reportonemonthago = new \DateTime('1 month ago');
        date_sub($reportonemonthago, date_interval_create_from_date_string('1 day'));
        $reportonemonthago->setTime(2, 0);

        $record            = new \stdClass();
        $record->courseid  = $this->course->id;
        $record->timestamp = $reportonemonthago->getTimestamp();
        $DB->insert_record('lytix_planner_last_report', $record);

    }

    /**
     * Executes Task.
     *
     * @throws \dml_exception
     */
    public function execute_task() {
        $task = new send_tug_report_notifications();
        $task->execute();
    }

    /**
     * Completes an event.
     *
     * @param int $eventid
     * @param int $courseid
     * @param int $userid
     * @param int $completed
     * @param int $timestamp
     * @return \stdClass
     * @throws \dml_exception
     */
    private function completeevent($eventid, $courseid, $userid, $completed, $timestamp) {
        global $DB;

        $record            = new \stdClass();
        $record->eventid   = $eventid;
        $record->courseid  = $courseid;
        $record->userid    = $userid;
        $record->completed = $completed;
        $record->timestamp = $timestamp;

        $record->id = $DB->insert_record('lytix_planner_event_comp', $record);
        return $record;

    }

    /**
     * Creates and enrols a student.
     *
     * @param string $email
     * @return \stdClass|null
     * @throws \dml_exception
     */
    private function create_enrol_student($email) {
        global $DB;
        $dg = $this->getDataGenerator();

        $role    = $DB->get_record('role', array('shortname' => 'student'), '*', MUST_EXIST);
        $student = $dg->create_user(array('email' => $email));
        if ($dg->enrol_user($student->id, $this->course->id, $role->id)) {
            return $student;
        } else {
            return null;
        }
    }

    /**
     * Test get_name of task.
     * @covers \lytix_planner\task\send_tug_report_notifications::get_name
     * @return void
     */
    public function test_task_get_name() {
        $task = new send_tug_report_notifications();
        self::assertEquals("Send planner report notification task for subplugin lytix_planner", $task->get_name());
    }

    /**
     * Tests if the span of the monthly report is correct.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::all_events_completed
     * @covers ::all_mandatory_and_graded_completed
     * @covers ::some_mandatory_and_graded_not_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_2_am() {
        $onemonthago = new \DateTime('1 month ago');
        $onemonthago->setTime(2, 1);

        $user1 = $this->create_enrol_student('user1@example.org');

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Event 1', 'Test Text 1', 'room',
                                                  1, 1, 0, 0);
        $this->completeevent($event->id, $this->course->id, $user1->id, 1, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 month'));
        $onemonthago->setTime(2, 0);

        $event2 = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                   $onemonthago->getTimestamp(), 'Event 2', 'Test Text 1', 'room',
                                                   1, 1, 0, 0);
        $this->completeevent($event2->id, $this->course->id, $user1->id, 1, $onemonthago->getTimestamp());

        $sink = $this->redirectEmails();
        $this->execute_task();
        $mails = $sink->get_messages();

        $this->assertEquals(1, count($mails), "mail should have been sent to 1 user cause all compl.");
    }

    // CASE 1: All events completed.

    /**
     * Tests if the monthly report is sent when all events are completed.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::all_events_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_all_events_completed() {
        $onemonthago = new \DateTime('1 month ago');

        date_add($onemonthago, date_interval_create_from_date_string('5 days'));

        $student = $this->create_enrol_student('user1@example.org');

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Event 1', 'Test Text 1', 'room',
                                                  1, 1, 0, 0);
        $this->completeevent($event->id, $this->course->id, $student->id, 1, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        $event2 = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                   $onemonthago->getTimestamp(), 'Event 2', 'Test Text 2', 'room',
                                                   1, 0, 0, 0);
        $this->completeevent($event2->id, $this->course->id, $student->id, 1, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        $event3 = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                   $onemonthago->getTimestamp(), 'Event 3', 'Test Text 3', 'room',
                                                   1, 0, 0, 0);
        $this->completeevent($event3->id, $this->course->id, $student->id, 1, $onemonthago->getTimestamp());

        $redirectmails = $this->redirectEmails();
        $this->execute_task();
        $messages = $redirectmails->get_messages();

        $this->assertEquals(1, count($messages), "mail should have been sent to 1 user cause all compl.");
    }

    /**
     * Tests if the monthly report is sent to more users when all events are completed.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::all_events_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_all_events_completed_more_users() {
        $onemonth = new \DateTime('1 month ago');

        date_add($onemonth, date_interval_create_from_date_string('1 day'));

        $user1 = $this->create_enrol_student('user1@example.org');
        $user2 = $this->create_enrol_student('user2@example.org');
        $user3 = $this->create_enrol_student('user3@example.org');

        $event1 = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonth->getTimestamp(),
                                                   $onemonth->getTimestamp(), 'Event 1', 'Test Text 1', 'room',
                                                   1, 0, 0, 0);
        $this->completeevent($event1->id, $this->course->id, $user1->id, 1, $onemonth->getTimestamp());
        $this->completeevent($event1->id, $this->course->id, $user2->id, 1, $onemonth->getTimestamp());
        $this->completeevent($event1->id, $this->course->id, $user3->id, 1, $onemonth->getTimestamp());

        date_add($onemonth, date_interval_create_from_date_string('1 week'));

        $event2 = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonth->getTimestamp(),
                                                   $onemonth->getTimestamp(), 'Event 2', 'Test Text 2', 'room',
                                                   1, 0, 0, 0);
        $this->completeevent($event2->id, $this->course->id, $user1->id, 1, $onemonth->getTimestamp());
        $this->completeevent($event2->id, $this->course->id, $user2->id, 1, $onemonth->getTimestamp());
        $this->completeevent($event2->id, $this->course->id, $user3->id, 1, $onemonth->getTimestamp());

        date_add($onemonth, date_interval_create_from_date_string('1 week'));

        $event3 = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonth->getTimestamp(),
                                                   $onemonth->getTimestamp(), 'Event 3', 'Test Text 3', 'room',
                                                   1, 1, 0, 0);
        $this->completeevent($event3->id, $this->course->id, $user1->id, 1, $onemonth->getTimestamp());
        $this->completeevent($event3->id, $this->course->id, $user2->id, 1, $onemonth->getTimestamp());
        $this->completeevent($event3->id, $this->course->id, $user3->id, 1, $onemonth->getTimestamp());

        $sink = $this->redirectEmails();

        $this->execute_task($this->course);

        $emails = $sink->get_messages();

        $this->assertEquals(3, count($emails), "mail should have been sent to 3 users cause all compl.");
    }

    /**
     * Tests if the monthly report is sent when all events are completed and is not sent again.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::all_events_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_all_events_completed_call_twice() {
        $onemonthago = new \DateTime('1 month ago');

        date_add($onemonthago, date_interval_create_from_date_string('1 day'));

        $user = $this->create_enrol_student('user1@example.org');

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Event One', 'Test Text 1', 'room',
                                                  1, 0, 0, 0);

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));
        $this->completeevent($event->id, $this->course->id, $user->id, 1, $onemonthago->getTimestamp());

        $lecture1 = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                     $onemonthago->getTimestamp(), 'Event Two', 'Test Text 1', 'room',
                                                     1, 0, 0, 0);

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));
        $this->completeevent($lecture1->id, $this->course->id, $user->id, 1, $onemonthago->getTimestamp());

        $lecture2 = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                     $onemonthago->getTimestamp(), 'Event Three', 'Test Text 1', 'room',
                                                     1, 0, 0, 0);
        $this->completeevent($lecture2->id, $this->course->id, $user->id, 1, $onemonthago->getTimestamp());

        $sink = $this->redirectEmails();
        $this->execute_task($this->course);
        $messages = $sink->get_messages();

        $this->assertEquals(1, count($messages), "mail should have been sent to 1 user cause all compl.");

        $sink = $this->redirectEmails();
        $this->execute_task($this->course);
        $messages = $sink->get_messages();

        $this->assertEquals(0, count($messages), "Monthly report should have been already sent.");
    }

    /**
     * Tests if the monthly report is sent when the event is two months ago and not in the span of the monthly report.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::all_events_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_all_events_completed_second_month() {
        $twomonthsago = new \DateTime('2 months ago');

        $user1 = $this->create_enrol_student('user1@example.org');

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $twomonthsago->getTimestamp(),
                                                  $twomonthsago->getTimestamp(), 'All events completed?', 'Test Text 1', 'room',
                                                  1, 0, 0, 0);
        $this->completeevent($event->id, $this->course->id, $user1->id, 1, $twomonthsago->getTimestamp());

        $sink = $this->redirectEmails();
        $this->execute_task($this->course);
        $sentmails = $sink->get_messages();

        $this->assertEquals(0, count($sentmails), "no mail should have been sent to user cause two months ago.");
    }

    /**
     * Tests if the monthly report is sent when the event is tomorrow and not in the span of the monthly report.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::all_events_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_all_events_completed_next_day() {

        $nextday = new \DateTime('now');
        date_add($nextday, date_interval_create_from_date_string('1 day'));

        $user1 = $this->create_enrol_student('user1@example.org');

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $nextday->getTimestamp(),
                                                  $nextday->getTimestamp(), 'Next Day', 'Test Text 1', 'room',
                                                  1, 1, 0, 0);
        $this->completeevent($event->id, $this->course->id, $user1->id, 1, $nextday->getTimestamp());

        $redirectemail = $this->redirectEmails();
        $this->execute_task($this->course);
        $zeromails = $redirectemail->get_messages();

        $this->assertEquals(0, count($zeromails), "no mail should have been sent to user cause next day.");
    }

    /**
     * Tests if the monthly report is sent when the event is after semesterend.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::all_events_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_all_events_completed_after_semesterend() {
        global $DB;
        $reportnow = new \DateTime('now');
        $reportnow->setTime(2, 0);

        $record            = new \stdClass();
        $record->courseid  = $this->course->id;
        $record->timestamp = $reportnow->getTimestamp();
        $DB->insert_record('lytix_planner_last_report', $record);

        $userone = $this->create_enrol_student('user1@example.org');

        $in2days = new \DateTime('now');
        date_add($in2days, date_interval_create_from_date_string('2 days'));

        $event2 = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $in2days->getTimestamp(),
                                                   $in2days->getTimestamp(), 'Event 1', 'Test Text 1', 'room',
                                                   1, 1, 0, 0);
        $this->completeevent($event2->id, $this->course->id, $userone->id, 1, $in2days->getTimestamp());

        $sink = $this->redirectEmails();

        $this->execute_task($this->course);

        $messages = $sink->get_messages();

        $this->assertEquals(0, count($messages), "mail should not have been sent to user cause after semesterend");

    }

    // CASE 2: All mandatory events completed.

    /**
     * Tests if the monthly report is sent when all mandatory events are completed.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::all_mandatory_and_graded_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_all_mandatory_completed() {
        $onemonthago = new \DateTime('1 month ago');

        date_add($onemonthago, date_interval_create_from_date_string('1 days'));

        $testuser = $this->create_enrol_student('user1@example.org');

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Not Mandatory', 'bla bla bla', 'room',
                                                  1, 0, 0, 0);
        $this->completeevent($event->id, $this->course->id, $testuser->id, 0, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        $event = dummy::create_fake_planner_event($this->course, 'Exam_Prüfung', 'E', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Mandatory Event', 'bla bla bla', 'room',
                                                  1, 1, 1, 0);
        $this->completeevent($event->id, $this->course->id, $testuser->id, 1, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                         $onemonthago->getTimestamp(), 'All Mandatory completed?', 'bla bla bla', 'room',
                                         1, 0, 0, 0);

        $sink = $this->redirectEmails();
        $this->execute_task($this->course);
        $mail = $sink->get_messages();

        $this->assertEquals(1, count($mail), "mail should have been sent to 1 user cause all compl.");
    }

    /**
     * Tests if the monthly report is sent to all users when all mandatory events are completed.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::all_mandatory_and_graded_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_all_mandatory_completed_more_user() {
        $onemonthago = new \DateTime('1 month ago');

        date_add($onemonthago, date_interval_create_from_date_string('1 days'));

        $student1 = $this->create_enrol_student('user1@example.org');
        $student2 = $this->create_enrol_student('user2@example.org');
        $student3 = $this->create_enrol_student('user3@example.org');

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Event not mandatory', 'bla bla bla', 'room',
                                                  1, 0, 0, 0);
        $this->completeevent($event->id, $this->course->id, $student1->id, 1, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        $event = dummy::create_fake_planner_event($this->course, 'Exam_Prüfung', 'E', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Mandator Event', 'bla bla bla', 'room',
                                                  1, 1, 1, 0);
        $this->completeevent($event->id, $this->course->id, $student1->id, 1, $onemonthago->getTimestamp());
        $this->completeevent($event->id, $this->course->id, $student2->id, 1, $onemonthago->getTimestamp());
        $this->completeevent($event->id, $this->course->id, $student3->id, 1, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                         $onemonthago->getTimestamp(), 'Last Event', 'bla bla bla', 'room',
                                         1, 0, 0, 0);

        $redirectemails = $this->redirectEmails();
        $this->execute_task($this->course);
        $messages = $redirectemails->get_messages();

        $this->assertEquals(3, count($messages), "mail should have been sent to 3 users cause all compl.");
    }

    /**
     * Tests if the monthly report is sent when all mandatory events are completed and is not sent again.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::all_mandatory_and_graded_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_all_mandatory_completed_call_twice() {
        $onemonthago = new \DateTime('1 month ago');

        date_add($onemonthago, date_interval_create_from_date_string('1 days'));

        $student1 = $this->create_enrol_student('user1@example.org');

        $lecture = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                    $onemonthago->getTimestamp(), 'Event 1', 'bla bla bla', 'room',
                                                    1, 0, 0, 0);
        $this->completeevent($lecture->id, $this->course->id, $student1->id, 0, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        $event2 = dummy::create_fake_planner_event($this->course, 'Exam_Prüfung', 'E', $onemonthago->getTimestamp(),
                                                   $onemonthago->getTimestamp(), 'Mandator Event', 'bla bla bla', 'room',
                                                   1, 1, 0, 0);
        $this->completeevent($event2->id, $this->course->id, $student1->id, 1, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                         $onemonthago->getTimestamp(), 'Testing Event 2', 'bla bla bla', 'room',
                                         1, 0, 0, 0);

        $redirectemails = $this->redirectEmails();

        $this->execute_task($this->course);

        $emails = $redirectemails->get_messages();

        $this->assertEquals(1, count($emails), "mail should have been sent to 1 user cause all compl.");

        $redirectemails = $this->redirectEmails();
        $this->execute_task($this->course);
        $emails = $redirectemails->get_messages();

        $this->assertEquals(0, count($emails), "Monthly report should have been already sent.");
    }

    /**
     * Tests if the monthly report is sent when the events are in the span of the monthly report.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::all_mandatory_and_graded_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_all_mandatory_events_completed_second_month() {
        $onemonthago = new \DateTime('1 month ago');

        $user1 = $this->create_enrol_student('user1@example.org');

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Learning', 'Test Text 1', 'room',
                                                  1, 0, 0, 0);
        $this->completeevent($event->id, $this->course->id, $user1->id, 0, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Testing', 'Test Text 1', 'room',
                                                  1, 1, 1, 0);
        $this->completeevent($event->id, $this->course->id, $user1->id, 1, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                         $onemonthago->getTimestamp(), 'Comparing', 'Test Text 1', 'room',
                                         1, 0, 0, 0);

        $redirectemails = $this->redirectEmails();

        $this->execute_task($this->course);

        $message = $redirectemails->get_messages();

        $this->assertEquals(1, count($message), "mail should have been sent to user cause all compl.");
    }

    // CASE 3: Some mandatory completed.

    /**
     * Tests if the monthly report is sent when some mandatory events are completed.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::some_mandatory_and_graded_not_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_some_mandatory_completed() {
        $onemonthago = new \DateTime('1 month ago');

        date_add($onemonthago, date_interval_create_from_date_string('1 day'));

        $user1 = $this->create_enrol_student('user1@example.org');

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Some Mandatory completed?', 'Test Text 1', 'room',
                                                  1, 1, 1, 0);
        $this->completeevent($event->id, $this->course->id, $user1->id, 1, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Mandatory', 'Test Text 1', 'room',
                                                  1, 1, 1, 0);
        $this->completeevent($event->id, $this->course->id, $user1->id, 0, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Not Mandatory', 'Test Text 1', 'room',
                                                  1, 0, 0, 0);
        $this->completeevent($event->id, $this->course->id, $user1->id, 1, $onemonthago->getTimestamp());

        $redirect = $this->redirectEmails();
        $this->execute_task($this->course);
        $messages = $redirect->get_messages();

        $this->assertEquals(1, count($messages), "mail should have been sent to 1 user cause all mand. compl.");
    }

    /**
     * Tests if the monthly report is sent to all users when all mandatory events are completed.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::some_mandatory_and_graded_not_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_some_mandatory_completed_more_users() {
        $onemonthago = new \DateTime('1 month ago');

        date_add($onemonthago, date_interval_create_from_date_string('1 days'));

        $userone   = $this->create_enrol_student('user1@example.org');
        $usertwo   = $this->create_enrol_student('user2@example.org');
        $userthree = $this->create_enrol_student('user3@example.org');

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Lecture 1', 'Test Text 1', 'room',
                                                  1, 1, 0, 0);
        $this->completeevent($event->id, $this->course->id, $userone->id, 1, $onemonthago->getTimestamp());
        $this->completeevent($event->id, $this->course->id, $usertwo->id, 1, $onemonthago->getTimestamp());
        $this->completeevent($event->id, $this->course->id, $userthree->id, 1, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                         $onemonthago->getTimestamp(), 'Lecture 2', 'Test Text 1', 'room',
                                         1, 1, 1, 0);

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Lecture 3', 'Test Text 1', 'room',
                                                  1, 0, 0, 0);
        $this->completeevent($event->id, $this->course->id, $userone->id, 1, $onemonthago->getTimestamp());

        $redirect = $this->redirectEmails();

        $this->execute_task($this->course);

        $messages = $redirect->get_messages();

        $this->assertEquals(3, count($messages), "mail should have been sent to 3 users cause all mand. compl.");
    }

    /**
     * Tests if the monthly report is sent when some mandatory events are completed and is not sent again.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::some_mandatory_and_graded_not_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_some_mandatory_completed_call_twice() {
        $onemonthago = new \DateTime('1 month ago');

        date_add($onemonthago, date_interval_create_from_date_string('1 days'));

        $user1 = $this->create_enrol_student('user1@example.org');

        $eventlecture = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                         $onemonthago->getTimestamp(), 'Event 1', 'Test Text 1', 'room',
                                                         1, 1, 1, 0);
        $this->completeevent($eventlecture->id, $this->course->id, $user1->id, 1, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        $eventlecture = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                         $onemonthago->getTimestamp(), 'Event 2', 'Test Text 1', 'room',
                                                         1, 1, 1, 0);
        $this->completeevent($eventlecture->id, $this->course->id, $user1->id, 0, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        $eventlecture = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                         $onemonthago->getTimestamp(), 'Event 3', 'Test Text 1', 'room',
                                                         1, 0, 0, 0);
        $this->completeevent($eventlecture->id, $this->course->id, $user1->id, 1, $onemonthago->getTimestamp());

        $redirect = $this->redirectEmails();

        $this->execute_task($this->course);

        $messages = $redirect->get_messages();

        $this->assertEquals(1, count($messages), "mail should have been sent to 1 user cause all compl.");

        $redirect = $this->redirectEmails();
        $this->execute_task($this->course);
        $this->execute_task($this->course);

        $messages = $redirect->get_messages();

        $this->assertEquals(0, count($messages), "Monthly report should have been already sent!");
    }

    /**
     * Tests if the monthly report is sent when the events are in the span of the monthly report.
     * @covers \lytix_planner\task\send_tug_report_notifications::execute
     * @covers ::mail_monthly_report
     * @covers ::some_mandatory_and_graded_not_completed
     * @covers ::set_last_report
     * @covers ::email_to_user
     * @throws \dml_exception
     */
    public function test_some_mandatory_completed_second_month() {
        $onemonthago = new \DateTime('1 month ago');

        $usera = $this->create_enrol_student('user1@example.org');

        $event = dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                                  $onemonthago->getTimestamp(), 'Some Mandatory', 'Test Text 1', 'room',
                                                  1, 1, 1, 0);
        $this->completeevent($event->id, $this->course->id, $usera->id, 1, $onemonthago->getTimestamp());

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                         $onemonthago->getTimestamp(), 'Completed Title', 'Test Text 1', 'room',
                                         1, 1, 1, 0);

        date_add($onemonthago, date_interval_create_from_date_string('1 week'));

        dummy::create_fake_planner_event($this->course, 'Lecture_Vorlesung', 'L', $onemonthago->getTimestamp(),
                                         $onemonthago->getTimestamp(), 'Second Month', 'Test Text 1', 'room',
                                         1, 0, 0, 0);
        $this->completeevent($event->id, $this->course->id, $usera->id, 1, $onemonthago->getTimestamp());

        $sink = $this->redirectEmails();

        $this->execute_task($this->course);

        $messages = $sink->get_messages();

        $this->assertEquals(1, count($messages), "mail should have been sent to user cause some compl.");
    }

    /**
     * Testcase2: Check user grade report with no points.
     * @covers \lytix_planner\notification_settings::test_and_set_report
     * @covers \lytix_planner\notification_settings::update_report
     * @throws \dml_exception
     */
    public function test_case2() {
        $student = $this->create_enrol_student('user1@example.org');
        $settings = notification_settings::test_and_set_report($this->course->id, $student->id);

        $this->assertEquals(0, $settings->quizpoints);
        $this->assertEquals(0, $settings->assingpoints);
        $this->assertEquals(0, $settings->totalpoints);
        $this->assertEquals(0, $settings->maxpoints);

        $settings->quizpoints = 20;
        notification_settings::update_report($settings);

        $this->assertEquals(20, $settings->quizpoints);

    }
}

