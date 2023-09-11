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
 * Testcases for planner.
 *
 * @package    lytix_planner
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_planner;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/lib/externallib.php');

use external_api;
use externallib_advanced_testcase;
use lytix_helper\dummy;

/**
 * Class planner_get_test
 *
 * @group learners_corner
 * @coversDefaultClass \lytix_planner\planner_get
 */
class planner_get_test extends externallib_advanced_testcase {
    /**
     * Variable for course.
     *
     * @var \stdClass|null
     */
    private $course = null;

    /**
     * Variable for the context
     *
     * @var bool|\context|\context_course|null
     */
    private $context = null;

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
        $this->resetAfterTest(true);
        $this->setAdminUser();

        $course            = new \stdClass();
        $course->fullname  = 'Planner Event Lib Test Course';
        $course->shortname = 'planner_event_lib_test_course';
        $course->category  = 1;

        $this->students = dummy::create_fake_students(10);
        $return         = dummy::create_course_and_enrol_users($course, $this->students);
        $this->course   = $return['course'];
        $this->context  = \context_course::instance($this->course->id);

        $twomonthsago = new \DateTime('2 months ago');
        $inthreemonths = new \DateTime('now');
        date_add($inthreemonths, date_interval_create_from_date_string('3 months'));
        set_config('semester_start', $twomonthsago->format('Y-m-d'), 'local_lytix');
        set_config('semester_end', $inthreemonths->format('Y-m-d'), 'local_lytix');

