<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Plugin entry point — redirects to the appropriate dashboard based on role.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$syscontext = context_system::instance();

if (has_capability('local/teacher_commissions:viewadmindashboard', $syscontext)) {
    redirect(new moodle_url('/local/teacher_commissions/admin/index.php'));
} else if (has_capability('local/teacher_commissions:viewowncommissions', $syscontext)) {
    redirect(new moodle_url('/local/teacher_commissions/teacher/dashboard.php'));
} else {
    throw new \moodle_exception('error_nopermission', 'local_teacher_commissions');
}
