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
 * @author     Günther Moser
 * @copyright  2020 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_planner\forms;

defined('MOODLE_INTERNAL') || die();

use context_course;
use moodleform;
use lytix_helper\forms_helper;

global $CFG;

// Moodleform is defined in formslib.php.
require_once("$CFG->libdir/formslib.php");

/**
 * Class event_completed_form
 */
class event_completed_form extends moodleform {
    /**
     * Add elements to form.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function definition() {
        global $COURSE, $DB;
        $lang = current_language();

        $record = new \stdClass();
        if ($DB->record_exists('lytix_planner_event_comp', ['eventid'  => $this->_customdata['eventid'],
                                                            'courseid' => $COURSE->id,
                                                            'userid'   => $this->_customdata['userid']])) {
            $record        = $DB->get_record('lytix_planner_event_comp', ['eventid'  => $this->_customdata['eventid'],
                                                                          'courseid' => $COURSE->id,
                                                                          'userid'   => $this->_customdata['userid']]);
            $record->title = $this->_customdata['title'];
            $record->text  = $this->_customdata['text'];
        } else {
            $record->eventid   = $this->_customdata['eventid'];
            $record->courseid  = $COURSE->id;
            $record->userid    = $this->_customdata['userid'];
            $record->title     = $this->_customdata['title'];
            $record->text      = $this->_customdata['text'];
            $record->completed = 0;
        }

        $record->startdate = $DB->get_record('lytix_planner_events', ['id' => $this->_customdata['eventid']])->startdate;
        $record->enddate   = $DB->get_record('lytix_planner_events', ['id' => $this->_customdata['eventid']])->enddate;
        $record->room      = $DB->get_record('lytix_planner_events', ['id' => $this->_customdata['eventid']])->room;
        $record->mandatory = $DB->get_record('lytix_planner_events', ['id' => $this->_customdata['eventid']])->mandatory;
        $record->graded    = $DB->get_record('lytix_planner_events', ['id' => $this->_customdata['eventid']])->graded;
        $record->points    = $DB->get_record('lytix_planner_events', ['id' => $this->_customdata['eventid']])->points;
        $record->gradeitem    = $DB->get_record('lytix_planner_events', ['id' => $this->_customdata['eventid']])->gradeitem;

        $grade = null;
        if ($record->graded && $record->gradeitem != get_string('connect_gradebook', 'lytix_planner')) {
            $params['courseid'] = $COURSE->id;
            $params['itemname'] = $record->gradeitem;
            $params['userid'] = $this->_customdata['userid'];

            $sql = "SELECT grade.finalgrade
                  FROM {grade_items} items INNER JOIN {grade_grades} grade
                  ON grade.itemid = items.id WHERE items.courseid = :courseid
                  AND grade.userid = :userid AND items.itemname = :itemname";
            $grade = $DB->get_record_sql($sql, $params);
            $grade = json_decode(json_encode($grade), true);
        }

        $component = 'lytix_planner';
        $mform     = $this->_form; // Don't forget the underscore!

        $mform->addElement('hidden', 'eventid', $record->eventid);
        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->addElement('hidden', 'userid', $record->userid);

        $mform->addElement('html', '<h4>' . $record->title . '</h4>');
        if ($lang == "en") {
            $mform->addElement('html', '<h5 style="font-weight: bold;">' . get_string('due_date', $component) .
                                       (new \DateTime())->setTimestamp($record->startdate)->format('Y-m-d H:i') . ' - ' .
                                       (new \DateTime())->setTimestamp($record->enddate)->format('H:i') . '</h5>');
        } else if ($lang == "de") {
            $mform->addElement('html', '<h5 style="font-weight: bold;">' . get_string('due_date', $component) . '</h5>' . '<h5>' .
                                       (new \DateTime())->setTimestamp($record->startdate)->format('d-m-Y H:i') . ' - ' .
                                       (new \DateTime())->setTimestamp($record->enddate)->format('H:i') . '</h5>');
        }

        if ($record->room != null) {
            $mform->addElement('html', '<h5 style="font-weight: bold;">' . get_string('set_room', $component) . '</h5>' . '<h5>' .
                                       $record->room . '</h5>');
        }

        $mform->addElement('html', '<p>' . $record->text . '</p>');

        if ($record->mandatory) {
            $mform->addElement('html', get_string('mandatory', $component));
        }
        if ($record->graded && $record->completed) {
            $mform->addElement('html', '<h5>' . get_string('set_points', $component) . " " . $record->points . '</h5>');
        }
        if ($record->graded && $grade['finalgrade']) {
            $mform->addElement('html', '<h5>' . get_string('set_gradeitem', $component) . " " .
                                       round($grade['finalgrade'], 2) . '</h5>');
        }

        $coursecontext = context_course::instance($COURSE->id);

        $studentroleid = $DB->get_record('role', ['shortname' => 'student'], '*')->id;
        $students      = get_role_users($studentroleid, $coursecontext);

        $counteventcompleted = $DB->count_records_sql("
                SELECT COUNT(*)
                FROM {lytix_planner_event_comp} eventcomp
                WHERE eventcomp.eventid= $record->eventid AND eventcomp.courseid = $record->courseid AND eventcomp.completed = 1");

        $mform->addElement('text', 'countcompleted', get_string('countcompleted', $component));
        $mform->addHelpButton('countcompleted', 'countcompleted', $component);
        $mform->setDefault('countcompleted', $counteventcompleted . "/" . count($students));
        $mform->disabledIf('countcompleted', 'eventid');

        $mform->addElement('advcheckbox', 'completed', get_string('set_completed', $component));
        $mform->setDefault('completed', $record->completed);
        $mform->addHelpButton('completed', 'set_completed', $component);

        $mform->addElement('hidden', 'timestamp', time());

    }
}
