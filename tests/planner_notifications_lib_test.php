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
require_once("{$CFG->dirroot}/webservice/tests/helpers.php");

use external_api;
use externallib_advanced_testcase;

/**
 * Class planner_notifications_lib
 *
 * @runTestsInSeparateProcesses
 * @coversDefaultClass \lytix_planner\planner_notifications_lib
 */
class planner_notifications_lib_test extends externallib_advanced_testcase {
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
     * Variable for the coursesettings
     *
     * @var \stdClass|null
     */
    private $crssettings = null;

    /**
     * Setup called before any test case.
     */
    public function setUp(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        global $CFG;
        require_once("{$CFG->libdir}/externallib.php");

        $this->course = $this->getDataGenerator()->create_course();
        $this->context  = \context_course::instance($this->course->id);

        $twomonthsago = new \DateTime('2 months ago');
        $inthreemonths = new \DateTime('now');
        date_add($inthreemonths, date_interval_create_from_date_string('3 months'));
        set_config('semester_start', $twomonthsago->format('Y-m-d'), 'local_lytix');
        set_config('semester_end', $inthreemonths->format('Y-m-d'), 'local_lytix');
        set_config('course_list', $this->course->id, 'local_lytix');
        set_config('platform', 'learners_corner', 'local_lytix');

        $this->crssettings = notification_settings::test_and_set_course($this->course->id);
    }

    /**
     * Test store_course_notification_settings
     * @covers ::store_course_notification_settings
     * @covers ::store_course_notification_settings_returns
     * @covers ::store_course_notification_settings_parameters
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \invalid_response_exception
     * @throws \restricted_context_exception
     */
    public function test_store_course_notification_settings() {
        $jsonformdata = $this->generate_course_jsonformdata(0, 0, 0, 0);

        $result = planner_notifications_lib::store_course_notification_settings(
            $this->context->id, $this->course->id, $jsonformdata);
        external_api::clean_returnvalue(planner_notifications_lib::store_course_notification_settings_returns(), $result);
        self::assertTrue($result['success']);
    }

    /**
     * Test store_course_notification_settings
     * @covers ::store_course_notification_settings
     * @covers ::store_course_notification_settings_returns
     * @covers ::store_course_notification_settings_parameters
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \invalid_response_exception
     * @throws \restricted_context_exception
     */
    public function test_store_course_notification_settings_rename() {
        $jsonformdata = $this->generate_course_jsonformdata(0, 0, 0, 1);

        $result = planner_notifications_lib::store_course_notification_settings(
            $this->context->id, $this->course->id, $jsonformdata);
        external_api::clean_returnvalue(planner_notifications_lib::store_course_notification_settings_returns(), $result);
        self::assertTrue($result['success']);
    }

    /**
     * Test store_course_notification_settings
     * @covers ::store_course_notification_settings
     * @covers ::store_course_notification_settings_returns
     * @covers ::store_course_notification_settings_parameters
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     * @throws \invalid_response_exception
     * @throws \restricted_context_exception
     */
    public function test_store_course_notification_settings_delete() {
        $jsonformdata = $this->generate_course_jsonformdata(0, 0, 1, 0);

        $result = planner_notifications_lib::store_course_notification_settings(
            $this->context->id, $this->course->id, $jsonformdata);
        external_api::clean_returnvalue(
            planner_notifications_lib::store_course_notification_settings_returns(), $result);
        self::assertTrue($result['success']);
    }

    /**
     * Helper function to fake the data from the form.
     * @param int $notifications
     * @param int $customization
     * @param int $delete
     * @param bool $rename
     * @return string
     * @throws \dml_exception
     */
    private function generate_course_jsonformdata($notifications, $customization, $delete, $rename) {
        $start = new \DateTime(get_config('local_lytix', 'semester_start' ));
        $end = new \DateTime(get_config('local_lytix', 'semester_end' ));

        if (!$rename) {
            $english = 'Lecture';
            $german = 'Vorlesung';
        } else {
            $english = 'NewLecture';
            $german = 'VorlesungNeu';
        }

        $formdata = "\"id=-1&courseid=" . $this->course->id .
            "&sesskey=MySuperCoolSessionKey&_qf__lytix_planner_forms_event_form=1&softlock=1&start_time%5Bday%5D="
            . $start->format('j') . "&start_time%5Bmonth%5D=" . $start->format('n') .
            "&start_time%5Byear%5D=" . $start->format('Y') . "&end_time%5Bday%5D=" . $end->format('j') .
            "&end_time%5Bmonth%5D=" . $end->format('n') . "&end_time%5Byear%5D=" . $end->format('Y') .
            "&enable_course_notifications=" . $notifications . "&enable_user_customization=" . $customization .
            "&englishLecture=" . $english . "&germanLecture=" . $german
            . "&deleteLecture=" . $delete .
            "&englishQuiz=Quiz&germanQuiz=Quiz&deleteQuiz=" . $delete
            . "&englishAssignment=Assignment&germanAssignment=Aufgabe".
            "&deleteAssignment=" . $delete . "&englishFeedback=Feedback&germanFeedback=Feedback" .
            "&deleteFeedback=" . $delete .
            "&englishExam=Exam&germanExam=Pr%C3%BCfung" ."&deleteExam=" . $delete .
            "&englishInterview=Interview&germanInterview=Abgabegespr%C3%A4ch" .
            "&deleteInterview=" . $delete .
            "&new_type=1&select_other_german=NewType&select_other_english=NewType" .
            "\"";

        return $formdata;
    }
}
