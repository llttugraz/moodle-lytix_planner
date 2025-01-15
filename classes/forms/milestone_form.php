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

use lytix_helper\forms_helper;
use moodleform;

global $CFG;

// Moodleform is defined in formslib.php.
require_once("$CFG->libdir/formslib.php");

/**
 * Class milestone_form
 */
class milestone_form extends moodleform {
    /**
     * Add elements to form.
     *
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function definition() {
        global $COURSE;

        $component = 'lytix_planner';
        $mform     = $this->_form; // Don't forget the underscore!

        $minutes = [];
        foreach (range(0, 59, 1) as $minute) {
            if ($minute < 10) {
                array_push($minutes, "0" . $minute);
            } else {
                array_push($minutes, $minute);
            }
        }

        $hours = [];
        foreach (range(0, 23, 1) as $hour) {
            if ($hour < 10) {
                array_push($hours, "0" . $hour);
            } else {
                array_push($hours, $hour);
            }
        }

        $mform->addElement('html', '<h4>' . $this->_customdata['title'] . '</h4><br>');

        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->addElement('hidden', 'userid', $this->_customdata['userid']);
        $mform->addElement('hidden', 'type', $this->_customdata['type']);
        $mform->addElement('hidden', 'marker', $this->_customdata['marker']);

        $startyear = forms_helper::get_semester_start_year();
        $stopyear = forms_helper::get_semester_end_year();

        $mform->addElement('date_time_selector', 'startdate', get_string('set_startdate', $component),
            ['startyear' => $startyear, 'stopyear' => $stopyear, 'optional' => false]);

        $mform->setDefault('startdate', $this->_customdata['startdate']);
        $mform->addHelpButton('startdate', 'set_startdate', $component);

        $timearray   = [];
        $timearray[] =& $mform->createElement('select', 'hour', get_string('set_hour', $component), $hours);
        $timearray[] =& $mform->createElement('select', 'minute', get_string('set_minute', $component), $minutes);

        $mform->addGroup($timearray, 'endtime', get_string('set_endtime', $component), [' '], false);
        $mform->addHelpButton('endtime', 'set_endtime', $component);

        form_helper::set_enddate($mform, 'startdate', '', $this->_customdata['enddate']);

        $mform->addElement('text', 'title', get_string('set_title', $component), ['size' => '80']);
        $mform->setDefault('title', $this->_customdata['title']);
        $mform->addRule('title', get_string('title_required', $component), 'required', null, 'client');
        $mform->addHelpButton('title', 'set_title', $component);

        $mform->addElement('editor', 'text', get_string('set_text', $component), 'wrap="virtual" rows="10" cols="80"',
            ['autosave' => 0, 'enable_filemanagement' => 0]);
        $mform->setDefault('text', ['text' => $this->_customdata['text']]);
        $mform->setType('text', PARAM_CLEANHTML);
        $mform->addHelpButton('text', 'set_text', $component);

        $mform->addElement('advcheckbox', 'completed', get_string('set_completed', $component));
        $mform->setDefault('completed', $this->_customdata['completed']);
    }
}
