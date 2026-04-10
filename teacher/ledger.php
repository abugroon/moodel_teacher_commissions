<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Teacher self-service: full commission ledger / statement (read-only).
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
require_capability('local/teacher_commissions:viewowncommissions', $syscontext);

// Teachers can only view their own ledger.
$teacherid = $USER->id;

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/teacher_commissions/teacher/ledger.php'));
$PAGE->set_title(get_string('nav_teacher_ledger', 'local_teacher_commissions'));
$PAGE->set_heading(get_string('nav_teacher_ledger', 'local_teacher_commissions'));
$PAGE->set_pagelayout('standard');

$PAGE->navbar->add(
    get_string('nav_teacher_dashboard', 'local_teacher_commissions'),
    new moodle_url('/local/teacher_commissions/teacher/dashboard.php')
);
$PAGE->navbar->add(get_string('nav_teacher_ledger', 'local_teacher_commissions'));

$summary              = commission_manager::get_teacher_summary($teacherid);
$summary->firstname   = $USER->firstname;
$summary->lastname    = $USER->lastname;

$txresult = commission_manager::get_transactions($teacherid);
$payouts  = payout_manager::get_payouts($teacherid);

/** @var local_teacher_commissions_renderer $renderer */
$renderer = $PAGE->get_renderer('local_teacher_commissions');

echo $OUTPUT->header();
$renderable = new teacher_ledger($summary, $txresult['records'], $payouts, false);
echo $renderer->render_teacher_ledger($renderable);
echo $OUTPUT->footer();
