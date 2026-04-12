<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Commission settings page — set global default or per-teacher rate.
 *
 * URL params:
 *   id  (optional)  Teacher user id. Omit for global default.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

use local_teacher_commissions\commission_manager;
use local_teacher_commissions\form\commission_settings as settings_form;

require_login();
$syscontext = context_system::instance();
require_capability('local/teacher_commissions:managecommissions', $syscontext);

$teacherid = optional_param('id', 0, PARAM_INT);

// Validate teacher if an id is given.
$teacher     = null;
$teachername = get_string('global_default', 'local_teacher_commissions');
if ($teacherid > 0) {
    $teacher = $DB->get_record('user', ['id' => $teacherid, 'deleted' => 0], '*', MUST_EXIST);
    $teachername = fullname($teacher);
}

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/teacher_commissions/admin/commission_settings.php', ['id' => $teacherid]));
$PAGE->set_title(get_string('commission_settings_title', 'local_teacher_commissions'));
$PAGE->set_heading(get_string('commission_settings_title', 'local_teacher_commissions'));
$PAGE->set_pagelayout('admin');

$PAGE->navbar->add(
    get_string('pluginname', 'local_teacher_commissions'),
    new moodle_url('/local/teacher_commissions/admin/index.php')
);
$PAGE->navbar->add(get_string('commission_settings_title', 'local_teacher_commissions'));

// Build form.
$existing = commission_manager::get_teacher_setting($teacherid);
$form = new settings_form(null, [
    'teacherid'   => $teacherid,
    'teachername' => $teachername,
]);

// Populate with current value.
if ($existing) {
    $form->set_data(['commission_percent' => $existing->commission_percent, 'teacherid' => $teacherid]);
} else {
    // Fall back to effective rate.
    $form->set_data(['commission_percent' => commission_manager::get_effective_rate($teacherid), 'teacherid' => $teacherid]);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/teacher_commissions/admin/index.php'));
}

if ($data = $form->get_data()) {
    commission_manager::save_rate((int) $data->teacherid, (float) $data->commission_percent);
    redirect(
        new moodle_url('/local/teacher_commissions/admin/index.php'),
        get_string('settings_saved', 'local_teacher_commissions'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Build teacher summary for balance (needed for payout link visibility).
$teachersummary = $teacherid > 0 ? commission_manager::get_teacher_summary($teacherid) : null;

echo $OUTPUT->header();
echo local_teacher_commissions_admin_nav('settings');

if ($teacherid > 0) {
    echo local_teacher_commissions_admin_teacher_nav(
        $teacherid,
        $teachername,
        'settings',
        $teachersummary ? $teachersummary->balance : 0.0
    );
}

echo $OUTPUT->heading(
    $teacherid > 0
        ? get_string('teacher_commission_override', 'local_teacher_commissions') . ': ' . $teachername
        : get_string('global_default', 'local_teacher_commissions')
);
$form->display();
echo $OUTPUT->single_button(
    new moodle_url('/local/teacher_commissions/admin/index.php'),
    get_string('back_to_dashboard', 'local_teacher_commissions'),
    'get'
);
echo $OUTPUT->footer();