        $this->crssettings = notification_settings::test_and_set_course($this->course->id);
        set_config('course_list', $this->course->id, 'local_lytix');
        // Set platform.
        set_config('platform', 'learners_corner', 'local_lytix');
    }

    /**
     * Tests planner webservice.
     *
     * @covers ::service
     * @covers ::service_parameters
     * @covers ::service_returns
     * @covers \lytix_planner\notification_settings::getcoursestartdate
     * @covers \lytix_planner\notification_settings::getcourseenddate
     * @covers \lytix_planner\notification_settings::test_and_set_course
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \restricted_context_exception
     */
    public function test_empty_planner() {
        $result = planner_get::service($this->course->id, $this->context->id, false);
        try {
            external_api::clean_returnvalue(planner_get::service_returns(), $result);
        } catch (\invalid_response_exception $e) {
            if ($e) {
                self::assertFalse(true, "invalid_responce_exception thorwn.");
            }
        }
        // Basic asserts.
        $this::assertEquals(3, count($result));

        $this->assertTrue(key_exists('startDate', $result));
        $this->assertTrue(key_exists('endDate', $result));
        $this->assertTrue(key_exists('items', $result));

        $this::assertEquals(0, count($result['items']));
    }

    /**
     * Create an event, complete and delete it.
     *
     * @covers ::service
     * @covers ::service_parameters
     * @covers ::service_returns
     *
     * @covers \lytix_planner\notification_settings::getcoursestartdate
     * @covers \lytix_planner\notification_settings::getcourseenddate
     * @covers \lytix_planner\notification_settings::test_and_set_course
     *
     * @covers \lytix_planner\planner_event_lib::planner_event
     * @covers \lytix_planner\planner_event_lib::planner_event_parameters
     * @covers \lytix_planner\planner_event_lib::planner_event_returns
     *
     * @covers \lytix_planner\dynamic_events::set_event_types
     * @covers \lytix_planner\dynamic_events::get_event_types
     *
     * @covers \lytix_planner\planner_event_lib::planner_event_completed
     * @covers \lytix_planner\planner_event_lib::planner_event_completed_parameters
     * @covers \lytix_planner\planner_event_lib::planner_event_completed_returns
     *
     * @covers \lytix_planner\planner_event_lib::planner_delete_event
     * @covers \lytix_planner\planner_event_lib::planner_delete_event_parameters
     * @covers \lytix_planner\planner_event_lib::planner_delete_event_returns
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \invalid_response_exception
     * @throws \restricted_context_exception
     */
    public function test_event() {
        list($eventdate, $eventenddate, $title, $visible, $mandatory, $graded, $formdata) =
            $this->generate_formdata();

        $result = planner_event_lib::planner_event($this->context->id, $formdata);
        external_api::clean_returnvalue(planner_event_lib::planner_event_returns(), $result);

        // Check if the event is correctly created.
        $event = planner_get::service($this->course->id, $this->context->id, false);

        $this->assertTrue(key_exists('items', $event));
        $this::assertEquals(1, count($event['items']));

        $this::assertEquals($this->course->id, $event['items'][0]->courseid);
        $this::assertEquals($title, $event['items'][0]->title);
        $this::assertEquals($eventdate->getTimestamp(), $event['items'][0]->startdate);
        $this::assertEquals($visible, $event['items'][0]->visible);
        $this::assertEquals($mandatory, $event['items'][0]->mandatory);
        $this::assertEquals($graded, $event['items'][0]->graded);

        external_api::clean_returnvalue(planner_get::service_returns(), $event);

        $completed = 1;
        // Test completion.
        $formdatacompleted = "\"eventid=" . $event['items'][0]->id . "&courseid=" . $this->course->id . "&userid=" .
                             get_admin()->id . "&timestamp=" . $eventenddate->getTimestamp() .
                             "&completed=" . $completed . "\"";

        $result = planner_event_lib::planner_event_completed($this->context->id, $formdatacompleted);
        external_api::clean_returnvalue(planner_event_lib::planner_event_completed_returns(), $result);

        // Call for both cases.
        $result = planner_event_lib::planner_event_completed($this->context->id, $formdatacompleted);
        external_api::clean_returnvalue(planner_event_lib::planner_event_completed_returns(), $result);

        // Check if event was completed correctly.
        $completeevent = planner_get::service($this->course->id, $this->context->id, false);

        $this->assertTrue(key_exists('items', $completeevent));
        $this::assertEquals(1, count($completeevent['items']));

        $this::assertEquals($completeevent['items'][0]->id, $event['items'][0]->id);
        $this::assertEquals(1, $completeevent['items'][0]->completed);

        external_api::clean_returnvalue(planner_get::service_returns(), $completeevent);

        // Now delete event.
        $result = planner_event_lib::planner_delete_event($this->context->id, $this->course->id, get_admin()->id,
                                                          $completeevent['items'][0]->id);
        external_api::clean_returnvalue(planner_event_lib::planner_delete_event_returns(), $result);

        // Check if it is deleted correctly.
        $deleteevent = planner_get::service($this->course->id, $this->context->id, false);
        $this::assertEquals(0, count($deleteevent['items']));

        external_api::clean_returnvalue(planner_get::service_returns(), $deleteevent);
    }

    /**
     * Test event with custom english type.
     * @covers \lytix_planner\planner_event_lib::planner_event
     * @covers \lytix_planner\planner_event_lib::planner_event_parameters
     * @covers \lytix_planner\planner_event_lib::planner_event_returns
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \invalid_response_exception
     * @throws \restricted_context_exception
     */
    public function test_event_select_other_english() {
        list($eventdate, $eventenddate, $title, $visible, $mandatory, $graded, $formdata) =
            $this->generate_formdata(-1, true);

        $result = planner_event_lib::planner_event($this->context->id, $formdata);
        external_api::clean_returnvalue(planner_event_lib::planner_event_returns(), $result);
        self::assertIsArray($result);
    }

    /**
     * Test event with custom types.
     * @covers \lytix_planner\planner_event_lib::planner_event
     * @covers \lytix_planner\planner_event_lib::planner_event_parameters
     * @covers \lytix_planner\planner_event_lib::planner_event_returns
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \invalid_response_exception
     * @throws \restricted_context_exception
     */
    public function test_event_select_other_both() {
        list($eventdate, $eventenddate, $title, $visible, $mandatory, $graded, $formdata) =
            $this->generate_formdata(-1, true, true);

        $result = planner_event_lib::planner_event($this->context->id, $formdata);
        external_api::clean_returnvalue(planner_event_lib::planner_event_returns(), $result);
        self::assertIsArray($result);
    }

    /**
     * Test event edit or delete with formdata.
     * @covers \lytix_planner\planner_event_lib::planner_event
     * @covers \lytix_planner\planner_event_lib::planner_event_parameters
     * @covers \lytix_planner\planner_event_lib::planner_event_returns
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \invalid_response_exception
     * @throws \restricted_context_exception
     */
    public function test_event_edit_delete() {
        global $DB;
        // Case add.
        list($eventdate, $eventenddate, $title, $visible, $mandatory, $graded, $formdata) =
            $this->generate_formdata();

        $result = planner_event_lib::planner_event($this->context->id, $formdata);
        external_api::clean_returnvalue(planner_event_lib::planner_event_returns(), $result);
        self::assertIsArray($result);

        $record = $DB->get_record('lytix_planner_events', ['courseid' => $this->course->id]);
        // Case edit.
        list($eventdate, $eventenddate, $title, $visible, $mandatory, $graded, $formdata) =
            $this->generate_formdata($record->id);

        $result = planner_event_lib::planner_event($this->context->id, $formdata);
        external_api::clean_returnvalue(planner_event_lib::planner_event_returns(), $result);
        self::assertIsArray($result);

        // Case delete.
        list($eventdate, $eventenddate, $title, $visible, $mandatory, $graded, $formdata) =
            $this->generate_formdata($record->id, false, false, true);

        $result = planner_event_lib::planner_event($this->context->id, $formdata);
        external_api::clean_returnvalue(planner_event_lib::planner_event_returns(), $result);
        self::assertIsArray($result);
    }

    /**
     * Create a milestone, complete and delete it.
     *
     * @covers ::service
     * @covers ::service_parameters
     * @covers ::service_returns
     *
     * @covers \lytix_planner\planner_milestone_lib::planner_milestone
     * @covers \lytix_planner\planner_milestone_lib::planner_milestone_parameters
     * @covers \lytix_planner\planner_milestone_lib::planner_milestone_returns
     *
     * @covers \lytix_planner\planner_milestone_lib::planner_delete_milestone
     * @covers \lytix_planner\planner_milestone_lib::planner_delete_milestone_parameters
     * @covers \lytix_planner\planner_milestone_lib::planner_delete_milestone_returns
     *
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \invalid_response_exception
     * @throws \restricted_context_exception
     */
    public function test_milestone() {
        $milestonedate = new \DateTime('now');
        $milestonedate->setTime($milestonedate->format('G'), $milestonedate->format('i'), 00);
        $milestoneenddate = new \DateTime('now');
        $milestoneenddate->setTime($milestoneenddate->format('G'), $milestoneenddate->format('i'), 00);
        $milestoneenddate->modify('+' . 2 . ' hours');
        $title     = "Title";
        $offset    = 2;
        $option    = 'email';
        $completed = 0;

        $formdata = "\"id=-1&courseid=" . $this->course->id . "&userid=" . get_admin()->id .
                    "&type=&marker=M&sesskey=LC85D7m7br&_qf__lytix_planner_forms_milestone_form=1" .
                    "&startdate%5Bday%5D=" . $milestonedate->format('j') . "&startdate%5Bmonth%5D=" .
                    $milestonedate->format('n') . "&startdate%5Byear%5D=" . $milestonedate->format('Y') . "&startdate%5Bhour%5D=" .
                    $milestonedate->format('G') . "&startdate%5Bminute%5D=" . $milestonedate->format('i') . "&hour=" .
                    $milestoneenddate->format('G') . "&minute=" . $milestoneenddate->format('i') . "&title=" .
                    $title . "&text%5Btext%5D=&text%5Bformat%5D=1&room%5Btext%5D=&room%5Bformat%5D=1&moffset=" .
                    $offset . "&moption=" . $option . "&completed=" . $completed . "\"";

        $result = planner_milestone_lib::planner_milestone($this->context->id, $formdata);
        external_api::clean_returnvalue(planner_milestone_lib::planner_milestone_returns(), $result);

        // Check if the milestone is correctly created.
        $milestone = planner_get::service($this->course->id, $this->context->id, true);

        $this->assertTrue(key_exists('items', $milestone));
        $this::assertEquals(1, count($milestone['items']));

        $this::assertEquals($this->course->id, $milestone['items'][0]->courseid);
        $this::assertEquals($title, $milestone['items'][0]->title);
        $this::assertEquals($milestonedate->getTimestamp(), $milestone['items'][0]->startdate);
        $this::assertEquals(0, $milestone['items'][0]->completed);
        $this::assertEquals('Milestone', $milestone['items'][0]->type);
        $this::assertEquals($offset, $milestone['items'][0]->moffset);
        $this::assertEquals($option, $milestone['items'][0]->moption);

        external_api::clean_returnvalue(planner_get::service_returns(), $milestone);

        $completed = 1;
        // Test completion.
        $formdatacompleted = "\"id=" . $milestone['items'][0]->id . "&courseid=" . $this->course->id . "&userid=" .
                             get_admin()->id . "&type=&marker=M&sesskey=LC85D7m7br&_qf__lytix_planner_forms_milestone_form=1" .
                             "&startdate%5Bday%5D=" . $milestonedate->format('j') . "&startdate%5Bmonth%5D=" .
                             $milestonedate->format('n') . "&startdate%5Byear%5D=" . $milestonedate->format('Y') .
                             "&startdate%5Bhour%5D=" .
                             $milestonedate->format('G') . "&startdate%5Bminute%5D=" . $milestonedate->format('i') . "&hour=" .
                             $milestoneenddate->format('G') . "&minute=" . $milestoneenddate->format('i') .
                             "&type=0&mgroup=0&title=" .
                             $title . "&text%5Btext%5D=&text%5Bformat%5D=1&room%5Btext%5D=&room%5Bformat%5D=1&moffset=" .
                             $offset . "&moption=" . $option . "email&completed=" . $completed . "\"";

        $result = planner_milestone_lib::planner_milestone($this->context->id, $formdatacompleted);
        external_api::clean_returnvalue(planner_milestone_lib::planner_milestone_returns(), $result);

        // Check if milestone was completed correctly.
        $completemilestone = planner_get::service($this->course->id, $this->context->id, true);

        $this->assertTrue(key_exists('items', $completemilestone));
        $this::assertEquals(1, count($completemilestone['items']));

        $this::assertEquals($completemilestone['items'][0]->id, $milestone['items'][0]->id);
        $this::assertEquals(1, $completemilestone['items'][0]->completed);

        external_api::clean_returnvalue(planner_get::service_returns(), $completemilestone);

        // Now delete the milestone.
        $result = planner_milestone_lib::planner_delete_milestone($this->context->id, $this->course->id, get_admin()->id,
                                                                  $completemilestone['items'][0]->id);
        external_api::clean_returnvalue(planner_milestone_lib::planner_delete_milestone_returns(), $result);

        // Check if it is deleted correctly.
        $deleteevent = planner_get::service($this->course->id, $this->context->id, true);
        $this::assertEquals(0, count($deleteevent['items']));

        external_api::clean_returnvalue(planner_get::service_returns(), $deleteevent);
    }

    /**
     * Helper function to generate the formdata.
     * @param int $eventid
     * @param bool $english
     * @param bool $german
     * @param bool $delete
     * @return array
     */
    public function generate_formdata($eventid = -1, $english = false, $german = false, $delete = false): array {
        $eventdate = new \DateTime('now');
        $eventdate->setTime($eventdate->format('G'), $eventdate->format('i'), 00);
        $eventenddate = new \DateTime('now');
        $eventenddate->setTime($eventenddate->format('G'), $eventenddate->format('i'), 00);
        $eventenddate->modify('+' . 2 . ' hours');
        $title = "Title";
        $visible = 1;
        $mandatory = 0;
        $graded = 0;
        if ($english) {
            $englishstr = "";
        } else {
            $englishstr = "&select_other_english=English";
        }
        if ($german) {
            $germanstr = "";
        } else {
            $germanstr = "&select_other_german=German";
        }
        if ($delete) {
            $deletestr = "&delete=0";
        } else {
            $deletestr = "&delete=1";
        }

        $formdata = "\"id=". $eventid . "&courseid=" . $this->course->id .
            "&disable=1&sesskey=DAXXc2CT4Q&_qf__lytix_planner_forms_event_form=1" .
            "&startdate%5Bday%5D=" . $eventdate->format('j') . "&startdate%5Bmonth%5D=" .
            $eventdate->format('n') . "&startdate%5Byear%5D=" . $eventdate->format('Y') . "&startdate%5Bhour%5D=" .
            $eventdate->format('G') . "&startdate%5Bminute%5D=" . $eventdate->format('i') . "&enddatehour=" .
            $eventenddate->format('G') . "&enddateminute=" . $eventenddate->format('i') . "&moreevents=0" .
            "&type=0&mgroup=0&title=" . $title . $englishstr . $germanstr .$deletestr .
            "&text%5Btext%5D=&text%5Bformat%5D=1&room%5Btext%5D=&room%5Bformat%5D=1&visible=0&visible=" .
            $visible . "&mandatory=" . $mandatory . "&graded=" . $graded . "\"";
        return array($eventdate, $eventenddate, $title, $visible, $mandatory, $graded, $formdata);
    }
}
