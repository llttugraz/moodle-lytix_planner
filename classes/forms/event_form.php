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
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_planner\forms;

defined('MOODLE_INTERNAL') || die();

use context_course;
use lytix_helper\forms_helper;
use moodleform;
use lytix_planner\dynamic_events;

global $CFG;

// Moodleform is defined in formslib.php.
require_once("$CFG->libdir/formslib.php");

/**
 * Class event_form
 */
class event_form extends moodleform {
    /**
     * Add elements to form.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function definition() {
        global $COURSE, $DB;
        $lang = current_language();
        $component = 'lytix_planner';
        $mform     = $this->_form; // Don't forget the underscore!
        $params = array('courseid' => $COURSE->id);
        $sql = "SELECT *
                  FROM {grade_items} grade
                 WHERE (grade.courseid = :courseid  AND NOT (grade.itemname = 'NULL'))";
        $grades = $DB->get_records_sql($sql, $params);
        $mform->addElement('html', '<h4>' . $this->_customdata['title'] . '</h4><br>');
        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->addElement('hidden', 'courseid', $COURSE->id);

        $id = $this->_customdata['id'];

        $coursecontext = context_course::instance($COURSE->id);

        $studentroleid = $DB->get_record('role', ['shortname' => 'student'], '*')->id;
        $students      = get_role_users($studentroleid, $coursecontext);

        $counteventcompleted = $DB->count_records_sql("
                SELECT COUNT(*)
                FROM {lytix_planner_event_comp} eventcomp
                WHERE eventcomp.eventid= $id AND eventcomp.courseid = $COURSE->id AND eventcomp.completed = 1");

        $startyear = forms_helper::get_semester_start_year();
        $stopyear = forms_helper::get_semester_end_year();

        $mform->addElement('date_time_selector', 'startdate', get_string('set_startdate', $component),
            ['startyear' => $startyear, 'stopyear' => $stopyear, 'optional' => false]);

        $mform->setDefault('startdate', $this->_customdata['startdate']);
        $mform->addHelpButton('startdate', 'set_startdate', $component);

        form_helper::set_endtime_selector($mform, 'enddate');
        form_helper::set_enddate($mform, 'startdate', 'enddate', $this->_customdata['enddate']);

        $mform->addElement('advcheckbox', 'moreevents', "Do you want to create more than 1 event?");
        $mform->addHelpButton('moreevents', 'set_moreevents', $component);

        $defaultdate = $mform->getElementValue('startdate');
        for ($i = 1; $i <= 5; $i++) {
            $mform->addElement('date_time_selector', 'date' . $i, get_string('set_startdate', $component),
                               ['startyear' => $startyear, 'stopyear' => $stopyear, 'optional' => true]);
            $mform->hideIf('date' . $i, 'moreevents', 'notchecked');
            $defaultdate['day'][0] += 7;
            $monthdays = form_helper::getmonthdays($defaultdate['month'][0], $defaultdate['year'][0]);
            if ($defaultdate['day'][0] > $monthdays) {
                $defaultdate['day'][0] -= $monthdays;
                $defaultdate['month'][0] += 1;
            }
            if ($defaultdate['month'][0] > 12) {
                $defaultdate['month'][0] -= 12;
                $defaultdate['year'][0] += 1;
            }
            $mform->setDefault('date' . $i, $defaultdate);
            form_helper::set_endtime_selector($mform, 'endtime' . $i);
            form_helper::set_enddate($mform, 'date' . $i, 'endtime' .  $i, $this->_customdata['endtime' . $i]);
            $mform->hideIf('endtime' . $i, 'date' . $i . '[enabled]', 'notchecked');
        }

        $options = dynamic_events::get_event_types($COURSE->id);
        $options = json_decode($options, true);
        $key = array_search("Other", $options['en']);
        $select = $mform->addElement('select', 'type', get_string('set_type', $component), $options[$lang]);
        $key1   = array_search($this->_customdata['type'], $options[$lang]);
        $select->setSelected($key1);
        $select->setMultiple(false);
        $mform->addHelpButton('type', 'set_type', $component);

        $mform->addElement('text', 'select_other_german', get_string('set_select_other_german', $component), array('size' => '80'));
        $mform->addHelpButton('select_other_german', 'set_select_other_german', $component);
        $mform->hideIf('select_other_german', 'type', 'neq', $key);
        $mform->addElement('text', 'select_other_english', get_string('set_select_other_english', $component),
                           array('size' => '80'));
        $mform->addHelpButton('select_other_english', 'set_select_other_english', $component);
        $mform->hideIf('select_other_english', 'type', 'neq', $key);

        form_helper::add_groups_selector($mform, $COURSE, $this->_customdata['mgroup']);

        $mform->addElement('text', 'countcompleted', get_string('countcompleted', $component));
        $mform->addHelpButton('countcompleted', 'countcompleted', $component);
        $mform->setDefault('countcompleted', $counteventcompleted . "/" . count($students));
        $mform->disabledIf('countcompleted', 'id');

        $mform->addElement('text', 'title', get_string('set_title', $component), array('size' => '80'));
        $mform->setDefault('title', $this->_customdata['title']);
        $mform->addHelpButton('title', 'set_title', $component);
        $mform->addRule('title', get_string('title_required', $component), 'required', null, 'client');

        $mform->addElement('editor', 'text', get_string('set_text', $component), 'wrap="virtual" rows="10" cols="80"',
                           array('autosave' => 0, 'enable_filemanagement' => 0));
        $mform->setDefault('text', array('text' => $this->_customdata['text']));
        $mform->setType('text', PARAM_CLEANHTML);
        $mform->addHelpButton('text', 'set_text', $component);

        $mform->addElement('editor', 'room', get_string('set_room', $component), 'wrap="virtual" rows="5" cols="80"',
                           array('autosave' => 0, 'enable_filemanagement' => 0));
        $mform->setDefault('room', array('text' => $this->_customdata['room']));
        $mform->addHelpButton('room', 'set_room', $component);
        $mform->setType('room', PARAM_CLEANHTML);

        $mform->addElement('advcheckbox', 'visible', get_string('set_visible', $component));
        $mform->setDefault('visible', $this->_customdata['visible']);
        $mform->addHelpButton('visible', 'set_visible', $component);

        $mform->addElement('advcheckbox', 'mandatory', get_string('set_mandatory', $component));
        $mform->setDefault('mandatory', $this->_customdata['mandatory']);
        $mform->addHelpButton('mandatory', 'set_mandatory', $component);

        $mform->addElement('advcheckbox', 'graded', get_string('set_graded', $component));
        $mform->setDefault('graded', $this->_customdata['graded']);
        $mform->addHelpButton('graded', 'set_graded', $component);

        $gradeitems[get_string('connect_gradebook', $component)] = get_string('connect_gradebook', $component);
        foreach ($grades as $key => $grade) {
            $gradeitems[$grade->itemname] = $grade->itemname;
        }
        $select = $mform->addElement('select', 'gradeitem', get_string('set_gradeitem', $component), $gradeitems);
        $mform->setDefault('gradeitem', $this->_customdata['gradeitem']);
        $select->setMultiple(false);
        $mform->addHelpButton('gradeitem', 'set_gradeitem', $component);
        $mform->disabledIf('gradeitem', 'graded', 'notchecked');
        $mform->hideIf('gradeitem', 'graded', 'notchecked');
    }
}
