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
 * Configuration for a predefined moodle config for the lytix lytix_planner plugin
 *
 * @package    lytix_planner
 * @author     Günther Moser
 * @copyright  2021 Educational Technologies, Graz, University of Technology
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_configurator\configuration\config;

defined('MOODLE_INTERNAL') || die();

$notifications = config::get_instance_by_source_and_id('tool_configurator', 'notifications');

/*
 * The configuration data message has been encoded to a simplified format and needs to be prepared for
 * configuration.
 *
 * Data:
 * [] (empty array) - message provider will be disabled.
 * [1,2,3] (array with exactly three entries)
 * array[0] -> popup
 * array[1] -> email
 * array[2] -> mobile (airnotifier)
 *
 * Values that are possible:
 * 0 -> disabled and unlocked (user can enable for herself)
 * 1 -> enabled and unlocked (user can disable for herself)
 * 2 -> disabled and locked (user cannot change)
 * 3 -> enabled and locked (user cannot change)
 *
 */
$notifications->add_data_entities('notification_types', [
    'lytix_planner_notification_message' => [1, 1, 2]
]);

