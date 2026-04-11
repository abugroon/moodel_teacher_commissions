<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin commission dashboard — shows all teachers with commission summary.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

use local_teacher_commissions\commission_manager;
use local_teacher_commissions\output\admin_dashboard;

require_login();
$syscontext = context_system::instance();
require_capability('local/teacher_commissions:viewadmindashboard', $syscontext);

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/teacher_commissions/admin/index.php'));
$PAGE->set_title(get_string('admin_dashboard_title', 'local_teacher_commissions'));
$PAGE->set_heading(get_string('admin_dashboard_title', 'local_teacher_commissions'));
$PAGE->set_pagelayout('admin');

// Breadcrumb.
$PAGE->navbar->add(
    get_string('pluginname', 'local_teacher_commissions'),
    new moodle_url('/local/teacher_commissions/admin/index.php')
);

$summaries = commission_manager::get_all_teacher_summaries();

/** @var local_teacher_commissions_renderer $renderer */
$renderer = $PAGE->get_renderer('local_teacher_commissions');

echo $OUTPUT->header();
echo local_teacher_commissions_admin_nav('dashboard');
$renderable = new admin_dashboard($summaries);
echo $renderer->render_admin_dashboard($renderable);
echo $OUTPUT->footer();
