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
use moodleform;

global $CFG;

// Moodleform is defined in formslib.php.
require_once("$CFG->libdir/formslib.php");

/**
 * Class user_notification_settings_form
 */
class user_notification_settings_form extends \moodleform {
    /**
     * Add elements to form.
     * @throws \coding_exception
     */
    public function definition() {
        global $COURSE, $USER;

        $mform = $this->_form;

        $component = 'lytix_planner';
        $data      = notification_settings::test_and_set_user($COURSE->id, $USER->id);

        $mform->addElement('hidden', 'id', $data->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);
        $mform->addElement('hidden', 'courseid', $COURSE->id);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('header', 'generalhdr', get_string('general'));

        $mform->addElement('advcheckbox', 'softlock', get_string('softlock', $component));
        $mform->setDefault('softlock', 0);
        $mform->addHelpButton('softlock', 'softlock', $component);

        $this->set_data($data);
    }
}
