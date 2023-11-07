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
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// We defined the web service functions to install.
$functions = array(
        'local_lytix_lytix_planner_get'                       => array(
                'classname'   => 'lytix_planner\\planner_get',
                'methodname'  => 'service',
                'description' => 'Provides data for planner widget',
                'type'        => 'read',
                'ajax'        => 'true'
        ),
        'local_lytix_lytix_planner_milestone'                 => array(
                'classname'   => 'lytix_planner\\planner_milestone_lib',
                'methodname'  => 'planner_milestone',
                'description' => 'Adds or edits an milestone for planner widget. Created by the student',
                'type'        => 'write',
                'ajax'        => 'true'
        ),
        'local_lytix_lytix_planner_event'                     => array(
                'classname'   => 'lytix_planner\\planner_event_lib',
                'methodname'  => 'planner_event',
                'description' => 'Adds or edits an event for planner widget. Created by the teaching Personal',
                'type'        => 'write',
                'ajax'        => 'true'
        ),
        'local_lytix_lytix_planner_event_completed'           => array(
                'classname'   => 'lytix_planner\\planner_event_lib',
                'methodname'  => 'planner_event_completed',
                'description' => 'Adds the completed flag (done by the Students) to an event of the planner widget',
                'type'        => 'write',
                'ajax'        => 'true'
        ),
        'lytix_planner_store_course_notification_settings' => array(
                'classname'   => 'lytix_planner\\planner_notifications_lib',
                'methodname'  => 'store_course_notification_settings',
                'description' => 'Store the course notification settings.',
                'type'        => 'write',
                'ajax'        => 'true'
        ),
        'lytix_planner_store_user_notification_settings'   => array(
                'classname'   => 'lytix_planner\\planner_notifications_lib',
                'methodname'  => 'store_user_notification_settings',
                'description' => 'Store the user notification settings.',
                'type'        => 'write',
                'ajax'        => 'true'
        ),
        'lytix_planner_store_custom_course_settings' => array(
                'classname'   => 'lytix_planner\\planner_event_lib',
                'methodname'  => 'store_custom_course_settings',
                'description' => 'Store the custom course settings.',
                'type'        => 'write',
                'ajax'        => 'true'
        ),
        'local_lytix_lytix_planner_delete_event'               => array(
                'classname'   => 'lytix_planner\\planner_event_lib',
                'methodname'  => 'planner_delete_event',
                'description' => 'Delete planner event.',
                'type'        => 'write',
                'ajax'        => 'true'
        ),
        'local_lytix_lytix_planner_delete_milestone'               => array(
                'classname'   => 'lytix_planner\\planner_milestone_lib',
                'methodname'  => 'planner_delete_milestone',
                'description' => 'Delete planner milestone.',
                'type'        => 'write',
                'ajax'        => 'true'
        ),
);

