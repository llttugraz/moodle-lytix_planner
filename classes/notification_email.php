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
 * @author     Guenther Moser
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_planner;

use context_course;
use core_calendar\local\event\forms\create;
use lytix_planner\notification_settings;
use local_lytix\helper\tests;
use lytix_logs\logger;
use PhpOffice\PhpSpreadsheet\Calculation\DateTime;

/**
 * Class notification_email
 */
class notification_email {

    /**
     * Name of the plugin.
     * @var string
     */
    private $component;

    /**
     * notification_email constructor.
     */
    public function __construct() {
        $this->component = 'lytix_planner';
    }

    /**
     * Sets last report by inersting the record in lytix_planner_last_report.
     * @param int $courseid
     * @param int $timestamp
     * @return \stdClass
     * @throws \dml_exception
     */
    private function set_last_report($courseid, $timestamp) {
        global $DB;

        $record            = new \stdClass();
        $record->courseid  = $courseid;
        $record->timestamp = $timestamp;

        $record->id = $DB->insert_record('lytix_planner_last_report', $record);
        return $record;
    }

    /**
     * Sends email to a specified user.
     * @param \stdClass $user
     * @param string    $subject
     * @param string    $body
     */
    private function email_to_user(\stdClass $user, string $subject, string $body) {
        static $noreplyuser = null;
        if ($noreplyuser === null) {
            $noreplyuser = \core_user::get_noreply_user();
        }
        email_to_user($user, $noreplyuser, $subject, $body, text_to_html($body));
    }

    /**
     * Creates a string from the subjects.
     * @param array|null $subjects
     * @return string
     */
    private function build_list($subjects) {
        $strsubject = "";
        for ($i = 0; $i < count($subjects); $i++) {
            $strsubject = $strsubject . " " . $subjects[$i]->title;
            if ($i < count($subjects) - 1) {
                $strsubject = $strsubject . ", ";
            } else {
                $strsubject = $strsubject . ".";
            }
        }
        return $strsubject;
    }

