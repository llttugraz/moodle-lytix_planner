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

use lytix_planner\notification_settings;
use lytix_helper\forms_helper;

global $CFG;

// Moodleform is defined in formslib.php.
require_once("$CFG->libdir/formslib.php");

/**
 * Class course_notification_settings_form
 */
class course_notification_settings_form extends \moodleform {
    /**
     * Add elements to form.
     * @throws \coding_exception
     */
    public function definition() {
        global $COURSE;
        $mform = $this->_form;
        $id        = $COURSE->id;
        $component = 'lytix_planner';
        $data      = notification_settings::test_and_set_course($id);

        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('hidden', 'id', $data->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'generalhdr', get_string('general'));

        $mform->addElement('advcheckbox', 'softlock', get_string('softlock', $component));
        $mform->setDefault('softlock', 0);
        $mform->addHelpButton('softlock', 'softlock', $component);

        $startyear = forms_helper::get_semester_start_year();
        $endyear = forms_helper::get_semester_end_year();
        $mform->addElement('date_selector', 'start_time', get_string('start_time', $component),
                           ['startyear' => $startyear, 'stopyear' => $endyear, 'optional' => false]);
        $mform->setDefault('start_time', $data->start_time);
        $mform->addHelpButton('start_time', 'start_time', $component);
        $mform->disabledIf('start_time', 'softlock', 'notchecked');

        $mform->addElement('date_selector', 'end_time', get_string('end_time', $component),
                           ['startyear' => $startyear, 'stopyear' => $endyear, 'optional' => false]);
        $mform->setDefault('end_time', $data->end_time);
        $mform->addHelpButton('end_time', 'end_time', $component);
        $mform->disabledIf('end_time', 'softlock', 'notchecked');

        $types = $data->types;
        $types = json_decode($types, true);
        $counttypes = count($types['en']);
        for ($i = 0; $i < $counttypes; $i++) {
            if ($types['de'][$i] == 'Sonstiges' || $types['en'][$i] == "Other") {
                continue;
            }
            $typenameen = str_replace(" ", "", $types['en'][$i]);
            $mform->addElement('header', 'header_' . $typenameen, $types['en'][$i] . ' - ' . $types['de'][$i]);
            $mform->disabledIf('header_' . $typenameen, 'softlock', 'notchecked');

            $mform->addElement('text', 'english' . $typenameen, '[EN] ' . $types['en'][$i], ['size' => '40']);
            $mform->setDefault('english' . $typenameen, $types['en'][$i]);
            $mform->disabledIf('english' . $typenameen, 'softlock', 'notchecked');
            $mform->addElement('text', 'german' . $typenameen, '[DE] ' . $types['de'][$i], ['size' => '40']);
            $mform->setDefault('german' . $typenameen, $types['de'][$i]);
            $mform->disabledIf('german' . $typenameen, 'softlock', 'notchecked');

            $mform->addElement('advcheckbox', 'delete' . $typenameen, get_string('set_delete_type', $component));
            $mform->setDefault('delete' . $typenameen, 0);
            $mform->disabledIf('delete' . $typenameen, 'softlock', 'notchecked');
        }
        $mform->addElement('header', 'add_new_type', get_string('set_new_type', $component));
        $mform->addElement('advcheckbox', 'new_type', get_string('set_new_type', $component));
        $mform->addHelpButton('new_type', 'set_new_type', $component);
        $mform->disabledIf('new_type', 'softlock', 'notchecked');

        $mform->addElement('text', 'select_other_german', get_string('set_select_other_german', $component), ['size' => '80']);
        $mform->addHelpButton('select_other_german', 'set_select_other_german', $component);
        $mform->hideIf('select_other_german', 'type', 'neq', 'Other');
        $mform->hideIf('select_other_german', 'new_type', 'notchecked');
        $mform->disabledIf('select_other_german', 'softlock', 'notchecked');

        $mform->addElement('text', 'select_other_english', get_string('set_select_other_english', $component), ['size' => '80']);
        $mform->addHelpButton('select_other_english', 'set_select_other_english', $component);
        $mform->hideIf('select_other_english', 'new_type', 'notchecked');
        $mform->disabledIf('select_other_english', 'softlock', 'notchecked');

        $this->set_data($data);
    }
}
