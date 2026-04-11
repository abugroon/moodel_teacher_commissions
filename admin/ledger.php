<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin: view the full commission ledger for a specific teacher.
 *
 * URL params:
 *   id  (required) Teacher user id.
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

use local_teacher_commissions\commission_manager;
use local_teacher_commissions\payout_manager;
use local_teacher_commissions\output\teacher_ledger;

require_login();
$syscontext = context_system::instance();
require_capability('local/teacher_commissions:viewadmindashboard', $syscontext);

$teacherid = required_param('id', PARAM_INT);
$teacher   = $DB->get_record('user', ['id' => $teacherid, 'deleted' => 0], '*', MUST_EXIST);

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/teacher_commissions/admin/ledger.php', ['id' => $teacherid]));
$PAGE->set_title(get_string('ledger_title', 'local_teacher_commissions', fullname($teacher)));
$PAGE->set_heading(get_string('ledger_title', 'local_teacher_commissions', fullname($teacher)));
$PAGE->set_pagelayout('admin');

$PAGE->navbar->add(
    get_string('pluginname', 'local_teacher_commissions'),
    new moodle_url('/local/teacher_commissions/admin/index.php')
);
$PAGE->navbar->add(fullname($teacher));

$summary  = commission_manager::get_teacher_summary($teacherid);
$summary->firstname = $teacher->firstname;
$summary->lastname  = $teacher->lastname;

$txresult = commission_manager::get_transactions($teacherid);
$payouts  = payout_manager::get_payouts($teacherid);

/** @var local_teacher_commissions_renderer $renderer */
$renderer = $PAGE->get_renderer('local_teacher_commissions');

echo $OUTPUT->header();
echo local_teacher_commissions_admin_nav('dashboard');
$renderable = new teacher_ledger($summary, $txresult['records'], $payouts, true);
echo $renderer->render_teacher_ledger($renderable);
echo $OUTPUT->footer();