    /**
     * Build the deadline subject and text.
     * @param int $courseid
     * @param array|false $subjects
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function build_deadline_strings(int $courseid, $subjects) {
        global $CFG;

        $course = get_course($courseid);
        $params = [
            'subjects' => $this->build_list($subjects),
            'courseurl' => $CFG->wwwroot . '/local/lytix/index.php?id=' . $course->id,
            'course' => $course->fullname,
        ];

        $emailsubject = get_string('deadline_subject', $this->component, $course->fullname);
        $emailbody    =
            get_string('deadline_text', $this->component, $params) .
            get_string('footer', $this->component);

        return [$emailsubject, $emailbody];
    }

    /**
     * Function that sends out the planner notifications.
     * @param int $courseid
     * @throws \dml_exception
     */
    public function planner_notifications($courseid) {
        global $DB;

        $now = new \DateTime('now');
        $context = context_course::instance($courseid);
        $studentroleid = $DB->get_record('role', ['shortname' => 'student'], '*')->id;
        $users = get_role_users($studentroleid, $context);

        $crssettings = notification_settings::test_and_set_course($courseid);
        if ($crssettings->enable_course_notifications) {
            // Get events.
            $params['courseid'] = $courseid;
            $params['date']     = $now->getTimestamp();

            // Get all relevant events.
            $sql = "SELECT * FROM {lytix_planner_events} events
                WHERE events.courseid = :courseid AND events.startdate > :date
                AND events.visible = 1 AND events.send = 0 ORDER BY events.startdate";

            $events = $DB->get_records_sql($sql, $params);

            // Get all not completed milestones.
            $sql = "SELECT * FROM {lytix_planner_milestone} mlstn
                WHERE mlstn.courseid = :courseid AND mlstn.startdate > :date
                AND mlstn.completed = 0 AND mlstn.send = 0 ORDER BY mlstn.userid";

            $mlstns = $DB->get_records_sql($sql, $params);

            // Get all completed events.
            $sql = "SELECT * FROM {lytix_planner_event_comp} compl
                WHERE compl.courseid = :courseid AND (compl.completed = 1
                OR compl.send = 1) ORDER BY compl.userid";

            $completed = $DB->get_records_sql($sql, $params);

            // Store all completed events in array key is eventid.
            $completedevents = [];
            foreach ($completed as $comp) {
                $completedevents[$comp->eventid][] = $comp;
            }

            // Get all user settings.
            $sql = "SELECT * FROM {lytix_planner_usr_settings} settings
                    WHERE settings.courseid = :courseid ORDER BY settings.userid";

            $usrsettings = $DB->get_records_sql($sql, $params);

            // Loop the users.
            foreach ($users as $user) {
                $sendmail = [];
                $sendmsg = [];

                // Loop the events.
                foreach ($events as $event) {

                    if (!group_helper::check_group($courseid, $user->id, $event)) {
                        continue;
                    }

                    $found = false;
                    // Check for completion first.
                    if ($completedevents && array_key_exists($event->id, $completedevents)) {
                        foreach ($completedevents[$event->id] as $completedevent) {
                            if ($completedevent->userid == $user->id) {
                                $found = true;
                            }
                        }
                    }
                    if ($found) {
                        continue;
                    }

                    $settings = null;
                    if ($crssettings->enable_user_customization) {
                        foreach ($usrsettings as $key => $usrsetting) {
                            if ($usrsetting->userid == $user->id) {
                                $settings = $usrsetting;
                                unset($usrsettings[$key]);
                            }
                        }

                        // Create the settings once.
                        if (!$settings) {
                            $settings = notification_settings::test_and_set_user($courseid, $user->id);
                        }

                        // Use course settings.
                        if (!$settings->enable_custom_customization) {
                            $settings = $crssettings;
                        }
                    } else {
                        $settings = $crssettings;
                    }

                    // Check to send.
                    $type = explode('_', $event->type);
                    $types = json_decode($settings->types);

                    for ($i = 0; $i < count($types->en); $i++) {
                        if ($types->en[$i] == "Other" || $types->de[$i] == "Sonstige") {
                            continue;
                        }
                        if ($type[0] == $types->en[$i] && $type[1] == $types->de[$i]) {
                            $offset = $types->offset[$i];
                            $option = $types->options[$i];

                            $duedate = (new \DateTime())->setTimestamp($event->startdate);
                            $duedate->setTime(0, 0, 0);

                            $bound = new \DateTime('now');
                            $bound->setTime(0, 0, 0);
                            ($offset > 1) ? $bound->modify("+$offset days")
                                : $bound->modify("+$offset day");

                            if ($duedate->getTimestamp() <= $bound->getTimestamp()) {
                                if ($option == 'email' || $option == 'both') {
                                    $sendmail[] = $event;
                                }
                                if ($option == 'message' || $option == 'both') {
                                    $sendmsg[] = $event;
                                }
                                $record = notification_settings::test_and_set_event_comp($event->id, $courseid, $user->id, 1);
                                $DB->update_record('lytix_planner_event_comp', $record);
                            }
                        }
                    }
                }

                // Loop all relevant milestones.
                foreach ($mlstns as $key => $mlstn) {
                    if ($mlstn->userid == $user->id) {
                        $offset = $mlstn->moffset;

                        $duedate = (new \DateTime())->setTimestamp($mlstn->startdate);
                        $duedate->setTime(0, 0, 0);

                        $bound = new \DateTime('now');
                        $bound->setTime(0, 0, 0);
                        $bound->modify("+$offset days");

                        if ($duedate->getTimestamp() <= $bound->getTimestamp()) {
                            if ($mlstn->moption == 'email' || $mlstn->moption == 'both') {
                                $sendmail[] = $mlstn;
                            }
                            if ($mlstn->moption == 'message' || $mlstn->moption == 'both') {
                                $sendmsg[] = $mlstn;
                            }
                            $mlstn->send = 1;
                            $DB->update_record('lytix_planner_milestone', $mlstn);
                        }
                        unset($mlstns[$key]);
                    }
                }

                // Send mails.
                if ($sendmail) {
                    $this->send_notification_mails($user, $courseid, $sendmail);
                    logger::add($user->id, $courseid, $context->id,
                        logger::TYPE_MAIL, json_encode($sendmail), $courseid);
                }

                // Write messages.
                if ($sendmsg) {
                    $this->write_notification_messages($user, $courseid, $sendmsg);
                    logger::add($user->id, $courseid, $context->id,
                        logger::TYPE_MESSAGE, json_encode($sendmsg), $courseid);
                }
            }
            // Update the events.
            foreach ($events as $event) {
                if ($event->send == 1) {
                    $DB->update_record('lytix_planner_events', $event);
                }
            }
        }
    }

