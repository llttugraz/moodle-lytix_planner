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
 * Choose and download exam backups
 *
 * @package    lytix_planner
 * @author     Guenther Moser <moser@tugraz.at>
 * @copyright  2023 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace lytix_planner\privacy;
use core\external\exporter;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\tests\request\content_writer;
use \core_privacy\local\request\writer;


/**
 * Class provider
 * @package lytix_planner
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @param collection $collection empty collection of tables for column translation
     * @return  collection the translated userdata
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table("lytix_planner_milestone",
            [
                "userid" => "privacy:metadata:lytix_planner_milestone:userid",
                "courseid" => "privacy:metadata:lytix_planner_milestone:courseid",
                "type" => "privacy:metadata:lytix_planner_milestone:type",
                "marker" => "privacy:metadata:lytix_planner_milestone:marker",
                "startdate" => "privacy:metadata:lytix_planner_milestone:startdate",
                "enddate" => "privacy:metadata:lytix_planner_milestone:enddate",
                "title" => "privacy:metadata:lytix_planner_milestone:title",
                "text" => "privacy:metadata:lytix_planner_milestone:text",
                "moffset" => "privacy:metadata:lytix_planner_milestone:moffset",
                "moption" => "privacy:metadata:lytix_planner_milestone:moption",
                "completed" => "privacy:metadata:lytix_planner_milestone:completed",
                "send" => "privacy:metadata:lytix_planner_milestone:send"
            ], "privacy:metadata:lytix_planner_milestone"
        );

        $collection->add_database_table("lytix_planner_event_comp",
            [
                "userid" => "privacy:metadata:lytix_planner_event_comp:userid",
                "courseid" => "privacy:metadata:lytix_planner_event_comp:courseid",
                "eventid" => "privacy:metadata:lytix_planner_event_comp:eventid",
                "completed" => "privacy:metadata:lytix_planner_event_comp:completed",
                "send" => "privacy:metadata:lytix_planner_event_comp:send",
                "timestamp" => "privacy:metadata:lytix_planner_event_comp:timestamp"
            ], "privacy:metadata:lytix_planner_event_comp"
        );

        $collection->add_database_table("lytix_planner_usr_settings",
            [
                "userid" => "privacy:metadata:lytix_planner_usr_settings:userid",
                "courseid" => "privacy:metadata:lytix_planner_usr_settings:courseid",
                "enable_custom_customization" => "privacy:metadata:lytix_planner_usr_settings:enable_custom_customization",
                "types" => "privacy:metadata:lytix_planner_usr_settings:types"
            ], "privacy:metadata:lytix_planner_usr_settings"
        );

        $collection->add_database_table("lytix_planner_usr_grade_rep",
            [
                "userid" => "privacy:metadata:lytix_planner_usr_grade_rep:userid",
                "courseid" => "privacy:metadata:lytix_planner_usr_grade_rep:courseid",
                "quizpoints" => "privacy:metadata:lytix_planner_usr_grade_rep:quizpoints",
                "assingpoints" => "privacy:metadata:lytix_planner_usr_grade_rep:assingpoints",
                "totalpoints" => "privacy:metadata:lytix_planner_usr_grade_rep:totalpoints",
                "maxpoints" => "privacy:metadata:lytix_planner_usr_grade_rep:maxpoints",
                "lastmodified" => "privacy:metadata:lytix_planner_usr_grade_rep:lastmodified"
            ], "privacy:metadata:lytix_planner_usr_grade_rep"
        );

        return $collection;
    }

    /**
     * Delete all personal data for all users in the specified context.
     *
     * @param \context $context Context to delete data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_USER ||
            $context->contextlevel == CONTEXT_COURSE ||
            $context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records('lytix_planner_milestone');
            $DB->delete_records('lytix_planner_event_comp');
        }
    }

    /**
     * Delete all records in lytix_planner for that particular user given by the approved_contextlist
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        $DB->delete_records('lytix_planner_milestone', ['userid' => $userid]);
        $DB->delete_records('lytix_planner_event_comp', ['userid' => $userid]);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        if (empty($userlist->count())) {
            return;
        }
        list(, $userparamsarray) = $DB->get_in_or_equal($userlist);

        $userparamsarray = implode(",", $userparamsarray[0]);

        $DB->delete_records_select('lytix_planner_milestone', "userid IN ({$userparamsarray})");
        $DB->delete_records_select('lytix_planner_event_comp', "userid IN ({$userparamsarray})");
    }

    /**
     * Export all user data for the specified user, in the specified contexts, using the supplied exporter instance.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $contextlevels = "SELECT roleid FROM {role_context_levels} WHERE contextlevel = :contextlevel";
        $roleids = "SELECT id FROM {role} WHERE (id IN ({$contextlevels}))";
        $roleassignments = "SELECT userid FROM {role_assignments} WHERE (roleid IN ({$roleids}))";
        $courseids = "SELECT * FROM {lytix_planner_milestone} WHERE (userid IN ({$roleassignments})) AND userid = :userid";

        // This CONTEXT_SYSTEM could be $userlist->contextid.
        $params = [
            "contextlevel" => CONTEXT_COURSE,
            "userid" => $contextlist->get_user()->id
        ];
        $dataset = $DB->get_records_sql($courseids, $params);

        $contextlist = new contextlist();
        $contextlist->add_system_context();

        writer::with_context($contextlist->get_contexts()[0])
            ->export_data(["lytix_planner_milestone"], (object)$dataset, "Entry of Download");

        // Redo for second table.
        $courseids = "SELECT * FROM {lytix_planner_event_comp} WHERE (userid IN ({$roleassignments})) AND userid = :userid";
        $dataset = $DB->get_records_sql($courseids, $params);

        writer::with_context($contextlist->get_contexts()[0])
            ->export_data(["lytix_planner_event_comp"], (object)$dataset, "Entry of Download");
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int           $userid       The user to search.
     * @return  contextlist   $contextlist  The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {

        $contextlevels = "SELECT roleid FROM {role_context_levels} WHERE contextlevel = :contextlevel";
        $roleids = "SELECT id FROM {role} WHERE (id IN ({$contextlevels}))";
        $roleassignments = "SELECT contextid FROM {role_assignments} WHERE
                                                (roleid IN ({$roleids})) AND userid = :userid";
        $contextlist = new contextlist();

        $params = [
            "contextlevel" => CONTEXT_SYSTEM,
            "userid" => $userid
        ];
        $contextlist->add_from_sql($roleassignments, $params);

        $params = [
            "contextlevel" => CONTEXT_COURSE,
            "userid" => $userid
        ];
        $contextlist->add_from_sql($roleassignments, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $contextlevels = "SELECT roleid FROM {role_context_levels} WHERE contextlevel = :contextlevel";
        $roleids = "SELECT id FROM {role} WHERE (id IN ({$contextlevels}))";
        $roleassignments = "SELECT userid FROM {role_assignments} WHERE (roleid IN ({$roleids}))";
        $courseids = "SELECT userid FROM {lytix_planner_milestone} WHERE (userid IN ({$roleassignments}))";
        $userids = "SELECT * FROM {user} WHERE (id IN ({$courseids}))";

        // This CONTEXT_SYSTEM could be $userlist->contextid.
        $params = [ "contextlevel" => CONTEXT_COURSE ];
        $userlist->add_from_sql("id", $userids, $params);
        $params = [ "contextlevel" => CONTEXT_SYSTEM ];
        $userlist->add_from_sql("id", $userids, $params);

        // Redo for the second table.
        $courseids = "SELECT userid FROM {lytix_planner_event_comp} WHERE (userid IN ({$roleassignments}))";
        $userids = "SELECT * FROM {user} WHERE (id IN ({$courseids}))";

        $params = [ "contextlevel" => CONTEXT_COURSE ];
        $userlist->add_from_sql("id", $userids, $params);
        $params = [ "contextlevel" => CONTEXT_SYSTEM ];
        $userlist->add_from_sql("id", $userids, $params);

        return $userlist;
    }
}
