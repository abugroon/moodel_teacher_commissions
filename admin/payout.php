<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin: process a payout for a teacher (with optional receipt upload).
 *
 * @package     local_teacher_commissions
 * @copyright   2024 Your Organization
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../lib.php');

use local_teacher_commissions\commission_manager;
use local_teacher_commissions\payout_manager;

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

$errormsg = '';

// Handle POST submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && optional_param('action', '', PARAM_ALPHA) === 'dopayout') {
    require_sesskey();

    $amount = (float) optional_param('amount', 0, PARAM_FLOAT);
    $notes  = clean_param(optional_param('notes', '', PARAM_TEXT), PARAM_TEXT);

    if ($amount <= 0) {
        $errormsg = get_string('payout_error_zero', 'local_teacher_commissions');
    } elseif ($amount > $balance + 0.01) {
        $errormsg = get_string('payout_error_exceed', 'local_teacher_commissions', number_format($balance, 2));
    } else {
        try {
            $payout = payout_manager::process_payout($teacherid, $amount, $currency, $notes);

            // Handle receipt file upload.
            if (!empty($_FILES['receipt_file']['name']) && $_FILES['receipt_file']['error'] === UPLOAD_ERR_OK) {
                if ($_FILES['receipt_file']['size'] > 5242880) {
                    \core\notification::warning('Receipt file exceeds 5 MB — payout was recorded without receipt.');
                } else {
                    $fs       = get_file_storage();
                    $fileinfo = [
                        'contextid' => $syscontext->id,
                        'component' => 'local_teacher_commissions',
                        'filearea'  => 'payout_receipts',
                        'itemid'    => $payout->id,
                        'filepath'  => '/',
                        'filename'  => clean_filename($_FILES['receipt_file']['name']),
                    ];
                    $fs->create_file_from_pathname($fileinfo, $_FILES['receipt_file']['tmp_name']);
                    $DB->set_field('local_tc_payouts', 'receipt_file', $fileinfo['filename'], ['id' => $payout->id]);
                }
            }

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
}

echo $OUTPUT->header();
echo local_teacher_commissions_admin_nav('dashboard');
echo local_teacher_commissions_admin_teacher_nav($teacherid, fullname($teacher), 'payout', $balance);

if (!empty($errormsg)) {
    echo $OUTPUT->notification($errormsg, 'error');
}

$cancel_url = (new moodle_url('/local/teacher_commissions/admin/index.php'))->out(false);
$form_url   = (new moodle_url('/local/teacher_commissions/admin/payout.php', ['id' => $teacherid]))->out(false);
?>

<style>
.tc-payout-card {
    max-width: 560px;
    margin: 24px auto;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.1);
    overflow: hidden;
    border: 1px solid #e2e8f0;
}
.tc-payout-hdr {
    background: linear-gradient(135deg, #1e293b 0%, #1e3a6e 55%, #2563eb 100%);
    color: #fff;
    padding: 24px 28px;
}
.tc-payout-hdr h3 { margin: 0 0 4px; font-size: 1.15rem; font-weight: 800; }
.tc-payout-hdr p  { margin: 0; opacity: .75; font-size: .88rem; }
.tc-payout-body { padding: 28px; }
.balance-display {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border: 1.5px solid #6ee7b7;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.balance-display .bd-label { font-size: .8rem; color: #065f46; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
.balance-display .bd-val   { font-size: 1.8rem; font-weight: 800; color: #065f46; }
.form-grp { margin-bottom: 18px; }
.form-grp label { display: block; font-size: .83rem; font-weight: 700; color: #374151; margin-bottom: 6px; }
.form-ctrl {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: .95rem;
    color: #1e293b;
    box-sizing: border-box;
    background: #f8fafc;
    transition: border-color .15s;
    font-weight: 600;
}
.form-ctrl:focus { outline: none; border-color: #2563eb; background: #fff; }
.form-ctrl-file {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px dashed #e2e8f0;
    border-radius: 10px;
    font-size: .88rem;
    box-sizing: border-box;
    background: #f8fafc;
    cursor: pointer;
}
.form-hint { font-size: .75rem; color: #94a3b8; margin-top: 4px; }
.tc-payout-footer {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}
.btn-confirm {
    flex: 1;
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: #fff;
    border: none;
    padding: 13px;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 800;
    cursor: pointer;
    transition: filter .15s;
}
.btn-confirm:hover { filter: brightness(.95); }
.btn-cancel {
    padding: 13px 24px;
    border-radius: 10px;
    font-size: .95rem;
    font-weight: 700;
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    color: #64748b;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
}
.btn-cancel:hover { background: #f1f5f9; color: #334155; text-decoration: none; }
</style>

<div class="tc-payout-card">
    <div class="tc-payout-hdr">
        <h3>&#x1F4B3; <?php echo get_string('payout_title', 'local_teacher_commissions', s(fullname($teacher))); ?></h3>
        <p><?php echo get_string('payout_notes', 'local_teacher_commissions'); ?></p>
    </div>
    <div class="tc-payout-body">
        <div class="balance-display">
            <div>
                <div class="bd-label"><?php echo get_string('available_balance', 'local_teacher_commissions'); ?></div>
                <div class="bd-val"><?php echo number_format($balance, 2); ?> <span style="font-size:.9rem;"><?php echo s($currency); ?></span></div>
            </div>
            <div style="font-size:2rem;opacity:.3;">&#x1F4B0;</div>
        </div>

        <form method="post"
              action="<?php echo $form_url; ?>"
              enctype="multipart/form-data"
              onsubmit="return validatePayoutForm()">
            <input type="hidden" name="action"    value="dopayout">
            <input type="hidden" name="sesskey"   value="<?php echo sesskey(); ?>">

            <div class="form-grp">
                <label for="tc_amount"><?php echo get_string('payout_amount', 'local_teacher_commissions'); ?> <span style="color:#dc2626;">*</span></label>
                <input type="number"
                       id="tc_amount"
                       name="amount"
                       class="form-ctrl"
                       min="0.01"
                       max="<?php echo $balance; ?>"
                       step="0.01"
                       value="<?php echo number_format($balance, 2, '.', ''); ?>"
                       required>
            </div>

            <div class="form-grp">
                <label for="tc_notes"><?php echo get_string('payout_notes', 'local_teacher_commissions'); ?></label>
                <textarea id="tc_notes" name="notes" class="form-ctrl" rows="3" style="resize:vertical;"></textarea>
            </div>

            <div class="form-grp">
                <label for="tc_receipt"><?php echo get_string('receipt_file', 'local_teacher_commissions'); ?></label>
                <input type="file"
                       id="tc_receipt"
                       name="receipt_file"
                       class="form-ctrl-file"
                       accept=".pdf,.jpg,.jpeg,.png">
                <div class="form-hint">PDF, JPG or PNG — max 5 MB</div>
            </div>

            <div class="tc-payout-footer">
                <button type="submit" class="btn-confirm">
                    &#x2705; <?php echo get_string('payout_submit', 'local_teacher_commissions'); ?>
                </button>
                <a href="<?php echo $cancel_url; ?>" class="btn-cancel">
                    &#x274C; Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function validatePayoutForm() {
    var amount = parseFloat(document.getElementById('tc_amount').value);
    var max    = <?php echo (float)$balance; ?>;
    var file   = document.getElementById('tc_receipt').files[0];

    if (isNaN(amount) || amount <= 0) {
        alert('Please enter a valid payout amount.');
        return false;
    }
    if (amount > max + 0.01) {
        alert('Amount exceeds available balance of ' + max.toFixed(2) + '.');
        return false;
    }
    if (file && file.size > 5242880) {
        alert('Receipt file must not exceed 5 MB.');
        return false;
    }
    return true;
}
</script>

<?php echo $OUTPUT->footer();
