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
use local_teacher_commissions\withdrawal_manager;
use local_teacher_commissions\output\teacher_ledger;

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

// Teachers can only view their own ledger.
$teacherid = $USER->id;

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/teacher_commissions/teacher/ledger.php'));
$PAGE->set_title(get_string('nav_teacher_ledger', 'local_teacher_commissions'));
$PAGE->set_heading(get_string('nav_teacher_ledger', 'local_teacher_commissions'));
$PAGE->set_pagelayout('standard');

$summary              = commission_manager::get_teacher_summary($teacherid);
$summary->firstname   = $USER->firstname;
$summary->lastname    = $USER->lastname;

$txresult = commission_manager::get_transactions($teacherid);
$payouts  = payout_manager::get_payouts($teacherid);

/** @var local_teacher_commissions_renderer $renderer */
$renderer = $PAGE->get_renderer('local_teacher_commissions');

echo $OUTPUT->header();
echo local_teacher_commissions_teacher_nav('ledger');
$renderable = new teacher_ledger($summary, $txresult['records'], $payouts, false);
echo $renderer->render_teacher_ledger($renderable);

// ---- Withdrawal request history ----
$my_requests = withdrawal_manager::get_teacher_requests($teacherid);
if (!empty($my_requests)) {
    $grouped_balances = withdrawal_manager::get_grouped_balances($teacherid);

    $status_labels = [
        withdrawal_manager::STATUS_PENDING  => get_string('withdrawal_status_pending',  'local_teacher_commissions'),
        withdrawal_manager::STATUS_APPROVED => get_string('withdrawal_status_approved', 'local_teacher_commissions'),
        withdrawal_manager::STATUS_REJECTED => get_string('withdrawal_status_rejected', 'local_teacher_commissions'),
        withdrawal_manager::STATUS_PAID     => get_string('withdrawal_status_paid',     'local_teacher_commissions'),
    ];
    $status_class = [
        withdrawal_manager::STATUS_PENDING  => 'warning',
        withdrawal_manager::STATUS_APPROVED => 'success',
        withdrawal_manager::STATUS_REJECTED => 'danger',
        withdrawal_manager::STATUS_PAID     => 'primary',
    ];

    echo $OUTPUT->heading(get_string('my_withdrawal_requests', 'local_teacher_commissions'), 3);

    $wt             = new html_table();
    $wt->head       = [
        get_string('date',            'local_teacher_commissions'),
        get_string('marketer_source', 'local_teacher_commissions'),
        get_string('payout_amount',   'local_teacher_commissions'),
        get_string('status',          'local_teacher_commissions'),
        get_string('notes',           'local_teacher_commissions'),
    ];
    $wt->attributes['class'] = 'generaltable table table-bordered table-sm';

    foreach ($my_requests as $req) {
        $mid   = (int)$req->mainmarketerid;
        $mname = isset($grouped_balances[$mid]) ? $grouped_balances[$mid]->marketername
                                                 : get_string('no_referral_marketer', 'local_teacher_commissions');
        $slbl  = $status_labels[$req->status] ?? $req->status;
        $scls  = $status_class[$req->status]  ?? 'secondary';
        $badge = html_writer::tag(
            'span', $slbl, ['class' => 'badge text-bg-' . $scls]
        );
        $wt->data[] = [
            userdate($req->timecreated, get_string('strftimedatetime', 'langconfig')),
            s($mname),
            number_format((float)$req->amount, 2) . ' ' . s($req->currency),
            $badge,
            s($req->notes ?? ''),
        ];
    }
    echo html_writer::table($wt);
}

echo $OUTPUT->footer();