    /**
     * Sends notification mails.
     * @param \stdClass|null $user
     * @param int $courseid
     * @param array|null $subjects
     * @throws \dml_exception
     */
    private function send_notification_mails($user, $courseid, $subjects) {
        list($emailsubject, $emailbody) = $this->build_deadline_strings($courseid, $subjects);

        $this->email_to_user($user, $emailsubject, $emailbody);
    }

    /**
     * Send the notification messages.
     * @param \stdClass|null $user
     * @param int $courseid
     * @param array|null $subjects
     * @throws \coding_exception
     */
    private function write_notification_messages($user, $courseid, $subjects) {
        global $CFG;
        $url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
        $course = get_course($courseid);

        list($smallmessage, $fullmessage) = $this->build_deadline_strings($courseid, $subjects);

        $message = new \core\message\message();
        $message->courseid = $courseid;
        $message->component = 'lytix_planner';
        $message->name = 'notification_message';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = \core_user::get_user($user->id);
        $message->subject = $smallmessage;
        $message->fullmessage = $fullmessage;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->smallmessage = $message->subject;
        $message->notification = 1;
        $message->contexturl = $url;
        $message->contexturlname = $course->fullname;

        message_send($message);
    }

    /**
     * Creates monthly report for users.
     * @param int $courseid
     * @throws \dml_exception
     */
    public function mail_monthly_report($courseid) {
        global $DB;

        $course = get_course($courseid);
        if (is_null($course)) {
            return;
        }

        // Check if notifications are enabled for this course.
        $crssettings = notification_settings::test_and_set_course($courseid);
        if (!$crssettings->enable_course_notifications) {
            return;
        }

        $now   = new \DateTime('now');
        $start = null;

        $params['courseid'] = $courseid;

        $sql = "SELECT * FROM {lytix_planner_last_report} reports WHERE reports.courseid = :courseid
                ORDER BY timestamp DESC";

        $record = $DB->get_records_sql($sql, $params);

        if ($record && reset($record) && reset($record)->timestamp) {
            $month = (new \DateTime())->setTimestamp(reset($record)->timestamp);
            $start = (new \DateTime())->setTimestamp(reset($record)->timestamp);
        } else {
            $month = (new \DateTime())->setTimestamp(notification_settings::getcoursestartdate($courseid));
            $start = (new \DateTime())->setTimestamp(notification_settings::getcoursestartdate($courseid));
        }

        $end = (new \DateTime())->setTimestamp(notification_settings::getcourseenddate($courseid));
        date_add($month, date_interval_create_from_date_string('1 month'));
        if ($now->getTimestamp() >= $month->getTimestamp() && $now->getTimestamp() <= $end->getTimestamp()) {
            $context       = context_course::instance($courseid);
            $studentroleid = $DB->get_record('role', ['shortname' => 'student'], '*')->id;
            $users         = get_role_users($studentroleid, $context);
            foreach ($users as $user) {

                // Get all events of course in timeframe.
                $params['courseid'] = $courseid;
                $params['start']    = $start->getTimestamp();
                $params['stop']     = $now->getTimestamp();

                $sql = "SELECT * FROM {lytix_planner_events} events
                WHERE events.courseid = :courseid AND events.visible = 1 AND events.startdate > :start
                AND events.startdate <= :stop";

                $events = $DB->get_records_sql($sql, $params);

                // Get all event_compl for user in timeframe.
                $params['courseid'] = $courseid;
                $params['userid']   = $user->id;
                $params['start']    = $start->getTimestamp();
                $params['stop']     = $now->getTimestamp();

                $sql = "SELECT * FROM {lytix_planner_event_comp} events
                WHERE events.courseid = :courseid AND events.userid = :userid AND events.timestamp > :start
                AND events.timestamp <= :stop";

                $completedevents = $DB->get_records_sql($sql, $params);

                $allevents = true;
                foreach ($completedevents as $completd) {
                    if ($completd->completed == 0) {
                        $allevents = false;
                    }
                }

                $allmandatorygraded = true;
                $mandatoryexists    = false;
                foreach ($events as $event) {
                    if ($event->mandatory) {
                        $mandatoryexists = true;
                        $found           = false;
                        $completed       = true;
                        foreach ($completedevents as $completedevent) {
                            if ($completedevent->eventid == $event->id) {
                                $found = true;
                                if (!$completedevent->completed) {
                                    $completed = false;
                                }
                            }
                        }
                        if (!$found || !$completed) {
                            $allmandatorygraded = false;
                        }
                    }
                }

                if ($allevents && count($events) == count($completedevents)) {
                    if (count($events)) {
                        $this->all_events_completed($course, $user);
                        logger::add($user->id, $course->id, $context->id, logger::TYPE_MAIL,
                            'report: all_events_completed', $courseid);
                    }
                } else if ($allmandatorygraded) {
                    if ($mandatoryexists) {
                        $this->all_mandatory_and_graded_completed($course, $user);
                        logger::add($user->id, $course->id, $context->id, logger::TYPE_MAIL,
                            'report: all_mandatory_and_graded_completed', $courseid);
                    }
                } else {
                    $shouldcomplete   = 0;
                    $notcompletedlist = [];
                    foreach ($events as $event) {
                        if ($event->mandatory) {
                            $shouldcomplete++;
                            $found = false;
                            foreach ($completedevents as $completedevent) {
                                if ($completedevent->eventid == $event->id) {
                                    $found = true;
                                    if (!$completedevent->completed) {
                                        $notcompletedlist[] = $event;
                                    }
                                }
                            }
                            if (!$found) {
                                $notcompletedlist[] = $event;
                            }
                        }
                    }
                    if ($shouldcomplete) {
                        $this->some_mandatory_and_graded_not_completed(
                                $course, $user, $notcompletedlist, $shouldcomplete);
                        logger::add($user->id, $course->id, $context->id, logger::TYPE_REPORT,
                             'report: some_mandatory_and_graded_not_completed', $courseid);
                    }
                }
            }
        }
        $now = new \DateTime('now');
        $this->set_last_report($course->id, $now->getTimestamp());
    }

