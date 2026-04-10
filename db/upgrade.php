<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Plugin upgrade steps for local_teacher_commissions.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute plugin upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_teacher_commissions_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    // Future upgrade steps go here, e.g.:
    // if ($oldversion < 2024020100) {
    //     // Add new column ...
    //     upgrade_plugin_savepoint(true, 2024020100, 'local', 'teacher_commissions');
    // }

    return true;
}
