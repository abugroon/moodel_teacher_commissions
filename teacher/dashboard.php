<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Teacher self-service commission dashboard (read-only).
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

use local_teacher_commissions\commission_manager;
use local_teacher_commissions\output\teacher_dashboard;

require_login();
$syscontext = context_system::instance();

// Teachers hold their role at course context, not system context.
// local_teacher_commissions_has_teacher_access() handles both cases.
if (!local_teacher_commissions_has_teacher_access()) {
    throw new required_capability_exception(
        $syscontext,
        'local/teacher_commissions:viewowncommissions',
        'nopermissions',
        ''
    );
}

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/teacher_commissions/teacher/dashboard.php'));
$PAGE->set_title(get_string('teacher_dashboard_title', 'local_teacher_commissions'));
$PAGE->set_heading(get_string('teacher_dashboard_title', 'local_teacher_commissions'));
$PAGE->set_pagelayout('standard');

$summary = commission_manager::get_teacher_summary($USER->id);
$summary->firstname = $USER->firstname;
$summary->lastname  = $USER->lastname;

/** @var local_teacher_commissions_renderer $renderer */
$renderer = $PAGE->get_renderer('local_teacher_commissions');

echo $OUTPUT->header();
$renderable = new teacher_dashboard($summary);
echo $renderer->render_teacher_dashboard($renderable);
echo $OUTPUT->footer();
