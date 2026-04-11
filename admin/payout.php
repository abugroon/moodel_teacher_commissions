<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin: process a payout for a teacher.
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
use local_teacher_commissions\form\payout as payout_form;

require_login();
$syscontext = context_system::instance();
require_capability('local/teacher_commissions:processpayout', $syscontext);

$teacherid = required_param('id', PARAM_INT);
$teacher   = $DB->get_record('user', ['id' => $teacherid, 'deleted' => 0], '*', MUST_EXIST);

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/teacher_commissions/admin/payout.php', ['id' => $teacherid]));
$PAGE->set_title(get_string('payout_title', 'local_teacher_commissions', fullname($teacher)));
$PAGE->set_heading(get_string('payout_title', 'local_teacher_commissions', fullname($teacher)));
$PAGE->set_pagelayout('admin');

$PAGE->navbar->add(
    get_string('pluginname', 'local_teacher_commissions'),
    new moodle_url('/local/teacher_commissions/admin/index.php')
);
$PAGE->navbar->add(get_string('payout_title', 'local_teacher_commissions', fullname($teacher)));

$summary  = commission_manager::get_teacher_summary($teacherid);
$currency = $summary->currency;
$balance  = $summary->balance;

$form = new payout_form(null, [
    'teacherid'   => $teacherid,
    'teachername' => fullname($teacher),
    'balance'     => $balance,
    'currency'    => $currency,
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/teacher_commissions/admin/index.php'));
}

if ($data = $form->get_data()) {
    require_sesskey();

    try {
        $payout = payout_manager::process_payout(
            $teacherid,
            (float) $data->amount,
            clean_param($data->currency, PARAM_ALPHA),
            clean_param($data->notes ?? '', PARAM_TEXT)
        );

        $msg = get_string('payout_success', 'local_teacher_commissions', (object) [
            'amount'   => number_format($payout->amount, 2),
            'currency' => $payout->currency,
        ]);

        redirect(
            new moodle_url('/local/teacher_commissions/admin/ledger.php', ['id' => $teacherid]),
            $msg,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } catch (\moodle_exception $e) {
        $errormsg = $e->getMessage();
    }
}

echo $OUTPUT->header();
echo local_teacher_commissions_admin_nav('dashboard');

if (!empty($errormsg)) {
    echo $OUTPUT->notification($errormsg, 'error');
}

$form->display();
echo $OUTPUT->footer();
