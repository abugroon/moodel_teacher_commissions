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
 * Returns true if the current user has teacher-level access to the commissions plugin.
 *
 * Teachers are assigned the editingteacher/teacher role at CONTEXT_COURSE level, not at
 * CONTEXT_SYSTEM.  A plain has_capability($syscontext) check therefore always fails for
 * them.  This function falls back to checking whether the user holds the capability in
 * any course context, which is the correct approach for course-level role assignments.
 *
 * @return bool
 */
function local_teacher_commissions_has_teacher_access(): bool {
    global $USER;

    $syscontext = context_system::instance();

    // Fast path: system-level assignment (site admins, managers who were explicitly
    // granted the role at system context).
    if (has_capability('local/teacher_commissions:viewowncommissions', $syscontext)) {
        return true;
    }

    // Course-level path: get_user_capability_course() returns all courses where the
    // user effectively has the capability.  For an editingteacher/teacher archetype,
    // Moodle grants CAP_ALLOW in every course where that role is assigned.
    // We only need to know if at least one such course exists, hence limit=1.
    $courses = get_user_capability_course(
        'local/teacher_commissions:viewowncommissions',
        $USER->id,
        true,   // $doanything — respect siteadmin overrides
        'id',   // fields to return
        '',     // no sort needed
        1       // limit — we only need one result
    );

    return !empty($courses);
}

/**
 * Inject commission navigation items into Moodle's navigation tree.
 *
 * @param global_navigation $nav
 */
function local_teacher_commissions_extend_navigation(global_navigation $nav): void {
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
        return; // Admins don't also need the teacher node.
    }

    // Teacher navigation node: use the extended check so course-level roles work.
    if (local_teacher_commissions_has_teacher_access()) {
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
