<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Plugin library / hook functions for local_teacher_commissions.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Inject commission navigation items into Moodle's navigation tree.
 *
 * @param global_navigation $nav
 */
function local_teacher_commissions_extend_navigation(global_navigation $nav): void {
    global $USER;

    $syscontext = context_system::instance();

    // Admin navigation node.
    if (has_capability('local/teacher_commissions:viewadmindashboard', $syscontext)) {
        $nav->add(
            get_string('nav_admin_dashboard', 'local_teacher_commissions'),
            new moodle_url('/local/teacher_commissions/admin/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'tc_admin_dashboard',
            new pix_icon('i/report', '')
        );
    }

    // Teacher navigation node (view-only, own data).
    if (has_capability('local/teacher_commissions:viewowncommissions', $syscontext)
            && !has_capability('local/teacher_commissions:viewadmindashboard', $syscontext)) {
        $nav->add(
            get_string('nav_teacher_dashboard', 'local_teacher_commissions'),
            new moodle_url('/local/teacher_commissions/teacher/dashboard.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'tc_teacher_dashboard',
            new pix_icon('i/report', '')
        );
    }
}

/**
 * Inject commission navigation items into the flat navigation (Boost theme).
 *
 * @param flat_navigation $nav
 */
function local_teacher_commissions_extend_navigation_user_settings(\navigation_node $nav): void {
    // Intentionally empty — we use extend_navigation above.
}