    /**
     * Monthly Report - Case all events completed.
     * @param false|mixed|\stdClass $course
     * @param false|mixed|\stdClass $user
     */
    public function all_events_completed($course, $user) {
        global $CFG;

        $params = [
            'courseurl' => $CFG->wwwroot . '/local/lytix/index.php?id=' . $course->id,
            'course' => $course->fullname,
        ];

        $emailsubject = get_string('report_subject', $this->component, $course->fullname);
        $emailbody    =
            get_string('report_text_all_completed', $this->component, $params) .
            get_string('footer', $this->component);

        $this->email_to_user($user, $emailsubject, $emailbody);
    }

    /**
     * Monthly Report - Case all mandatory and graded events completed.
     * @param false|mixed|\stdClass $course
     * @param false|mixed|\stdClass $user
     */
    public function all_mandatory_and_graded_completed($course, $user) {
        global $CFG;

        $params = [
            'courseurl' => $CFG->wwwroot . '/local/lytix/index.php?id=' . $course->id,
            'course' => $course->fullname,
        ];

        $emailsubject = get_string('report_subject', $this->component, $course->fullname);
        $emailbody    =
            get_string('report_text_mandatory_completed', $this->component, $params) .
            get_string('footer', $this->component);

        $this->email_to_user($user, $emailsubject, $emailbody);
    }

    /**
     * Monthly Report - Case some mandatory and graded events completed.
     * @param false|mixed|\stdClass $course
     * @param false|mixed|\stdClass $user
     * @param array $notcompletedlist
     * @param int $shouldcomplete
     */
    public function some_mandatory_and_graded_not_completed($course, $user, $notcompletedlist, $shouldcomplete) {
        global $CFG;

        $params = [
            'courseurl' => $CFG->wwwroot . '/local/lytix/index.php?id=' . $course->id,
            'course' => $course->fullname,
            'complete' => $shouldcomplete - count($notcompletedlist),
            'shouldcomplete' => $shouldcomplete,
            'missedevents' => $this->build_list($notcompletedlist)
        ];

        $emailsubject = get_string('report_subject', $this->component, $course->fullname);
        $emailbody    =
            get_string('report_text_not_completed', $this->component, $params) .
            get_string('footer', $this->component);

        $this->email_to_user($user, $emailsubject, $emailbody);
    }
}
