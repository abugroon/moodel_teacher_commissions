<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Teacher commission withdrawal request page.
 *
 * Teacher submits a withdrawal request for commissions from a specific
 * main marketer group. The request is routed to that main marketer.
 *
 * @package     local_teacher_commissions
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

use local_teacher_commissions\withdrawal_manager;

require_login();
$syscontext = context_system::instance();

if (!local_teacher_commissions_has_teacher_access()) {
    throw new required_capability_exception(
        $syscontext,
        'local/teacher_commissions:viewowncommissions',
        'nopermissions',
        ''
    );
}

$can_withdraw = has_capability('local/teacher_commissions:requestwithdrawal', $syscontext)
    || !empty(get_user_capability_course('local/teacher_commissions:requestwithdrawal', $USER->id, true, 'id', '', 1));
if (!$can_withdraw) {
    throw new required_capability_exception($syscontext, 'local/teacher_commissions:requestwithdrawal', 'nopermissions', '');
}

$mainmarketerid = required_param('mainmarketerid', PARAM_INT);
$back_url       = new moodle_url('/local/teacher_commissions/teacher/dashboard.php');

// Verify this marketer group belongs to the current teacher.
$groups = withdrawal_manager::get_grouped_balances($USER->id);
if (!array_key_exists($mainmarketerid, $groups)) {
    redirect($back_url, get_string('error_nopermission', 'local_teacher_commissions'));
}
$grp = $groups[$mainmarketerid];

$PAGE->set_context($syscontext);
$PAGE->set_url(new moodle_url('/local/teacher_commissions/teacher/withdrawals.php', ['mainmarketerid' => $mainmarketerid]));
$PAGE->set_title(get_string('request_withdrawal', 'local_teacher_commissions'));
$PAGE->set_heading(get_string('request_withdrawal', 'local_teacher_commissions'));
$PAGE->set_pagelayout('standard');

$error   = '';
$success = '';

// Handle POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $amount   = (float)optional_param('amount', 0, PARAM_FLOAT);
    $notes    = optional_param('notes', '', PARAM_TEXT);
    $currency = $grp->currency ?: 'SDG';

    try {
        withdrawal_manager::create_request($USER->id, $mainmarketerid, $amount, $currency, $notes);
        redirect($back_url, get_string('withdrawal_request_sent', 'local_teacher_commissions'));
    } catch (\moodle_exception $e) {
        $error = $e->getMessage();
    }
}

echo $OUTPUT->header();
echo local_teacher_commissions_teacher_nav('dashboard');
?>
<style>
.tc-wd { max-width:520px; margin:24px auto 48px; }
.tc-wd-card {
    background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;
    box-shadow:0 2px 8px rgba(0,0,0,.07);
}
.tc-wd-hdr {
    background:linear-gradient(135deg,#1d4ed8,#2563eb);
    padding:18px 22px;
}
.tc-wd-hdr h2 { margin:0 0 2px; font-size:1.05rem; font-weight:800; color:#fff; }
.tc-wd-hdr p  { margin:0; font-size:.82rem; color:rgba(255,255,255,.75); }
.tc-wd-body { padding:22px; }
.tc-balance-box {
    background:#f0fdf4; border:1px solid #bbf7d0; border-radius:9px;
    padding:14px 18px; margin-bottom:20px; text-align:center;
}
.tc-balance-box .lbl { font-size:.68rem; font-weight:700; color:#166534;
                        text-transform:uppercase; letter-spacing:.05em; margin-bottom:3px; }
.tc-balance-box .val { font-size:1.8rem; font-weight:800; color:#166534; line-height:1; }
.tc-field { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
.tc-field label { font-size:.82rem; font-weight:700; color:#374151; }
.tc-input {
    padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:8px;
    font-size:.95rem; font-weight:700; color:#1e293b; background:#f8fafc;
    transition:border-color .15s;
}
.tc-input:focus { outline:none; border-color:#2563eb; background:#fff; }
.tc-hint { font-size:.75rem; color:#64748b; }
.tc-btn {
    width:100%; padding:12px; background:#2563eb; color:#fff; border:none;
    border-radius:8px; font-size:.92rem; font-weight:700; cursor:pointer;
    transition:opacity .15s;
}
.tc-btn:hover { opacity:.88; }
.tc-btn:disabled { opacity:.45; cursor:not-allowed; }
.tc-alert { border-radius:8px; padding:11px 16px; margin-bottom:16px;
            font-size:.86rem; font-weight:600; }
.tc-alert-err { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
.tc-back { display:inline-block; margin-bottom:14px; font-size:.82rem; color:#2563eb;
           text-decoration:none; font-weight:600; }
.tc-back:hover { text-decoration:underline; }
</style>

<div class="tc-wd">
    <a href="<?php echo $back_url->out(false); ?>" class="tc-back">&#x2190; <?php echo get_string('back_to_dashboard', 'local_teacher_commissions'); ?></a>

    <div class="tc-wd-card">
        <div class="tc-wd-hdr">
            <h2><?php echo get_string('request_withdrawal', 'local_teacher_commissions'); ?></h2>
            <p><?php echo s($grp->marketername); ?></p>
        </div>
        <div class="tc-wd-body">
            <?php if ($error): ?>
            <div class="tc-alert tc-alert-err">&#x26A0; <?php echo s($error); ?></div>
            <?php endif; ?>

            <div class="tc-balance-box">
                <div class="lbl"><?php echo get_string('remaining_balance', 'local_teacher_commissions'); ?></div>
                <div class="val"><?php echo number_format($grp->balance, 2); ?> <small style="font-size:.55em;"><?php echo s($grp->currency); ?></small></div>
            </div>

            <?php if ($grp->balance <= 0): ?>
            <p style="text-align:center;color:#64748b;font-size:.88rem;">
                <?php echo get_string('no_balance_to_withdraw', 'local_teacher_commissions'); ?>
            </p>
            <?php else: ?>
            <form method="post" action="<?php echo (new moodle_url('/local/teacher_commissions/teacher/withdrawals.php', ['mainmarketerid' => $mainmarketerid]))->out(false); ?>">
                <?php echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]); ?>

                <div class="tc-field">
                    <label for="tc_amount"><?php echo get_string('payout_amount', 'local_teacher_commissions'); ?></label>
                    <input type="number" id="tc_amount" name="amount" class="tc-input"
                           min="0.01" max="<?php echo $grp->balance; ?>" step="0.01"
                           placeholder="0.00" required>
                    <span class="tc-hint">
                        <?php echo get_string('max_amount', 'local_teacher_commissions'); ?>:
                        <?php echo number_format($grp->balance, 2); ?> <?php echo s($grp->currency); ?>
                    </span>
                </div>

                <div class="tc-field">
                    <label for="tc_notes"><?php echo get_string('payout_notes', 'local_teacher_commissions'); ?></label>
                    <textarea id="tc_notes" name="notes" class="tc-input" rows="3"
                              style="resize:vertical;font-weight:400;"
                              placeholder="<?php echo get_string('notes_optional', 'local_teacher_commissions'); ?>"></textarea>
                </div>

                <button type="submit" class="tc-btn">
                    <?php echo get_string('submit_withdrawal_request', 'local_teacher_commissions'); ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
echo $OUTPUT->footer();
