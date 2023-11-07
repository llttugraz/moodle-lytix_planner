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
 * Upgrade changes between versions
 *
 * @package   lytix_planner
 * @author    Günther Moser <moser@tugraz.at>
 * @copyright 2023 Educational Technologies, Graz, University of Technology
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or laterB
 */

/**
 * Upgrade lytix_planner DB
 * @param int $oldversion
 * @return bool
 * @throws ddl_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_lytix_planner_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023091100) {

        // Define table lytix_planner_course_custom to be dropped.
        $table = new xmldb_table('lytix_planner_course_custom');

        // Conditionally launch drop table for lytix_planner_course_custom.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table lytix_planner_usr_grade_rep to be dropped.
        $table = new xmldb_table('lytix_planner_usr_grade_rep');

        // Conditionally launch drop table for lytix_planner_usr_grade_rep.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table lytix_planner_last_report to be dropped.
        $table = new xmldb_table('lytix_planner_last_report');

        // Conditionally launch drop table for lytix_planner_last_report.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define table lytix_planner_usr_settings to be dropped.
        $table = new xmldb_table('lytix_planner_usr_settings');

        // Conditionally launch drop table for lytix_planner_usr_settings.
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Define field send to be dropped from lytix_planner_events.
        $table = new xmldb_table('lytix_planner_events');
        $field = new xmldb_field('send');

        // Conditionally launch drop field send.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field moffset to be dropped from lytix_planner_milestone.
        $table = new xmldb_table('lytix_planner_milestone');
        $field = new xmldb_field('moffset');

        // Conditionally launch drop field moffset.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field moption to be dropped from lytix_planner_milestone.
        $table = new xmldb_table('lytix_planner_milestone');
        $field = new xmldb_field('moption');

        // Conditionally launch drop field moption.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field send to be dropped from lytix_planner_milestone.
        $table = new xmldb_table('lytix_planner_milestone');
        $field = new xmldb_field('send');

        // Conditionally launch drop field send.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field send to be dropped from lytix_planner_event_comp.
        $table = new xmldb_table('lytix_planner_event_comp');
        $field = new xmldb_field('send');

        // Conditionally launch drop field send.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field enable_course_notifications to be dropped from lytix_planner_crs_settings.
        $table = new xmldb_table('lytix_planner_crs_settings');
        $field = new xmldb_field('enable_course_notifications');

        // Conditionally launch drop field enable_course_notifications.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field enable_user_customization to be dropped from lytix_planner_crs_settings.
        $table = new xmldb_table('lytix_planner_crs_settings');
        $field = new xmldb_field('enable_user_customization');

        // Conditionally launch drop field enable_user_customization.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Basic savepoint reached.
        upgrade_plugin_savepoint(true, 2023091100, 'lytix', 'planner');
    }
    return true;
}
